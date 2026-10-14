<?php
/**
 * Content Eligibility Service
 *
 * @package WPCalibrate\UserContentMigrator\Services
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determines which post types, statuses, and content types are eligible for ownership transfer.
 */
class ContentEligibilityService {

	/**
	 * Default blocked post types.
	 *
	 * @var array<string>
	 */
	private const DEFAULT_BLOCKED_POST_TYPES = [
		'revision',
		'nav_menu_item',
		'custom_css',
		'customize_changeset',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
		'wp_font_family',
		'wp_font_face',
		'oembed_cache',
		'user_request',
		'action_scheduler',
		'scheduled-action',
		'shop_order',
		'shop_order_refund',
		'shop_order_placehold',
		'shop_coupon',
		'shop_webhook',
		'wp_block',
	];

	/**
	 * Default supported statuses for authored content.
	 *
	 * @var array<string>
	 */
	private const DEFAULT_STATUSES = [
		'publish',
		'draft',
		'pending',
		'private',
		'future',
		'trash',
	];

	/**
	 * Get list of eligible post types with metadata.
	 *
	 * @return array<string, array{slug: string, label: string, singular_label: string, is_attachment: bool, is_product: bool}>
	 */
	public static function get_eligible_post_types(): array {
		$post_types = get_post_types( [], 'objects' );
		$eligible   = [];

		$blocked = self::get_blocked_post_types();

		foreach ( $post_types as $slug => $obj ) {
			if ( in_array( $slug, $blocked, true ) ) {
				continue;
			}

			// Must support author or be attachment.
			$supports_author = post_type_supports( $slug, 'author' );
			$is_attachment   = ( 'attachment' === $slug );

			if ( ! $supports_author && ! $is_attachment ) {
				continue;
			}

			$label          = ! empty( $obj->labels->name ) ? $obj->labels->name : $obj->label;
			$singular_label = ! empty( $obj->labels->singular_name ) ? $obj->labels->singular_name : $label;

			$eligible[ $slug ] = [
				'slug'           => $slug,
				'label'          => $label,
				'singular_label' => $singular_label,
				'is_attachment'  => $is_attachment,
				'is_product'     => ( 'product' === $slug ),
			];
		}

		/**
		 * Filters the eligible post types for ownership transfer.
		 *
		 * @param array<string, array<string, mixed>> $eligible Eligible post types array.
		 */
		$filtered = apply_filters( 'wpcalibrate_ucm_eligible_post_types', $eligible );

		return is_array( $filtered ) ? $filtered : $eligible;
	}

	/**
	 * Get list of blocked post types.
	 *
	 * @return array<string>
	 */
	public static function get_blocked_post_types(): array {
		/**
		 * Filters the blocked post types.
		 *
		 * @param array<string> $blocked Blocked post types.
		 */
		$blocked = apply_filters( 'wpcalibrate_ucm_excluded_post_types', self::DEFAULT_BLOCKED_POST_TYPES );

		return is_array( $blocked ) ? array_unique( $blocked ) : self::DEFAULT_BLOCKED_POST_TYPES;
	}

	/**
	 * Check if a post type is eligible.
	 *
	 * @param string $post_type Post type slug.
	 * @return bool True if eligible.
	 */
	public static function is_post_type_eligible( string $post_type ): bool {
		$eligible = self::get_eligible_post_types();
		return isset( $eligible[ $post_type ] );
	}

	/**
	 * Get supported statuses for migration.
	 *
	 * @param string $post_type Optional post type to get statuses for.
	 * @return array<string, string> Array of status_slug => label.
	 */
	public static function get_supported_statuses( string $post_type = '' ): array {
		$statuses = [
			'publish' => __( 'Published', 'wpcalibrate-user-content-migrator' ),
			'draft'   => __( 'Draft', 'wpcalibrate-user-content-migrator' ),
			'pending' => __( 'Pending Review', 'wpcalibrate-user-content-migrator' ),
			'private' => __( 'Private', 'wpcalibrate-user-content-migrator' ),
			'future'  => __( 'Scheduled', 'wpcalibrate-user-content-migrator' ),
			'trash'   => __( 'Trash', 'wpcalibrate-user-content-migrator' ),
		];

		// For attachments, WordPress uses 'inherit' as primary status.
		if ( 'attachment' === $post_type ) {
			$statuses = [
				'inherit' => __( 'Inherit / Standard', 'wpcalibrate-user-content-migrator' ),
				'trash'   => __( 'Trash', 'wpcalibrate-user-content-migrator' ),
			];
		}

		/**
		 * Filters the supported post statuses.
		 *
		 * @param array<string, string> $statuses Array of status slug => label.
		 * @param string                $post_type Post type slug.
		 */
		$filtered = apply_filters( 'wpcalibrate_ucm_supported_statuses', $statuses, $post_type );

		return is_array( $filtered ) ? $filtered : $statuses;
	}

	/**
	 * Check if comments migration option is enabled in settings.
	 *
	 * @return bool True if comments option is allowed to be configured.
	 */
	public static function is_comments_feature_enabled(): bool {
		return SettingsManager::get_bool( 'allow_comments_migration', true );
	}
}
