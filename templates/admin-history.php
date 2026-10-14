<?php
/**
 * Admin History Template
 *
 * @package WPCalibrate\UserContentMigrator
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_title    = __( 'Migration History & Rollback', 'wpcalibrate-user-content-migrator' );
$page_subtitle = __( 'Audit trail of past content ownership migrations with itemized ledgers and exact rollback capabilities.', 'wpcalibrate-user-content-migrator' );
?>
<div class="wrap wpcalibrate-ucm-wrap">
	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="wpcalibrate-ucm-history-container">
		<!-- Toolbar & Filters -->
		<div class="wpcalibrate-ucm-toolbar">
			<ul class="subsubsub">
				<li class="all">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-history' ) ); ?>" class="<?php echo empty( $status ) ? 'current' : ''; ?>">
						<?php esc_html_e( 'All', 'wpcalibrate-user-content-migrator' ); ?> <span class="count">(<?php echo esc_html( (string) $total_items ); ?>)</span>
					</a> |
				</li>
				<li class="completed">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-history&status=completed' ) ); ?>" class="<?php echo ( 'completed' === $status ) ? 'current' : ''; ?>">
						<?php esc_html_e( 'Completed', 'wpcalibrate-user-content-migrator' ); ?>
					</a> |
				</li>
				<li class="rolled_back">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm-history&status=rolled_back' ) ); ?>" class="<?php echo ( 'rolled_back' === $status ) ? 'current' : ''; ?>">
						<?php esc_html_e( 'Rolled Back', 'wpcalibrate-user-content-migrator' ); ?>
					</a>
				</li>
			</ul>

			<div class="wpcalibrate-ucm-toolbar-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-ucm' ) ); ?>" class="button button-primary">
					<?php esc_html_e( '+ New Migration', 'wpcalibrate-user-content-migrator' ); ?>
				</a>
			</div>
		</div>

		<!-- Migrations Table -->
		<div class="wpcalibrate-ucm-table-responsive">
			<table class="wp-list-table widefat fixed striped table-view-list" id="wpcalibrate-ucm-history-table">
				<thead>
					<tr>
						<th scope="col" style="width: 70px;"><?php esc_html_e( 'ID', 'wpcalibrate-user-content-migrator' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Source User', 'wpcalibrate-user-content-migrator' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Destination User', 'wpcalibrate-user-content-migrator' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date', 'wpcalibrate-user-content-migrator' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Items', 'wpcalibrate-user-content-migrator' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'wpcalibrate-user-content-migrator' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Verification', 'wpcalibrate-user-content-migrator' ); ?></th>
						<th scope="col" style="width: 220px;"><?php esc_html_e( 'Actions', 'wpcalibrate-user-content-migrator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $migrations ) ) : ?>
						<tr>
							<td colspan="8" class="wpcalibrate-ucm-empty-td">
								<p><?php esc_html_e( 'No migration history records found.', 'wpcalibrate-user-content-migrator' ); ?></p>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $migrations as $m ) : ?>
							<?php
							$source_user = get_userdata( (int) $m->source_user_id );
							$dest_user   = get_userdata( (int) $m->dest_user_id );
							$is_locked   = \WPCalibrate\UserContentMigrator\Services\LockManager::is_locked( (int) $m->id );
							$status_cls  = 'wpcalibrate-ucm-status-' . sanitize_html_class( $m->status );
							?>
							<tr data-migration-id="<?php echo esc_attr( (string) $m->id ); ?>">
								<td>
									<strong>#<?php echo esc_html( (string) $m->id ); ?></strong>
									<span class="screen-reader-text"><?php echo esc_html( $m->uuid ); ?></span>
								</td>
								<td>
									<?php if ( $source_user ) : ?>
										<strong><?php echo esc_html( $source_user->display_name ); ?></strong>
										<br><small><code><?php echo esc_html( $source_user->user_login ); ?></code> (#<?php echo esc_html( (string) $m->source_user_id ); ?>)</small>
									<?php else : ?>
										<em><?php echo esc_html( sprintf( __( 'User #%d (deleted)', 'wpcalibrate-user-content-migrator' ), (int) $m->source_user_id ) ); ?></em>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $dest_user ) : ?>
										<strong><?php echo esc_html( $dest_user->display_name ); ?></strong>
										<br><small><code><?php echo esc_html( $dest_user->user_login ); ?></code> (#<?php echo esc_html( (string) $m->dest_user_id ); ?>)</small>
									<?php else : ?>
										<em><?php echo esc_html( sprintf( __( 'User #%d (deleted)', 'wpcalibrate-user-content-migrator' ), (int) $m->dest_user_id ) ); ?></em>
									<?php endif; ?>
								</td>
								<td>
									<?php echo esc_html( $m->created_at ); ?>
								</td>
								<td>
									<div><?php printf( esc_html__( '%d planned', 'wpcalibrate-user-content-migrator' ), (int) $m->total_planned ); ?></div>
									<small>
										<span class="wpcalibrate-ucm-color-success"><?php printf( esc_html__( '%d migrated', 'wpcalibrate-user-content-migrator' ), (int) $m->total_migrated ); ?></span>
										<?php if ( (int) $m->total_rolled_back > 0 ) : ?>
											&bull; <span class="wpcalibrate-ucm-color-info"><?php printf( esc_html__( '%d rolled back', 'wpcalibrate-user-content-migrator' ), (int) $m->total_rolled_back ); ?></span>
										<?php endif; ?>
										<?php if ( (int) $m->total_failed > 0 ) : ?>
											&bull; <span class="wpcalibrate-ucm-color-danger"><?php printf( esc_html__( '%d failed', 'wpcalibrate-user-content-migrator' ), (int) $m->total_failed ); ?></span>
										<?php endif; ?>
									</small>
								</td>
								<td>
									<span class="wpcalibrate-ucm-badge <?php echo esc_attr( $status_cls ); ?>">
										<?php echo esc_html( str_replace( '_', ' ', $m->status ) ); ?>
									</span>
									<?php if ( $is_locked ) : ?>
										<span class="dashicons dashicons-lock" title="<?php esc_attr_e( 'Actively locked by worker', 'wpcalibrate-user-content-migrator' ); ?>"></span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( 'verified' === $m->verification_status ) : ?>
										<span class="wpcalibrate-ucm-badge wpcalibrate-ucm-badge-success">✓ <?php esc_html_e( 'Verified', 'wpcalibrate-user-content-migrator' ); ?></span>
									<?php elseif ( 'mismatch' === $m->verification_status ) : ?>
										<span class="wpcalibrate-ucm-badge wpcalibrate-ucm-badge-danger">⚠ <?php esc_html_e( 'Mismatch', 'wpcalibrate-user-content-migrator' ); ?></span>
									<?php else : ?>
										<span class="wpcalibrate-ucm-badge wpcalibrate-ucm-badge-neutral"><?php esc_html_e( 'Unverified', 'wpcalibrate-user-content-migrator' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<div class="wpcalibrate-ucm-row-actions">
										<button type="button" class="button button-small btn-view-ledger" data-id="<?php echo esc_attr( (string) $m->id ); ?>">
											<?php esc_html_e( 'Ledger', 'wpcalibrate-user-content-migrator' ); ?>
										</button>

										<?php if ( in_array( $m->status, [ 'completed', 'completed_with_errors' ], true ) && (int) $m->total_migrated > 0 ) : ?>
											<button type="button" class="button button-small button-danger btn-history-rollback" data-id="<?php echo esc_attr( (string) $m->id ); ?>">
												<?php esc_html_e( 'Rollback', 'wpcalibrate-user-content-migrator' ); ?>
											</button>
										<?php endif; ?>

										<?php if ( $is_locked ) : ?>
											<button type="button" class="button button-small btn-force-unlock" data-id="<?php echo esc_attr( (string) $m->id ); ?>">
												<?php esc_html_e( 'Unlock', 'wpcalibrate-user-content-migrator' ); ?>
											</button>
										<?php else : ?>
											<button type="button" class="button button-small button-link-delete btn-delete-migration" data-id="<?php echo esc_attr( (string) $m->id ); ?>">
												<?php esc_html_e( 'Delete', 'wpcalibrate-user-content-migrator' ); ?>
											</button>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- Pagination -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					echo paginate_links(
						[
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => __( '&laquo; Prev', 'wpcalibrate-user-content-migrator' ),
							'next_text' => __( 'Next &raquo;', 'wpcalibrate-user-content-migrator' ),
							'total'     => $total_pages,
							'current'   => $page,
						]
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<!-- Ledger Detail Drawer/Modal -->
	<div class="wpcalibrate-ucm-modal-backdrop" id="ledger-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ledger-modal-title">
		<div class="wpcalibrate-ucm-modal wpcalibrate-ucm-modal-large">
			<div class="wpcalibrate-ucm-modal-header">
				<h2 id="ledger-modal-title"><?php esc_html_e( 'Migration Item Ledger', 'wpcalibrate-user-content-migrator' ); ?></h2>
				<button type="button" class="wpcalibrate-ucm-modal-close" id="ledger-modal-close-btn">&times;</button>
			</div>
			<div class="wpcalibrate-ucm-modal-body">
				<div class="wpcalibrate-ucm-table-responsive">
					<table class="wp-list-table widefat fixed striped" id="ledger-items-table">
						<thead>
							<tr>
								<th style="width: 80px;"><?php esc_html_e( 'Obj ID', 'wpcalibrate-user-content-migrator' ); ?></th>
								<th><?php esc_html_e( 'Type', 'wpcalibrate-user-content-migrator' ); ?></th>
								<th><?php esc_html_e( 'Original Owner', 'wpcalibrate-user-content-migrator' ); ?></th>
								<th><?php esc_html_e( 'New Owner', 'wpcalibrate-user-content-migrator' ); ?></th>
								<th><?php esc_html_e( 'Migration Status', 'wpcalibrate-user-content-migrator' ); ?></th>
								<th><?php esc_html_e( 'Rollback Status', 'wpcalibrate-user-content-migrator' ); ?></th>
								<th><?php esc_html_e( 'Details', 'wpcalibrate-user-content-migrator' ); ?></th>
							</tr>
						</thead>
						<tbody id="ledger-items-tbody">
							<tr><td colspan="7"><?php esc_html_e( 'Loading items...', 'wpcalibrate-user-content-migrator' ); ?></td></tr>
						</tbody>
					</table>
				</div>
				<div class="wpcalibrate-ucm-pagination" id="ledger-table-pagination"></div>
			</div>
			<div class="wpcalibrate-ucm-modal-footer">
				<button type="button" class="button" id="ledger-modal-dismiss-btn"><?php esc_html_e( 'Close', 'wpcalibrate-user-content-migrator' ); ?></button>
			</div>
		</div>
	</div>

	<?php include __DIR__ . '/partials/footer.php'; ?>
</div>
