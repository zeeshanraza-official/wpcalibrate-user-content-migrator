<?php
/**
 * Batch Processor
 *
 * @package WPCalibrate\UserContentMigrator\Migration
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Migration;

use WP_Error;
use WPCalibrate\UserContentMigrator\Database\ItemsTable;
use WPCalibrate\UserContentMigrator\Database\MigrationsTable;
use WPCalibrate\UserContentMigrator\Services\LockManager;
use WPCalibrate\UserContentMigrator\Services\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes resumable, idempotent server-side batches for content migration.
 */
class BatchProcessor {

	/**
	 * Process a single batch for a migration job.
	 *
	 * @param int    $migration_id Migration ID.
	 * @param string $lock_token Optional active lock token.
	 * @return array<string, mixed>|WP_Error Batch execution summary.
	 */
	public static function process_batch( int $migration_id, string $lock_token = '' ) {
		$migration = MigrationsTable::get( $migration_id );
		if ( ! $migration ) {
			return new WP_Error( 'migration_not_found', __( 'Migration record not found.', 'wpcalibrate-user-content-migrator' ) );
		}

		// Check if cancellation was requested or already cancelled.
		if ( 'cancelling' === $migration->status || 'cancelled' === $migration->status ) {
			if ( 'cancelling' === $migration->status ) {
				MigrationJob::transition_status( $migration_id, 'cancelled' );
			}
			if ( ! empty( $lock_token ) ) {
				LockManager::release_lock( $migration_id, $lock_token );
			} else {
				LockManager::force_unlock( $migration_id );
			}
			return [
				'done'      => true,
				'status'    => 'cancelled',
				'message'   => __( 'Migration cancelled by administrator.', 'wpcalibrate-user-content-migrator' ),
				'processed' => 0,
			];
		}

		// Ensure lock is acquired or refreshed.
		if ( empty( $lock_token ) ) {
			$lock_token = LockManager::acquire_lock( $migration_id );
			if ( ! $lock_token ) {
				return new WP_Error(
					'migration_locked',
					__( 'This migration is currently locked by another active process.', 'wpcalibrate-user-content-migrator' )
				);
			}
		} else {
			$refreshed = LockManager::refresh_lock( $migration_id, $lock_token );
			if ( ! $refreshed ) {
				// Re-acquire if expired or renewing with existing token.
				$lock_token = LockManager::acquire_lock( $migration_id, 0, $lock_token );
				if ( ! $lock_token ) {
					return new WP_Error(
						'lock_expired',
						__( 'Lock expired and could not be re-acquired.', 'wpcalibrate-user-content-migrator' )
					);
				}
			}
		}

		// Ensure migration is in processing state.
		if ( 'processing' !== $migration->status ) {
			MigrationJob::transition_status( $migration_id, 'processing' );
		}

		$batch_size = SettingsManager::get_int( 'batch_size', 25 );
		/**
		 * Filter migration batch size.
		 *
		 * @param int $batch_size Batch size.
		 * @param int $migration_id Migration ID.
		 */
		$batch_size = (int) apply_filters( 'wpcalibrate_ucm_batch_size', $batch_size, $migration_id );
		$batch_size = max( 1, min( 100, $batch_size ) );

		$items = ItemsTable::get_pending_migration_items( $migration_id, $batch_size );

		// If no items remain, migration is complete!
		if ( empty( $items ) ) {
			$counts = ItemsTable::get_status_counts( $migration_id );

			// Run verification.
			$verification = VerificationService::verify_migration( $migration_id );

			$final_status = ( $counts['failed'] > 0 || 'mismatch' === $verification['status'] )
				? 'completed_with_errors'
				: 'completed';

			MigrationJob::transition_status( $migration_id, $final_status );

			MigrationsTable::update(
				$migration_id,
				[
					'total_migrated' => $counts['migrated'],
					'total_failed'   => $counts['failed'],
					'total_skipped'  => $counts['skipped'],
					'completed_at'   => current_time( 'mysql', true ),
				]
			);

			LockManager::release_lock( $migration_id, $lock_token );

			/**
			 * Action fired when a migration completes.
			 *
			 * @param int                  $migration_id Migration ID.
			 * @param string               $final_status Final status string.
			 * @param array<string, int>   $counts Status counts.
			 * @param array<string, mixed> $verification Verification results.
			 */
			do_action( 'wpcalibrate_ucm_migration_completed', $migration_id, $final_status, $counts, $verification );

			return [
				'done'                => true,
				'status'              => $final_status,
				'lock_token'          => '',
				'total_planned'       => (int) $migration->total_planned,
				'total_migrated'      => $counts['migrated'],
				'total_failed'        => $counts['failed'],
				'total_skipped'       => $counts['skipped'],
				'verification_status' => $verification['status'],
				'verification'        => $verification,
			];
		}

		$processed_in_batch = 0;

		foreach ( $items as $item ) {
			$item_id        = (int) $item->id;
			$obj_id         = (int) $item->object_id;
			$obj_type       = (string) $item->object_type;
			$original_owner = (int) $item->original_owner_id;
			$new_owner      = (int) $item->new_owner_id;

			$transfer_result = null;

			if ( 'comment' === $obj_type ) {
				$transfer_result = CommentTransferHandler::transfer( $obj_id, $original_owner, $new_owner );
			} elseif ( 'attachment' === $obj_type ) {
				$transfer_result = AttachmentTransferHandler::transfer( $obj_id, $original_owner, $new_owner );
			} else {
				$transfer_result = PostTransferHandler::transfer( $obj_id, $original_owner, $new_owner );
			}

			if ( is_wp_error( $transfer_result ) ) {
				$err_msg = $transfer_result->get_error_message();
				ItemsTable::mark_migration_failed( $item_id, $err_msg );
				MigrationsTable::append_log( $migration_id, $err_msg, 'error' );
			} else {
				ItemsTable::mark_migrated( $item_id );
			}

			$processed_in_batch++;
		}

		// Refresh lock after batch.
		LockManager::refresh_lock( $migration_id, $lock_token );

		// Recount and update migration progress.
		$counts = ItemsTable::get_status_counts( $migration_id );
		MigrationsTable::update(
			$migration_id,
			[
				'total_migrated' => $counts['migrated'],
				'total_failed'   => $counts['failed'],
				'total_skipped'  => $counts['skipped'],
			]
		);

		$total_planned = (int) $migration->total_planned;
		$total_done    = $counts['migrated'] + $counts['failed'] + $counts['skipped'];
		$percent       = $total_planned > 0 ? (int) round( ( $total_done / $total_planned ) * 100 ) : 100;

		return [
			'done'               => false,
			'status'             => 'processing',
			'lock_token'         => $lock_token,
			'processed_in_batch' => $processed_in_batch,
			'total_planned'      => $total_planned,
			'total_migrated'     => $counts['migrated'],
			'total_failed'       => $counts['failed'],
			'total_skipped'      => $counts['skipped'],
			'total_done'         => $total_done,
			'percent'            => min( 99, $percent ),
		];
	}
}
