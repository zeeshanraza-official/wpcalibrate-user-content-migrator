<?php
/**
 * Database Schema Manager
 *
 * @package WPCalibrate\UserContentMigrator\Database
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles creation and migration of custom plugin database tables.
 */
class Schema {

	/**
	 * Database schema version.
	 *
	 * @var string
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * Option key storing current schema version.
	 *
	 * @var string
	 */
	public const SCHEMA_VERSION_OPTION = 'wpcalibrate_ucm_schema_version';

	/**
	 * Table name for migrations without prefix.
	 *
	 * @var string
	 */
	public const MIGRATIONS_TABLE = 'wpcalibrate_ucm_migrations';

	/**
	 * Table name for migration items without prefix.
	 *
	 * @var string
	 */
	public const ITEMS_TABLE = 'wpcalibrate_ucm_items';

	/**
	 * Get full table name with WordPress prefix.
	 *
	 * @param string $table Table identifier ('migrations' or 'items').
	 * @return string Full prefixed table name.
	 */
	public static function get_table_name( string $table ): string {
		global $wpdb;

		if ( 'items' === $table || self::ITEMS_TABLE === $table ) {
			return $wpdb->prefix . self::ITEMS_TABLE;
		}

		return $wpdb->prefix . self::MIGRATIONS_TABLE;
	}

	/**
	 * Create or update custom database tables using dbDelta.
	 *
	 * @return void
	 */
	public static function create_or_update_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$migrations_table = self::get_table_name( 'migrations' );
		$items_table      = self::get_table_name( 'items' );

		if ( ! function_exists( 'dbDelta' ) && file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$sql_migrations = "CREATE TABLE {$migrations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid varchar(64) NOT NULL,
			source_user_id bigint(20) unsigned NOT NULL,
			dest_user_id bigint(20) unsigned NOT NULL,
			initiator_user_id bigint(20) unsigned NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'draft',
			include_comments tinyint(1) NOT NULL DEFAULT 0,
			config_snapshot longtext NOT NULL,
			total_planned bigint(20) unsigned NOT NULL DEFAULT 0,
			total_migrated bigint(20) unsigned NOT NULL DEFAULT 0,
			total_skipped bigint(20) unsigned NOT NULL DEFAULT 0,
			total_failed bigint(20) unsigned NOT NULL DEFAULT 0,
			total_rolled_back bigint(20) unsigned NOT NULL DEFAULT 0,
			total_rollback_failed bigint(20) unsigned NOT NULL DEFAULT 0,
			total_rollback_conflicts bigint(20) unsigned NOT NULL DEFAULT 0,
			verification_status varchar(32) NOT NULL DEFAULT 'unverified',
			verification_data longtext NULL,
			errors_warnings longtext NULL,
			lock_token varchar(64) NULL,
			lock_expires_at datetime NULL,
			started_at datetime NULL,
			completed_at datetime NULL,
			rollback_started_at datetime NULL,
			rollback_completed_at datetime NULL,
			plugin_version varchar(32) NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY status (status),
			KEY source_user_id (source_user_id),
			KEY dest_user_id (dest_user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_items = "CREATE TABLE {$items_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			migration_id bigint(20) unsigned NOT NULL,
			object_type varchar(32) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			original_owner_id bigint(20) unsigned NOT NULL,
			new_owner_id bigint(20) unsigned NOT NULL,
			migration_status varchar(32) NOT NULL DEFAULT 'pending',
			rollback_status varchar(32) NOT NULL DEFAULT 'none',
			original_post_status varchar(32) NULL,
			error_message text NULL,
			migrated_at datetime NULL,
			rolled_back_at datetime NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY migration_id (migration_id),
			KEY object_lookup (object_type, object_id),
			KEY migration_status (migration_status),
			KEY rollback_status (rollback_status),
			KEY original_owner_id (original_owner_id),
			KEY new_owner_id (new_owner_id)
		) {$charset_collate};";

		dbDelta( $sql_migrations );
		dbDelta( $sql_items );

		update_option( self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION );
	}

	/**
	 * Drop custom tables (used during complete uninstall if configured).
	 *
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$migrations_table = self::get_table_name( 'migrations' );
		$items_table      = self::get_table_name( 'items' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$items_table}" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$migrations_table}" );

		delete_option( self::SCHEMA_VERSION_OPTION );
	}
}
