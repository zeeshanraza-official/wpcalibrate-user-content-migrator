<?php
/**
 * Plugin Upgrader
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator;

use WPCalibrate\UserContentMigrator\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles automated schema and data upgrades between versions.
 */
class Upgrader {

	/**
	 * Check and run upgrades if version has changed.
	 *
	 * @return void
	 */
	public static function check_upgrade(): void {
		$installed_schema = (string) get_option( Schema::SCHEMA_VERSION_OPTION, '0.0.0' );

		if ( version_compare( $installed_schema, Schema::SCHEMA_VERSION, '<' ) ) {
			Schema::create_or_update_tables();
		}

		$installed_version = (string) get_option( 'wpcalibrate_ucm_version', '0.0.0' );
		if ( version_compare( $installed_version, WPCALIBRATE_UCM_VERSION, '<' ) ) {
			update_option( 'wpcalibrate_ucm_version', WPCALIBRATE_UCM_VERSION );
		}
	}
}
