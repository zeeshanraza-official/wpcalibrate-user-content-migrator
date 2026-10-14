<?php
/**
 * Post Transfer Handler
 *
 * @package WPCalibrate\UserContentMigrator\Migration
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Migration;

use WP_Error;
use WP_Post;
use WPCalibrate\UserContentMigrator\Integrations\WooCommerceIntegration;
use WPCalibrate\UserContentMigrator\Services\ContentEligibilityService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles safe authorship transfer for standard posts, pages, and eligible custom post types.
 */
class PostTransferHandler {

	/**
	 * Transfer post authorship to new owner.
	 *
	 * @param int $post_id Post ID.
	 * @param int $original_owner_id Expected current post author ID.
	 * @param int $new_owner_id New post author ID.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function transfer( int $post_id, int $original_owner_id, int $new_owner_id ) {
		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) ) {
			return new WP_Error(
				'post_not_found',
				sprintf(
					/* translators: %d: post ID */
					__( 'Post ID %d was not found.', 'wpcalibrate-user-content-migrator' ),
					$post_id
				)
			);
		}

		// Security: verify post type is eligible and not a protected WooCommerce order type.
		if ( WooCommerceIntegration::is_protected_woo_type( $post->post_type ) ) {
			return new WP_Error(
				'protected_woo_type',
				sprintf(
					/* translators: %s: post type */
					__( 'Transferring ownership of WooCommerce order type "%s" is strictly prohibited.', 'wpcalibrate-user-content-migrator' ),
					$post->post_type
				)
			);
		}

		if ( ! ContentEligibilityService::is_post_type_eligible( $post->post_type ) ) {
			return new WP_Error(
				'ineligible_post_type',
				sprintf(
					/* translators: %s: post type */
					__( 'Post type "%s" is not eligible for migration.', 'wpcalibrate-user-content-migrator' ),
					$post->post_type
				)
			);
		}

		// Verify current owner matches expected original owner.
		if ( (int) $post->post_author !== $original_owner_id ) {
			return new WP_Error(
				'author_mismatch',
				sprintf(
					/* translators: 1: post ID, 2: expected author ID, 3: actual author ID */
					__( 'Post #%1$d author mismatch. Expected author #%2$d, found #%3$d.', 'wpcalibrate-user-content-migrator' ),
					$post_id,
					$original_owner_id,
					(int) $post->post_author
				)
			);
		}

		/**
		 * Action before post ownership transfer.
		 *
		 * @param int    $post_id Post ID.
		 * @param int    $original_owner_id Original author ID.
		 * @param int    $new_owner_id New author ID.
		 * @param string $post_type Post type.
		 */
		do_action( 'wpcalibrate_ucm_before_post_transfer', $post_id, $original_owner_id, $new_owner_id, $post->post_type );

		// Use supported WordPress API wp_update_post to preserve all content, dates, slugs, statuses, etc.
		$update_args = [
			'ID'          => $post_id,
			'post_author' => $new_owner_id,
		];

		$result = wp_update_post( $update_args, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( 0 === $result ) {
			return new WP_Error(
				'post_update_failed',
				sprintf(
					/* translators: %d: post ID */
					__( 'wp_update_post failed for post ID %d.', 'wpcalibrate-user-content-migrator' ),
					$post_id
				)
			);
		}

		// Invalidate post caches.
		clean_post_cache( $post_id );

		/**
		 * Action after post ownership transfer.
		 *
		 * @param int    $post_id Post ID.
		 * @param int    $original_owner_id Original author ID.
		 * @param int    $new_owner_id New author ID.
		 * @param string $post_type Post type.
		 */
		do_action( 'wpcalibrate_ucm_after_post_transfer', $post_id, $original_owner_id, $new_owner_id, $post->post_type );

		return true;
	}
}
