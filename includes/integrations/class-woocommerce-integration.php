<?php
/**
 * WooCommerce Integration
 *
 * @package WPCalibrate\UserContentMigrator\Integrations
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles safe compatibility with WooCommerce when active.
 */
class WooCommerceIntegration {

	/**
	 * Blocked WooCommerce post types that must NEVER be migrated.
	 *
	 * @var array<string>
	 */
	public const BLOCKED_ORDER_TYPES = [
		'shop_order',
		'shop_order_refund',
		'shop_order_placehold',
		'shop_coupon',
		'shop_webhook',
	];

	/**
	 * Initialize WooCommerce compatibility declarations.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Declare HPOS and Blocks compatibility safely on 'before_woocommerce_init'.
		add_action( 'before_woocommerce_init', [ self::class, 'declare_hpos_compatibility' ] );
	}

	/**
	 * Declare High-Performance Order Storage (HPOS) compatibility.
	 *
	 * Since this plugin NEVER modifies or queries order storage, it is fully compatible with HPOS.
	 *
	 * @return void
	 */
	public static function declare_hpos_compatibility(): void {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) && defined( 'WPCALIBRATE_UCM_FILE' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				WPCALIBRATE_UCM_FILE,
				true
			);
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				WPCALIBRATE_UCM_FILE,
				true
			);
		}
	}

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool True if WooCommerce active.
	 */
	public static function is_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Check if a post type is a protected WooCommerce order type.
	 *
	 * @param string $post_type Post type slug.
	 * @return bool True if protected.
	 */
	public static function is_protected_woo_type( string $post_type ): bool {
		return in_array( $post_type, self::BLOCKED_ORDER_TYPES, true );
	}
}
