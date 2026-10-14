<?php
/**
 * Attachment Transfer Handler
 *
 * @package WPCalibrate\UserContentMigrator\Migration
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Migration;

use WP_Error;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles safe authorship transfer for Media Library attachments.
 * Strictly alters post_author without moving files, renaming uploads, or altering metadata.
 */
class AttachmentTransferHandler {

	/**
	 * Transfer attachment authorship to new owner.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @param int $original_owner_id Expected current author ID.
	 * @param int $new_owner_id New author ID.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function transfer( int $attachment_id, int $original_owner_id, int $new_owner_id ) {
		$attachment = get_post( $attachment_id );
		if ( ! ( $attachment instanceof WP_Post ) || 'attachment' !== $attachment->post_type ) {
			return new WP_Error(
				'attachment_not_found',
				sprintf(
					/* translators: %d: attachment ID */
					__( 'Attachment ID %d was not found.', 'wpcalibrate-user-content-migrator' ),
					$attachment_id
				)
			);
		}

		if ( (int) $attachment->post_author !== $original_owner_id ) {
			return new WP_Error(
				'attachment_author_mismatch',
				sprintf(
					/* translators: 1: attachment ID, 2: expected author ID, 3: actual author ID */
					__( 'Attachment #%1$d author mismatch. Expected author #%2$d, found #%3$d.', 'wpcalibrate-user-content-migrator' ),
					$attachment_id,
					$original_owner_id,
					(int) $attachment->post_author
				)
			);
		}

		/**
		 * Action before attachment ownership transfer.
		 *
		 * @param int $attachment_id Attachment ID.
		 * @param int $original_owner_id Original author ID.
		 * @param int $new_owner_id New author ID.
		 */
		do_action( 'wpcalibrate_ucm_before_attachment_transfer', $attachment_id, $original_owner_id, $new_owner_id );

		// Update post_author ONLY using standard wp_update_post.
		$result = wp_update_post(
			[
				'ID'          => $attachment_id,
				'post_author' => $new_owner_id,
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( 0 === $result ) {
			return new WP_Error(
				'attachment_update_failed',
				sprintf(
					/* translators: %d: attachment ID */
					__( 'wp_update_post failed for attachment ID %d.', 'wpcalibrate-user-content-migrator' ),
					$attachment_id
				)
			);
		}

		clean_post_cache( $attachment_id );

		/**
		 * Action after attachment ownership transfer.
		 *
		 * @param int $attachment_id Attachment ID.
		 * @param int $original_owner_id Original author ID.
		 * @param int $new_owner_id New author ID.
		 */
		do_action( 'wpcalibrate_ucm_after_attachment_transfer', $attachment_id, $original_owner_id, $new_owner_id );

		return true;
	}
}
