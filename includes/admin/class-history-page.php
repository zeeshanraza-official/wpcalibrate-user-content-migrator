<?php
/**
 * History Page View Controller
 *
 * @package WPCalibrate\UserContentMigrator\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Admin;

use WPCalibrate\UserContentMigrator\Database\MigrationsTable;
use WPCalibrate\UserContentMigrator\Services\LockManager;
use WPCalibrate\UserContentMigrator\Support\Branding;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Migration History ledger table and rollback interface.
 */
class HistoryPage {

	/**
	 * Render the history page.
	 *
	 * @return void
	 */
	public static function render(): void {
		$page     = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = 20;

		$query_data  = MigrationsTable::query(
			[
				'page'     => $page,
				'per_page' => $per_page,
				'status'   => $status,
			]
		);

		$migrations  = $query_data['items'];
		$total_items = $query_data['total'];
		$total_pages = (int) ceil( $total_items / $per_page );

		$template_path = dirname( __DIR__, 2 ) . '/templates/admin-history.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}
