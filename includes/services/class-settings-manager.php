<?php
/**
 * Settings Manager
 *
 * @package WPCalibrate\UserContentMigrator\Services
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin configuration options and sanitization.
 */
class SettingsManager {

	/**
	 * Option name in wp_options.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'wpcalibrate_ucm_settings';

	/**
	 * Default settings values.
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULTS = [
		'default_statuses'         => [ 'publish', 'draft', 'pending', 'private', 'future' ],
		'allow_comments_migration' => true,
		'default_comment_transfer' => false,
		'retention_days'           => 90,
		'batch_size'               => 25,
		'lock_timeout_seconds'     => 60,
		'uninstall_delete_data'    => false,
		'license_key'              => '',
	];

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		$options = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $options ) ) {
			$options = [];
		}

		return wp_parse_args( $options, self::DEFAULTS );
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Optional default fallback.
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$settings = self::get_all();
		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return $default ?? ( self::DEFAULTS[ $key ] ?? null );
	}

	/**
	 * Get boolean setting value.
	 *
	 * @param string $key Setting key.
	 * @param bool   $default Default fallback.
	 * @return bool
	 */
	public static function get_bool( string $key, bool $default = false ): bool {
		$val = self::get( $key, $default );
		return (bool) $val;
	}

	/**
	 * Get integer setting value.
	 *
	 * @param string $key Setting key.
	 * @param int    $default Default fallback.
	 * @return int
	 */
	public static function get_int( string $key, int $default = 0 ): int {
		$val = self::get( $key, $default );
		return (int) $val;
	}

	/**
	 * Update settings.
	 *
	 * @param array<string, mixed> $new_settings Settings to update.
	 * @return bool
	 */
	public static function update_all( array $new_settings ): bool {
		$current   = self::get_all();
		$sanitized = self::sanitize_settings( wp_parse_args( $new_settings, $current ) );

		return update_option( self::OPTION_NAME, $sanitized );
	}

	/**
	 * Sanitize and validate all settings.
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed> Sanitized values.
	 */
	public static function sanitize_settings( array $input ): array {
		$output = self::DEFAULTS;

		// Default statuses.
		if ( isset( $input['default_statuses'] ) && is_array( $input['default_statuses'] ) ) {
			$allowed_statuses          = array_keys( ContentEligibilityService::get_supported_statuses() );
			$output['default_statuses'] = array_values(
				array_intersect(
					array_map( 'sanitize_key', $input['default_statuses'] ),
					$allowed_statuses
				)
			);
		}

		// Allow comments migration.
		$output['allow_comments_migration'] = ! empty( $input['allow_comments_migration'] );

		// Default comment transfer (MUST default to false).
		$output['default_comment_transfer'] = ! empty( $input['default_comment_transfer'] );

		// Retention days.
		$valid_retention = [ 0, 30, 60, 90, 180, 365 ];
		$retention       = isset( $input['retention_days'] ) ? (int) $input['retention_days'] : 90;
		$output['retention_days'] = in_array( $retention, $valid_retention, true ) ? $retention : 90;

		// Batch size (5 to 100).
		$batch_size = isset( $input['batch_size'] ) ? (int) $input['batch_size'] : 25;
		$output['batch_size'] = max( 5, min( 100, $batch_size ) );

		// Lock timeout seconds (30 to 300).
		$lock_timeout = isset( $input['lock_timeout_seconds'] ) ? (int) $input['lock_timeout_seconds'] : 60;
		$output['lock_timeout_seconds'] = max( 30, min( 300, $lock_timeout ) );

		// Uninstall delete data (default false).
		$output['uninstall_delete_data'] = ! empty( $input['uninstall_delete_data'] );

		// License key.
		if ( isset( $input['license_key'] ) ) {
			$raw_key = sanitize_text_field( (string) $input['license_key'] );
			// If not a masked string, update it.
			if ( false === strpos( $raw_key, '••••' ) ) {
				$output['license_key'] = trim( $raw_key );
			} else {
				$current_settings      = self::get_all();
				$output['license_key'] = $current_settings['license_key'] ?? '';
			}
		}

		return $output;
	}
}
