# WPCalibrate User Content Migrator

[![WordPress](https://img.shields.io/badge/WordPress-6.2%20to%207.1.3-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-8892BF.svg)](https://php.net/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WooCommerce Compatible](https://img.shields.io/badge/WooCommerce-HPOS%20Compatible-96588a.svg)](https://woocommerce.com/)
[![Version](https://img.shields.io/badge/version-1.0.1-brightgreen.svg)](#changelog)

> Safely transfer ownership of WordPress posts, pages, custom post types, WooCommerce products, and media attachments between existing users with an exact rollback ledger and post-migration database verification.

---

## Table of Contents
- [About](#about)
- [Why WPCalibrate User Content Migrator?](#why-wpcalibrate-user-content-migrator)
- [Key Features](#key-features)
- [Content Exclusions & Safeguards](#content-exclusions--safeguards)
- [Requirements](#requirements)
- [Installation](#installation)
- [Documentation & How to Use](#documentation--how-to-use)
  - [The 5-Step Migration Method](#the-5-step-migration-method)
  - [Rollback & Conflict Protection](#rollback--conflict-protection)
  - [Developer Hooks & Filters](#developer-hooks--filters)
  - [REST API Endpoints](#rest-api-endpoints)
- [Changelog](#changelog)
- [Support & Inquiries](#support--inquiries)
- [License](#license)

---

## About

**WPCalibrate User Content Migrator** is an enterprise-grade administration utility designed to safely reassign content ownership from one WordPress user to another **without requiring user deletion**.

In standard WordPress core, the only native mechanism to transfer authored content from user A to user B is when permanently deleting user A's account. This poses massive operational risks for editorial agencies, multi-author blogs, enterprises, and WooCommerce stores where accounts must remain active, or where content needs selective reassignment prior to offboarding.

WPCalibrate User Content Migrator decouples ownership transfer from user deletion, giving administrators fine-grained control, automated previews, atomic concurrency locks, and an immutable rollback ledger.

---

## Why WPCalibrate User Content Migrator?

| Feature / Scenario | Native WordPress | Generic Plugins | WPCalibrate User Content Migrator |
| :--- | :---: | :---: | :---: |
| **Keep source user account active** | ❌ (Must delete user) | ⚠️ Partial | ✅ **Yes (Full preservation)** |
| **Selective post type scoping** | ❌ All or nothing | ⚠️ Inconsistent | ✅ **Granular selection & CPTs** |
| **WooCommerce product reassignment** | ❌ Risk to order data | ⚠️ Often corrupts orders | ✅ **Products supported; orders strictly protected** |
| **Media attachments ownership** | ❌ Core ignores media | ⚠️ Duplicates or breaks URLs | ✅ **Author updated; files & URLs untouched** |
| **Pre-migration dry-run preview** | ❌ None | ❌ Rare | ✅ **Live scan & breakdown** |
| **Process concurrency locking** | ❌ None | ❌ Race condition risks | ✅ **Atomic database lock manager** |
| **Exact item-by-item rollback** | ❌ Impossible | ❌ Unavailable | ✅ **Immutable ledger with conflict protection** |
| **Post-migration integrity verification** | ❌ None | ❌ None | ✅ **Automated database check** |

---

## Key Features

- **Scalable User Selection:** Debounced AJAX user search by username, display name, or email with live authored content breakdown and prevention of accidental self-assignment.
- **Granular Content Scoping:** Reassign standard Posts, Pages, custom post types (CPTs), WooCommerce Products, and Media Attachments.
- **Date & Taxonomy Filtering:** Filter target content by publication date ranges (From / To), post statuses (Published, Draft, Pending, Private, Future), and specific categories or tags.
- **Read-Only Scan & Preview:** Run a comprehensive dry-run discovery scan to preview exact counts and sample item titles prior to mutating any records.
- **Resumable Batch Execution:** Processes transfers in memory-safe background batches (default: 25 items/batch) with live progress tracking, zero PHP execution timeouts, and an immediate cancel/pause option.
- **Atomic Concurrency Protection:** Employs a process lock manager with lease expiration and re-acquisition guards, preventing overlapping executions.
- **Post-Execution Database Verification:** Inspects live database records after transfer to verify that every item reflects the expected destination ownership.
- **Exact Item Rollback:** Dedicated ledger table (`wp_wpcalibrate_ucm_items`) records original and destination owner IDs. Revert any migration with a single click.
- **Conflict Detection on Rollback:** If an administrator or author re-assigned a post after migration, rollback detects the ownership modification and flags a conflict rather than destructively overwriting newer data.
- **Configurable Retention & Cleanup:** Configure migration history retention (30, 60, 90, 180, 365 days, or indefinite) with scheduled background cleanup.

---

## Content Exclusions & Safeguards

To prevent store corruption, security bypasses, or data loss, internal and sensitive WordPress objects are strictly blocked from reassignment:
- **WooCommerce Orders:** `shop_order`, `shop_order_refund`, and `shop_order_placehold` are permanently blocked. High-Performance Order Storage (HPOS) tables are untouched.
- **WordPress Core Internals:** `revision`, `nav_menu_item`, `custom_css`, `customize_changeset`, `wp_template`, `wp_template_part`, `wp_global_styles`, and `wp_navigation`.
- **Scheduled Background Tasks:** Action Scheduler jobs (`action_scheduler`) and internal transients.

---

## Requirements

- **WordPress:** 6.2 or higher (fully tested up to 7.1.3).
- **PHP:** 8.2 or 8.3+.
- **Database:** MySQL 5.7+ / MariaDB 10.3+.
- **User Role:** Administrator (`manage_options` capability).
- **WooCommerce (Optional):** Compatible with WooCommerce 8.0 through 11.2+ with HPOS.

---

## Installation

### Via WordPress Dashboard
1. Download the latest `wpcalibrate-user-content-migrator-1.0.1.zip` from [Releases](https://github.com/zeeshanraza-official/wpcalibrate-user-content-migrator/releases).
2. Go to **Plugins > Add New > Upload Plugin** in your WordPress admin dashboard.
3. Choose the downloaded ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

### Via Git
Clone this repository directly into your WordPress plugins directory:
```bash
cd wp-content/plugins/
git clone https://github.com/zeeshanraza-official/wpcalibrate-user-content-migrator.git
```
Activate through **Plugins** in the WordPress admin panel.

---

## Documentation & How to Use

### The 5-Step Migration Method

Navigate to **WPCalibrate > Content Migrator** in your WordPress admin:

1. **Step 1: Select Users**
   - Search and select a **Source User** (current content owner).
   - Search and select a **Destination User** (new content owner).
   - Real-time validation ensures both accounts are distinct and active.
2. **Step 2: Content & Scope**
   - Select the content types to transfer (Posts, Pages, CPTs, WooCommerce Products, Media).
   - Toggle optional comment ownership reassignment (disabled by default).
3. **Step 3: Filters & Options**
   - Refine scope by date ranges (From / To).
   - Select publication statuses (e.g. Published, Draft, Private).
   - Filter by specific categories and tags.
4. **Step 4: Scan & Preview**
   - Run a discovery scan to preview exact counts and item titles.
   - Verify scope before making changes.
5. **Step 5: Execute & Verify**
   - Click **Confirm & Start Migration**.
   - Watch live batch progress with counters (`Planned`, `Migrated`, `Failed`, `Skipped`).
   - Automated database verification executes upon completion to confirm database integrity.

### Rollback & Conflict Protection

1. Navigate to **WPCalibrate > Migration History**.
2. Locate any completed migration in the audit log.
3. Click **View Ledger** to inspect every single post transferred with before/after ownership timestamps.
4. Click **Rollback This Migration** to revert ownership back to the source user.
5. If any item was modified after the migration occurred, the system flags a **Conflict** and preserves the post without overwriting.

### Developer Hooks & Filters

Extend or customize the plugin using WordPress action and filter hooks:

```php
// Filter eligible post types
add_filter( 'wpcalibrate_ucm_eligible_post_types', function( array $types ): array {
    $types['portfolio'] = get_post_type_object( 'portfolio' );
    return $types;
} );

// Filter batch size (clamped between 1 and 100)
add_filter( 'wpcalibrate_ucm_batch_size', function( int $size, int $migration_id ): int {
    return 50;
}, 10, 2 );

// Action fired before individual post transfer
add_action( 'wpcalibrate_ucm_before_post_transfer', function( int $post_id, int $source_user, int $dest_user, string $post_type ) {
    // Custom logging or integrations
}, 10, 4 );

// Action fired after successful post transfer
add_action( 'wpcalibrate_ucm_after_post_transfer', function( int $post_id, int $source_user, int $dest_user, string $post_type ) {
    // Purge external caches or notify author
}, 10, 4 );

// Action fired when migration is completed
add_action( 'wpcalibrate_ucm_migration_completed', function( int $migration_id ) {
    // Send completion notification
} );
```

### REST API Endpoints

The plugin provides authenticated REST API endpoints under `/wp-json/wpcalibrate-ucm/v1/`:
- `GET /wp-json/wpcalibrate-ucm/v1/users` - Search users with pagination.
- `GET /wp-json/wpcalibrate-ucm/v1/preview` - Dry-run discovery scan.
- `GET /wp-json/wpcalibrate-ucm/v1/migrations` - Fetch migration history.
- `GET /wp-json/wpcalibrate-ucm/v1/migrations/<id>` - Fetch migration ledger details.

---

## Changelog

### 1.0.1 - 2026-10-14
- **New Feature:** Added dedicated **Overview** tab in header navigation and `UCM Overview & Guide` submenu detailing purpose, 5-step method, and safety guarantees.
- **Fixed:** Resolved "Cancel Migration" button issue with immediate lock release, state transitions from `queued`/`processing`, and clean recovery UI.
- **Fixed:** Resolved `Lock expired and could not be re-acquired` issue caused by MySQL zero-row affected return on identical timestamps. Callers now pass active tokens for seamless renewal.
- **Fixed:** Support tab Plugin Marketplace layout blowout resolved with responsive `min-width: 0` grid cards and word wrapping.
- **Fixed:** Top-level admin sidebar menu icon dimensions (`20x20px`) and color matching native WordPress sidebar icon tones (`opacity: 0.68` normal, `1.0` active/hover).

### 1.0.0 - 2026-10-14
- Initial release.
- 5-step guided migration wizard.
- Post, page, CPT, WooCommerce product, and media attachment ownership transfer.
- Resumable batch processor with atomic concurrency locks.
- Exact per-item rollback ledger and conflict detection.
- Post-migration database verification service.
- Configurable retention cleanup.

---

## Support & Inquiries

- **Author:** WPCalibrate
- **Website:** [wpcalibrate.com](https://wpcalibrate.com)
- **Marketplace:** [marketplace.wpcalibrate.com](https://marketplace.wpcalibrate.com/)
- **Support Email:** support@wpcalibrate.com
- **Phone / WhatsApp:** +447474795976

---

## License

This project is licensed under the GNU General Public License v2.0 or later - see the [LICENSE](license.txt) file for details.
