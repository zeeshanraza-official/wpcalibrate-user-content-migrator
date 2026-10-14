<?php
/**
 * Settings Page View Controller
 *
 * @package WPCalibrate\UserContentMigrator\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Admin;

use WPCalibrate\UserContentMigrator\Api\AjaxController;
use WPCalibrate\UserContentMigrator\Services\ContentEligibilityService;
use WPCalibrate\UserContentMigrator\Services\SettingsManager;
use WPCalibrate\UserContentMigrator\Support\LicenseManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders plugin settings with isolated tabs: General, Processing, History, License, Support.
 */
class SettingsPage {

	/**
	 * Nonce action for saving settings.
	 */
	public const SAVE_NONCE_ACTION = 'wpcalibrate_ucm_save_settings_nonce';

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render(): void {
		$cap = AjaxController::get_capability();
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'You do not have permission to access these settings.', 'wpcalibrate-user-content-migrator' ) );
		}

		$notice = '';

		// Handle Settings Form Submission.
		if (
			'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' )
			&& isset( $_POST['wpcalibrate_ucm_save_settings'] )
		) {
			check_admin_referer( self::SAVE_NONCE_ACTION, 'wpcalibrate_ucm_settings_nonce' );

			$input = [
				'default_statuses'         => isset( $_POST['default_statuses'] ) ? array_map( 'sanitize_key', (array) $_POST['default_statuses'] ) : [],
				'allow_comments_migration' => ! empty( $_POST['allow_comments_migration'] ),
				'default_comment_transfer' => ! empty( $_POST['default_comment_transfer'] ),
				'retention_days'           => isset( $_POST['retention_days'] ) ? (int) $_POST['retention_days'] : 90,
				'batch_size'               => isset( $_POST['batch_size'] ) ? (int) $_POST['batch_size'] : 25,
				'lock_timeout_seconds'     => isset( $_POST['lock_timeout_seconds'] ) ? (int) $_POST['lock_timeout_seconds'] : 60,
				'uninstall_delete_data'    => ! empty( $_POST['uninstall_delete_data'] ),
				'license_key'              => isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '',
			];

			SettingsManager::update_all( $input );
			$notice = __( 'Settings saved successfully.', 'wpcalibrate-user-content-migrator' );
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		$valid_tabs  = [ 'general', 'processing', 'history', 'license', 'support' ];
		if ( ! in_array( $current_tab, $valid_tabs, true ) ) {
			$current_tab = 'general';
		}

		$settings           = SettingsManager::get_all();
		$supported_statuses = ContentEligibilityService::get_supported_statuses();
		$masked_license_key = LicenseManager::get_masked_key();
		$license_info       = LicenseManager::get_status_info();

		$template_path = dirname( __DIR__, 2 ) . '/templates/admin-settings.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
