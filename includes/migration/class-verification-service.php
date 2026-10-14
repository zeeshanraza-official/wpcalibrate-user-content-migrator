<?php
/**
 * Verification Service
 *
 * @package WPCalibrate\UserContentMigrator\Migration
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Migration;

use WP_Comment;
use WP_Post;
use WPCalibrate\UserContentMigrator\Database\MigrationsTable;
use WPCalibrate\UserContentMigrator\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifies post-migration and post-rollback database integrity and ownership truth.
 */
class VerificationService {

	/**
	 * Verify an executed migration against live database ownership.
	 *
	 * @param int $migration_id Migration ID.
	 * @return array{status: string, verified: int, mismatches: int, missing: int, details: array<string>}
	 */
	public static function verify_migration( int $migration_id ): array {
		global $wpdb;

		$items_table = Schema::get_table_name( 'items' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, object_type, object_id, original_owner_id, new_owner_id 
				FROM {$items_table} 
				WHERE migration_id = %d AND migration_status = 'migrated'",
				$migration_id
			)
		);

		$verified   = 0;
		$mismatches = 0;
		$missing    = 0;
		$details    = [];

		if ( $items ) {
			foreach ( $items as $item ) {
				$obj_id    = (int) $item->object_id;
				$new_owner = (int) $item->new_owner_id;
				$type      = (string) $item->object_type;

				if ( 'comment' === $type ) {
					clean_comment_cache( $obj_id );
					$comment = get_comment( $obj_id );
					if ( ! ( $comment instanceof WP_Comment ) ) {
						$missing++;
						$details[] = sprintf( __( 'Comment #%d not found during verification.', 'wpcalibrate-user-content-migrator' ), $obj_id );
					} elseif ( (int) $comment->user_id !== $new_owner ) {
						$mismatches++;
						$details[] = sprintf(
							__( 'Comment #%1$d owner mismatch: expected #%2$d, found #%3$d.', 'wpcalibrate-user-content-migrator' ),
							$obj_id,
							$new_owner,
							(int) $comment->user_id
						);
					} else {
						$verified++;
					}
				} else {
					clean_post_cache( $obj_id );
					$post = get_post( $obj_id );
					if ( ! ( $post instanceof WP_Post ) ) {
						$missing++;
						$details[] = sprintf( __( 'Post #%d not found during verification.', 'wpcalibrate-user-content-migrator' ), $obj_id );
					} elseif ( (int) $post->post_author !== $new_owner ) {
						$mismatches++;
						$details[] = sprintf(
							__( 'Post #%1$d author mismatch: expected #%2$d, found #%3$d.', 'wpcalibrate-user-content-migrator' ),
							$obj_id,
							$new_owner,
							(int) $post->post_author
						);
					} else {
						$verified++;
					}
				}
			}
		}

		$status = ( 0 === $mismatches && 0 === $missing ) ? 'verified' : 'mismatch';

		$summary = [
			'status'     => $status,
			'verified'   => $verified,
			'mismatches' => $mismatches,
			'missing'    => $missing,
			'details'    => array_slice( $details, 0, 50 ), // Cap details to top 50
			'time'       => current_time( 'mysql', true ),
		];

		MigrationsTable::update(
			$migration_id,
			[
				'verification_status' => $status,
				'verification_data'   => $summary,
			]
		);

		return $summary;
	}

	/**
	 * Verify an executed rollback against live database ownership.
	 *
	 * @param int $migration_id Migration ID.
	 * @return array{status: string, verified: int, mismatches: int, missing: int, details: array<string>}
	 */
	public static function verify_rollback( int $migration_id ): array {
		global $wpdb;

		$items_table = Schema::get_table_name( 'items' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, object_type, object_id, original_owner_id, new_owner_id 
				FROM {$items_table} 
				WHERE migration_id = %d AND rollback_status = 'rolled_back'",
				$migration_id
			)
		);

		$verified   = 0;
		$mismatches = 0;
		$missing    = 0;
		$details    = [];

		if ( $items ) {
			foreach ( $items as $item ) {
				$obj_id         = (int) $item->object_id;
				$original_owner = (int) $item->original_owner_id;
				$type           = (string) $item->object_type;

				if ( 'comment' === $type ) {
					clean_comment_cache( $obj_id );
					$comment = get_comment( $obj_id );
					if ( ! ( $comment instanceof WP_Comment ) ) {
						$missing++;
						$details[] = sprintf( __( 'Comment #%d not found during rollback verification.', 'wpcalibrate-user-content-migrator' ), $obj_id );
					} elseif ( (int) $comment->user_id !== $original_owner ) {
						$mismatches++;
						$details[] = sprintf(
							__( 'Comment #%1$d rollback mismatch: expected #%2$d, found #%3$d.', 'wpcalibrate-user-content-migrator' ),
							$obj_id,
							$original_owner,
							(int) $comment->user_id
						);
					} else {
						$verified++;
					}
				} else {
					clean_post_cache( $obj_id );
					$post = get_post( $obj_id );
					if ( ! ( $post instanceof WP_Post ) ) {
						$missing++;
						$details[] = sprintf( __( 'Post #%d not found during rollback verification.', 'wpcalibrate-user-content-migrator' ), $obj_id );
					} elseif ( (int) $post->post_author !== $original_owner ) {
						$mismatches++;
						$details[] = sprintf(
							__( 'Post #%1$d rollback mismatch: expected #%2$d, found #%3$d.', 'wpcalibrate-user-content-migrator' ),
							$obj_id,
							$original_owner,
							(int) $post->post_author
						);
					} else {
						$verified++;
					}
				}
			}
		}

		$status = ( 0 === $mismatches && 0 === $missing ) ? 'verified' : 'mismatch';

		return [
			'status'     => $status,
			'verified'   => $verified,
			'mismatches' => $mismatches,
			'missing'    => $missing,
			'details'    => array_slice( $details, 0, 50 ),
			'time'       => current_time( 'mysql', true ),
		];
	}
}
