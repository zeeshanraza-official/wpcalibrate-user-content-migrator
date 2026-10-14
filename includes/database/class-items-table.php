<?php
/**
 * Items Table Repository
 *
 * @package WPCalibrate\UserContentMigrator\Database
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository for managing records in wpcalibrate_ucm_items.
 */
class ItemsTable {

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function get_table(): string {
		return Schema::get_table_name( 'items' );
	}

	/**
	 * Insert multiple items in bulk.
	 *
	 * @param int                   $migration_id Migration ID.
	 * @param array<int, array<string, mixed>> $items Array of item definitions.
	 * @return int Number of inserted items.
	 */
	public static function insert_batch( int $migration_id, array $items ): int {
		global $wpdb;

		if ( empty( $items ) ) {
			return 0;
		}

		$table = self::get_table();
		$now   = current_time( 'mysql', true );

		$values       = [];
		$placeholders = [];

		foreach ( $items as $item ) {
			$placeholders[] = '(%d, %s, %d, %d, %d, %s, %s, %s, %s, %s)';
			$values[]       = $migration_id;
			$values[]       = sanitize_key( $item['object_type'] ?? 'post' );
			$values[]       = (int) ( $item['object_id'] ?? 0 );
			$values[]       = (int) ( $item['original_owner_id'] ?? 0 );
			$values[]       = (int) ( $item['new_owner_id'] ?? 0 );
			$values[]       = sanitize_key( $item['migration_status'] ?? 'pending' );
			$values[]       = sanitize_key( $item['rollback_status'] ?? 'none' );
			$values[]       = sanitize_text_field( $item['original_post_status'] ?? '' );
			$values[]       = null; // error_message
			$values[]       = $now;
		}

		$query = "INSERT INTO {$table} (
			migration_id,
			object_type,
			object_id,
			original_owner_id,
			new_owner_id,
			migration_status,
			rollback_status,
			original_post_status,
			error_message,
			created_at
		) VALUES " . implode( ', ', $placeholders );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->query(
			$wpdb->prepare( $query, $values ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		return false !== $result ? count( $items ) : 0;
	}

	/**
	 * Get pending migration items.
	 *
	 * @param int $migration_id Migration ID.
	 * @param int $limit Batch limit.
	 * @return array<object> Array of item objects.
	 */
	public static function get_pending_migration_items( int $migration_id, int $limit = 50 ): array {
		global $wpdb;

		$table = self::get_table();
		$limit = max( 1, $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE migration_id = %d AND migration_status = 'pending' ORDER BY id ASC LIMIT %d",
				$migration_id,
				$limit
			)
		);

