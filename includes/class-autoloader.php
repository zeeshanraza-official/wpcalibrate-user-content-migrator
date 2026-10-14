<?php
/**
 * Plugin Autoloader
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader class for WPCalibrate User Content Migrator.
 */
class Autoloader {

	/**
	 * Namespace prefix to autoload.
	 *
	 * @var string
	 */
	private const NAMESPACE_PREFIX = 'WPCalibrate\\UserContentMigrator\\';

	/**
	 * Base directory for class files.
	 *
	 * @var string
	 */
	private static string $base_dir = '';

	/**
	 * Register the autoloader.
	 *
	 * @param string $base_dir Base includes directory path.
	 * @return void
	 */
	public static function register( string $base_dir ): void {
		self::$base_dir = rtrim( $base_dir, '/\\' ) . DIRECTORY_SEPARATOR;
		spl_autoload_register( [ self::class, 'autoload' ] );
	}

	/**
	 * Autoload callback.
	 *
	 * Maps:
	 * WPCalibrate\UserContentMigrator\Admin\AdminMenu -> includes/admin/class-admin-menu.php
	 * WPCalibrate\UserContentMigrator\Services\LockManager -> includes/services/class-lock-manager.php
	 * WPCalibrate\UserContentMigrator\Plugin -> includes/class-plugin.php
	 *
	 * @param string $class Full class name.
	 * @return void
	 */
	public static function autoload( string $class ): void {
		if ( 0 !== strpos( $class, self::NAMESPACE_PREFIX ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( self::NAMESPACE_PREFIX ) );
		$parts          = explode( '\\', $relative_class );

		// Format filename according to WordPress coding standards: class-lowercase-kebab.php.
		$class_name = array_pop( $parts );
		$kebab_name = strtolower( (string) preg_replace( '/([a-z])([A-Z])/', '$1-$2', $class_name ) );
		$file_name  = 'class-' . $kebab_name . '.php';

		// Subdirectories in lowercase kebab-case.
		$sub_dirs = array_map(
			static function ( string $part ): string {
				return strtolower( (string) preg_replace( '/([a-z])([A-Z])/', '$1-$2', $part ) );
			},
			$parts
		);

		$candidates = [
			$file_name,
			'class-' . str_replace( 'woo-commerce', 'woocommerce', $kebab_name ) . '.php',
			'class-' . strtolower( $class_name ) . '.php',
			'class-woocommerce-integration.php',
			$class_name . '.php',
		];

		$dir_path = self::$base_dir;
		if ( ! empty( $sub_dirs ) ) {
			$dir_path .= implode( DIRECTORY_SEPARATOR, $sub_dirs ) . DIRECTORY_SEPARATOR;
		}

		foreach ( array_unique( $candidates ) as $candidate ) {
			$path = $dir_path . $candidate;
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
