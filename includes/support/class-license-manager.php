<?php
/**
 * License Manager Integration Boundary
 *
 * @package WPCalibrate\UserContentMigrator\Support
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Support;

use WPCalibrate\UserContentMigrator\Services\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Architectural integration boundary for WPCalibrate licensing.
 *
 * Note: A remote licensing provider/API has not yet been supplied.
 * This class provides the clean contract, key masking, and interface without faking activation.
 */
class LicenseManager {

	/**
	 * License status constants.
	 */
	public const STATUS_NOT_CONFIGURED = 'not_configured';
	public const STATUS_ACTIVE         = 'active';
	public const STATUS_INACTIVE       = 'inactive';
	public const STATUS_EXPIRED        = 'expired';
	public const STATUS_INVALID        = 'invalid';

	/**
	 * Check whether a remote licensing provider has been wired up.
	 *
	 * @return bool Always false until WPCalibrate supplies the API provider details.
	 */
	public static function has_provider(): bool {
		/**
		 * Filter licensing provider configuration.
		 *
		 * @param bool $has_provider True if licensing adapter configured.
		 */
		return (bool) apply_filters( 'wpcalibrate_ucm_has_licensing_provider', false );
	}

	/**
	 * Get current license status.
	 *
	 * @return string Current status.
	 */
	public static function get_status(): string {
		if ( ! self::has_provider() ) {
			return self::STATUS_NOT_CONFIGURED;
		}

		/**
		 * Filter current license status from registered adapter.
		 *
		 * @param string $status Current status.
		 */
		return (string) apply_filters( 'wpcalibrate_ucm_license_status', self::STATUS_NOT_CONFIGURED );
	}

	/**
	 * Get masked version of stored license key.
	 *
	 * @return string Masked key for UI display.
	 */
	public static function get_masked_key(): string {
		$raw_key = (string) SettingsManager::get( 'license_key', '' );
		$raw_key = trim( $raw_key );

		if ( empty( $raw_key ) ) {
			return '';
		}

		$length = strlen( $raw_key );
		if ( $length <= 4 ) {
			return str_repeat( '•', $length );
		}

		$visible_suffix = substr( $raw_key, -4 );
		$masked_prefix  = str_repeat( '•', max( 8, $length - 4 ) );

		return $masked_prefix . $visible_suffix;
	}

	/**
	 * Get human-readable status badge info.
	 *
	 * @return array{slug: string, label: string, class: string, description: string}
	 */
	public static function get_status_info(): array {
		$status = self::get_status();

		if ( self::STATUS_NOT_CONFIGURED === $status ) {
			return [
				'slug'        => self::STATUS_NOT_CONFIGURED,
				'label'       => __( 'Provider Not Configured', 'wpcalibrate-user-content-migrator' ),
				'class'       => 'wpcalibrate-ucm-badge-neutral',
				'description' => __( 'Remote license activation requires WPCalibrate licensing API configuration. The plugin remains fully functional locally for administrators.', 'wpcalibrate-user-content-migrator' ),
			];
		}

		if ( self::STATUS_ACTIVE === $status ) {
			return [
				'slug'        => self::STATUS_ACTIVE,
				'label'       => __( 'Active', 'wpcalibrate-user-content-migrator' ),
				'class'       => 'wpcalibrate-ucm-badge-success',
				'description' => __( 'License is active and valid.', 'wpcalibrate-user-content-migrator' ),
			];
		}

		return [
			'slug'        => $status,
			'label'       => ucfirst( $status ),
			'class'       => 'wpcalibrate-ucm-badge-warning',
			'description' => __( 'License requires attention.', 'wpcalibrate-user-content-migrator' ),
		];
	}
}