		return $results ?: [];
	}

	/**
	 * Get items eligible for rollback.
	 *
	 * @param int $migration_id Migration ID.
	 * @param int $limit Batch limit.
	 * @return array<object> Array of item objects.
	 */
	public static function get_pending_rollback_items( int $migration_id, int $limit = 50 ): array {
		global $wpdb;

		$table = self::get_table();
		$limit = max( 1, $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE migration_id = %d AND migration_status = 'migrated' AND rollback_status IN ('none', 'pending') ORDER BY id ASC LIMIT %d",
				$migration_id,
				$limit
			)
		);

		return $results ?: [];
	}

	/**
	 * Mark an item as migrated.
	 *
	 * @param int $item_id Item row ID.
	 * @return bool True on success.
	 */
	public static function mark_migrated( int $item_id ): bool {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$table,
			[
				'migration_status' => 'migrated',
				'migrated_at'      => current_time( 'mysql', true ),
			],
			[ 'id' => $item_id ]
		);

		return false !== $result;
	}

	/**
	 * Mark an item as failed in migration.
	 *
	 * @param int    $item_id Item row ID.
	 * @param string $error Error message.
	 * @return bool True on success.
	 */
	public static function mark_migration_failed( int $item_id, string $error ): bool {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$table,
			[
				'migration_status' => 'failed',
				'error_message'    => sanitize_text_field( $error ),
			],
			[ 'id' => $item_id ]
		);

		return false !== $result;
	}

	/**
	 * Mark an item as skipped in migration.
	 *
	 * @param int    $item_id Item row ID.
	 * @param string $reason Reason skipped.
	 * @return bool True on success.
	 */
	public static function mark_migration_skipped( int $item_id, string $reason ): bool {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$table,
			[
				'migration_status' => 'skipped',
				'error_message'    => sanitize_text_field( $reason ),
			],
			[ 'id' => $item_id ]
		);

		return false !== $result;
	}

	/**
	 * Mark an item as rolled back.
	 *
	 * @param int $item_id Item row ID.
	 * @return bool True on success.
	 */
	public static function mark_rolled_back( int $item_id ): bool {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$table,
			[
				'rollback_status' => 'rolled_back',
				'rolled_back_at'  => current_time( 'mysql', true ),
			],
			[ 'id' => $item_id ]
		);

		return false !== $result;
	}

	/**
	 * Mark an item as conflicting during rollback (e.g. current owner modified).
	 *
	 * @param int    $item_id Item row ID.
	 * @param string $details Conflict explanation.
	 * @return bool True on success.
	 */
	public static function mark_rollback_conflict( int $item_id, string $details ): bool {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$table,
			[
				'rollback_status' => 'conflict',
				'error_message'   => sanitize_text_field( $details ),
			],
			[ 'id' => $item_id ]
		);

		return false !== $result;
	}

	/**
	 * Mark an item as failed in rollback.
	 *
	 * @param int    $item_id Item row ID.
	 * @param string $error Error message.
	 * @return bool True on success.
	 */
	public static function mark_rollback_failed( int $item_id, string $error ): bool {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->update(
			$table,
			[
				'rollback_status' => 'failed',
				'error_message'   => sanitize_text_field( $error ),
			],
			[ 'id' => $item_id ]
		);

		return false !== $result;
	}

	/**
	 * Count items by migration status for a migration.
	 *
	 * @param int $migration_id Migration ID.
	 * @return array<string, int> Status counts.
	 */
	public static function get_status_counts( int $migration_id ): array {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT migration_status, COUNT(*) as count FROM {$table} WHERE migration_id = %d GROUP BY migration_status",
				$migration_id
			)
		);

		$counts = [
			'pending'  => 0,
			'migrated' => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'conflict' => 0,
		];

		if ( $rows ) {
			foreach ( $rows as $row ) {
				$counts[ $row->migration_status ] = (int) $row->count;
			}
		}

		return $counts;
	}

	/**
	 * Count items by rollback status for a migration.
	 *
	 * @param int $migration_id Migration ID.
	 * @return array<string, int> Status counts.
	 */
	public static function get_rollback_status_counts( int $migration_id ): array {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT rollback_status, COUNT(*) as count FROM {$table} WHERE migration_id = %d GROUP BY rollback_status",
				$migration_id
			)
		);

		$counts = [
			'none'        => 0,
			'pending'     => 0,
			'rolled_back' => 0,
			'conflict'    => 0,
			'failed'      => 0,
			'skipped'     => 0,
		];

		if ( $rows ) {
			foreach ( $rows as $row ) {
				$counts[ $row->rollback_status ] = (int) $row->count;
			}
		}

		return $counts;
	}

	/**
	 * Query items with pagination and filters (for history item ledger view).
	 *
	 * @param int                  $migration_id Migration ID.
	 * @param array<string, mixed> $args Query arguments.
	 * @return array{items: array<object>, total: int}
	 */
	public static function query( int $migration_id, array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'status'          => '',
			'rollback_status' => '',
			'object_type'     => '',
			'per_page'        => 20,
			'page'            => 1,
		];

		$args = wp_parse_args( $args, $defaults );

		$table         = self::get_table();
		$where_clauses = [ 'migration_id = %d' ];
		$params        = [ $migration_id ];

		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'migration_status = %s';
			$params[]        = $args['status'];
		}

		if ( ! empty( $args['rollback_status'] ) ) {
			$where_clauses[] = 'rollback_status = %s';
			$params[]        = $args['rollback_status'];
		}

		if ( ! empty( $args['object_type'] ) ) {
			$where_clauses[] = 'object_type = %s';
			$params[]        = $args['object_type'];
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$per_page  = max( 1, (int) $args['per_page'] );
		$page      = max( 1, (int) $args['page'] );
		$offset    = ( $page - 1 ) * $per_page;

		// Count.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Items.
		$items_sql    = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id ASC LIMIT %d OFFSET %d";
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
	 * Delete all items for a given migration ID.
	 *
	 * @param int $migration_id Migration ID.
	 * @return bool True on success.
	 */
	public static function delete_by_migration( int $migration_id ): bool {
		global $wpdb;

		$table = self::get_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->delete( $table, [ 'migration_id' => $migration_id ], [ '%d' ] );

		return false !== $result;
	}
}
