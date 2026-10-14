<?php
/**
 * AJAX Controller
 *
 * @package WPCalibrate\UserContentMigrator\Api
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Api;

use WPCalibrate\UserContentMigrator\Database\ItemsTable;
use WPCalibrate\UserContentMigrator\Database\MigrationsTable;
use WPCalibrate\UserContentMigrator\Migration\BatchProcessor;
use WPCalibrate\UserContentMigrator\Migration\MigrationJob;
use WPCalibrate\UserContentMigrator\Rollback\RollbackService;
use WPCalibrate\UserContentMigrator\Services\CleanupService;
use WPCalibrate\UserContentMigrator\Services\ContentDiscoveryService;
use WPCalibrate\UserContentMigrator\Services\LockManager;
use WPCalibrate\UserContentMigrator\Services\UserSearchService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all secure AJAX endpoints for WPCalibrate User Content Migrator.
 */
class AjaxController {

	/**
	 * Nonce action name.
	 */
	public const NONCE_ACTION = 'wpcalibrate_ucm_admin_nonce';

	/**
	 * Get required capability.
	 *
	 * @return string Capability string.
	 */
	public static function get_capability(): string {
		/**
		 * Filter required administrative capability.
		 *
		 * @param string $capability Default 'manage_options'.
		 */
		return (string) apply_filters( 'wpcalibrate_ucm_capability', 'manage_options' );
	}

	/**
	 * Register all AJAX actions.
	 *
	 * @return void
	 */
	public static function register_actions(): void {
		$actions = [
			'wpcalibrate_ucm_search_users'            => 'handle_search_users',
			'wpcalibrate_ucm_user_summary'            => 'handle_user_summary',
			'wpcalibrate_ucm_scan_content'            => 'handle_scan_content',
			'wpcalibrate_ucm_query_records'           => 'handle_query_records',
			'wpcalibrate_ucm_start_migration'         => 'handle_start_migration',
			'wpcalibrate_ucm_process_batch'           => 'handle_process_batch',
			'wpcalibrate_ucm_cancel_migration'        => 'handle_cancel_migration',
			'wpcalibrate_ucm_start_rollback'          => 'handle_start_rollback',
			'wpcalibrate_ucm_process_rollback_batch'  => 'handle_process_rollback_batch',
			'wpcalibrate_ucm_force_unlock'            => 'handle_force_unlock',
			'wpcalibrate_ucm_query_history_items'     => 'handle_query_history_items',
			'wpcalibrate_ucm_delete_migration'        => 'handle_delete_migration',
			'wpcalibrate_ucm_manual_cleanup'          => 'handle_manual_cleanup',
		];

		foreach ( $actions as $action => $method ) {
			add_action( "wp_ajax_{$action}", [ self::class, $method ] );
		}
	}

