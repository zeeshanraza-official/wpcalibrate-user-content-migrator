<?php
/**
 * Main Plugin Container
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator;

use WPCalibrate\UserContentMigrator\Admin\AdminMenu;
use WPCalibrate\UserContentMigrator\Api\AjaxController;
use WPCalibrate\UserContentMigrator\Api\RestController;
use WPCalibrate\UserContentMigrator\Integrations\ActionSchedulerIntegration;
use WPCalibrate\UserContentMigrator\Integrations\WooCommerceIntegration;
use WPCalibrate\UserContentMigrator\Services\CleanupService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin orchestrator class.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Initialize plugin hooks and subsystems.
	 *
	 * @return void
	 */
	private function setup_hooks(): void {
		// Localization.
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// Check for database upgrades on admin_init.
		add_action( 'admin_init', [ Upgrader::class, 'check_upgrade' ] );

		// Admin menus and assets.
		if ( is_admin() ) {
			AdminMenu::init();
			AjaxController::register_actions();
		}

		// REST API.
		add_action(
			'rest_api_init',
			static function (): void {
				$controller = new RestController();
				$controller->register_routes();
			}
		);

		// Integrations.
		WooCommerceIntegration::init();
		ActionSchedulerIntegration::init();

		// Daily cleanup cron.
		add_action( 'wpcalibrate_ucm_daily_retention_cleanup', [ CleanupService::class, 'run_retention_cleanup' ] );

		// WordPress Core Privacy Tools integration.
		add_filter( 'wp_privacy_personal_data_exporters', [ $this, 'register_privacy_exporter' ] );
	}

	/**
	 * Load plugin textdomain for translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wpcalibrate-user-content-migrator',
			false,
			dirname( plugin_basename( WPCALIBRATE_UCM_FILE ) ) . '/languages'
		);
	}

	/**
	 * Register personal data exporter with WordPress privacy tools.
	 *
	 * @param array<string, mixed> $exporters Registered exporters.
	 * @return array<string, mixed>
	 */
	public function register_privacy_exporter( array $exporters ): array {
		$exporters['wpcalibrate-user-content-migrator'] = [
			'exporter_friendly_name' => __( 'WPCalibrate Migration Logs', 'wpcalibrate-user-content-migrator' ),
			'callback'               => [ $this, 'export_privacy_data' ],
		];

		return $exporters;
	}

	/**
	 * Privacy data exporter callback.
	 *
	 * @param string $email_address Email address being exported.
	 * @param int    $page Page number.
	 * @return array{data: array<array<string, mixed>>, done: bool}
	 */
	public function export_privacy_data( string $email_address, int $page = 1 ): array {
		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return [ 'data' => [], 'done' => true ];
		}

		global $wpdb;
		$table = Database\Schema::get_table_name( 'migrations' );
		$u_id  = (int) $user->ID;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, uuid, status, total_migrated, created_at 
				FROM {$table} 
				WHERE source_user_id = %d OR dest_user_id = %d 
				ORDER BY id DESC LIMIT 50",
				$u_id,
				$u_id
			)
		);

		$export_items = [];
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$export_items[] = [
					'group_id'    => 'wpcalibrate_ucm_migrations',
					'group_label' => __( 'Content Migration Records', 'wpcalibrate-user-content-migrator' ),
					'item_id'     => 'migration-' . $row->id,
					'data'        => [
						[
							'name'  => __( 'Migration ID', 'wpcalibrate-user-content-migrator' ),
							'value' => $row->uuid,
						],
						[
							'name'  => __( 'Status', 'wpcalibrate-user-content-migrator' ),
							'value' => $row->status,
						],
						[
							'name'  => __( 'Items Migrated', 'wpcalibrate-user-content-migrator' ),
							'value' => (string) $row->total_migrated,
						],
						[
							'name'  => __( 'Date', 'wpcalibrate-user-content-migrator' ),
							'value' => $row->created_at,
						],
					],
				];
			}
		}

		return [
			'data' => $export_items,
			'done' => true,
		];
	}
}
