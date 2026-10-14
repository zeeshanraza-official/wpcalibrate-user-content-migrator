<?php
/**
 * Rollback Service
 *
 * @package WPCalibrate\UserContentMigrator\Rollback
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Rollback;

use WP_Comment;
use WP_Error;
use WP_Post;
use WPCalibrate\UserContentMigrator\Database\ItemsTable;
use WPCalibrate\UserContentMigrator\Database\MigrationsTable;
use WPCalibrate\UserContentMigrator\Migration\MigrationJob;
use WPCalibrate\UserContentMigrator\Migration\VerificationService;
use WPCalibrate\UserContentMigrator\Services\LockManager;
use WPCalibrate\UserContentMigrator\Services\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles exact, conflict-safe item ledger rollback in resumable batches.
 */
class RollbackService {

	/**
	 * Prepare and start a rollback process.
	 *
	 * @param int $migration_id Migration ID.
	 * @return array<string, mixed>|WP_Error Initial status and lock token or error.
	 */
	public static function start_rollback( int $migration_id ) {
		$migration = MigrationsTable::get( $migration_id );
		if ( ! $migration ) {
			return new WP_Error( 'migration_not_found', __( 'Migration record not found.', 'wpcalibrate-user-content-migrator' ) );
		}

		if ( 'rolling_back' === $migration->status ) {
			// Already in progress, allow resuming.
		} elseif ( ! in_array( $migration->status, [ 'completed', 'completed_with_errors', 'failed' ], true ) ) {
			return new WP_Error(
				'invalid_rollback_state',
				sprintf(
					/* translators: %s: status */
					__( 'Cannot rollback a migration in "%s" state.', 'wpcalibrate-user-content-migrator' ),
					$migration->status
				)
			);
		}

		$lock_token = LockManager::acquire_lock( $migration_id );
		if ( ! $lock_token ) {
			return new WP_Error(
				'migration_locked',
				__( 'Cannot initiate rollback: this migration is currently locked by another worker.', 'wpcalibrate-user-content-migrator' )
			);
		}

		MigrationJob::transition_status( $migration_id, 'rollback_queued' );
		MigrationJob::transition_status( $migration_id, 'rolling_back' );

		MigrationsTable::update(
			$migration_id,
			[
				'rollback_started_at' => current_time( 'mysql', true ),
			]
		);

		return [
			'started'    => true,
			'lock_token' => $lock_token,
		];
	}

