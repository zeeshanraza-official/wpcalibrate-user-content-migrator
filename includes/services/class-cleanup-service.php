<?php
/**
 * Cleanup and Retention Service
 *
 * @package WPCalibrate\UserContentMigrator\Services
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Services;

use WPCalibrate\UserContentMigrator\Database\MigrationsTable;
use WPCalibrate\UserContentMigrator\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles retention cleanup and pruning of historical migration records.
 */
class CleanupService {

	/**
	 * Run automatic retention cleanup based on configured retention_days.
	 *
	 * @return int Number of deleted migrations.
	 */
	public static function run_retention_cleanup(): int {
		$days = SettingsManager::get_int( 'retention_days', 90 );
		if ( 0 === $days ) {
			// Retention set to indefinite / keep until manually deleted.
			return 0;
		}

		return self::prune_records_older_than( $days );
	}

	/**
	 * Delete historical migrations older than a specific number of days.
	 * Strictly skips active or locked migrations.
	 *
	 * @param int $days Number of days.
	 * @return int Number of deleted migrations.
	 */
	public static function prune_records_older_than( int $days ): int {
		global $wpdb;

		if ( $days <= 0 ) {
			return 0;
		}

		$cutoff_time = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		$table       = Schema::get_table_name( 'migrations' );

		// Only target terminal states: completed, completed_with_errors, failed, cancelled, rolled_back, rollback_completed_with_errors.
		// Never prune processing, queued, cancelling, rolling_back, or actively locked records.
		$terminal_statuses = [
			'completed',
			'completed_with_errors',
			'failed',
			'cancelled',
			'rolled_back',
			'rollback_completed_with_errors',
		];

		$placeholders = implode( ', ', array_fill( 0, count( $terminal_statuses ), '%s' ) );
		$params       = array_merge( [ $cutoff_time ], $terminal_statuses );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$eligible_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table} 
				WHERE created_at < %s 
				AND status IN ({$placeholders}) 
				AND (lock_token IS NULL OR lock_expires_at IS NULL OR lock_expires_at < UTC_TIMESTAMP())",
				$params
			)
		);

		if ( empty( $eligible_ids ) ) {
			return 0;
		}

		$deleted_count = 0;
		foreach ( $eligible_ids as $id ) {
			$id = (int) $id;
			if ( MigrationsTable::delete( $id ) ) {
				$deleted_count++;
			}
		}

		return $deleted_count;
	}

	/**
	 * Perform manual cleanup of all eligible completed/terminal migrations.
	 *
	 * @return int Number of deleted migrations.
	 */
	public static function prune_all_completed(): int {
		global $wpdb;

		$table = Schema::get_table_name( 'migrations' );

		$terminal_statuses = [
			'completed',
			'completed_with_errors',
			'failed',
			'cancelled',
			'rolled_back',
			'rollback_completed_with_errors',
		];

		$placeholders = implode( ', ', array_fill( 0, count( $terminal_statuses ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$eligible_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table} 
				WHERE status IN ({$placeholders}) 
				AND (lock_token IS NULL OR lock_expires_at IS NULL OR lock_expires_at < UTC_TIMESTAMP())",
				$terminal_statuses
			)
		);

		if ( empty( $eligible_ids ) ) {
			return 0;
		}

		$deleted_count = 0;
		foreach ( $eligible_ids as $id ) {
			$id = (int) $id;
			if ( MigrationsTable::delete( $id ) ) {
				$deleted_count++;
			}
		}

		return $deleted_count;
	}
}
