<?php
/**
 * Plugin Activator
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator;

use WPCalibrate\UserContentMigrator\Database\Schema;
use WPCalibrate\UserContentMigrator\Services\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 */
class Activator {

	/**
	 * Minimum PHP version required.
	 */
	public const MIN_PHP_VERSION = '8.2.0';

	/**
	 * Minimum WordPress version required.
	 */
	public const MIN_WP_VERSION = '6.2.0';

	/**
	 * Handle plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// 1. Verify PHP version.
		if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
			deactivate_plugins( plugin_basename( WPCALIBRATE_UCM_FILE ) );
			wp_die(
				sprintf(
					/* translators: 1: required PHP version, 2: current PHP version */
					esc_html__( 'WPCalibrate User Content Migrator requires PHP %1$s or higher. Your server is running PHP %2$s. Plugin activation aborted.', 'wpcalibrate-user-content-migrator' ),
					self::MIN_PHP_VERSION,
					PHP_VERSION
				),
				esc_html__( 'Plugin Activation Error', 'wpcalibrate-user-content-migrator' ),
				[ 'back_link' => true ]
			);
		}

		// 2. Verify WordPress version.
		global $wp_version;
		if ( version_compare( $wp_version, self::MIN_WP_VERSION, '<' ) ) {
			deactivate_plugins( plugin_basename( WPCALIBRATE_UCM_FILE ) );
			wp_die(
				sprintf(
					/* translators: 1: required WordPress version, 2: current WordPress version */
					esc_html__( 'WPCalibrate User Content Migrator requires WordPress %1$s or higher. Your site is running WordPress %2$s. Plugin activation aborted.', 'wpcalibrate-user-content-migrator' ),
					self::MIN_WP_VERSION,
					$wp_version
				),
				esc_html__( 'Plugin Activation Error', 'wpcalibrate-user-content-migrator' ),
				[ 'back_link' => true ]
			);
		}

		// 3. Create or update custom database tables.
		Schema::create_or_update_tables();

		// 4. Initialize default options if not yet stored.
		if ( false === get_option( SettingsManager::OPTION_NAME ) ) {
			SettingsManager::update_all( [] );
		}

		// 5. Schedule daily retention cleanup cron.
		if ( ! wp_next_scheduled( 'wpcalibrate_ucm_daily_retention_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wpcalibrate_ucm_daily_retention_cleanup' );
		}

		// 6. Record installed version.
		update_option( 'wpcalibrate_ucm_version', WPCALIBRATE_UCM_VERSION );
	}
}
