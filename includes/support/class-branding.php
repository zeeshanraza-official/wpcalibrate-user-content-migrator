<?php
/**
 * Branding Helper
 *
 * @package WPCalibrate\UserContentMigrator\Support
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WPCalibrate branding assets resolution and context-aware icons.
 */
class Branding {

	/**
	 * Get the URL of a branding icon based on background context.
	 *
	 * @param string $context 'dark' (for dark backgrounds like WP admin menu) or 'light' (for light backgrounds).
	 * @return string Image URL.
	 */
	public static function get_icon_url( string $context = 'dark' ): string {
		$plugin_url = defined( 'WPCALIBRATE_UCM_URL' ) ? WPCALIBRATE_UCM_URL : plugin_dir_url( dirname( __DIR__ ) . '/wpcalibrate-user-content-migrator.php' );

		if ( 'dark' === $context ) {
			// For dark background (e.g. wp-admin menu), use icon-white.png.
			return esc_url( $plugin_url . 'branding/icon-white.png' );
		}

		// For light background (e.g. cards, header, white pages), use icon-dark.png.
		return esc_url( $plugin_url . 'branding/icon-dark.png' );
	}

	/**
	 * Render WPCalibrate plugin header markup.
	 *
	 * @param string $title Page title.
	 * @param string $subtitle Optional subtitle.
	 * @return void
	 */
	public static function render_header( string $title, string $subtitle = '' ): void {
		$logo_url = self::get_icon_url( 'light' );
		?>
		<header class="wpcalibrate-ucm-header" role="banner">
			<div class="wpcalibrate-ucm-header-brand">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="WPCalibrate" class="wpcalibrate-ucm-brand-logo" width="36" height="36" />
				<div class="wpcalibrate-ucm-header-titles">
					<span class="wpcalibrate-ucm-badge"><?php esc_html_e( 'WPCalibrate', 'wpcalibrate-user-content-migrator' ); ?></span>
					<h1 class="wpcalibrate-ucm-title"><?php echo esc_html( $title ); ?></h1>
					<?php if ( ! empty( $subtitle ) ) : ?>
						<p class="wpcalibrate-ucm-subtitle"><?php echo esc_html( $subtitle ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<div class="wpcalibrate-ucm-header-links">
				<a href="<?php echo esc_url( self::get_support_url() ); ?>" class="button" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Support', 'wpcalibrate-user-content-migrator' ); ?>
				</a>
				<a href="<?php echo esc_url( self::get_docs_url() ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Documentation', 'wpcalibrate-user-content-migrator' ); ?>
				</a>
			</div>
		</header>
		<?php
	}

	/**
	 * Get official support email.
	 *
	 * @return string
	 */
	public static function get_support_email(): string {
		return 'support@wpcalibrate.com';
	}

	/**
	 * Get official website URL.
	 *
	 * @return string
	 */
	public static function get_website_url(): string {
		return 'https://wpcalibrate.com';
	}

	/**
	 * Get marketplace URL.
	 *
	 * @return string
	 */
	public static function get_marketplace_url(): string {
		return 'https://marketplace.wpcalibrate.com/';
	}

	/**
	 * Get support / contact URL.
	 *
	 * @return string
	 */
	public static function get_support_url(): string {
		return 'https://wpcalibrate.com/support/';
	}

	/**
	 * Get documentation URL.
	 *
	 * @return string
	 */
	public static function get_docs_url(): string {
		return 'https://wpcalibrate.com/docs/user-content-migrator/';
	}

	/**
	 * Get phone/WhatsApp contact.
	 *
	 * @return string
	 */
	public static function get_whatsapp(): string {
		return '+447474795976';
	}
}
