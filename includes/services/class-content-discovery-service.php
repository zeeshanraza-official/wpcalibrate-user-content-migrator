<?php
/**
 * Content Discovery Service
 *
 * @package WPCalibrate\UserContentMigrator\Services
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles read-only discovery, counting, filtering, and pagination of source user content.
 */
class ContentDiscoveryService {

	/**
	 * Get summary counts of authored content for a user.
	 *
	 * @param int $user_id Source user ID.
	 * @return array<string, int>
	 */
	public static function get_user_authored_counts( int $user_id ): array {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return [];
		}

		$eligible_types = array_keys( ContentEligibilityService::get_eligible_post_types() );
		if ( empty( $eligible_types ) ) {
			return [];
		}

		$placeholders = implode( ', ', array_fill( 0, count( $eligible_types ), '%s' ) );
		$params       = array_merge( [ $user_id ], $eligible_types );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_type, COUNT(*) as count 
				FROM {$wpdb->posts} 
				WHERE post_author = %d 
				AND post_type IN ({$placeholders}) 
				GROUP BY post_type",
				$params
			)
		);

		$counts = [];
		foreach ( $eligible_types as $pt ) {
			$counts[ $pt ] = 0;
		}

		if ( $results ) {
			foreach ( $results as $row ) {
				$counts[ $row->post_type ] = (int) $row->count;
			}
		}

		// Also count authored comments.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$comment_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->comments} WHERE user_id = %d",
				$user_id
			)
		);
		$counts['comment'] = $comment_count;

		return $counts;
	}

	/**
	 * Scan content based on configured criteria (read-only preview).
	 *
	 * @param array<string, mixed> $criteria Filter criteria.
	 * @return array{
	 *     total: int,
	 *     post_types: array<string, int>,
	 *     statuses: array<string, int>,
	 *     media_count: int,
	 *     comment_count: int,
	 *     warnings: array<string>
	 * }
	 */
	public static function scan_content( array $criteria ): array {
		global $wpdb;

		$source_user_id = (int) ( $criteria['source_user_id'] ?? 0 );
		$warnings       = [];

		if ( $source_user_id <= 0 ) {
			return [
				'total'         => 0,
				'post_types'    => [],
				'statuses'      => [],
				'media_count'   => 0,
				'comment_count' => 0,
				'warnings'      => [ __( 'Invalid source user selected.', 'wpcalibrate-user-content-migrator' ) ],
			];
		}

		$eligible_types_map = ContentEligibilityService::get_eligible_post_types();
		$eligible_types     = array_keys( $eligible_types_map );

		$selected_post_types = ! empty( $criteria['post_types'] ) ? (array) $criteria['post_types'] : $eligible_types;
		$selected_post_types = array_values( array_intersect( $selected_post_types, $eligible_types ) );

		$include_media    = ! empty( $criteria['include_media'] );
		$include_comments = ! empty( $criteria['include_comments'] ) && ContentEligibilityService::is_comments_feature_enabled();

		if ( $include_media && ! in_array( 'attachment', $selected_post_types, true ) ) {
			$selected_post_types[] = 'attachment';
		} elseif ( ! $include_media ) {
			$selected_post_types = array_diff( $selected_post_types, [ 'attachment' ] );
		}

		$selected_statuses = ! empty( $criteria['statuses'] ) ? (array) $criteria['statuses'] : array_keys( ContentEligibilityService::get_supported_statuses() );
		$selected_statuses = array_map( 'sanitize_key', $selected_statuses );

		// Attachments usually have status 'inherit'.
		if ( in_array( 'attachment', $selected_post_types, true ) && ! in_array( 'inherit', $selected_statuses, true ) ) {
			$selected_statuses[] = 'inherit';
		}

		$date_from = ! empty( $criteria['date_from'] ) ? sanitize_text_field( $criteria['date_from'] ) : '';
		$date_to   = ! empty( $criteria['date_to'] ) ? sanitize_text_field( $criteria['date_to'] ) : '';

		$individual_ids = ! empty( $criteria['individual_ids'] ) ? array_map( 'intval', (array) $criteria['individual_ids'] ) : [];

		$post_type_counts = [];
		$status_counts    = [];
		$total_posts      = 0;
		$media_count      = 0;

		if ( ! empty( $selected_post_types ) && ! empty( $selected_statuses ) ) {
			$where  = [ 'post_author = %d' ];
			$params = [ $source_user_id ];

			// Post types.
			$pt_placeholders = implode( ', ', array_fill( 0, count( $selected_post_types ), '%s' ) );
			$where[]         = "post_type IN ({$pt_placeholders})";
			$params          = array_merge( $params, $selected_post_types );

			// Statuses.
			$st_placeholders = implode( ', ', array_fill( 0, count( $selected_statuses ), '%s' ) );
			$where[]         = "post_status IN ({$st_placeholders})";
			$params          = array_merge( $params, $selected_statuses );

			// Date filters.
			if ( ! empty( $date_from ) ) {
				$where[]  = 'post_date >= %s';
				$params[] = $date_from . ' 00:00:00';
			}
			if ( ! empty( $date_to ) ) {
				$where[]  = 'post_date <= %s';
				$params[] = $date_to . ' 23:59:59';
			}

			// Individual IDs if selected.
			if ( ! empty( $individual_ids ) ) {
				$id_placeholders = implode( ', ', array_fill( 0, count( $individual_ids ), '%d' ) );
				$where[]         = "ID IN ({$id_placeholders})";
				$params          = array_merge( $params, $individual_ids );
			}

			$where_sql = implode( ' AND ', $where );

			// Group by post_type.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$pt_results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_type, COUNT(*) as count FROM {$wpdb->posts} WHERE {$where_sql} GROUP BY post_type",
					$params
				)
			);

			if ( $pt_results ) {
				foreach ( $pt_results as $row ) {
					$c = (int) $row->count;
					$post_type_counts[ $row->post_type ] = $c;
					$total_posts                        += $c;
					if ( 'attachment' === $row->post_type ) {
						$media_count = $c;
					}
				}
			}

			// Group by status.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$st_results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_status, COUNT(*) as count FROM {$wpdb->posts} WHERE {$where_sql} GROUP BY post_status",
					$params
				)
			);

			if ( $st_results ) {
				foreach ( $st_results as $row ) {
					$status_counts[ $row->post_status ] = (int) $row->count;
				}
			}
		}

		// Comment scan if enabled.
		$comment_count = 0;
		if ( $include_comments && empty( $individual_ids ) ) {
			$comment_where  = [ 'user_id = %d' ];
			$comment_params = [ $source_user_id ];

			if ( ! empty( $date_from ) ) {
				$comment_where[]  = 'comment_date >= %s';
				$comment_params[] = $date_from . ' 00:00:00';
			}
			if ( ! empty( $date_to ) ) {
				$comment_where[]  = 'comment_date <= %s';
				$comment_params[] = $date_to . ' 23:59:59';
			}

			$comment_where_sql = implode( ' AND ', $comment_where );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$comment_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->comments} WHERE {$comment_where_sql}",
					$comment_params
				)
			);
		}

		$total = $total_posts + $comment_count;

		if ( 0 === $total ) {
			$warnings[] = __( 'No matching content found for the selected criteria.', 'wpcalibrate-user-content-migrator' );
		}

		return [
			'total'         => $total,
			'post_types'    => $post_type_counts,
			'statuses'      => $status_counts,
			'media_count'   => $media_count,
			'comment_count' => $comment_count,
			'warnings'      => $warnings,
		];
	}

	/**
	 * Paginated query for individual content selection table.
	 *
	 * @param array<string, mixed> $criteria Filter criteria.
	 * @param int                  $page Page number.
	 * @param int                  $per_page Items per page.
	 * @param string               $search Search keyword.
	 * @return array{items: array<array<string, mixed>>, total: int}
	 */
	public static function query_records( array $criteria, int $page = 1, int $per_page = 20, string $search = '' ): array {
		global $wpdb;

		$source_user_id = (int) ( $criteria['source_user_id'] ?? 0 );
		if ( $source_user_id <= 0 ) {
			return [ 'items' => [], 'total' => 0 ];
		}

		$eligible_types = array_keys( ContentEligibilityService::get_eligible_post_types() );
		$selected_types = ! empty( $criteria['post_types'] ) ? (array) $criteria['post_types'] : $eligible_types;
		$selected_types = array_values( array_intersect( $selected_types, $eligible_types ) );

		if ( ! empty( $criteria['include_media'] ) && ! in_array( 'attachment', $selected_types, true ) ) {
			$selected_types[] = 'attachment';
		}

		$selected_statuses = ! empty( $criteria['statuses'] ) ? (array) $criteria['statuses'] : array_keys( ContentEligibilityService::get_supported_statuses() );
		$selected_statuses = array_map( 'sanitize_key', $selected_statuses );

		if ( in_array( 'attachment', $selected_types, true ) && ! in_array( 'inherit', $selected_statuses, true ) ) {
			$selected_statuses[] = 'inherit';
		}

		if ( empty( $selected_types ) || empty( $selected_statuses ) ) {
			return [ 'items' => [], 'total' => 0 ];
		}

		$where  = [ 'post_author = %d' ];
		$params = [ $source_user_id ];

		$pt_placeholders = implode( ', ', array_fill( 0, count( $selected_types ), '%s' ) );
		$where[]         = "post_type IN ({$pt_placeholders})";
		$params          = array_merge( $params, $selected_types );

		$st_placeholders = implode( ', ', array_fill( 0, count( $selected_statuses ), '%s' ) );
		$where[]         = "post_status IN ({$st_placeholders})";
		$params          = array_merge( $params, $selected_statuses );

		if ( ! empty( $criteria['date_from'] ) ) {
			$where[]  = 'post_date >= %s';
			$params[] = sanitize_text_field( $criteria['date_from'] ) . ' 00:00:00';
		}
		if ( ! empty( $criteria['date_to'] ) ) {
			$where[]  = 'post_date <= %s';
			$params[] = sanitize_text_field( $criteria['date_to'] ) . ' 23:59:59';
		}

		if ( '' !== trim( $search ) ) {
			$where[]  = 'post_title LIKE %s';
			$params[] = '%' . $wpdb->esc_like( trim( $search ) ) . '%';
		}

		$where_sql = implode( ' AND ', $where );
		$per_page  = max( 1, min( 100, $per_page ) );
		$page      = max( 1, $page );
		$offset    = ( $page - 1 ) * $per_page;

		// Count.
		$count_sql = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$where_sql}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Items.
		$items_sql    = "SELECT ID, post_title, post_type, post_status, post_date FROM {$wpdb->posts} WHERE {$where_sql} ORDER BY ID DESC LIMIT %d OFFSET %d";
		$items_params = array_merge( $params, [ $per_page, $offset ] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare( $items_sql, $items_params ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		$items = [];
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$title = ! empty( $row->post_title ) ? $row->post_title : sprintf( __( '(no title: #%d)', 'wpcalibrate-user-content-migrator' ), (int) $row->ID );
				$items[] = [
					'id'     => (int) $row->ID,
					'title'  => esc_html( $title ),
					'type'   => esc_html( $row->post_type ),
					'status' => esc_html( $row->post_status ),
					'date'   => esc_html( $row->post_date ),
				];
			}
		}

		return [
			'items' => $items,
			'total' => $total,
		];
	}

	/**
	 * Collect matching item definitions to populate the migration ledger.
	 *
	 * @param array<string, mixed> $criteria Filter criteria.
	 * @param int                  $dest_user_id Destination user ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_ledger_definitions( array $criteria, int $dest_user_id ): array {
		global $wpdb;

		$source_user_id = (int) ( $criteria['source_user_id'] ?? 0 );
		if ( $source_user_id <= 0 || $dest_user_id <= 0 ) {
			return [];
		}

		$eligible_types = array_keys( ContentEligibilityService::get_eligible_post_types() );
		$selected_types = ! empty( $criteria['post_types'] ) ? (array) $criteria['post_types'] : $eligible_types;
		$selected_types = array_values( array_intersect( $selected_types, $eligible_types ) );

		if ( ! empty( $criteria['include_media'] ) && ! in_array( 'attachment', $selected_types, true ) ) {
			$selected_types[] = 'attachment';
		} elseif ( empty( $criteria['include_media'] ) ) {
			$selected_types = array_diff( $selected_types, [ 'attachment' ] );
		}

		$selected_statuses = ! empty( $criteria['statuses'] ) ? (array) $criteria['statuses'] : array_keys( ContentEligibilityService::get_supported_statuses() );
		$selected_statuses = array_map( 'sanitize_key', $selected_statuses );

		if ( in_array( 'attachment', $selected_types, true ) && ! in_array( 'inherit', $selected_statuses, true ) ) {
			$selected_statuses[] = 'inherit';
		}

		$individual_ids = ! empty( $criteria['individual_ids'] ) ? array_map( 'intval', (array) $criteria['individual_ids'] ) : [];

		$ledger = [];

		if ( ! empty( $selected_types ) && ! empty( $selected_statuses ) ) {
			$where  = [ 'post_author = %d' ];
			$params = [ $source_user_id ];

			$pt_placeholders = implode( ', ', array_fill( 0, count( $selected_types ), '%s' ) );
			$where[]         = "post_type IN ({$pt_placeholders})";
			$params          = array_merge( $params, $selected_types );

			$st_placeholders = implode( ', ', array_fill( 0, count( $selected_statuses ), '%s' ) );
			$where[]         = "post_status IN ({$st_placeholders})";
			$params          = array_merge( $params, $selected_statuses );

			if ( ! empty( $criteria['date_from'] ) ) {
				$where[]  = 'post_date >= %s';
				$params[] = sanitize_text_field( $criteria['date_from'] ) . ' 00:00:00';
			}
			if ( ! empty( $criteria['date_to'] ) ) {
				$where[]  = 'post_date <= %s';
				$params[] = sanitize_text_field( $criteria['date_to'] ) . ' 23:59:59';
			}

			if ( ! empty( $individual_ids ) ) {
				$id_placeholders = implode( ', ', array_fill( 0, count( $individual_ids ), '%d' ) );
				$where[]         = "ID IN ({$id_placeholders})";
				$params          = array_merge( $params, $individual_ids );
			}

			$where_sql = implode( ' AND ', $where );

			// Query IDs, post_type, post_status.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_type, post_status FROM {$wpdb->posts} WHERE {$where_sql} ORDER BY ID ASC",
					$params
				)
			);

			if ( $posts ) {
				foreach ( $posts as $post ) {
					$ledger[] = [
						'object_type'          => $post->post_type,
						'object_id'            => (int) $post->ID,
						'original_owner_id'    => $source_user_id,
						'new_owner_id'         => $dest_user_id,
						'migration_status'     => 'pending',
						'rollback_status'      => 'none',
						'original_post_status' => $post->post_status,
					];
				}
			}
		}

		// Comments ledger.
		$include_comments = ! empty( $criteria['include_comments'] ) && ContentEligibilityService::is_comments_feature_enabled();
		if ( $include_comments && empty( $individual_ids ) ) {
			$comment_where  = [ 'user_id = %d' ];
			$comment_params = [ $source_user_id ];

			if ( ! empty( $criteria['date_from'] ) ) {
				$comment_where[]  = 'comment_date >= %s';
				$comment_params[] = sanitize_text_field( $criteria['date_from'] ) . ' 00:00:00';
			}
			if ( ! empty( $criteria['date_to'] ) ) {
				$comment_where[]  = 'comment_date <= %s';
				$comment_params[] = sanitize_text_field( $criteria['date_to'] ) . ' 23:59:59';
			}

			$comment_where_sql = implode( ' AND ', $comment_where );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$comments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT comment_ID, comment_approved FROM {$wpdb->comments} WHERE {$comment_where_sql} ORDER BY comment_ID ASC",
					$comment_params
				)
			);

			if ( $comments ) {
				foreach ( $comments as $comment ) {
					$ledger[] = [
						'object_type'          => 'comment',
						'object_id'            => (int) $comment->comment_ID,
						'original_owner_id'    => $source_user_id,
						'new_owner_id'         => $dest_user_id,
						'migration_status'     => 'pending',
						'rollback_status'      => 'none',
						'original_post_status' => (string) $comment->comment_approved,
					];
				}
			}
		}

		return $ledger;
	}
}
