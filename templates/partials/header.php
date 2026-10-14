<?php
/**
 * Admin Header Partial
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo_url = \WPCalibrate\UserContentMigrator\Support\Branding::get_icon_url( 'light' );
$support_url = \WPCalibrate\UserContentMigrator\Support\Branding::get_support_url();
$docs_url = \WPCalibrate\UserContentMigrator\Support\Branding::get_docs_url();
?>
<header class="wpcalibrate-ucm-header" role="banner">
	<div class="wpcalibrate-ucm-header-brand">
		<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'WPCalibrate Logo', 'wpcalibrate-user-content-migrator' ); ?>" class="wpcalibrate-ucm-brand-logo" width="38" height="38" />
		<div class="wpcalibrate-ucm-header-titles">
			<div class="wpcalibrate-ucm-brand-row">
				<span class="wpcalibrate-ucm-badge"><?php esc_html_e( 'WPCalibrate Suite', 'wpcalibrate-user-content-migrator' ); ?></span>
				<span class="wpcalibrate-ucm-version">v<?php echo esc_html( WPCALIBRATE_UCM_VERSION ); ?></span>
			</div>
			<h1 class="wpcalibrate-ucm-title"><?php echo esc_html( $page_title ?? __( 'User Content Migrator', 'wpcalibrate-user-content-migrator' ) ); ?></h1>
			<?php if ( ! empty( $page_subtitle ) ) : ?>
				<p class="wpcalibrate-ucm-subtitle"><?php echo esc_html( $page_subtitle ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<div class="wpcalibrate-ucm-header-actions">
		<nav class="wpcalibrate-ucm-nav-tabs" aria-label="<?php esc_attr_e( 'Plugin navigation', 'wpcalibrate-user-content-migrator' ); ?>">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-overview' ) ); ?>" class="wpcalibrate-ucm-nav-tab <?php echo ( 'wpcalibrate-ucm-overview' === ( $_GET['page'] ?? '' ) ) ? 'is-active' : ''; ?>">
				<?php esc_html_e( 'Overview', 'wpcalibrate-user-content-migrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm' ) ); ?>" class="wpcalibrate-ucm-nav-tab <?php echo ( 'wpcalibrate-ucm' === ( $_GET['page'] ?? '' ) ) ? 'is-active' : ''; ?>">
				<?php esc_html_e( 'Migrator', 'wpcalibrate-user-content-migrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-history' ) ); ?>" class="wpcalibrate-ucm-nav-tab <?php echo ( 'wpcalibrate-ucm-history' === ( $_GET['page'] ?? '' ) ) ? 'is-active' : ''; ?>">
				<?php esc_html_e( 'History & Rollback', 'wpcalibrate-user-content-migrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-settings' ) ); ?>" class="wpcalibrate-ucm-nav-tab <?php echo ( 'wpcalibrate-ucm-settings' === ( $_GET['page'] ?? '' ) ) ? 'is-active' : ''; ?>">
				<?php esc_html_e( 'Settings', 'wpcalibrate-user-content-migrator' ); ?>
			</a>
		</nav>
	</div>
</header>
