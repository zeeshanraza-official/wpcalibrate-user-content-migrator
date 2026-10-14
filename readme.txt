=== WPCalibrate User Content Migrator ===
Contributors: wpcalibrate
Donate link: https://wpcalibrate.com/
Tags: content migration, user migration, author reassignment, bulk edit, rollback
Requires at least: 6.2
Tested up to: 7.1.3
Requires PHP: 8.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Safely transfer ownership of WordPress posts, pages, custom post types, WooCommerce products, and media attachments between existing users with exact rollback support.

== Description ==

WPCalibrate User Content Migrator is an administrator utility designed to safely transfer ownership of WordPress authored content from one existing user to another without requiring user deletion.

Unlike the default WordPress user deletion screen which forces reassignment only when deleting an account, WPCalibrate User Content Migrator operates while keeping both accounts active, providing granular scoping, read-only preview scanning, resumable batch processing, post-migration database verification, and an exact per-item rollback ledger.

= Key Features =

* **Scalable User Selection:** Search large user directories by username, display name, or email with debounced queries and live authored content breakdowns.
* **Granular Scope & Filtering:** Filter by eligible post types, post statuses (publish, draft, pending, private, future, trash), date ranges, or pick individual records manually.
* **Media Attachment Ownership:** Safely reassign media library attachment authorship (`post_author`) without moving files, renaming uploads, duplicating files, or modifying URLs.
* **WooCommerce Product Compatibility:** Safely reassign product post ownership while strictly excluding customer orders, refunds, and HPOS tables.
* **Optional Comment Ownership:** Optionally reassign comment `user_id` to the destination user without rewriting comment author names, emails, IPs, or dates (disabled by default).
* **Read-Only Scan & Preview:** Inspect exact counts before making any changes.
* **Resumable Batch Runner:** Server-side batch processing with atomic concurrency locks, preventing PHP memory exhaustion and request timeouts.
* **Post-Execution Database Verification:** Verifies actual database truth against expected item ledgers and flags mismatches.
* **Exact Item Rollback:** Restores original owners recorded during the specific migration. Automatically protects and flags records if subsequent third-party modifications took place.
* **Configurable Retention:** Retain migration ledgers for 30, 60, 90, 180, 365 days, or keep indefinitely until manually cleaned up.

= Content Exclusions =

For site security and data integrity, internal and sensitive post types are strictly excluded from migration:
* Revisions, navigation menu items, changeset customizations, template parts, and global styles.
* WooCommerce orders (`shop_order`, `shop_order_refund`, `shop_order_placehold`).
* WooCommerce coupons, customer payment tokens, and HPOS order tables.
* Action Scheduler jobs and internal plugin transients.

== Installation ==

1. Upload the `wpcalibrate-user-content-migrator` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the migrator via **WPCalibrate > User Content Migrator** in the WordPress administration menu.

== Frequently Asked Questions ==

= Does this plugin delete the source user? =
No. Content ownership is safely updated while both source and destination accounts remain intact.

= How does rollback work? =
During migration, every modified post, attachment, or comment is recorded with its original owner ID and new owner ID in a dedicated database ledger. When rollback is triggered, the plugin inspects each object, verifies that the current owner matches the migrated destination user, and restores the original owner. If the ownership was modified after the migration occurred, the plugin flags a conflict and avoids overwriting newer data.

= Does migrating media move files on the server? =
No. Only the `post_author` attribute in the WordPress database is updated. File paths, upload folders, filenames, and image URLs are never touched.

= Is WooCommerce required? =
No. WooCommerce is entirely optional. If WooCommerce is installed, the plugin supports product ownership migration while ensuring order storage remains untouched.

== Screenshots ==

1. Guided migration wizard with scalable user search and live authored content statistics.
2. Content selection with eligible custom post types and media attachments.
3. Read-only preview and scan summary.
4. Real-time batch progress with live counters and verification.
5. Migration history with item ledger inspection and conflict-safe rollback.

== Developer Hooks & Filters ==

* `wpcalibrate_ucm_eligible_post_types`: Filter the list of post types eligible for migration.
* `wpcalibrate_ucm_excluded_post_types`: Add post types to the blocklist.
* `wpcalibrate_ucm_supported_statuses`: Filter supported post statuses.
* `wpcalibrate_ucm_batch_size`: Customize internal batch size.
* `wpcalibrate_ucm_before_post_transfer`: Action fired before post transfer (`$post_id, $original_owner_id, $new_owner_id, $post_type`).
* `wpcalibrate_ucm_after_post_transfer`: Action fired after post transfer.
* `wpcalibrate_ucm_migration_completed`: Action fired when a migration completes.
* `wpcalibrate_ucm_rollback_completed`: Action fired when rollback finishes.

== Changelog ==

= 1.0.1 =
* Added dedicated Overview tab and submenu with 5-step method guide and safety architecture.
* Fixed lock renewal edge case where MySQL reports zero affected rows for unchanged timestamps.
* Fixed migration cancellation lifecycle: immediate lock release, state transition, and clear recovery UI.
* Fixed support tab grid blowout on long marketplace URLs.
* Fixed admin menu icon size and WordPress sidebar icon color matching.

= 1.0.0 =
* Initial production release.
* Scalable source and destination user search.
* Support for posts, pages, custom post types, WooCommerce products, and media attachments.
* Optional comment authorship migration (default OFF).
* Exact per-item rollback ledger and conflict detection.
* Database verification service.
* Configurable retention and automated cleanup.
