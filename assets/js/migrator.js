/**
 * WPCalibrate User Content Migrator - Client Controller
 *
 * @package WPCalibrate\UserContentMigrator
 */

(function ($) {
	'use strict';

	const UCM = {
		state: {
			currentStep: 1,
			sourceUser: null,
			destUser: null,
			criteria: {},
			scanResults: null,
			selectedIndividualIds: [],
			activeMigrationId: 0,
			lockToken: '',
			isProcessing: false,
			isCancelled: false,
		},

		init: function () {
			this.bindUserSearch();
			this.bindStepNavigation();
			this.bindScanAction();
			this.bindIndividualSelection();
			this.bindModalActions();
			this.bindHistoryActions();
			this.bindSettingsActions();
		},

		// 1. User Search & Selection
		bindUserSearch: function () {
			const self = this;
			let sourceDebounce, destDebounce;

			// Source search
			$('#source-user-search').on('input', function () {
				const term = $(this).val().trim();
				clearTimeout(sourceDebounce);
				if (term.length < 1) {
					$('#source-user-dropdown').hide().empty();
					return;
				}
				sourceDebounce = setTimeout(function () {
					self.searchUsers(term, 0, function (users) {
						self.renderUserDropdown('#source-user-dropdown', users, 'source');
					});
				}, 250);
			});

			// Dest search
			$('#dest-user-search').on('input', function () {
				const term = $(this).val().trim();
				clearTimeout(destDebounce);
				if (term.length < 1) {
					$('#dest-user-dropdown').hide().empty();
					return;
				}
				const excludeId = self.state.sourceUser ? self.state.sourceUser.id : 0;
				destDebounce = setTimeout(function () {
					self.searchUsers(term, excludeId, function (users) {
						self.renderUserDropdown('#dest-user-dropdown', users, 'dest');
					});
				}, 250);
			});

			// Change user handlers
			$('#change-source-user').on('click', function () {
				self.state.sourceUser = null;
				$('#selected-source-user-id').val(0);
				$('#source-user-selected').hide();
				$('#source-user-search').val('').show().focus();
				self.validateStep1();
			});

			$('#change-dest-user').on('click', function () {
				self.state.destUser = null;
				$('#selected-dest-user-id').val(0);
				$('#dest-user-selected').hide();
				$('#dest-user-search').val('').show().focus();
				self.validateStep1();
			});

			// Click outside to close dropdowns
			$(document).on('click', function (e) {
				if (!$(e.target).closest('.wpcalibrate-ucm-search-input-wrap').length) {
					$('.wpcalibrate-ucm-search-dropdown').hide();
				}
			});
		},

		searchUsers: function (term, excludeId, callback) {
			$.post(wpcalibrateUcm.ajaxUrl, {
				action: 'wpcalibrate_ucm_search_users',
				nonce: wpcalibrateUcm.nonce,
				term: term,
				exclude: excludeId,
			}).done(function (res) {
				if (res.success && res.data && res.data.users) {
					callback(res.data.users);
				}
			});
		},

		renderUserDropdown: function (selector, users, role) {
			const self = this;
			const $dropdown = $(selector);
			$dropdown.empty();

			if (!users.length) {
				$dropdown.append('<div class="wpcalibrate-ucm-search-item"><em>No users found</em></div>').show();
				return;
			}

			users.forEach(function (user) {
				const $item = $(`
					<div class="wpcalibrate-ucm-search-item" tabindex="0">
						<img src="${user.avatar}" width="32" height="32" class="wpcalibrate-ucm-user-avatar" alt="" />
						<div>
							<strong>${user.display_name}</strong>
							<span class="wpcalibrate-ucm-user-login">${user.user_login} (#${user.id})</span>
						</div>
					</div>
				`);

				$item.on('click keypress', function (e) {
					if (e.type === 'click' || e.which === 13) {
						self.selectUser(user, role);
						$dropdown.hide();
					}
				});

				$dropdown.append($item);
			});

			$dropdown.show();
		},

		selectUser: function (user, role) {
			const self = this;
			if (role === 'source') {
				self.state.sourceUser = user;
				$('#selected-source-user-id').val(user.id);
				$('#source-user-search').hide();
				$('#source-user-name').text(user.display_name);
				$('#source-user-login').text(user.user_login + ' (ID #' + user.id + ')');
				$('#source-user-avatar').attr('src', user.avatar);
				$('#source-user-selected').show();

				// Fetch authored counts
				$.post(wpcalibrateUcm.ajaxUrl, {
					action: 'wpcalibrate_ucm_user_summary',
					nonce: wpcalibrateUcm.nonce,
					user_id: user.id,
				}).done(function (res) {
					if (res.success && res.data && res.data.counts) {
						const c = res.data.counts;
						let badges = '';
						Object.keys(c).forEach(function (key) {
							if (c[key] > 0) {
								badges += `<span class="wpcalibrate-ucm-pill">${key}: ${c[key]}</span>`;
							}
						});
						$('#source-author-counts').html(badges || '<em>No authored posts found</em>');
					}
				});
			} else {
				self.state.destUser = user;
				$('#selected-dest-user-id').val(user.id);
				$('#dest-user-search').hide();
				$('#dest-user-name').text(user.display_name);
				$('#dest-user-login').text(user.user_login + ' (ID #' + user.id + ')');
				$('#dest-user-avatar').attr('src', user.avatar);
				$('#dest-user-selected').show();
			}

			self.validateStep1();
		},

		validateStep1: function () {
			const sId = parseInt($('#selected-source-user-id').val(), 10);
			const dId = parseInt($('#selected-dest-user-id').val(), 10);
			const valid = sId > 0 && dId > 0 && sId !== dId;

			$('#btn-goto-step-2').prop('disabled', !valid);
		},

		// 2. Wizard Step Navigation
		bindStepNavigation: function () {
			const self = this;

			$('#btn-goto-step-2').on('click', function () {
				self.goToStep(2);
			});

			$('#btn-goto-step-3').on('click', function () {
				self.goToStep(3);
			});

			$('#btn-goto-step-4').on('click', function () {
				self.collectCriteria();
				self.goToStep(4);
				self.executeScan();
			});

			$('[data-goto-step]').on('click', function () {
				const step = parseInt($(this).data('goto-step'), 10);
				self.goToStep(step);
			});
		},

		goToStep: function (step) {
			this.state.currentStep = step;
			$('.wpcalibrate-ucm-panel').removeClass('is-active');
			$('#wpcalibrate-ucm-step-' + step).addClass('is-active');

			$('.wpcalibrate-ucm-step').removeClass('is-active');
			$(`.wpcalibrate-ucm-step[data-step="${step}"]`).addClass('is-active');

			for (let i = 1; i < step; i++) {
				$(`.wpcalibrate-ucm-step[data-step="${i}"]`).addClass('is-complete');
			}

			window.scrollTo({ top: 0, behavior: 'smooth' });
		},

		collectCriteria: function () {
			const postTypes = [];
			$('.wpcalibrate-ucm-post-type-cb:checked').each(function () {
				postTypes.push($(this).val());
			});

			const statuses = [];
			$('input[name="statuses[]"]:checked').each(function () {
				statuses.push($(this).val());
			});

			this.state.criteria = {
				source_user_id: parseInt($('#selected-source-user-id').val(), 10),
				post_types: postTypes,
				statuses: statuses,
				include_media: $('#include_media').is(':checked') ? 1 : 0,
				include_comments: $('#include_comments').is(':checked') ? 1 : 0,
				date_from: $('#date_from').val(),
				date_to: $('#date_to').val(),
				selection_mode: $('input[name="selection_mode"]:checked').val(),
				individual_ids: this.state.selectedIndividualIds,
			};
		},

		// 3. Scan & Preview
		bindScanAction: function () {
			const self = this;
			$('#btn-open-confirm-modal').on('click', function () {
				self.openConfirmationModal();
			});
		},

		executeScan: function () {
			const self = this;
			const isIndividual = $('input[name="selection_mode"]:checked').val() === 'individual';

			if (isIndividual) {
				$('#wpcalibrate-ucm-individual-selection-container').show();
				self.loadIndividualRecords(1);
			} else {
				$('#wpcalibrate-ucm-individual-selection-container').hide();
			}

			$('#scan-total-count').text('...');
			$('#btn-open-confirm-modal').prop('disabled', true);

			$.post(wpcalibrateUcm.ajaxUrl, {
				action: 'wpcalibrate_ucm_scan_content',
				nonce: wpcalibrateUcm.nonce,
				criteria: self.state.criteria,
			}).done(function (res) {
				if (res.success && res.data) {
					self.state.scanResults = res.data;
					self.renderScanResults(res.data);
				}
			});
		},

		renderScanResults: function (data) {
			$('#scan-total-count').text(data.total);
			$('#scan-media-count').text(data.media_count);
			$('#scan-comments-count').text(data.comment_count);

			let postSum = 0;
			let typesHtml = '<h4>Post Types</h4><ul>';
			Object.keys(data.post_types).forEach(function (pt) {
				if (pt !== 'attachment') {
					postSum += data.post_types[pt];
				}
				typesHtml += `<li><strong>${pt}:</strong> ${data.post_types[pt]}</li>`;
			});
			typesHtml += '</ul>';
			$('#scan-posts-count').text(postSum);
			$('#scan-types-breakdown').html(typesHtml);

			let statusesHtml = '<h4>Statuses</h4><ul>';
			Object.keys(data.statuses).forEach(function (st) {
				statusesHtml += `<li><strong>${st}:</strong> ${data.statuses[st]}</li>`;
			});
			statusesHtml += '</ul>';
			$('#scan-statuses-breakdown').html(statusesHtml);

			if (data.warnings && data.warnings.length) {
				$('#scan-warnings-box').html('<strong>Warnings:</strong> ' + data.warnings.join('<br>')).show();
			} else {
				$('#scan-warnings-box').hide();
			}

			if (data.total > 0) {
				$('#btn-open-confirm-modal').prop('disabled', false);
			}
		},

		// 4. Individual Content Selection Table
		bindIndividualSelection: function () {
			const self = this;

			$('#individual-check-all').on('change', function () {
				const checked = $(this).is(':checked');
				$('.individual-item-cb').prop('checked', checked).trigger('change');
			});

			$(document).on('change', '.individual-item-cb', function () {
				const id = parseInt($(this).val(), 10);
				if ($(this).is(':checked')) {
					if (!self.state.selectedIndividualIds.includes(id)) {
						self.state.selectedIndividualIds.push(id);
					}
				} else {
					self.state.selectedIndividualIds = self.state.selectedIndividualIds.filter(x => x !== id);
				}
				$('#individual-selected-count').text(self.state.selectedIndividualIds.length);
			});

			let searchTimeout;
			$('#individual-table-search').on('input', function () {
				clearTimeout(searchTimeout);
				const term = $(this).val();
				searchTimeout = setTimeout(function () {
					self.loadIndividualRecords(1, term);
				}, 300);
			});
		},

		loadIndividualRecords: function (page, search = '') {
			const self = this;
			const $tbody = $('#individual-records-tbody');
			$tbody.html('<tr><td colspan="6">Loading records...</td></tr>');

			$.post(wpcalibrateUcm.ajaxUrl, {
				action: 'wpcalibrate_ucm_query_records',
				nonce: wpcalibrateUcm.nonce,
				criteria: self.state.criteria,
				page: page,
				search: search,
			}).done(function (res) {
				if (res.success && res.data) {
					const items = res.data.items;
					if (!items.length) {
						$tbody.html('<tr><td colspan="6">No authored records found for filters.</td></tr>');
						return;
					}

					let rows = '';
					items.forEach(function (item) {
						const checked = self.state.selectedIndividualIds.includes(item.id) ? 'checked' : '';
						rows += `
							<tr>
								<th scope="row" class="check-column">
									<input type="checkbox" class="individual-item-cb" value="${item.id}" ${checked} />
								</th>
								<td><strong>${item.title}</strong></td>
								<td><code>${item.type}</code></td>
								<td>${item.status}</td>
								<td>${item.date}</td>
								<td>#${item.id}</td>
							</tr>
						`;
					});
					$tbody.html(rows);
				}
			});
		},

		// 5. Modal and Execution
		bindModalActions: function () {
			const self = this;

			$('#modal-close-btn, #modal-cancel-btn').on('click', function () {
				$('#confirmation-modal').removeClass('is-open').attr('aria-hidden', 'true');
			});

			$('#modal-execute-btn').on('click', function () {
				$('#confirmation-modal').removeClass('is-open').attr('aria-hidden', 'true');
				self.startMigrationExecution();
			});

			$('#btn-cancel-migration').on('click', function () {
				self.cancelMigration();
			});

			$('#btn-rollback-now').on('click', function () {
				if (confirm(wpcalibrateUcm.i18n.confirmRollback)) {
					self.startRollbackProcess(self.state.activeMigrationId);
				}
			});
		},

		openConfirmationModal: function () {
			const total = this.state.scanResults ? this.state.scanResults.total : 0;
			$('#modal-item-count').text(total);
			$('#modal-source-name').text(this.state.sourceUser ? this.state.sourceUser.display_name : 'Source');
			$('#modal-dest-name').text(this.state.destUser ? this.state.destUser.display_name : 'Destination');

			$('#confirmation-modal').addClass('is-open').attr('aria-hidden', 'false');
			$('#modal-execute-btn').focus();
		},

		startMigrationExecution: function () {
			const self = this;
			self.goToStep(5);
			self.state.isProcessing = true;
			self.state.isCancelled = false;

			$('#progress-status-text').text(wpcalibrateUcm.i18n.migrating);
			$('#progress-bar-fill').css('width', '5%');
			$('#migration-controls').show();
			$('#migration-final-report').hide();

			$.post(wpcalibrateUcm.ajaxUrl, {
				action: 'wpcalibrate_ucm_start_migration',
				nonce: wpcalibrateUcm.nonce,
				source_user_id: self.state.sourceUser.id,
				dest_user_id: self.state.destUser.id,
				criteria: self.state.criteria,
			}).done(function (res) {
				if (res.success && res.data) {
					self.state.activeMigrationId = res.data.migration_id;
					self.state.lockToken = res.data.lock_token;
					self.runBatchLoop();
				} else {
					alert(res.data ? res.data.message : wpcalibrateUcm.i18n.error);
				}
			});
		},

		runBatchLoop: function () {
			const self = this;
			if (!self.state.isProcessing || self.state.isCancelled) return;

			$.post(wpcalibrateUcm.ajaxUrl, {
				action: 'wpcalibrate_ucm_process_batch',
				nonce: wpcalibrateUcm.nonce,
				migration_id: self.state.activeMigrationId,
				lock_token: self.state.lockToken,
			}).done(function (res) {
				if (res.success && res.data) {
					const data = res.data;
					self.state.lockToken = data.lock_token || self.state.lockToken;

					// Update counters
					$('#counter-planned').text(data.total_planned || 0);
					$('#counter-migrated').text(data.total_migrated || 0);
					$('#counter-failed').text(data.total_failed || 0);
					$('#counter-skipped').text(data.total_skipped || 0);

					const pct = data.percent || 0;
					$('#progress-percent-text').text(pct + '%');
					$('#progress-bar-fill').css('width', pct + '%');
					$('#progress-bar-element').attr('aria-valuenow', pct);

					if (data.done) {
						self.state.isProcessing = false;
						if (data.status === 'cancelled') {
							self.showCancelledReport(data);
							return;
						}
						$('#progress-percent-text').text('100%');
						$('#progress-bar-fill').css('width', '100%');
						$('#progress-status-text').text(wpcalibrateUcm.i18n.completed);
						self.showFinalReport(data);
					} else {
						// Continue next batch
						setTimeout(function () {
							self.runBatchLoop();
						}, 100);
					}
				} else {
					self.state.isProcessing = false;
					alert(res.data ? res.data.message : wpcalibrateUcm.i18n.error);
				}
			}).fail(function () {
				self.state.isProcessing = false;
				alert(wpcalibrateUcm.i18n.error);
			});
		},

		cancelMigration: function () {
			const self = this;
			if (self.state.isCancelled) return;
			self.state.isCancelled = true;
			self.state.isProcessing = false;

			const $cancelBtn = $('#btn-cancel-migration');
			$cancelBtn.prop('disabled', true).text('Cancelling...');
			$('#progress-status-text').text('Cancelling migration...');

			$.post(wpcalibrateUcm.ajaxUrl, {
				action: 'wpcalibrate_ucm_cancel_migration',
				nonce: wpcalibrateUcm.nonce,
				migration_id: self.state.activeMigrationId,
			}).done(function (res) {
				const data = (res && res.data) ? res.data : {};
				self.showCancelledReport(data);
			}).fail(function () {
				self.showCancelledReport({});
			});
		},

		showCancelledReport: function (data) {
			const self = this;
			self.state.isProcessing = false;
			self.state.isCancelled = true;

			$('#migration-controls').hide();
			$('#progress-status-text').text('Migration cancelled.');
			$('#migration-final-report').show();

			const migrated = data.total_migrated || parseInt($('#counter-migrated').text(), 10) || 0;

			$('#report-badge-wrap').html(`
				<div class="notice notice-warning" style="margin: 0 0 15px; padding: 12px 18px;">
					<h3>Migration Cancelled</h3>
					<p>The migration process was stopped. ${migrated} items were transferred before cancellation.</p>
				</div>
			`);

			$('#verification-results-box').hide();

			if (migrated > 0) {
				$('#btn-rollback-now').show();
			} else {
				$('#btn-rollback-now').hide();
			}
		},

		showFinalReport: function (data) {
			$('#migration-controls').hide();
			$('#verification-results-box').show();
			$('#btn-rollback-now').show();
			$('#migration-final-report').show();

			const isMismatch = data.verification_status === 'mismatch' || data.total_failed > 0;
			const badgeClass = isMismatch ? 'notice notice-warning' : 'notice notice-success';
			const badgeTitle = isMismatch ? 'Migration Completed with Warnings' : 'Migration Completed Successfully';

			$('#report-badge-wrap').html(`
				<div class="${badgeClass}" style="margin: 0 0 15px; padding: 12px 18px;">
					<h3>${badgeTitle}</h3>
					<p>${data.total_migrated} items transferred. Integrity verification: <strong>${data.verification_status}</strong>.</p>
				</div>
			`);

			if (data.verification) {
				const v = data.verification;
				let desc = `Verified: ${v.verified} items. Mismatches: ${v.mismatches}. Missing: ${v.missing}.`;
				$('#verification-status-desc').text(desc);

				if (v.details && v.details.length) {
					$('#verification-mismatches-list').html('<ul>' + v.details.map(d => `<li>${d}</li>`).join('') + '</ul>').show();
				}
			}
		},

		// 6. History Page Actions
		bindHistoryActions: function () {
			const self = this;

			// View ledger details
			$('.btn-view-ledger').on('click', function () {
				const id = $(this).data('id');
				self.openLedgerModal(id, 1);
			});

			$('#ledger-modal-close-btn, #ledger-modal-dismiss-btn').on('click', function () {
				$('#ledger-modal').removeClass('is-open').attr('aria-hidden', 'true');
			});

			// History rollback button
			$('.btn-history-rollback').on('click', function () {
				const id = $(this).data('id');
				if (confirm(wpcalibrateUcm.i18n.confirmRollback)) {
					self.startRollbackProcess(id);
				}
			});

			// Force unlock button
			$('.btn-force-unlock').on('click', function () {
				const id = $(this).data('id');
				$.post(wpcalibrateUcm.ajaxUrl, {
					action: 'wpcalibrate_ucm_force_unlock',
					nonce: wpcalibrateUcm.nonce,
					migration_id: id,
				}).done(function (res) {
					alert(res.data.message);
					location.reload();
				});
			});

			// Delete migration record
			$('.btn-delete-migration').on('click', function () {
				if (!confirm(wpcalibrateUcm.i18n.confirmCleanup)) return;
				const id = $(this).data('id');
				$.post(wpcalibrateUcm.ajaxUrl, {
					action: 'wpcalibrate_ucm_delete_migration',
					nonce: wpcalibrateUcm.nonce,
					migration_id: id,
				}).done(function (res) {
					location.reload();
				});
			});
		},

		openLedgerModal: function (migrationId, page) {
			const $tbody = $('#ledger-items-tbody');
			$tbody.html('<tr><td colspan="7">Loading ledger items...</td></tr>');
			$('#ledger-modal').addClass('is-open').attr('aria-hidden', 'false');

			$.post(wpcalibrateUcm.ajaxUrl, {
				action: 'wpcalibrate_ucm_query_history_items',
				nonce: wpcalibrateUcm.nonce,
				migration_id: migrationId,
				page: page,
			}).done(function (res) {
				if (res.success && res.data) {
					const items = res.data.items;
					if (!items.length) {
						$tbody.html('<tr><td colspan="7">No item ledger records found.</td></tr>');
						return;
					}

					let rows = '';
					items.forEach(function (i) {
						rows += `
							<tr>
								<td>#${i.object_id}</td>
								<td><code>${i.object_type}</code></td>
								<td>User #${i.original_owner_id}</td>
								<td>User #${i.new_owner_id}</td>
								<td><span class="wpcalibrate-ucm-badge wpcalibrate-ucm-status-${i.migration_status}">${i.migration_status}</span></td>
								<td><span class="wpcalibrate-ucm-badge wpcalibrate-ucm-status-${i.rollback_status}">${i.rollback_status}</span></td>
								<td><small>${i.error_message || '-'}</small></td>
							</tr>
						`;
					});
					$tbody.html(rows);
				}
			});
		},

		startRollbackProcess: function (migrationId) {
			const self = this;
			alert(wpcalibrateUcm.i18n.rollingBack);

			$.post(wpcalibrateUcm.ajaxUrl, {
				action: 'wpcalibrate_ucm_start_rollback',
				nonce: wpcalibrateUcm.nonce,
				migration_id: migrationId,
			}).done(function (res) {
				if (res.success && res.data) {
					self.runRollbackLoop(migrationId, res.data.lock_token);
				} else {
					alert(res.data ? res.data.message : wpcalibrateUcm.i18n.error);
				}
			});
		},

		runRollbackLoop: function (migrationId, lockToken) {
			const self = this;

			$.post(wpcalibrateUcm.ajaxUrl, {
				action: 'wpcalibrate_ucm_process_rollback_batch',
				nonce: wpcalibrateUcm.nonce,
				migration_id: migrationId,
				lock_token: lockToken,
			}).done(function (res) {
				if (res.success && res.data) {
					const d = res.data;
					if (d.done) {
						alert(wpcalibrateUcm.i18n.rollbackDone + ` Rolled back: ${d.total_rolled_back}, Conflicts: ${d.total_rollback_conflicts}, Failures: ${d.total_rollback_failed}.`);
						location.reload();
					} else {
						setTimeout(function () {
							self.runRollbackLoop(migrationId, d.lock_token || lockToken);
						}, 100);
					}
				} else {
					alert(res.data ? res.data.message : wpcalibrateUcm.i18n.error);
				}
			});
		},

		// 7. Settings Actions
		bindSettingsActions: function () {
			$('#btn-manual-cleanup').on('click', function () {
				if (!confirm(wpcalibrateUcm.i18n.confirmCleanup)) return;

				const $btn = $(this);
				$btn.prop('disabled', true);
				$('#manual-cleanup-status').text('Cleaning up...');

				$.post(wpcalibrateUcm.ajaxUrl, {
					action: 'wpcalibrate_ucm_manual_cleanup',
					nonce: wpcalibrateUcm.nonce,
				}).done(function (res) {
					$btn.prop('disabled', false);
					$('#manual-cleanup-status').text(res.data.message);
				});
			});
		},
	};

	$(document).ready(function () {
		UCM.init();
	});

})(jQuery);
