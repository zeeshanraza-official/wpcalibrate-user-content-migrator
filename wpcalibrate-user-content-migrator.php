<?php
/**
 * Plugin Name:       WPCalibrate User Content Migrator
 * Plugin URI:        https://marketplace.wpcalibrate.com/
 * Description:       Safely transfer ownership of WordPress posts, pages, custom post types, WooCommerce products, and media attachments between existing users with exact rollback support.
 * Version:           1.0.1
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-user-content-migrator
 * Domain Path:       /languages
 *
 * @package           WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'WPCALIBRATE_UCM_VERSION', '1.0.1' );
define( 'WPCALIBRATE_UCM_FILE', __FILE__ );
define( 'WPCALIBRATE_UCM_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPCALIBRATE_UCM_URL', plugin_dir_url( __FILE__ ) );

// Require autoloader.
require_once WPCALIBRATE_UCM_DIR . 'includes/class-autoloader.php';
Autoloader::register( WPCALIBRATE_UCM_DIR . 'includes' );

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Deactivator::class, 'deactivate' ] );

/**
 * Bootstrap plugin on plugins_loaded.
 *
 * @return void
 */
function wpcalibrate_ucm_init(): void {
	Plugin::get_instance();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\wpcalibrate_ucm_init' );