	/**
	 * Verify permissions and nonce.
	 *
	 * @return void Dies with JSON error if unauthorized.
	 */
	private static function check_auth(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				[ 'message' => __( 'Security check failed. Please refresh the page and try again.', 'wpcalibrate-user-content-migrator' ) ],
				403
			);
		}

		if ( ! current_user_can( self::get_capability() ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You do not have permission to perform this operation.', 'wpcalibrate-user-content-migrator' ) ],
				403
			);
		}
	}

	/**
	 * Handle user search.
	 *
	 * @return void
	 */
	public static function handle_search_users(): void {
		self::check_auth();

		$term     = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		$page     = isset( $_POST['page'] ) ? max( 1, (int) $_POST['page'] ) : 1;
		$exclude  = isset( $_POST['exclude'] ) ? (int) $_POST['exclude'] : 0;
		$per_page = 20;

		$results = UserSearchService::search_users( $term, $page, $per_page, $exclude );
		wp_send_json_success( $results );
	}

	/**
	 * Handle user summary and authored content counts.
	 *
	 * @return void
	 */
	public static function handle_user_summary(): void {
		self::check_auth();

		$user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
		$summary = UserSearchService::get_user_summary( $user_id );

		if ( ! $summary ) {
			wp_send_json_error( [ 'message' => __( 'User not found.', 'wpcalibrate-user-content-migrator' ) ] );
		}

		wp_send_json_success( $summary );
	}

	/**
	 * Handle read-only scan of source content.
	 *
	 * @return void
	 */
	public static function handle_scan_content(): void {
		self::check_auth();

		$criteria = self::sanitize_criteria( $_POST['criteria'] ?? [] );
		$scan     = ContentDiscoveryService::scan_content( $criteria );

		wp_send_json_success( $scan );
	}

	/**
	 * Handle querying individual records for selection table.
	 *
	 * @return void
	 */
	public static function handle_query_records(): void {
		self::check_auth();

		$criteria = self::sanitize_criteria( $_POST['criteria'] ?? [] );
		$page     = isset( $_POST['page'] ) ? max( 1, (int) $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? max( 5, min( 100, (int) $_POST['per_page'] ) ) : 20;
		$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$results = ContentDiscoveryService::query_records( $criteria, $page, $per_page, $search );
		wp_send_json_success( $results );
	}

	/**
	 * Handle initializing and starting a new migration.
	 *
	 * @return void
	 */
	public static function handle_start_migration(): void {
		self::check_auth();

		$source_id = isset( $_POST['source_user_id'] ) ? (int) $_POST['source_user_id'] : 0;
		$dest_id   = isset( $_POST['dest_user_id'] ) ? (int) $_POST['dest_user_id'] : 0;
		$criteria  = self::sanitize_criteria( $_POST['criteria'] ?? [] );

		$job_id = MigrationJob::create_job( $source_id, $dest_id, $criteria );

		if ( is_wp_error( $job_id ) ) {
			wp_send_json_error( [ 'message' => $job_id->get_error_message() ] );
		}

		// Acquire lock for initial batch worker.
		$lock_token = LockManager::acquire_lock( (int) $job_id );

		wp_send_json_success(
			[
				'migration_id' => (int) $job_id,
				'lock_token'   => (string) $lock_token,
				'message'      => __( 'Migration initialized. Starting batches...', 'wpcalibrate-user-content-migrator' ),
			]
		);
	}

	/**
	 * Handle processing next migration batch.
	 *
	 * @return void
	 */
	public static function handle_process_batch(): void {
		self::check_auth();

		$migration_id = isset( $_POST['migration_id'] ) ? (int) $_POST['migration_id'] : 0;
		$lock_token   = isset( $_POST['lock_token'] ) ? sanitize_text_field( wp_unslash( $_POST['lock_token'] ) ) : '';

		$result = BatchProcessor::process_batch( $migration_id, $lock_token );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle cancelling a migration.
	 *
	 * @return void
	 */
	public static function handle_cancel_migration(): void {
		self::check_auth();

		$migration_id = isset( $_POST['migration_id'] ) ? (int) $_POST['migration_id'] : 0;
		if ( $migration_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid migration ID.', 'wpcalibrate-user-content-migrator' ) ] );
		}

		$migration = MigrationsTable::get( $migration_id );
		if ( ! $migration ) {
			wp_send_json_error( [ 'message' => __( 'Migration not found.', 'wpcalibrate-user-content-migrator' ) ] );
		}

		if ( 'cancelled' !== $migration->status ) {
			MigrationJob::transition_status( $migration_id, 'cancelled' );
			LockManager::force_unlock( $migration_id );
		}

		$counts = ItemsTable::get_status_counts( $migration_id );

		wp_send_json_success(
			[
				'message'        => __( 'Migration cancelled successfully.', 'wpcalibrate-user-content-migrator' ),
				'migration_id'   => $migration_id,
				'total_migrated' => $counts['migrated'] ?? 0,
				'total_failed'   => $counts['failed'] ?? 0,
				'total_skipped'  => $counts['skipped'] ?? 0,
			]
		);
	}

	/**
	 * Handle initiating rollback.
	 *
	 * @return void
	 */
	public static function handle_start_rollback(): void {
		self::check_auth();

		$migration_id = isset( $_POST['migration_id'] ) ? (int) $_POST['migration_id'] : 0;
		$result       = RollbackService::start_rollback( $migration_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle processing next rollback batch.
	 *
	 * @return void
	 */
	public static function handle_process_rollback_batch(): void {
		self::check_auth();

		$migration_id = isset( $_POST['migration_id'] ) ? (int) $_POST['migration_id'] : 0;
		$lock_token   = isset( $_POST['lock_token'] ) ? sanitize_text_field( wp_unslash( $_POST['lock_token'] ) ) : '';

		$result = RollbackService::process_batch( $migration_id, $lock_token );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle force unlocking a migration.
	 *
	 * @return void
	 */
	public static function handle_force_unlock(): void {
		self::check_auth();

		$migration_id = isset( $_POST['migration_id'] ) ? (int) $_POST['migration_id'] : 0;
		LockManager::force_unlock( $migration_id );

		wp_send_json_success( [ 'message' => __( 'Migration unlocked successfully.', 'wpcalibrate-user-content-migrator' ) ] );
	}

	/**
	 * Handle querying history item ledger with pagination.
	 *
	 * @return void
	 */
	public static function handle_query_history_items(): void {
		self::check_auth();

		$migration_id = isset( $_POST['migration_id'] ) ? (int) $_POST['migration_id'] : 0;
		$page         = isset( $_POST['page'] ) ? max( 1, (int) $_POST['page'] ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? max( 5, min( 100, (int) $_POST['per_page'] ) ) : 20;
		$status       = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';

		$args = [
			'page'     => $page,
			'per_page' => $per_page,
			'status'   => $status,
		];

		$results = ItemsTable::query( $migration_id, $args );
		wp_send_json_success( $results );
	}

	/**
	 * Handle deleting an individual migration record.
	 *
	 * @return void
	 */
	public static function handle_delete_migration(): void {
		self::check_auth();

		$migration_id = isset( $_POST['migration_id'] ) ? (int) $_POST['migration_id'] : 0;
		$migration    = MigrationsTable::get( $migration_id );

		if ( ! $migration ) {
			wp_send_json_error( [ 'message' => __( 'Migration not found.', 'wpcalibrate-user-content-migrator' ) ] );
		}

		if ( LockManager::is_locked( $migration_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Cannot delete an actively locked migration.', 'wpcalibrate-user-content-migrator' ) ] );
		}

		MigrationsTable::delete( $migration_id );
		wp_send_json_success( [ 'message' => __( 'Migration and rollback history deleted.', 'wpcalibrate-user-content-migrator' ) ] );
	}

	/**
	 * Handle manual cleanup of completed migrations.
	 *
	 * @return void
	 */
	public static function handle_manual_cleanup(): void {
		self::check_auth();

		$deleted = CleanupService::prune_all_completed();
		wp_send_json_success(
			[
				/* translators: %d: count of deleted migrations */
				'message' => sprintf( __( 'Cleaned up %d completed migration records.', 'wpcalibrate-user-content-migrator' ), $deleted ),
				'deleted' => $deleted,
			]
		);
	}

	/**
	 * Sanitize filter criteria array.
	 *
	 * @param mixed $raw Raw input.
	 * @return array<string, mixed> Sanitized criteria.
	 */
	private static function sanitize_criteria( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$clean = [];

		$clean['source_user_id'] = isset( $raw['source_user_id'] ) ? (int) $raw['source_user_id'] : 0;

		if ( ! empty( $raw['post_types'] ) && is_array( $raw['post_types'] ) ) {
			$clean['post_types'] = array_map( 'sanitize_key', $raw['post_types'] );
		}

		if ( ! empty( $raw['statuses'] ) && is_array( $raw['statuses'] ) ) {
			$clean['statuses'] = array_map( 'sanitize_key', $raw['statuses'] );
		}

		$clean['include_media']    = ! empty( $raw['include_media'] );
		$clean['include_comments'] = ! empty( $raw['include_comments'] );

		$clean['date_from'] = ! empty( $raw['date_from'] ) ? sanitize_text_field( (string) $raw['date_from'] ) : '';
		$clean['date_to']   = ! empty( $raw['date_to'] ) ? sanitize_text_field( (string) $raw['date_to'] ) : '';

		if ( ! empty( $raw['individual_ids'] ) && is_array( $raw['individual_ids'] ) ) {
			$clean['individual_ids'] = array_values( array_filter( array_map( 'intval', $raw['individual_ids'] ) ) );
		}

		return $clean;
	}
}
