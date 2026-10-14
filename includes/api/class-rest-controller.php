<?php
/**
 * REST API Controller
 *
 * @package WPCalibrate\UserContentMigrator\Api
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Api;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPCalibrate\UserContentMigrator\Database\ItemsTable;
use WPCalibrate\UserContentMigrator\Database\MigrationsTable;
use WPCalibrate\UserContentMigrator\Migration\BatchProcessor;
use WPCalibrate\UserContentMigrator\Rollback\RollbackService;
use WPCalibrate\UserContentMigrator\Services\ContentEligibilityService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for WPCalibrate User Content Migrator.
 */
class RestController extends WP_REST_Controller {

	/**
	 * Route namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wpcalibrate-ucm/v1';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/status',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_status' ],
					'permission_callback' => [ $this, 'check_admin_permission' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/migrations',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_migrations' ],
					'permission_callback' => [ $this, 'check_admin_permission' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/migrations/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_migration' ],
					'permission_callback' => [ $this, 'check_admin_permission' ],
					'args'                => [
						'id' => [
							'validate_callback' => static function ( $param ) {
								return is_numeric( $param ) && (int) $param > 0;
							},
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/migrations/(?P<id>\d+)/process',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'process_migration' ],
					'permission_callback' => [ $this, 'check_admin_permission' ],
					'args'                => [
						'id'         => [
							'validate_callback' => static function ( $param ) {
								return is_numeric( $param ) && (int) $param > 0;
							},
						],
						'lock_token' => [
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/migrations/(?P<id>\d+)/rollback',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'rollback_migration' ],
					'permission_callback' => [ $this, 'check_admin_permission' ],
					'args'                => [
						'id'         => [
							'validate_callback' => static function ( $param ) {
								return is_numeric( $param ) && (int) $param > 0;
							},
						],
						'lock_token' => [
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);
	}

	/**
	 * Verify admin capability for REST routes.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_admin_permission( WP_REST_Request $request ) {
		$cap = AjaxController::get_capability();
		if ( ! current_user_can( $cap ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access WPCalibrate Content Migrator API.', 'wpcalibrate-user-content-migrator' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get plugin status and eligible post types.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_status( WP_REST_Request $request ): WP_REST_Response {
		$eligible_types = ContentEligibilityService::get_eligible_post_types();
		$statuses       = ContentEligibilityService::get_supported_statuses();

		return rest_ensure_response(
			[
				'version'        => defined( 'WPCALIBRATE_UCM_VERSION' ) ? WPCALIBRATE_UCM_VERSION : '1.0.0',
				'eligible_types' => $eligible_types,
				'statuses'       => $statuses,
			]
		);
	}

	/**
	 * Get paginated migrations list.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_migrations( WP_REST_Request $request ): WP_REST_Response {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = max( 1, min( 100, (int) ( $request->get_param( 'per_page' ) ?: 20 ) ) );
		$status   = sanitize_key( (string) $request->get_param( 'status' ) );

		$data = MigrationsTable::query(
			[
				'page'     => $page,
				'per_page' => $per_page,
				'status'   => $status,
			]
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Get single migration record and its items.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_migration( WP_REST_Request $request ) {
		$id        = (int) $request->get_param( 'id' );
		$migration = MigrationsTable::get( $id );

		if ( ! $migration ) {
			return new WP_Error( 'not_found', __( 'Migration not found.', 'wpcalibrate-user-content-migrator' ), [ 'status' => 404 ] );
		}

		$items_page = max( 1, (int) ( $request->get_param( 'items_page' ) ?: 1 ) );
		$items_data = ItemsTable::query( $id, [ 'page' => $items_page, 'per_page' => 50 ] );

		return rest_ensure_response(
			[
				'migration' => $migration,
				'items'     => $items_data['items'],
				'total'     => $items_data['total'],
			]
		);
	}

	/**
	 * Process a batch via REST.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function process_migration( WP_REST_Request $request ) {
		$id         = (int) $request->get_param( 'id' );
		$lock_token = (string) $request->get_param( 'lock_token' );

		$result = BatchProcessor::process_batch( $id, $lock_token );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Process a rollback batch via REST.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rollback_migration( WP_REST_Request $request ) {
		$id         = (int) $request->get_param( 'id' );
		$lock_token = (string) $request->get_param( 'lock_token' );

		$result = RollbackService::process_batch( $id, $lock_token );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}
}