	/**
	 * Process a batch of items for rollback.
	 *
	 * @param int    $migration_id Migration ID.
	 * @param string $lock_token Active lock token.
	 * @return array<string, mixed>|WP_Error Batch status.
	 */
	public static function process_batch( int $migration_id, string $lock_token = '' ) {
		$migration = MigrationsTable::get( $migration_id );
		if ( ! $migration ) {
			return new WP_Error( 'migration_not_found', __( 'Migration record not found.', 'wpcalibrate-user-content-migrator' ) );
		}

		// Ensure lock.
		if ( empty( $lock_token ) ) {
			$lock_token = LockManager::acquire_lock( $migration_id );
			if ( ! $lock_token ) {
				return new WP_Error( 'migration_locked', __( 'Migration is locked.', 'wpcalibrate-user-content-migrator' ) );
			}
		} else {
			$refreshed = LockManager::refresh_lock( $migration_id, $lock_token );
			if ( ! $refreshed ) {
				$lock_token = LockManager::acquire_lock( $migration_id, 0, $lock_token );
				if ( ! $lock_token ) {
					return new WP_Error( 'lock_expired', __( 'Lock expired and could not be renewed.', 'wpcalibrate-user-content-migrator' ) );
				}
			}
		}

		$batch_size = SettingsManager::get_int( 'batch_size', 25 );
		/**
		 * Filter rollback batch size.
		 *
		 * @param int $batch_size Batch size.
		 * @param int $migration_id Migration ID.
		 */
		$batch_size = (int) apply_filters( 'wpcalibrate_ucm_rollback_batch_size', $batch_size, $migration_id );
		$batch_size = max( 1, min( 100, $batch_size ) );

		$items = ItemsTable::get_pending_rollback_items( $migration_id, $batch_size );

		// Rollback complete!
		if ( empty( $items ) ) {
			$counts       = ItemsTable::get_rollback_status_counts( $migration_id );
			$verification = VerificationService::verify_rollback( $migration_id );

			$final_status = ( $counts['failed'] > 0 || $counts['conflict'] > 0 || 'mismatch' === $verification['status'] )
				? 'rollback_completed_with_errors'
				: 'rolled_back';

			MigrationJob::transition_status( $migration_id, $final_status );

			MigrationsTable::update(
				$migration_id,
				[
					'total_rolled_back'        => $counts['rolled_back'],
					'total_rollback_failed'    => $counts['failed'],
					'total_rollback_conflicts' => $counts['conflict'],
					'rollback_completed_at'    => current_time( 'mysql', true ),
				]
			);

			LockManager::release_lock( $migration_id, $lock_token );

			/**
			 * Action fired when rollback finishes.
			 *
			 * @param int                  $migration_id Migration ID.
			 * @param string               $final_status Final status string.
			 * @param array<string, int>   $counts Rollback status counts.
			 * @param array<string, mixed> $verification Verification summary.
			 */
			do_action( 'wpcalibrate_ucm_rollback_completed', $migration_id, $final_status, $counts, $verification );

			return [
				'done'                     => true,
				'status'                   => $final_status,
				'lock_token'               => '',
				'total_rolled_back'        => $counts['rolled_back'],
				'total_rollback_failed'    => $counts['failed'],
				'total_rollback_conflicts' => $counts['conflict'],
				'verification'             => $verification,
			];
		}

		$processed_in_batch = 0;

		foreach ( $items as $item ) {
			$item_id        = (int) $item->id;
			$obj_id         = (int) $item->object_id;
			$obj_type       = (string) $item->object_type;
			$original_owner = (int) $item->original_owner_id;
			$expected_owner = (int) $item->new_owner_id;

			if ( 'comment' === $obj_type ) {
				clean_comment_cache( $obj_id );
				$comment = get_comment( $obj_id );
				if ( ! ( $comment instanceof WP_Comment ) ) {
					ItemsTable::mark_rollback_failed( $item_id, sprintf( __( 'Comment #%d not found.', 'wpcalibrate-user-content-migrator' ), $obj_id ) );
					$processed_in_batch++;
					continue;
				}

				// Conflict detection: if comment user_id changed after migration, DO NOT overwrite!
				if ( (int) $comment->user_id !== $expected_owner ) {
					$conflict_msg = sprintf(
						/* translators: 1: comment ID, 2: expected user ID, 3: actual user ID */
						__( 'Conflict on Comment #%1$d: current user #%3$d differs from expected user #%2$d. Rollback skipped to prevent data overwrite.', 'wpcalibrate-user-content-migrator' ),
						$obj_id,
						$expected_owner,
						(int) $comment->user_id
					);
					ItemsTable::mark_rollback_conflict( $item_id, $conflict_msg );
					MigrationsTable::append_log( $migration_id, $conflict_msg, 'warning' );
					$processed_in_batch++;
					continue;
				}

				// Restore original comment user.
				do_action( 'wpcalibrate_ucm_before_comment_rollback', $obj_id, $original_owner );
				$updated = wp_update_comment( [ 'comment_ID' => $obj_id, 'user_id' => $original_owner ] );
				clean_comment_cache( $obj_id );

				if ( false === $updated || 0 === $updated ) {
					ItemsTable::mark_rollback_failed( $item_id, __( 'wp_update_comment failed during rollback.', 'wpcalibrate-user-content-migrator' ) );
				} else {
					ItemsTable::mark_rolled_back( $item_id );
					do_action( 'wpcalibrate_ucm_after_comment_rollback', $obj_id, $original_owner );
				}
			} else {
				clean_post_cache( $obj_id );
				$post = get_post( $obj_id );
				if ( ! ( $post instanceof WP_Post ) ) {
					ItemsTable::mark_rollback_failed( $item_id, sprintf( __( 'Post #%d not found.', 'wpcalibrate-user-content-migrator' ), $obj_id ) );
					$processed_in_batch++;
					continue;
				}

				// Conflict detection: if author changed after migration, DO NOT overwrite!
				if ( (int) $post->post_author !== $expected_owner ) {
					$conflict_msg = sprintf(
						/* translators: 1: post ID, 2: expected author ID, 3: actual author ID */
						__( 'Conflict on Post #%1$d: current author #%3$d differs from expected author #%2$d. Rollback skipped to prevent data overwrite.', 'wpcalibrate-user-content-migrator' ),
						$obj_id,
						$expected_owner,
						(int) $post->post_author
					);
					ItemsTable::mark_rollback_conflict( $item_id, $conflict_msg );
					MigrationsTable::append_log( $migration_id, $conflict_msg, 'warning' );
					$processed_in_batch++;
					continue;
				}

				// Restore original post author.
				do_action( 'wpcalibrate_ucm_before_post_rollback', $obj_id, $original_owner, $post->post_type );
				$updated = wp_update_post( [ 'ID' => $obj_id, 'post_author' => $original_owner ], true );
				clean_post_cache( $obj_id );

				if ( is_wp_error( $updated ) || 0 === $updated ) {
					$err_text = is_wp_error( $updated ) ? $updated->get_error_message() : __( 'wp_update_post failed during rollback.', 'wpcalibrate-user-content-migrator' );
					ItemsTable::mark_rollback_failed( $item_id, $err_text );
				} else {
					ItemsTable::mark_rolled_back( $item_id );
					do_action( 'wpcalibrate_ucm_after_post_rollback', $obj_id, $original_owner, $post->post_type );
				}
			}

			$processed_in_batch++;
		}

		LockManager::refresh_lock( $migration_id, $lock_token );

		$counts = ItemsTable::get_rollback_status_counts( $migration_id );
		MigrationsTable::update(
			$migration_id,
			[
				'total_rolled_back'        => $counts['rolled_back'],
				'total_rollback_failed'    => $counts['failed'],
				'total_rollback_conflicts' => $counts['conflict'],
			]
		);

		return [
			'done'                     => false,
			'status'                   => 'rolling_back',
			'lock_token'               => $lock_token,
			'processed_in_batch'       => $processed_in_batch,
			'total_rolled_back'        => $counts['rolled_back'],
			'total_rollback_failed'    => $counts['failed'],
			'total_rollback_conflicts' => $counts['conflict'],
		];
	}
}
