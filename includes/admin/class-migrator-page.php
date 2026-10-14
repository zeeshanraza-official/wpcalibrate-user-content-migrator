<?php
/**
 * Migrator Page View Controller
 *
 * @package WPCalibrate\UserContentMigrator\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Admin;

use WPCalibrate\UserContentMigrator\Services\ContentEligibilityService;
use WPCalibrate\UserContentMigrator\Services\SettingsManager;
use WPCalibrate\UserContentMigrator\Support\Branding;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the main User Content Migrator wizard interface.
 */
class MigratorPage {

	/**
	 * Render the main migrator page.
	 *
	 * @return void
	 */
	public static function render(): void {
		$eligible_post_types = ContentEligibilityService::get_eligible_post_types();
		$supported_statuses  = ContentEligibilityService::get_supported_statuses();
		$default_statuses    = SettingsManager::get( 'default_statuses', [ 'publish', 'draft', 'pending', 'private', 'future' ] );
		$comments_allowed    = ContentEligibilityService::is_comments_feature_enabled();
		$default_comments    = SettingsManager::get_bool( 'default_comment_transfer', false );

		$template_path = dirname( __DIR__, 2 ) . '/templates/admin-migrator.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
