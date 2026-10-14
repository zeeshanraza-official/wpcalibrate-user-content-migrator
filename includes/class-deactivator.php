<?php
/**
 * Plugin Deactivator
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator;

use WPCalibrate\UserContentMigrator\Database\MigrationsTable;
use WPCalibrate\UserContentMigrator\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation.
 * Preserves all user data, settings, and migration histories safely.
 */
class Deactivator {

	/**
	 * Handle plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear scheduled cron event.
		$timestamp = wp_next_scheduled( 'wpcalibrate_ucm_daily_retention_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'wpcalibrate_ucm_daily_retention_cleanup' );
		}

		// Clear recoverable locks from active jobs to prevent permanent deadlocks on reactivation.
		global $wpdb;
		$table = Schema::get_table_name( 'migrations' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			"UPDATE {$table} SET lock_token = NULL, lock_expires_at = NULL WHERE lock_token IS NOT NULL"
		);
	}
}
