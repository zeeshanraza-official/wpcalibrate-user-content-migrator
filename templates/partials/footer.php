<?php
/**
 * Admin Footer Partial
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="wpcalibrate-ucm-footer" role="contentinfo">
	<div class="wpcalibrate-ucm-footer-left">
		<p>
			<?php
			printf(
				/* translators: 1: WPCalibrate link, 2: current year */
				esc_html__( 'Developed by %1$s &bull; Safe Ownership Transfer &bull; &copy; %2$s', 'wpcalibrate-user-content-migrator' ),
				'<a href="' . esc_url( \WPCalibrate\UserContentMigrator\Support\Branding::get_website_url() ) . '" target="_blank" rel="noopener noreferrer">WPCalibrate</a>',
				esc_html( gmdate( 'Y' ) )
			);
			?>
		</p>
	</div>
	<div class="wpcalibrate-ucm-footer-right">
		<ul class="wpcalibrate-ucm-footer-links">
			<li><a href="<?php echo esc_url( \WPCalibrate\UserContentMigrator\Support\Branding::get_docs_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'wpcalibrate-user-content-migrator' ); ?></a></li>
			<li><a href="<?php echo esc_url( \WPCalibrate\UserContentMigrator\Support\Branding::get_support_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Help & Support', 'wpcalibrate-user-content-migrator' ); ?></a></li>
			<li><a href="<?php echo esc_url( \WPCalibrate\UserContentMigrator\Support\Branding::get_marketplace_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Marketplace', 'wpcalibrate-user-content-migrator' ); ?></a></li>
		</ul>
	</div>
</footer>
