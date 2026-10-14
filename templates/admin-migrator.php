<?php
/**
 * Admin Migrator Template
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title    = __( 'User Content Migrator', 'wpcalibrate-user-content-migrator' );
$page_subtitle = __( 'Safely reassign ownership of posts, pages, custom post types, WooCommerce products, and media between WordPress users.', 'wpcalibrate-user-content-migrator' );
?>
<div class="wrap wpcalibrate-ucm-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<!-- Step Navigation Indicator -->
	<nav class="wpcalibrate-ucm-wizard-steps" aria-label="<?php esc_attr_e( 'Migration wizard progress', 'wpcalibrate-user-content-migrator' ); ?>">
		<ol class="wpcalibrate-ucm-steps-list">
			<li class="wpcalibrate-ucm-step is-active" data-step="1">
				<span class="wpcalibrate-ucm-step-num">1</span>
				<span class="wpcalibrate-ucm-step-label"><?php esc_html_e( 'Select Users', 'wpcalibrate-user-content-migrator' ); ?></span>
			</li>
			<li class="wpcalibrate-ucm-step" data-step="2">
				<span class="wpcalibrate-ucm-step-num">2</span>
				<span class="wpcalibrate-ucm-step-label"><?php esc_html_e( 'Content & Scope', 'wpcalibrate-user-content-migrator' ); ?></span>
			</li>
			<li class="wpcalibrate-ucm-step" data-step="3">
				<span class="wpcalibrate-ucm-step-num">3</span>
				<span class="wpcalibrate-ucm-step-label"><?php esc_html_e( 'Filters & Options', 'wpcalibrate-user-content-migrator' ); ?></span>
			</li>
			<li class="wpcalibrate-ucm-step" data-step="4">
				<span class="wpcalibrate-ucm-step-num">4</span>
				<span class="wpcalibrate-ucm-step-label"><?php esc_html_e( 'Scan & Preview', 'wpcalibrate-user-content-migrator' ); ?></span>
			</li>
			<li class="wpcalibrate-ucm-step" data-step="5">
				<span class="wpcalibrate-ucm-step-num">5</span>
				<span class="wpcalibrate-ucm-step-label"><?php esc_html_e( 'Execute & Verify', 'wpcalibrate-user-content-migrator' ); ?></span>
			</li>
		</ol>
	</nav>

	<main class="wpcalibrate-ucm-main-content">
		<!-- Step 1: Users Selection -->
		<section class="wpcalibrate-ucm-panel is-active" id="wpcalibrate-ucm-step-1" aria-labelledby="heading-step-1">
			<div class="wpcalibrate-ucm-card">
				<h2 id="heading-step-1" class="wpcalibrate-ucm-card-title"><?php esc_html_e( 'Step 1: Select Source and Destination Users', 'wpcalibrate-user-content-migrator' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Search and choose the user whose content will be transferred (Source) and the user who will become the new owner (Destination). Content will NOT be deleted.', 'wpcalibrate-user-content-migrator' ); ?></p>

				<div class="wpcalibrate-ucm-users-grid">
					<!-- Source User Selector -->
					<div class="wpcalibrate-ucm-user-box" id="source-user-box">
						<label for="source-user-search" class="wpcalibrate-ucm-label">
							<strong><?php esc_html_e( 'Source User (Current Owner)', 'wpcalibrate-user-content-migrator' ); ?></strong>
							<span class="required">*</span>
						</label>
						<div class="wpcalibrate-ucm-search-input-wrap">
							<input type="text" id="source-user-search" class="regular-text wpcalibrate-ucm-user-input" placeholder="<?php esc_attr_e( 'Search by name, username, or email...', 'wpcalibrate-user-content-migrator' ); ?>" autocomplete="off" />
							<div class="wpcalibrate-ucm-search-dropdown" id="source-user-dropdown" role="listbox" aria-hidden="true"></div>
						</div>
						<input type="hidden" id="selected-source-user-id" value="0" />

						<div class="wpcalibrate-ucm-selected-user-card" id="source-user-selected" style="display: none;">
							<img src="" alt="" class="wpcalibrate-ucm-user-avatar" id="source-user-avatar" width="48" height="48" />
							<div class="wpcalibrate-ucm-user-info">
								<strong id="source-user-name"></strong>
								<span class="wpcalibrate-ucm-user-login" id="source-user-login"></span>
								<div class="wpcalibrate-ucm-author-counts" id="source-author-counts"></div>
							</div>
							<button type="button" class="button-link wpcalibrate-ucm-change-user" id="change-source-user" aria-label="<?php esc_attr_e( 'Change source user', 'wpcalibrate-user-content-migrator' ); ?>">&times;</button>
						</div>
					</div>

					<!-- Destination User Selector -->
					<div class="wpcalibrate-ucm-user-box" id="dest-user-box">
						<label for="dest-user-search" class="wpcalibrate-ucm-label">
							<strong><?php esc_html_e( 'Destination User (New Owner)', 'wpcalibrate-user-content-migrator' ); ?></strong>
							<span class="required">*</span>
						</label>
						<div class="wpcalibrate-ucm-search-input-wrap">
							<input type="text" id="dest-user-search" class="regular-text wpcalibrate-ucm-user-input" placeholder="<?php esc_attr_e( 'Search by name, username, or email...', 'wpcalibrate-user-content-migrator' ); ?>" autocomplete="off" />
							<div class="wpcalibrate-ucm-search-dropdown" id="dest-user-dropdown" role="listbox" aria-hidden="true"></div>
						</div>
						<input type="hidden" id="selected-dest-user-id" value="0" />

						<div class="wpcalibrate-ucm-selected-user-card" id="dest-user-selected" style="display: none;">
							<img src="" alt="" class="wpcalibrate-ucm-user-avatar" id="dest-user-avatar" width="48" height="48" />
							<div class="wpcalibrate-ucm-user-info">
								<strong id="dest-user-name"></strong>
								<span class="wpcalibrate-ucm-user-login" id="dest-user-login"></span>
							</div>
							<button type="button" class="button-link wpcalibrate-ucm-change-user" id="change-dest-user" aria-label="<?php esc_attr_e( 'Change destination user', 'wpcalibrate-user-content-migrator' ); ?>">&times;</button>
						</div>
					</div>
				</div>

				<div class="wpcalibrate-ucm-step-actions">
					<button type="button" class="button button-primary button-hero" id="btn-goto-step-2" disabled>
						<?php esc_html_e( 'Continue to Content Selection &rarr;', 'wpcalibrate-user-content-migrator' ); ?>
					</button>
				</div>
			</div>
		</section>

		<!-- Step 2: Content Selection -->
		<section class="wpcalibrate-ucm-panel" id="wpcalibrate-ucm-step-2" aria-labelledby="heading-step-2">
			<div class="wpcalibrate-ucm-card">
				<h2 id="heading-step-2" class="wpcalibrate-ucm-card-title"><?php esc_html_e( 'Step 2: Choose Eligible Content Types', 'wpcalibrate-user-content-migrator' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Select which authored post types to include in this migration. Internal WordPress types, sensitive data, and WooCommerce orders are strictly protected and excluded.', 'wpcalibrate-user-content-migrator' ); ?></p>

				<fieldset class="wpcalibrate-ucm-fieldset">
					<legend class="screen-reader-text"><?php esc_html_e( 'Eligible Content Types', 'wpcalibrate-user-content-migrator' ); ?></legend>
					<div class="wpcalibrate-ucm-type-checkboxes">
						<?php foreach ( $eligible_post_types as $slug => $meta ) : ?>
							<?php if ( 'attachment' === $slug ) continue; ?>
							<label class="wpcalibrate-ucm-checkbox-card">
								<input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $slug ); ?>" class="wpcalibrate-ucm-post-type-cb" checked />
								<div class="wpcalibrate-ucm-checkbox-label">
									<strong><?php echo esc_html( $meta['label'] ); ?></strong>
									<code><?php echo esc_html( $slug ); ?></code>
									<?php if ( ! empty( $meta['is_product'] ) ) : ?>
										<span class="wpcalibrate-ucm-pill wpcalibrate-ucm-pill-woo"><?php esc_html_e( 'WooCommerce Product', 'wpcalibrate-user-content-migrator' ); ?></span>
									<?php endif; ?>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<div class="wpcalibrate-ucm-divider"></div>

				<!-- Media Attachments -->
				<div class="wpcalibrate-ucm-special-options">
					<label class="wpcalibrate-ucm-checkbox-card wpcalibrate-ucm-media-card">
						<input type="checkbox" id="include_media" name="include_media" value="1" checked />
						<div class="wpcalibrate-ucm-checkbox-label">
							<strong><?php esc_html_e( 'Media Library Attachments', 'wpcalibrate-user-content-migrator' ); ?></strong>
							<p class="description"><?php esc_html_e( 'Reassigns attachment authorship without moving files, changing upload directories, rewriting URLs, or altering metadata.', 'wpcalibrate-user-content-migrator' ); ?></p>
						</div>
					</label>

					<!-- Optional Comments -->
					<?php if ( $comments_allowed ) : ?>
						<label class="wpcalibrate-ucm-checkbox-card wpcalibrate-ucm-comments-card">
							<input type="checkbox" id="include_comments" name="include_comments" value="1" <?php checked( $default_comments ); ?> />
							<div class="wpcalibrate-ucm-checkbox-label">
								<strong><?php esc_html_e( 'User Comments (Optional)', 'wpcalibrate-user-content-migrator' ); ?></strong>
								<p class="description"><?php esc_html_e( 'Transfers the user_id on comments authored by the source user to the destination user. Comment author name, email, IP, and content remain unchanged.', 'wpcalibrate-user-content-migrator' ); ?></p>
							</div>
						</label>
					<?php endif; ?>
				</div>

				<div class="wpcalibrate-ucm-step-actions">
					<button type="button" class="button" data-goto-step="1">&larr; <?php esc_html_e( 'Back', 'wpcalibrate-user-content-migrator' ); ?></button>
					<button type="button" class="button button-primary" id="btn-goto-step-3"><?php esc_html_e( 'Next: Filters & Options &rarr;', 'wpcalibrate-user-content-migrator' ); ?></button>
				</div>
			</div>
		</section>

		<!-- Step 3: Filters and Options -->
		<section class="wpcalibrate-ucm-panel" id="wpcalibrate-ucm-step-3" aria-labelledby="heading-step-3">
			<div class="wpcalibrate-ucm-card">
				<h2 id="heading-step-3" class="wpcalibrate-ucm-card-title"><?php esc_html_e( 'Step 3: Filters & Selection Mode', 'wpcalibrate-user-content-migrator' ); ?></h2>

				<div class="wpcalibrate-ucm-grid-2col">
					<!-- Statuses -->
					<div class="wpcalibrate-ucm-filter-section">
						<label class="wpcalibrate-ucm-label"><strong><?php esc_html_e( 'Post Statuses to Include', 'wpcalibrate-user-content-migrator' ); ?></strong></label>
						<div class="wpcalibrate-ucm-statuses-list">
							<?php foreach ( $supported_statuses as $st_slug => $st_label ) : ?>
								<label class="wpcalibrate-ucm-status-check">
									<input type="checkbox" name="statuses[]" value="<?php echo esc_attr( $st_slug ); ?>" <?php checked( in_array( $st_slug, (array) $default_statuses, true ) ); ?> />
									<span><?php echo esc_html( $st_label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Date Range -->
					<div class="wpcalibrate-ucm-filter-section">
						<label class="wpcalibrate-ucm-label"><strong><?php esc_html_e( 'Date Range (Optional)', 'wpcalibrate-user-content-migrator' ); ?></strong></label>
						<div class="wpcalibrate-ucm-dates-row">
							<div>
								<label for="date_from" class="screen-reader-text"><?php esc_html_e( 'Date From', 'wpcalibrate-user-content-migrator' ); ?></label>
								<input type="date" id="date_from" name="date_from" class="regular-text" />
								<small class="description"><?php esc_html_e( 'From date (optional)', 'wpcalibrate-user-content-migrator' ); ?></small>
							</div>
							<div>
								<label for="date_to" class="screen-reader-text"><?php esc_html_e( 'Date To', 'wpcalibrate-user-content-migrator' ); ?></label>
								<input type="date" id="date_to" name="date_to" class="regular-text" />
								<small class="description"><?php esc_html_e( 'To date (optional)', 'wpcalibrate-user-content-migrator' ); ?></small>
							</div>
						</div>
					</div>
				</div>

				<div class="wpcalibrate-ucm-divider"></div>

				<!-- Selection Mode -->
				<div class="wpcalibrate-ucm-mode-selector">
					<label class="wpcalibrate-ucm-label"><strong><?php esc_html_e( 'Migration Scope Mode', 'wpcalibrate-user-content-migrator' ); ?></strong></label>
					<div class="wpcalibrate-ucm-radio-group">
						<label class="wpcalibrate-ucm-radio-card">
							<input type="radio" name="selection_mode" value="all" checked />
							<div class="wpcalibrate-ucm-radio-label">
								<strong><?php esc_html_e( 'Migrate All Matching Content', 'wpcalibrate-user-content-migrator' ); ?></strong>
								<p class="description"><?php esc_html_e( 'Reassign all objects matching the selected post types, statuses, and date range.', 'wpcalibrate-user-content-migrator' ); ?></p>
							</div>
						</label>

						<label class="wpcalibrate-ucm-radio-card">
							<input type="radio" name="selection_mode" value="individual" />
							<div class="wpcalibrate-ucm-radio-label">
								<strong><?php esc_html_e( 'Select Individual Records', 'wpcalibrate-user-content-migrator' ); ?></strong>
								<p class="description"><?php esc_html_e( 'Browse a paginated table of matching items to pick specific posts, pages, or media items manually.', 'wpcalibrate-user-content-migrator' ); ?></p>
							</div>
						</label>
					</div>
				</div>

				<div class="wpcalibrate-ucm-step-actions">
					<button type="button" class="button" data-goto-step="2">&larr; <?php esc_html_e( 'Back', 'wpcalibrate-user-content-migrator' ); ?></button>
					<button type="button" class="button button-primary" id="btn-goto-step-4"><?php esc_html_e( 'Scan Content &rarr;', 'wpcalibrate-user-content-migrator' ); ?></button>
				</div>
			</div>
		</section>

		<!-- Step 4: Scan and Preview -->
		<section class="wpcalibrate-ucm-panel" id="wpcalibrate-ucm-step-4" aria-labelledby="heading-step-4">
			<div class="wpcalibrate-ucm-card">
				<h2 id="heading-step-4" class="wpcalibrate-ucm-card-title"><?php esc_html_e( 'Step 4: Read-Only Content Scan & Preview', 'wpcalibrate-user-content-migrator' ); ?></h2>
				<p class="description"><?php esc_html_e( 'This scan inspects the database without modifying any content. Review the exact counts and items that will be queued for ownership transfer.', 'wpcalibrate-user-content-migrator' ); ?></p>

				<!-- Individual Table (if individual mode active) -->
				<div id="wpcalibrate-ucm-individual-selection-container" style="display: none; margin-bottom: 25px;">
					<div class="wpcalibrate-ucm-table-toolbar">
						<input type="search" id="individual-table-search" placeholder="<?php esc_attr_e( 'Filter items by title...', 'wpcalibrate-user-content-migrator' ); ?>" class="regular-text" />
						<div class="wpcalibrate-ucm-table-info">
							<span id="individual-selected-count">0</span> <?php esc_html_e( 'items selected', 'wpcalibrate-user-content-migrator' ); ?>
						</div>
					</div>
					<div class="wpcalibrate-ucm-table-responsive">
						<table class="wp-list-table widefat fixed striped" id="individual-records-table">
							<thead>
								<tr>
									<td class="manage-column column-cb check-column">
										<input type="checkbox" id="individual-check-all" />
									</td>
									<th scope="col"><?php esc_html_e( 'Title', 'wpcalibrate-user-content-migrator' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Type', 'wpcalibrate-user-content-migrator' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Status', 'wpcalibrate-user-content-migrator' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Date', 'wpcalibrate-user-content-migrator' ); ?></th>
									<th scope="col" style="width: 80px;"><?php esc_html_e( 'ID', 'wpcalibrate-user-content-migrator' ); ?></th>
								</tr>
							</thead>
							<tbody id="individual-records-tbody">
								<tr><td colspan="6"><?php esc_html_e( 'Loading records...', 'wpcalibrate-user-content-migrator' ); ?></td></tr>
							</tbody>
						</table>
					</div>
					<div class="wpcalibrate-ucm-pagination" id="individual-table-pagination"></div>
				</div>

				<!-- Scan Summary Cards -->
				<div class="wpcalibrate-ucm-scan-results" id="scan-results-container">
					<div class="wpcalibrate-ucm-stat-cards">
						<div class="wpcalibrate-ucm-stat-card wpcalibrate-ucm-stat-total">
							<span class="wpcalibrate-ucm-stat-value" id="scan-total-count">0</span>
							<span class="wpcalibrate-ucm-stat-label"><?php esc_html_e( 'Total Objects to Migrate', 'wpcalibrate-user-content-migrator' ); ?></span>
						</div>
						<div class="wpcalibrate-ucm-stat-card">
							<span class="wpcalibrate-ucm-stat-value" id="scan-posts-count">0</span>
							<span class="wpcalibrate-ucm-stat-label"><?php esc_html_e( 'Authored Posts/Pages', 'wpcalibrate-user-content-migrator' ); ?></span>
						</div>
						<div class="wpcalibrate-ucm-stat-card">
							<span class="wpcalibrate-ucm-stat-value" id="scan-media-count">0</span>
							<span class="wpcalibrate-ucm-stat-label"><?php esc_html_e( 'Media Attachments', 'wpcalibrate-user-content-migrator' ); ?></span>
						</div>
						<div class="wpcalibrate-ucm-stat-card">
							<span class="wpcalibrate-ucm-stat-value" id="scan-comments-count">0</span>
							<span class="wpcalibrate-ucm-stat-label"><?php esc_html_e( 'Authored Comments', 'wpcalibrate-user-content-migrator' ); ?></span>
						</div>
					</div>

					<!-- Detailed breakdown -->
					<div class="wpcalibrate-ucm-breakdown-box" id="scan-breakdown-box">
						<h3><?php esc_html_e( 'Breakdown by Post Type & Status', 'wpcalibrate-user-content-migrator' ); ?></h3>
						<div class="wpcalibrate-ucm-breakdown-lists">
							<div id="scan-types-breakdown"></div>
							<div id="scan-statuses-breakdown"></div>
						</div>
					</div>

					<!-- Warnings if any -->
					<div class="wpcalibrate-ucm-notice wpcalibrate-ucm-notice-warning" id="scan-warnings-box" style="display: none;"></div>

					<div class="wpcalibrate-ucm-rollback-notice">
						<span class="dashicons dashicons-backup"></span>
						<div>
							<strong><?php esc_html_e( 'Exact Rollback Ledger Enabled', 'wpcalibrate-user-content-migrator' ); ?></strong>
							<p><?php esc_html_e( 'Every transferred object will be individually recorded in an immutable ledger. You can safely rollback this migration at any time, restoring original owners without affecting other content.', 'wpcalibrate-user-content-migrator' ); ?></p>
						</div>
					</div>
				</div>

				<div class="wpcalibrate-ucm-step-actions">
					<button type="button" class="button" data-goto-step="3">&larr; <?php esc_html_e( 'Back', 'wpcalibrate-user-content-migrator' ); ?></button>
					<button type="button" class="button button-primary button-hero" id="btn-open-confirm-modal" disabled>
						<?php esc_html_e( 'Confirm & Start Migration &rarr;', 'wpcalibrate-user-content-migrator' ); ?>
					</button>
				</div>
			</div>
		</section>

		<!-- Step 5: Execution, Progress & Verification -->
		<section class="wpcalibrate-ucm-panel" id="wpcalibrate-ucm-step-5" aria-labelledby="heading-step-5">
			<div class="wpcalibrate-ucm-card">
				<h2 id="heading-step-5" class="wpcalibrate-ucm-card-title"><?php esc_html_e( 'Step 5: Migration Execution & Integrity Verification', 'wpcalibrate-user-content-migrator' ); ?></h2>

				<!-- Live Progress Section -->
				<div class="wpcalibrate-ucm-progress-container" id="migration-progress-wrap">
					<div class="wpcalibrate-ucm-progress-header">
						<span class="wpcalibrate-ucm-progress-title" id="progress-status-text" aria-live="polite"><?php esc_html_e( 'Initializing migration...', 'wpcalibrate-user-content-migrator' ); ?></span>
						<span class="wpcalibrate-ucm-progress-percent" id="progress-percent-text">0%</span>
					</div>
					<div class="wpcalibrate-ucm-progress-bar-track" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progress-bar-element">
						<div class="wpcalibrate-ucm-progress-bar-fill" id="progress-bar-fill" style="width: 0%;"></div>
					</div>

					<div class="wpcalibrate-ucm-progress-counters">
						<div><strong><?php esc_html_e( 'Planned:', 'wpcalibrate-user-content-migrator' ); ?></strong> <span id="counter-planned">0</span></div>
						<div><strong><?php esc_html_e( 'Migrated:', 'wpcalibrate-user-content-migrator' ); ?></strong> <span id="counter-migrated">0</span></div>
						<div><strong><?php esc_html_e( 'Failed:', 'wpcalibrate-user-content-migrator' ); ?></strong> <span id="counter-failed">0</span></div>
						<div><strong><?php esc_html_e( 'Skipped:', 'wpcalibrate-user-content-migrator' ); ?></strong> <span id="counter-skipped">0</span></div>
					</div>

					<div class="wpcalibrate-ucm-control-actions" id="migration-controls">
						<button type="button" class="button" id="btn-cancel-migration"><?php esc_html_e( 'Cancel Migration', 'wpcalibrate-user-content-migrator' ); ?></button>
					</div>
				</div>

				<!-- Verification & Final Report Card -->
				<div class="wpcalibrate-ucm-final-report" id="migration-final-report" style="display: none;">
					<div class="wpcalibrate-ucm-report-badge-wrap" id="report-badge-wrap">
						<!-- Populated via JS: success or error banner -->
					</div>

					<div class="wpcalibrate-ucm-verification-box" id="verification-results-box">
						<h3><?php esc_html_e( 'Database Integrity Verification', 'wpcalibrate-user-content-migrator' ); ?></h3>
						<p id="verification-status-desc"></p>
						<div id="verification-mismatches-list" style="display: none;"></div>
					</div>

					<div class="wpcalibrate-ucm-final-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-history' ) ); ?>" class="button button-secondary">
							<?php esc_html_e( 'View Migration History', 'wpcalibrate-user-content-migrator' ); ?>
						</a>
						<button type="button" class="button button-secondary button-danger" id="btn-rollback-now">
							<?php esc_html_e( 'Rollback This Migration', 'wpcalibrate-user-content-migrator' ); ?>
						</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Start New Migration', 'wpcalibrate-user-content-migrator' ); ?>
						</a>
					</div>
				</div>
			</div>
		</section>
	</main>

	<!-- Confirmation Modal -->
	<div class="wpcalibrate-ucm-modal-backdrop" id="confirmation-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title">
		<div class="wpcalibrate-ucm-modal">
			<div class="wpcalibrate-ucm-modal-header">
				<h2 id="confirm-modal-title"><?php esc_html_e( 'Confirm Ownership Transfer', 'wpcalibrate-user-content-migrator' ); ?></h2>
				<button type="button" class="wpcalibrate-ucm-modal-close" id="modal-close-btn" aria-label="<?php esc_attr_e( 'Close dialog', 'wpcalibrate-user-content-migrator' ); ?>">&times;</button>
			</div>
			<div class="wpcalibrate-ucm-modal-body">
				<p class="wpcalibrate-ucm-modal-alert">
					<?php
					printf(
						/* translators: 1: count placeholder, 2: source user, 3: destination user */
						esc_html__( 'You are about to transfer ownership of %1$s items from %2$s to %3$s.', 'wpcalibrate-user-content-migrator' ),
						'<strong id="modal-item-count">0</strong>',
						'<strong id="modal-source-name"></strong>',
						'<strong id="modal-dest-name"></strong>'
					);
					?>
				</p>
				<ul class="wpcalibrate-ucm-modal-points">
					<li>✓ <?php esc_html_e( 'Content itself will NOT be deleted or altered.', 'wpcalibrate-user-content-migrator' ); ?></li>
					<li>✓ <?php esc_html_e( 'Post titles, dates, slugs, statuses, taxonomies, and meta are strictly preserved.', 'wpcalibrate-user-content-migrator' ); ?></li>
					<li>✓ <?php esc_html_e( 'Every item is recorded in an exact rollback ledger.', 'wpcalibrate-user-content-migrator' ); ?></li>
					<li>✓ <?php esc_html_e( 'Automated verification will inspect database integrity after transfer.', 'wpcalibrate-user-content-migrator' ); ?></li>
				</ul>
			</div>
			<div class="wpcalibrate-ucm-modal-footer">
				<button type="button" class="button" id="modal-cancel-btn"><?php esc_html_e( 'Cancel', 'wpcalibrate-user-content-migrator' ); ?></button>
				<button type="button" class="button button-primary button-hero" id="modal-execute-btn"><?php esc_html_e( 'Confirm & Start Migration', 'wpcalibrate-user-content-migrator' ); ?></button>
			</div>
		</div>
	</div>

	<?php include __DIR__ . '/partials/footer.php'; ?>
</div>
