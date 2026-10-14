<?php
/**
 * Overview Page View Controller
 *
 * @package WPCalibrate\UserContentMigrator\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Admin;

use WPCalibrate\UserContentMigrator\Api\AjaxController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Overview & Documentation page for User Content Migrator.
 */
class OverviewPage {

	/**
	 * Render the overview page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( AjaxController::get_capability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpcalibrate-user-content-migrator' ) );
		}

		$page_title    = __( 'User Content Migrator — Overview & Guide', 'wpcalibrate-user-content-migrator' );
		$page_subtitle = __( 'Learn the purpose, transfer methods, safety guarantees, and step-by-step usage of the plugin.', 'wpcalibrate-user-content-migrator' );

		$template_path = dirname( __DIR__, 2 ) . '/templates/admin-overview.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
