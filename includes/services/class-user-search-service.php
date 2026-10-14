<?php
/**
 * User Search Service
 *
 * @package WPCalibrate\UserContentMigrator\Services
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Services;

use WP_Error;
use WP_User;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides scalable search and validation for source and destination users.
 */
class UserSearchService {

	/**
	 * Search users with pagination for AJAX dropdown.
	 *
	 * @param string $term Search keyword (username, display name, email).
	 * @param int    $page Page number.
	 * @param int    $per_page Results per page.
	 * @param int    $exclude_user_id Optional user ID to exclude (e.g. source user when picking destination).
	 * @return array{users: array<array<string, mixed>>, total: int}
	 */
	public static function search_users( string $term = '', int $page = 1, int $per_page = 20, int $exclude_user_id = 0 ): array {
		$term     = trim( $term );
		$per_page = max( 1, min( 50, $per_page ) );
		$page     = max( 1, $page );
		$offset   = ( $page - 1 ) * $per_page;

		$query_args = [
			'number'      => $per_page,
			'offset'      => $offset,
			'count_total' => true,
			'orderby'     => 'display_name',
			'order'       => 'ASC',
			'fields'      => [ 'ID', 'user_login', 'display_name', 'user_email' ],
		];

		if ( $exclude_user_id > 0 ) {
			$query_args['exclude'] = [ $exclude_user_id ];
		}

		if ( '' !== $term ) {
			$query_args['search']         = '*' . $term . '*';
			$query_args['search_columns'] = [ 'user_login', 'user_nicename', 'display_name', 'user_email' ];
		}

		/**
		 * Filter user query arguments.
		 *
		 * @param array<string, mixed> $query_args WP_User_Query arguments.
		 */
		$query_args = apply_filters( 'wpcalibrate_ucm_user_search_args', $query_args );

		$user_query = new WP_User_Query( $query_args );
		$results    = $user_query->get_results();
		$total      = (int) $user_query->get_total();

		$users = [];
		foreach ( $results as $user ) {
			$user_id = (int) $user->ID;
			$users[] = [
				'id'           => $user_id,
				'display_name' => esc_html( $user->display_name ),
				'user_login'   => esc_html( $user->user_login ),
				'avatar'       => get_avatar_url( $user_id, [ 'size' => 64 ] ),
			];
		}

		return [
			'users' => $users,
			'total' => $total,
		];
	}

	/**
	 * Get user summary details including authored counts.
	 *
	 * @param int $user_id User ID.
	 * @return array<string, mixed>|null User details or null if user does not exist.
	 */
	public static function get_user_summary( int $user_id ): ?array {
		if ( $user_id <= 0 ) {
			return null;
		}

		$user = get_userdata( $user_id );
		if ( ! ( $user instanceof WP_User ) ) {
			return null;
		}

		$counts = ContentDiscoveryService::get_user_authored_counts( $user_id );

		return [
			'id'           => (int) $user->ID,
			'display_name' => esc_html( $user->display_name ),
			'user_login'   => esc_html( $user->user_login ),
			'avatar'       => get_avatar_url( (int) $user->ID, [ 'size' => 64 ] ),
			'counts'       => $counts,
		];
	}

	/**
	 * Validate source and destination users.
	 *
	 * @param int $source_user_id Source user ID.
	 * @param int $dest_user_id Destination user ID.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function validate_users( int $source_user_id, int $dest_user_id ) {
		if ( $source_user_id <= 0 ) {
			return new WP_Error(
				'invalid_source_user',
				__( 'Please select a valid source user.', 'wpcalibrate-user-content-migrator' )
			);
		}

		if ( $dest_user_id <= 0 ) {
			return new WP_Error(
				'invalid_dest_user',
				__( 'Please select a valid destination user.', 'wpcalibrate-user-content-migrator' )
			);
		}

		if ( $source_user_id === $dest_user_id ) {
			return new WP_Error(
				'identical_users',
				__( 'Source and destination users cannot be the same user.', 'wpcalibrate-user-content-migrator' )
			);
		}

		$source_user = get_userdata( $source_user_id );
		if ( ! ( $source_user instanceof WP_User ) ) {
			return new WP_Error(
				'source_user_not_found',
				__( 'The selected source user no longer exists.', 'wpcalibrate-user-content-migrator' )
			);
		}

		$dest_user = get_userdata( $dest_user_id );
		if ( ! ( $dest_user instanceof WP_User ) ) {
			return new WP_Error(
				'dest_user_not_found',
				__( 'The selected destination user no longer exists.', 'wpcalibrate-user-content-migrator' )
			);
		}

		return true;
	}
}
