<?php
/**
 * Fired when the plugin is deleted via the WordPress admin.
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Require autoloader.
$autoloader = dirname( __FILE__ ) . '/includes/class-autoloader.php';
if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
	\WPCalibrate\UserContentMigrator\Autoloader::register( dirname( __FILE__ ) . '/includes' );
}

// Check administrator preference: delete data on uninstall.
$settings = get_option( 'wpcalibrate_ucm_settings', [] );
$delete_data = ! empty( $settings['uninstall_delete_data'] );

// If preference is OFF (default), preserve all migration history tables and settings!
if ( ! $delete_data ) {
	return;
}

// Drop custom tables.
if ( class_exists( '\WPCalibrate\UserContentMigrator\Database\Schema' ) ) {
	\WPCalibrate\UserContentMigrator\Database\Schema::drop_tables();
}

// Delete options.
delete_option( 'wpcalibrate_ucm_settings' );
delete_option( 'wpcalibrate_ucm_schema_version' );
delete_option( 'wpcalibrate_ucm_version' );

// Clear scheduled cron events.
$timestamp = wp_next_scheduled( 'wpcalibrate_ucm_daily_retention_cleanup' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'wpcalibrate_ucm_daily_retention_cleanup' );
}

// Clear transients.
delete_transient( 'wpcalibrate_ucm_active_lock' );
