<?php
/**
 * Overview & Guide Page Template
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title    = __( 'User Content Migrator — Overview & Guide', 'wpcalibrate-user-content-migrator' );
$page_subtitle = __( 'Safely reassign ownership of posts, pages, custom post types, WooCommerce products, and media between WordPress users.', 'wpcalibrate-user-content-migrator' );

$migrator_url = admin_url( 'admin.php?page=wpcalibrate-ucm' );
$history_url  = admin_url( 'admin.php?page=wpcalibrate-ucm-history' );
$settings_url = admin_url( 'admin.php?page=wpcalibrate-ucm-settings' );
?>
<div class="wrap wpcalibrate-ucm-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<main class="wpcalibrate-ucm-main" id="wpcalibrate-ucm-overview-main">
		<!-- Purpose & Overview Banner -->
		<div class="wpcalibrate-ucm-card wpcalibrate-ucm-overview-hero">
			<div class="wpcalibrate-ucm-overview-hero-content">
				<span class="wpcalibrate-ucm-badge"><?php esc_html_e( 'Plugin Purpose & Architecture', 'wpcalibrate-user-content-migrator' ); ?></span>
				<h2><?php esc_html_e( 'Why WPCalibrate User Content Migrator?', 'wpcalibrate-user-content-migrator' ); ?></h2>
				<p class="wpcalibrate-ucm-hero-desc">
					<?php esc_html_e( 'In native WordPress, the only built-in way to reassign content ownership from one user to another is by deleting the user account entirely. This creates severe operational risks for editorial teams, client handovers, agency workflows, and WooCommerce stores.', 'wpcalibrate-user-content-migrator' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'WPCalibrate User Content Migrator decouples ownership reassignment from user deletion. You can transfer content while keeping accounts active, reassign author credits, consolidate guest posts, or prepare for clean offboarding—with full batch processing, integrity verification, and one-click rollback.', 'wpcalibrate-user-content-migrator' ); ?>
				</p>
				<div class="wpcalibrate-ucm-overview-cta-row">
					<a href="<?php echo esc_url( $migrator_url ); ?>" class="button button-primary button-hero">
						<?php esc_html_e( 'Launch Content Migrator &rarr;', 'wpcalibrate-user-content-migrator' ); ?>
					</a>
					<a href="<?php echo esc_url( $history_url ); ?>" class="button button-secondary">
						<?php esc_html_e( 'View Migration History & Rollback', 'wpcalibrate-user-content-migrator' ); ?>
					</a>
				</div>
			</div>
		</div>

		<!-- How to Use: 5-Step Method -->
		<div class="wpcalibrate-ucm-card" style="margin-top: 24px;">
			<h2 class="wpcalibrate-ucm-card-title"><?php esc_html_e( 'How to Use: The 5-Step Migration Method', 'wpcalibrate-user-content-migrator' ); ?></h2>
			<p class="description" style="margin-bottom: 20px;">
				<?php esc_html_e( 'The plugin guides you through an intuitive, fail-safe 5-step wizard to guarantee precision and safety.', 'wpcalibrate-user-content-migrator' ); ?>
			</p>

			<div class="wpcalibrate-ucm-steps-overview-grid">
				<div class="wpcalibrate-ucm-step-card">
					<div class="wpcalibrate-ucm-step-card-num">1</div>
					<div class="wpcalibrate-ucm-step-card-body">
						<h3><?php esc_html_e( 'Select Users', 'wpcalibrate-user-content-migrator' ); ?></h3>
						<p><?php esc_html_e( 'Search and select both a Source User (the current author) and a Destination User (the new author). Real-time validation ensures users are active and distinct.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>

				<div class="wpcalibrate-ucm-step-card">
					<div class="wpcalibrate-ucm-step-card-num">2</div>
					<div class="wpcalibrate-ucm-step-card-body">
						<h3><?php esc_html_e( 'Content & Scope', 'wpcalibrate-user-content-migrator' ); ?></h3>
						<p><?php esc_html_e( 'Choose which post types to transfer: standard Posts, Pages, Custom Post Types (CPTs), WooCommerce Products, and Media Attachments.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>

				<div class="wpcalibrate-ucm-step-card">
					<div class="wpcalibrate-ucm-step-card-num">3</div>
					<div class="wpcalibrate-ucm-step-card-body">
						<h3><?php esc_html_e( 'Filters & Options', 'wpcalibrate-user-content-migrator' ); ?></h3>
						<p><?php esc_html_e( 'Target specific content subsets using date ranges (From / To), post statuses (Published, Draft, etc.), and categories or tags.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>

				<div class="wpcalibrate-ucm-step-card">
					<div class="wpcalibrate-ucm-step-card-num">4</div>
					<div class="wpcalibrate-ucm-step-card-body">
						<h3><?php esc_html_e( 'Scan & Preview', 'wpcalibrate-user-content-migrator' ); ?></h3>
						<p><?php esc_html_e( 'Perform a dry-run discovery scan. Review the exact count of eligible items, breakdown by type, and inspect sample titles before executing.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>

				<div class="wpcalibrate-ucm-step-card">
					<div class="wpcalibrate-ucm-step-card-num">5</div>
					<div class="wpcalibrate-ucm-step-card-body">
						<h3><?php esc_html_e( 'Execute & Verify', 'wpcalibrate-user-content-migrator' ); ?></h3>
						<p><?php esc_html_e( 'Batched background execution with live progress bar. Upon completion, an automated database integrity verification runs to confirm correct ownership.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Safety Guarantees & Technical Method -->
		<div class="wpcalibrate-ucm-card" style="margin-top: 24px;">
			<h2 class="wpcalibrate-ucm-card-title"><?php esc_html_e( 'Technical Architecture & Safety Guarantees', 'wpcalibrate-user-content-migrator' ); ?></h2>
			<p class="description" style="margin-bottom: 20px;">
				<?php esc_html_e( 'Engineered for zero downtime, data integrity, and strict adherence to WordPress core standards.', 'wpcalibrate-user-content-migrator' ); ?>
			</p>

			<div class="wpcalibrate-ucm-guarantees-grid">
				<div class="wpcalibrate-ucm-guarantee-box">
					<div class="wpcalibrate-ucm-guarantee-icon">🛡️</div>
					<div class="wpcalibrate-ucm-guarantee-content">
						<h4><?php esc_html_e( 'Non-Destructive Core Updates', 'wpcalibrate-user-content-migrator' ); ?></h4>
						<p><?php esc_html_e( 'Reassignment is performed strictly through WordPress core functions (wp_update_post). Post slugs, dates, taxonomies, comments, and meta fields are untouched.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>

				<div class="wpcalibrate-ucm-guarantee-box">
					<div class="wpcalibrate-ucm-guarantee-icon">📋</div>
					<div class="wpcalibrate-ucm-guarantee-content">
						<h4><?php esc_html_e( 'Exact Rollback Ledger', 'wpcalibrate-user-content-migrator' ); ?></h4>
						<p><?php esc_html_e( 'Every transferred item is recorded in an immutable ledger (wp_wpcalibrate_ucm_items) tracking source user, destination user, and timestamps for safe, granular rollback.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>

				<div class="wpcalibrate-ucm-guarantee-box">
					<div class="wpcalibrate-ucm-guarantee-icon">🔒</div>
					<div class="wpcalibrate-ucm-guarantee-content">
						<h4><?php esc_html_e( 'Process Concurrency Locks', 'wpcalibrate-user-content-migrator' ); ?></h4>
						<p><?php esc_html_e( 'Atomic database lock tokens prevent duplicate migrations, race conditions, or accidental concurrent execution by multiple administrators.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>

				<div class="wpcalibrate-ucm-guarantee-box">
					<div class="wpcalibrate-ucm-guarantee-icon">⚡</div>
					<div class="wpcalibrate-ucm-guarantee-content">
						<h4><?php esc_html_e( 'Memory-Safe Batching', 'wpcalibrate-user-content-migrator' ); ?></h4>
						<p><?php esc_html_e( 'Processes content in configurable chunks (default: 25 items per batch) to prevent PHP execution timeouts and memory exhaustion on large websites.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>

				<div class="wpcalibrate-ucm-guarantee-box">
					<div class="wpcalibrate-ucm-guarantee-icon">🛍️</div>
					<div class="wpcalibrate-ucm-guarantee-content">
						<h4><?php esc_html_e( 'WooCommerce Compatibility', 'wpcalibrate-user-content-migrator' ); ?></h4>
						<p><?php esc_html_e( 'Seamlessly transfers product catalog items (product, product_variation) while strictly excluding orders to protect financial and billing records.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>

				<div class="wpcalibrate-ucm-guarantee-box">
					<div class="wpcalibrate-ucm-guarantee-icon">⚠️</div>
					<div class="wpcalibrate-ucm-guarantee-content">
						<h4><?php esc_html_e( 'Post-Migration Conflict Detection', 'wpcalibrate-user-content-migrator' ); ?></h4>
						<p><?php esc_html_e( 'During rollback, if any post was modified or reassigned by an administrator after the migration completed, the system detects the conflict and preserves manual changes.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Direct Access Links -->
		<div class="wpcalibrate-ucm-card" style="margin-top: 24px;">
			<h2 class="wpcalibrate-ucm-card-title"><?php esc_html_e( 'Quick Navigation', 'wpcalibrate-user-content-migrator' ); ?></h2>
			<div class="wpcalibrate-ucm-quick-nav-grid">
				<a href="<?php echo esc_url( $migrator_url ); ?>" class="wpcalibrate-ucm-quick-link">
					<span class="wpcalibrate-ucm-quick-icon">🚀</span>
					<div>
						<strong><?php esc_html_e( 'Start a New Migration', 'wpcalibrate-user-content-migrator' ); ?></strong>
						<p><?php esc_html_e( 'Launch the 5-step wizard to scan and transfer content.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</a>
				<a href="<?php echo esc_url( $history_url ); ?>" class="wpcalibrate-ucm-quick-link">
					<span class="wpcalibrate-ucm-quick-icon">📜</span>
					<div>
						<strong><?php esc_html_e( 'Migration History & Rollback', 'wpcalibrate-user-content-migrator' ); ?></strong>
						<p><?php esc_html_e( 'Audit past transfers, inspect item ledgers, or revert changes.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</a>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="wpcalibrate-ucm-quick-link">
					<span class="wpcalibrate-ucm-quick-icon">⚙️</span>
					<div>
						<strong><?php esc_html_e( 'Plugin Settings & Support', 'wpcalibrate-user-content-migrator' ); ?></strong>
						<p><?php esc_html_e( 'Adjust batch size, default post statuses, and contact support.', 'wpcalibrate-user-content-migrator' ); ?></p>
					</div>
				</a>
			</div>
		</div>
	</main>

	<?php include __DIR__ . '/partials/footer.php'; ?>
</div>
