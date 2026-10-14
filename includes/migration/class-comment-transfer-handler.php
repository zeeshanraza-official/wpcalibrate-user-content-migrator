<?php
/**
 * Comment Transfer Handler
 *
 * @package WPCalibrate\UserContentMigrator\Migration
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Migration;

use WP_Comment;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles safe user_id reassignment for comments.
 * Strictly alters user_id without touching comment_author name, email, IP, content, or post.
 */
class CommentTransferHandler {

	/**
	 * Transfer comment user_id to destination user.
	 *
	 * @param int $comment_id Comment ID.
	 * @param int $original_owner_id Expected source user_id.
	 * @param int $new_owner_id Destination user_id.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function transfer( int $comment_id, int $original_owner_id, int $new_owner_id ) {
		$comment = get_comment( $comment_id );
		if ( ! ( $comment instanceof WP_Comment ) ) {
			return new WP_Error(
				'comment_not_found',
				sprintf(
					/* translators: %d: comment ID */
					__( 'Comment ID %d was not found.', 'wpcalibrate-user-content-migrator' ),
					$comment_id
				)
			);
		}

		if ( (int) $comment->user_id !== $original_owner_id ) {
			return new WP_Error(
				'comment_user_mismatch',
				sprintf(
					/* translators: 1: comment ID, 2: expected user ID, 3: actual user ID */
					__( 'Comment #%1$d user mismatch. Expected user #%2$d, found #%3$d.', 'wpcalibrate-user-content-migrator' ),
					$comment_id,
					$original_owner_id,
					(int) $comment->user_id
				)
			);
		}

		/**
		 * Action before comment user transfer.
		 *
		 * @param int $comment_id Comment ID.
		 * @param int $original_owner_id Original user ID.
		 * @param int $new_owner_id New user ID.
		 */
		do_action( 'wpcalibrate_ucm_before_comment_transfer', $comment_id, $original_owner_id, $new_owner_id );

		// Update ONLY user_id using wp_update_comment.
		$updated = wp_update_comment(
			[
				'comment_ID' => $comment_id,
				'user_id'    => $new_owner_id,
			]
		);

		if ( 0 === $updated || false === $updated ) {
			return new WP_Error(
				'comment_update_failed',
				sprintf(
					/* translators: %d: comment ID */
					__( 'wp_update_comment failed for comment ID %d.', 'wpcalibrate-user-content-migrator' ),
					$comment_id
				)
			);
		}

		clean_comment_cache( $comment_id );

		/**
		 * Action after comment user transfer.
		 *
		 * @param int $comment_id Comment ID.
		 * @param int $original_owner_id Original user ID.
		 * @param int $new_owner_id New user ID.
		 */
		do_action( 'wpcalibrate_ucm_after_comment_transfer', $comment_id, $original_owner_id, $new_owner_id );

		return true;
	}
}
