<?php
/**
 * Migration Job Manager and State Machine
 *
 * @package WPCalibrate\UserContentMigrator\Migration
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Migration;

use WP_Error;
use WPCalibrate\UserContentMigrator\Database\ItemsTable;
use WPCalibrate\UserContentMigrator\Database\MigrationsTable;
use WPCalibrate\UserContentMigrator\Services\ContentDiscoveryService;
use WPCalibrate\UserContentMigrator\Services\UserSearchService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages migration lifecycle, validated status transitions, and job initialization.
 */
class MigrationJob {

	/**
	 * Valid migration statuses.
	 *
	 * @var array<string>
	 */
	public const VALID_STATUSES = [
		'draft',
		'scanned',
		'queued',
		'processing',
		'completed',
		'completed_with_errors',
		'failed',
		'cancelling',
		'cancelled',
		'rollback_queued',
		'rolling_back',
		'rolled_back',
		'rollback_completed_with_errors',
	];

	/**
	 * Allowed state transitions map.
	 *
	 * @var array<string, array<string>>
	 */
	private const ALLOWED_TRANSITIONS = [
		'draft'                          => [ 'scanned', 'queued', 'cancelled' ],
		'scanned'                        => [ 'queued', 'cancelled' ],
		'queued'                         => [ 'processing', 'cancelling', 'cancelled' ],
		'processing'                     => [ 'completed', 'completed_with_errors', 'failed', 'cancelling', 'cancelled' ],
		'cancelling'                     => [ 'cancelled', 'failed' ],
		'cancelled'                      => [],
		'completed'                      => [ 'rollback_queued' ],
		'completed_with_errors'          => [ 'rollback_queued' ],
		'failed'                         => [ 'queued', 'rollback_queued' ], // can retry or rollback what completed
		'rollback_queued'                => [ 'rolling_back' ],
		'rolling_back'                   => [ 'rolled_back', 'rollback_completed_with_errors', 'failed' ],
		'rolled_back'                    => [],
		'rollback_completed_with_errors' => [],
	];

	/**
	 * Validate if a status transition is allowed.
	 *
	 * @param string $current_status Current status.
	 * @param string $new_status Desired new status.
	 * @return bool True if transition is valid.
	 */
	public static function is_valid_transition( string $current_status, string $new_status ): bool {
		if ( ! in_array( $new_status, self::VALID_STATUSES, true ) ) {
			return false;
		}

		if ( $current_status === $new_status ) {
			return true;
		}

		$allowed = self::ALLOWED_TRANSITIONS[ $current_status ] ?? [];
		return in_array( $new_status, $allowed, true );
	}

	/**
	 * Create and initialize a new migration job.
	 *
	 * @param int                  $source_user_id Source user ID.
	 * @param int                  $dest_user_id Destination user ID.
	 * @param array<string, mixed> $criteria Filter criteria.
	 * @return int|WP_Error Created migration ID or WP_Error.
	 */
	public static function create_job( int $source_user_id, int $dest_user_id, array $criteria ) {
		// 1. Validate users.
		$user_valid = UserSearchService::validate_users( $source_user_id, $dest_user_id );
		if ( is_wp_error( $user_valid ) ) {
			return $user_valid;
		}

		// 2. Discover matching records for exact ledger.
		$criteria['source_user_id'] = $source_user_id;
		$ledger_items               = ContentDiscoveryService::get_ledger_definitions( $criteria, $dest_user_id );

		if ( empty( $ledger_items ) ) {
			return new WP_Error(
				'no_matching_content',
				__( 'No matching content found for the selected criteria.', 'wpcalibrate-user-content-migrator' )
			);
		}

		$total_planned = count( $ledger_items );

		// 3. Create migration record.
		$migration_id = MigrationsTable::create(
			[
				'source_user_id'   => $source_user_id,
				'dest_user_id'     => $dest_user_id,
				'status'           => 'queued',
				'include_comments' => ! empty( $criteria['include_comments'] ) ? 1 : 0,
				'config_snapshot'  => $criteria,
				'total_planned'    => $total_planned,
				'started_at'       => current_time( 'mysql', true ),
			]
		);

		if ( $migration_id <= 0 ) {
			return new WP_Error(
				'job_creation_failed',
				__( 'Failed to initialize migration record in database.', 'wpcalibrate-user-content-migrator' )
			);
		}

		// 4. Insert items into ledger in chunks.
		$chunk_size = 500;
		$chunks     = array_chunk( $ledger_items, $chunk_size );
		foreach ( $chunks as $chunk ) {
			ItemsTable::insert_batch( $migration_id, $chunk );
		}

		return $migration_id;
	}

	/**
	 * Update migration status with validation.
	 *
	 * @param int    $migration_id Migration ID.
	 * @param string $new_status Target status.
	 * @return bool True if transitioned, false if invalid transition.
	 */
	public static function transition_status( int $migration_id, string $new_status ): bool {
		$migration = MigrationsTable::get( $migration_id );
		if ( ! $migration ) {
			return false;
		}

		if ( ! self::is_valid_transition( $migration->status, $new_status ) ) {
			return false;
		}

		$update_data = [ 'status' => $new_status ];

		if ( 'completed' === $new_status || 'completed_with_errors' === $new_status || 'failed' === $new_status || 'cancelled' === $new_status ) {
			$update_data['completed_at'] = current_time( 'mysql', true );
		}

		if ( 'rolled_back' === $new_status || 'rollback_completed_with_errors' === $new_status ) {
			$update_data['rollback_completed_at'] = current_time( 'mysql', true );
		}

		return MigrationsTable::update( $migration_id, $update_data );
	}
}
