<?php
/**
 * Admin Settings Template
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title    = __( 'User Content Migrator Settings', 'wpcalibrate-user-content-migrator' );
$page_subtitle = __( 'Configure processing limits, default migration options, rollback retention, and plugin preferences.', 'wpcalibrate-user-content-migrator' );
?>
<div class="wrap wpcalibrate-ucm-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice notice-success is-dismissible" style="margin: 20px 0;">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpcalibrate-ucm-settings-wrap">
		<!-- Settings Navigation Tabs -->
		<nav class="nav-tab-wrapper wpcalibrate-ucm-settings-tabs" aria-label="<?php esc_attr_e( 'Settings Tabs', 'wpcalibrate-user-content-migrator' ); ?>">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-settings&tab=general' ) ); ?>" class="nav-tab <?php echo ( 'general' === $current_tab ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'General', 'wpcalibrate-user-content-migrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-settings&tab=processing' ) ); ?>" class="nav-tab <?php echo ( 'processing' === $current_tab ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Processing', 'wpcalibrate-user-content-migrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-settings&tab=history' ) ); ?>" class="nav-tab <?php echo ( 'history' === $current_tab ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'History & Retention', 'wpcalibrate-user-content-migrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-settings&tab=license' ) ); ?>" class="nav-tab <?php echo ( 'license' === $current_tab ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'License', 'wpcalibrate-user-content-migrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-settings&tab=support' ) ); ?>" class="nav-tab <?php echo ( 'support' === $current_tab ) ? 'nav-tab-active' : ''; ?>">
				<?php esc_html_e( 'Support', 'wpcalibrate-user-content-migrator' ); ?>
			</a>
		</nav>

		<form method="post" action="" class="wpcalibrate-ucm-form" id="wpcalibrate-ucm-settings-form">
			<?php wp_nonce_field( self::SAVE_NONCE_ACTION, 'wpcalibrate_ucm_settings_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_ucm_save_settings" value="1" />

			<!-- Tab 1: General -->
			<?php if ( 'general' === $current_tab ) : ?>
				<div class="wpcalibrate-ucm-card">
					<h2><?php esc_html_e( 'General Migration Defaults', 'wpcalibrate-user-content-migrator' ); ?></h2>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Default Post Statuses', 'wpcalibrate-user-content-migrator' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><?php esc_html_e( 'Default Post Statuses', 'wpcalibrate-user-content-migrator' ); ?></legend>
									<?php foreach ( $supported_statuses as $slug => $label ) : ?>
										<label style="display: block; margin-bottom: 6px;">
											<input type="checkbox" name="default_statuses[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, (array) ( $settings['default_statuses'] ?? [] ), true ) ); ?> />
											<?php echo esc_html( $label ); ?> (<code><?php echo esc_html( $slug ); ?></code>)
										</label>
									<?php endforeach; ?>
									<p class="description"><?php esc_html_e( 'Statuses checked here will be selected by default when configuring a new migration.', 'wpcalibrate-user-content-migrator' ); ?></p>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Comments Option Availability', 'wpcalibrate-user-content-migrator' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="allow_comments_migration" value="1" <?php checked( ! empty( $settings['allow_comments_migration'] ) ); ?> />
									<?php esc_html_e( 'Enable comments migration checkbox in the migrator wizard', 'wpcalibrate-user-content-migrator' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Controls whether administrators have the option to include comment authorship in migrations.', 'wpcalibrate-user-content-migrator' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Default Comment Transfer State', 'wpcalibrate-user-content-migrator' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="default_comment_transfer" value="1" <?php checked( ! empty( $settings['default_comment_transfer'] ) ); ?> />
									<?php esc_html_e( 'Default comment checkbox to checked (Default: OFF)', 'wpcalibrate-user-content-migrator' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Comment migration alters comment user_id. For safety, this defaults to OFF.', 'wpcalibrate-user-content-migrator' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Data Retention on Uninstall', 'wpcalibrate-user-content-migrator' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="uninstall_delete_data" value="1" <?php checked( ! empty( $settings['uninstall_delete_data'] ) ); ?> />
									<strong class="wpcalibrate-ucm-color-danger"><?php esc_html_e( 'Permanently delete all custom migration tables and settings upon plugin deletion (Default: OFF)', 'wpcalibrate-user-content-migrator' ); ?></strong>
								</label>
								<p class="description"><?php esc_html_e( 'When left unchecked, all historical migration records and rollback ledgers remain safely stored in the database if the plugin is uninstalled.', 'wpcalibrate-user-content-migrator' ); ?></p>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save General Settings', 'wpcalibrate-user-content-migrator' ) ); ?>
				</div>
			<?php endif; ?>

			<!-- Tab 2: Processing -->
			<?php if ( 'processing' === $current_tab ) : ?>
				<div class="wpcalibrate-ucm-card">
					<h2><?php esc_html_e( 'Batch Processing & Concurrency Limits', 'wpcalibrate-user-content-migrator' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Tune server-side batch sizes and lock timeouts according to your server memory and hosting resources.', 'wpcalibrate-user-content-migrator' ); ?></p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="batch_size"><?php esc_html_e( 'Batch Size', 'wpcalibrate-user-content-migrator' ); ?></label></th>
							<td>
								<input type="number" id="batch_size" name="batch_size" min="5" max="100" step="5" value="<?php echo esc_attr( (string) ( $settings['batch_size'] ?? 25 ) ); ?>" class="small-text" />
								<span class="description"><?php esc_html_e( 'records per batch (Default: 25. Range: 5 to 100). Lower values prevent timeouts on shared hosting.', 'wpcalibrate-user-content-migrator' ); ?></span>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="lock_timeout_seconds"><?php esc_html_e( 'Lock Lease Timeout', 'wpcalibrate-user-content-migrator' ); ?></label></th>
							<td>
								<input type="number" id="lock_timeout_seconds" name="lock_timeout_seconds" min="30" max="300" step="10" value="<?php echo esc_attr( (string) ( $settings['lock_timeout_seconds'] ?? 60 ) ); ?>" class="small-text" />
								<span class="description"><?php esc_html_e( 'seconds (Default: 60). Ensures active batches are protected from duplicate concurrent workers.', 'wpcalibrate-user-content-migrator' ); ?></span>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save Processing Settings', 'wpcalibrate-user-content-migrator' ) ); ?>
				</div>
			<?php endif; ?>

			<!-- Tab 3: History & Retention -->
			<?php if ( 'history' === $current_tab ) : ?>
				<div class="wpcalibrate-ucm-card">
					<h2><?php esc_html_e( 'History Retention & Automated Pruning', 'wpcalibrate-user-content-migrator' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Configure how long completed migration reports and rollback ledgers are preserved.', 'wpcalibrate-user-content-migrator' ); ?></p>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="retention_days"><?php esc_html_e( 'Retention Period', 'wpcalibrate-user-content-migrator' ); ?></label></th>
							<td>
								<select id="retention_days" name="retention_days">
									<option value="30" <?php selected( (int) ( $settings['retention_days'] ?? 90 ), 30 ); ?>><?php esc_html_e( '30 Days', 'wpcalibrate-user-content-migrator' ); ?></option>
									<option value="60" <?php selected( (int) ( $settings['retention_days'] ?? 90 ), 60 ); ?>><?php esc_html_e( '60 Days', 'wpcalibrate-user-content-migrator' ); ?></option>
									<option value="90" <?php selected( (int) ( $settings['retention_days'] ?? 90 ), 90 ); ?>><?php esc_html_e( '90 Days (Recommended default)', 'wpcalibrate-user-content-migrator' ); ?></option>
									<option value="180" <?php selected( (int) ( $settings['retention_days'] ?? 90 ), 180 ); ?>><?php esc_html_e( '180 Days', 'wpcalibrate-user-content-migrator' ); ?></option>
									<option value="365" <?php selected( (int) ( $settings['retention_days'] ?? 90 ), 365 ); ?>><?php esc_html_e( '365 Days (1 Year)', 'wpcalibrate-user-content-migrator' ); ?></option>
									<option value="0" <?php selected( (int) ( $settings['retention_days'] ?? 90 ), 0 ); ?>><?php esc_html_e( 'Keep indefinitely (until manually deleted)', 'wpcalibrate-user-content-migrator' ); ?></option>
								</select>
								<p class="description">
									<strong><?php esc_html_e( 'Important Notice:', 'wpcalibrate-user-content-migrator' ); ?></strong>
									<?php esc_html_e( 'Deleting migration records permanently removes the exact per-item rollback ledger. You will not be able to rollback pruned migrations.', 'wpcalibrate-user-content-migrator' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save Retention Settings', 'wpcalibrate-user-content-migrator' ) ); ?>

					<div class="wpcalibrate-ucm-divider"></div>

					<div class="wpcalibrate-ucm-manual-cleanup-box">
						<h3><?php esc_html_e( 'Manual Cleanup', 'wpcalibrate-user-content-migrator' ); ?></h3>
						<p><?php esc_html_e( 'Clean up all terminal completed, rolled back, or failed migrations now. Actively processing or locked migrations will never be pruned.', 'wpcalibrate-user-content-migrator' ); ?></p>
						<button type="button" class="button button-secondary button-danger" id="btn-manual-cleanup">
							<?php esc_html_e( 'Clean Up Completed Migrations Now', 'wpcalibrate-user-content-migrator' ); ?>
						</button>
						<span id="manual-cleanup-status" style="margin-left: 10px;"></span>
					</div>
				</div>
			<?php endif; ?>

			<!-- Tab 4: License -->
			<?php if ( 'license' === $current_tab ) : ?>
				<div class="wpcalibrate-ucm-card">
					<h2><?php esc_html_e( 'Plugin License Configuration', 'wpcalibrate-user-content-migrator' ); ?></h2>

					<div class="wpcalibrate-ucm-license-status-card">
						<span class="wpcalibrate-ucm-badge <?php echo esc_attr( $license_info['class'] ); ?>">
							<?php echo esc_html( $license_info['label'] ); ?>
						</span>
						<p><?php echo esc_html( $license_info['description'] ); ?></p>
					</div>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="license_key"><?php esc_html_e( 'License Key', 'wpcalibrate-user-content-migrator' ); ?></label></th>
							<td>
								<input type="text" id="license_key" name="license_key" value="<?php echo esc_attr( $masked_license_key ); ?>" class="regular-text" placeholder="••••••••••••••••" autocomplete="off" />
								<p class="description">
									<?php esc_html_e( 'Enter your WPCalibrate license key. Keys are safely masked for security.', 'wpcalibrate-user-content-migrator' ); ?>
								</p>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save License Key', 'wpcalibrate-user-content-migrator' ) ); ?>
				</div>
			<?php endif; ?>

			<!-- Tab 5: Support -->
			<?php if ( 'support' === $current_tab ) : ?>
				<div class="wpcalibrate-ucm-card">
					<?php include __DIR__ . '/partials/support.php'; ?>
				</div>
			<?php endif; ?>
		</form>
	</div>

	<?php include __DIR__ . '/partials/footer.php'; ?>
</div>
