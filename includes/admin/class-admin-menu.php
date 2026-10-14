<?php
/**
 * Admin Menu Manager
 *
 * @package WPCalibrate\UserContentMigrator\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Admin;

use WPCalibrate\UserContentMigrator\Api\AjaxController;
use WPCalibrate\UserContentMigrator\Support\Branding;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles shared WPCalibrate parent menu and isolated plugin submenu registration.
 */
class AdminMenu {

	/**
	 * Top-level parent menu slug shared across WPCalibrate plugins.
	 */
	public const PARENT_SLUG = 'wpcalibrate';

	/**
	 * Overview and guide screen slug.
	 */
	public const OVERVIEW_SLUG = 'wpcalibrate-ucm-overview';

	/**
	 * Main migrator screen slug.
	 */
	public const MIGRATOR_SLUG = 'wpcalibrate-ucm';

	/**
	 * History screen slug.
	 */
	public const HISTORY_SLUG = 'wpcalibrate-ucm-history';

	/**
	 * Settings screen slug.
	 */
	public const SETTINGS_SLUG = 'wpcalibrate-ucm-settings';

	/**
	 * Registered screen hook suffixes for this plugin.
	 *
	 * @var array<string>
	 */
	private static array $screen_hooks = [];

	/**
	 * Initialize admin menu hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'register_menus' ], 10 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
		add_action( 'admin_head', [ self::class, 'render_admin_menu_icon_styles' ] );
	}

	/**
	 * Register shared parent menu and isolated submenus.
	 *
	 * @return void
	 */
	public static function register_menus(): void {
		global $admin_page_hooks;

		$cap = AjaxController::get_capability();

		// 1. Create or reuse top-level WPCalibrate menu.
		if ( ! isset( $admin_page_hooks[ self::PARENT_SLUG ] ) ) {
			add_menu_page(
				__( 'WPCalibrate', 'wpcalibrate-user-content-migrator' ),
				__( 'WPCalibrate', 'wpcalibrate-user-content-migrator' ),
				$cap,
				self::PARENT_SLUG,
				[ OverviewPage::class, 'render' ],
				Branding::get_icon_url( 'dark' ),
				58.2
			);

			// Register overview item under parent so parent item is cleanly labeled.
			add_submenu_page(
				self::PARENT_SLUG,
				__( 'WPCalibrate Overview', 'wpcalibrate-user-content-migrator' ),
				__( 'Overview', 'wpcalibrate-user-content-migrator' ),
				$cap,
				self::PARENT_SLUG,
				[ OverviewPage::class, 'render' ]
			);
		}

		// 2. Add User Content Migrator submenus.
		$overview_hook = add_submenu_page(
			self::PARENT_SLUG,
			__( 'UCM Overview & Guide', 'wpcalibrate-user-content-migrator' ),
			__( 'Migrator Overview', 'wpcalibrate-user-content-migrator' ),
			$cap,
			self::OVERVIEW_SLUG,
			[ OverviewPage::class, 'render' ]
		);

		$migrator_hook = add_submenu_page(
			self::PARENT_SLUG,
			__( 'User Content Migrator', 'wpcalibrate-user-content-migrator' ),
			__( 'Content Migrator', 'wpcalibrate-user-content-migrator' ),
			$cap,
			self::MIGRATOR_SLUG,
			[ MigratorPage::class, 'render' ]
		);

		$history_hook = add_submenu_page(
			self::PARENT_SLUG,
			__( 'UCM Migration History', 'wpcalibrate-user-content-migrator' ),
			__( 'Migration History', 'wpcalibrate-user-content-migrator' ),
			$cap,
			self::HISTORY_SLUG,
			[ HistoryPage::class, 'render' ]
		);

		$settings_hook = add_submenu_page(
			self::PARENT_SLUG,
			__( 'UCM Settings', 'wpcalibrate-user-content-migrator' ),
			__( 'Migrator Settings', 'wpcalibrate-user-content-migrator' ),
			$cap,
			self::SETTINGS_SLUG,
			[ SettingsPage::class, 'render' ]
		);

		if ( $overview_hook ) {
			self::$screen_hooks[] = $overview_hook;
		}
		if ( $migrator_hook ) {
			self::$screen_hooks[] = $migrator_hook;
		}
		if ( $history_hook ) {
			self::$screen_hooks[] = $history_hook;
		}
		if ( $settings_hook ) {
			self::$screen_hooks[] = $settings_hook;
		}
	}

	/**
	 * Check if current admin page belongs to this plugin.
	 *
	 * @param string $hook Current admin page hook.
	 * @return bool True if page belongs to this plugin.
	 */
	public static function is_plugin_page( string $hook ): bool {
		return in_array( $hook, self::$screen_hooks, true );
	}

