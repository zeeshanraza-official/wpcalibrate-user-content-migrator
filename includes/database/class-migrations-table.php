<?php
/**
 * Migrations Table Repository
 *
 * @package WPCalibrate\UserContentMigrator\Database
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository for managing records in wpcalibrate_ucm_migrations.
 */
class MigrationsTable {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function get_table(): string {
		return Schema::get_table_name( 'migrations' );
	}

	/**
	 * Create a new migration job record.
	 *
	 * @param array<string, mixed> $data Migration attributes.
	 * @return int Inserted ID or 0 on failure.
	 */
	public static function create( array $data ): int {
		global $wpdb;

		$now = current_time( 'mysql', true );

		$defaults = [
			'uuid'                     => wp_generate_uuid4(),
			'source_user_id'           => 0,
			'dest_user_id'             => 0,
			'initiator_user_id'        => get_current_user_id(),
			'status'                   => 'draft',
			'include_comments'         => 0,
			'config_snapshot'          => '{}',
			'total_planned'            => 0,
			'total_migrated'           => 0,
			'total_skipped'            => 0,
			'total_failed'             => 0,
			'total_rolled_back'        => 0,
			'total_rollback_failed'    => 0,
			'total_rollback_conflicts' => 0,
			'verification_status'      => 'unverified',
			'verification_data'        => null,
			'errors_warnings'          => null,
			'lock_token'               => null,
			'lock_expires_at'          => null,
			'started_at'               => null,
			'completed_at'             => null,
			'rollback_started_at'      => null,
			'rollback_completed_at'    => null,
			'plugin_version'           => defined( 'WPCALIBRATE_UCM_VERSION' ) ? WPCALIBRATE_UCM_VERSION : '1.0.0',
			'created_at'               => $now,
			'updated_at'               => $now,
		];

		$row = wp_parse_args( $data, $defaults );

		if ( is_array( $row['config_snapshot'] ) ) {
			$row['config_snapshot'] = wp_json_encode( $row['config_snapshot'] );
		}
		if ( is_array( $row['verification_data'] ) ) {
			$row['verification_data'] = wp_json_encode( $row['verification_data'] );
		}
		if ( is_array( $row['errors_warnings'] ) ) {
			$row['errors_warnings'] = wp_json_encode( $row['errors_warnings'] );
		}

		$format = [
			'%s', // uuid
			'%d', // source_user_id
			'%d', // dest_user_id
			'%d', // initiator_user_id
			'%s', // status
			'%d', // include_comments
			'%s', // config_snapshot
			'%d', // total_planned
			'%d', // total_migrated
			'%d', // total_skipped
			'%d', // total_failed
			'%d', // total_rolled_back
			'%d', // total_rollback_failed
			'%d', // total_rollback_conflicts
			'%s', // verification_status
			'%s', // verification_data
			'%s', // errors_warnings
			'%s', // lock_token
			'%s', // lock_expires_at
			'%s', // started_at
			'%s', // completed_at
			'%s', // rollback_started_at
			'%s', // rollback_completed_at
			'%s', // plugin_version
			'%s', // created_at
			'%s', // updated_at
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( self::get_table(), $row, $format );

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Find migration by ID.
	 *
	 * @param int $id Migration ID.
	 * @return object|null Row object or null.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Find migration by UUID.
	 *
	 * @param string $uuid UUID string.
	 * @return object|null Row object or null.
	 */
	public static function get_by_uuid( string $uuid ): ?object {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE uuid = %s", $uuid ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $row ?: null;
	}

	/**
	 * Update migration record by ID.
	 *
	 * @param int                  $id Migration ID.
	 * @param array<string, mixed> $data Fields to update.
	 * @return bool True on success, false on failure.
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql', true );

		if ( isset( $data['config_snapshot'] ) && is_array( $data['config_snapshot'] ) ) {
			$data['config_snapshot'] = wp_json_encode( $data['config_snapshot'] );
		}
		if ( isset( $data['verification_data'] ) && is_array( $data['verification_data'] ) ) {
			$data['verification_data'] = wp_json_encode( $data['verification_data'] );
		}
		if ( isset( $data['errors_warnings'] ) && is_array( $data['errors_warnings'] ) ) {
			$data['errors_warnings'] = wp_json_encode( $data['errors_warnings'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			self::get_table(),
			$data,
			[ 'id' => $id ]
		);

		return false !== $result;
	}

	/**
	 * Query migrations with pagination and filters.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array{items: array<object>, total: int}
	 */
	public static function query( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'status'   => '',
			'search'   => '',
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'id',
			'order'    => 'DESC',
		];

		$args = wp_parse_args( $args, $defaults );

		$table        = self::get_table();
		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$params[]        = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$search_term     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_clauses[] = '(uuid LIKE %s OR source_user_id = %d OR dest_user_id = %d)';
			$params[]        = $search_term;
			$params[]        = is_numeric( $args['search'] ) ? (int) $args['search'] : 0;
			$params[]        = is_numeric( $args['search'] ) ? (int) $args['search'] : 0;
		}

		$where_sql = implode( ' AND ', $where_clauses );

		$allowed_orderby = [ 'id', 'created_at', 'status', 'total_planned', 'total_migrated' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'id';
		$order           = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		$per_page = max( 1, (int) $args['per_page'] );
		$page     = max( 1, (int) $args['page'] );
		$offset   = ( $page - 1 ) * $per_page;

		// Count query.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// Items query.
		$items_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$items_params = array_merge( $params, [ $per_page, $offset ] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$items = $wpdb->get_results(
			$wpdb->prepare( $items_sql, $items_params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return [
			'items' => $items ?: [],
			'total' => $total,
		];
	}

	/**
	 * Delete migration record and its items.
	 *
	 * @param int $id Migration ID.
	 * @return bool True on success.
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		// Delete associated items first.
		ItemsTable::delete_by_migration( $id );

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );

		return false !== $result;
	}

	/**
	 * Append an error or warning message to a migration's log.
	 *
	 * @param int    $id Migration ID.
	 * @param string $message Error or warning message.
	 * @param string $type Message level ('error', 'warning', 'info').
	 * @return void
	 */
	public static function append_log( int $id, string $message, string $type = 'error' ): void {
		$migration = self::get( $id );
		if ( ! $migration ) {
			return;
		}

		$logs = ! empty( $migration->errors_warnings ) ? json_decode( $migration->errors_warnings, true ) : [];
		if ( ! is_array( $logs ) ) {
			$logs = [];
		}

		$logs[] = [
			'time'    => current_time( 'mysql', true ),
			'type'    => $type,
			'message' => sanitize_text_field( $message ),
		];

		self::update( $id, [ 'errors_warnings' => $logs ] );
	}
}
