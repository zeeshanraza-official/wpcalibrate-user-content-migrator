<?php
/**
 * Support Partial View
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email       = \WPCalibrate\UserContentMigrator\Support\Branding::get_support_email();
$website     = \WPCalibrate\UserContentMigrator\Support\Branding::get_website_url();
$marketplace = \WPCalibrate\UserContentMigrator\Support\Branding::get_marketplace_url();
$whatsapp    = \WPCalibrate\UserContentMigrator\Support\Branding::get_whatsapp();
?>
<div class="wpcalibrate-ucm-support-box">
	<div class="wpcalibrate-ucm-card-header">
		<h2><?php esc_html_e( 'Official WPCalibrate Support', 'wpcalibrate-user-content-migrator' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Get direct assistance from our engineering and support team.', 'wpcalibrate-user-content-migrator' ); ?></p>
	</div>
	<div class="wpcalibrate-ucm-support-grid">
		<div class="wpcalibrate-ucm-support-channel">
			<div class="wpcalibrate-ucm-support-icon">✉</div>
			<div class="wpcalibrate-ucm-support-meta">
				<strong><?php esc_html_e( 'Email Support', 'wpcalibrate-user-content-migrator' ); ?></strong>
				<p><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
			</div>
		</div>

		<div class="wpcalibrate-ucm-support-channel">
			<div class="wpcalibrate-ucm-support-icon">🌐</div>
			<div class="wpcalibrate-ucm-support-meta">
				<strong><?php esc_html_e( 'Official Website', 'wpcalibrate-user-content-migrator' ); ?></strong>
				<p><a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $website ); ?></a></p>
			</div>
		</div>

		<div class="wpcalibrate-ucm-support-channel">
			<div class="wpcalibrate-ucm-support-icon">🛒</div>
			<div class="wpcalibrate-ucm-support-meta">
				<strong><?php esc_html_e( 'Plugin Marketplace', 'wpcalibrate-user-content-migrator' ); ?></strong>
				<p><a href="<?php echo esc_url( $marketplace ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Visit Marketplace &rarr;', 'wpcalibrate-user-content-migrator' ); ?></a></p>
			</div>
		</div>

		<div class="wpcalibrate-ucm-support-channel">
			<div class="wpcalibrate-ucm-support-icon">💬</div>
			<div class="wpcalibrate-ucm-support-meta">
				<strong><?php esc_html_e( 'WhatsApp / Phone', 'wpcalibrate-user-content-migrator' ); ?></strong>
				<p><a href="<?php echo esc_url( 'https://wa.me/' . ltrim( $whatsapp, '+' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $whatsapp ); ?></a></p>
			</div>
		</div>
	</div>
</div>