	/**
	 * Inline CSS to ensure the top-level menu icon renders at 20x20 crisp in WP admin bar.
	 *
	 * @return void
	 */
	public static function render_admin_menu_icon_styles(): void {
		?>
		<style id="wpcalibrate-ucm-menu-icon-css">
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image img {
				width: 20px;
				height: 20px;
				padding: 7px 0 0 0;
				object-fit: contain;
				opacity: 0.68;
				filter: grayscale(100%) brightness(85%);
				transition: opacity 0.15s ease-in-out, filter 0.15s ease-in-out;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.current .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.wp-menu-open .wp-menu-image img {
				opacity: 1;
				filter: none;
			}
		</style>
		<?php
	}

	/**
	 * Enqueue assets strictly on this plugin's admin pages.
	 *
	 * @param string $hook Admin screen hook.
	 * @return void
	 */
	public static function enqueue_assets( string $hook ): void {
		if ( ! self::is_plugin_page( $hook ) ) {
			return;
		}

		$plugin_url = defined( 'WPCALIBRATE_UCM_URL' ) ? WPCALIBRATE_UCM_URL : plugin_dir_url( dirname( __DIR__ ) . '/wpcalibrate-user-content-migrator.php' );
		$version    = defined( 'WPCALIBRATE_UCM_VERSION' ) ? WPCALIBRATE_UCM_VERSION : '1.0.0';

		// CSS.
		wp_enqueue_style(
			'wpcalibrate-ucm-admin',
			$plugin_url . 'assets/css/admin.css',
			[],
			$version
		);

		if ( is_rtl() ) {
			wp_enqueue_style(
				'wpcalibrate-ucm-admin-rtl',
				$plugin_url . 'assets/css/admin-rtl.css',
				[ 'wpcalibrate-ucm-admin' ],
				$version
			);
		}

		// JS.
		wp_enqueue_script(
			'wpcalibrate-ucm-migrator',
			$plugin_url . 'assets/js/migrator.js',
			[ 'jquery' ],
			$version,
			true
		);

		// Localized parameters.
		wp_localize_script(
			'wpcalibrate-ucm-migrator',
			'wpcalibrateUcm',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'restUrl'   => esc_url_raw( rest_url( 'wpcalibrate-ucm/v1/' ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'nonce'     => wp_create_nonce( AjaxController::NONCE_ACTION ),
				'i18n'      => [
					'confirmStart'     => __( 'You are about to transfer ownership of the selected items. Content itself will not be deleted. This operation will be recorded so eligible changes can be rolled back. Do you want to proceed?', 'wpcalibrate-user-content-migrator' ),
					'confirmRollback'  => __( 'Are you sure you want to rollback this migration? Only items recorded in this migration ledger will have their original ownership restored. Any items modified after the migration will be protected and flagged as conflicts.', 'wpcalibrate-user-content-migrator' ),
					'confirmCleanup'   => __( 'Warning: Cleaning up migration history permanently removes exact item rollback ledgers for completed migrations. Future rollback for those migrations will no longer be possible. Continue?', 'wpcalibrate-user-content-migrator' ),
					'selectUsersError' => __( 'Please select both a source user and a destination user.', 'wpcalibrate-user-content-migrator' ),
					'sameUserError'    => __( 'Source and destination users must be different.', 'wpcalibrate-user-content-migrator' ),
					'scanning'         => __( 'Scanning content...', 'wpcalibrate-user-content-migrator' ),
					'migrating'        => __( 'Migrating ownership in batches...', 'wpcalibrate-user-content-migrator' ),
					'verifying'        => __( 'Verifying database ownership...', 'wpcalibrate-user-content-migrator' ),
					'completed'        => __( 'Migration successfully completed!', 'wpcalibrate-user-content-migrator' ),
					'rollingBack'      => __( 'Rolling back ownership...', 'wpcalibrate-user-content-migrator' ),
					'rollbackDone'     => __( 'Rollback completed!', 'wpcalibrate-user-content-migrator' ),
					'error'            => __( 'An error occurred during processing.', 'wpcalibrate-user-content-migrator' ),
				],
			]
		);
	}

	/**
	 * Fallback render for top-level WPCalibrate overview page if accessed directly.
	 *
	 * @return void
	 */
	public static function render_parent_overview(): void {
		$migrator_url = admin_url( 'admin.php?page=' . self::MIGRATOR_SLUG );
		?>
		<div class="wrap wpcalibrate-ucm-wrap">
			<?php Branding::render_header( __( 'WPCalibrate Suite', 'wpcalibrate-user-content-migrator' ), __( 'Professional utilities and administrative tools for WordPress.', 'wpcalibrate-user-content-migrator' ) ); ?>
			<div class="wpcalibrate-ucm-card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'User Content Migrator', 'wpcalibrate-user-content-migrator' ); ?></h2>
				<p><?php esc_html_e( 'Safely transfer ownership of WordPress posts, pages, custom post types, WooCommerce products, and media attachments between users with exact rollback support.', 'wpcalibrate-user-content-migrator' ); ?></p>
				<p>
					<a href="<?php echo esc_url( $migrator_url ); ?>" class="button button-primary">
						<?php esc_html_e( 'Open User Content Migrator', 'wpcalibrate-user-content-migrator' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
}
