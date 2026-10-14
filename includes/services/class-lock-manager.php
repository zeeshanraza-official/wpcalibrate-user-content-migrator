<?php
/**
 * Lock Manager Service
 *
 * @package WPCalibrate\UserContentMigrator\Services
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Services;

use WPCalibrate\UserContentMigrator\Database\MigrationsTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages atomic locking and recovery for migration and rollback operations.
 */
class LockManager {

	/**
	 * Default lock time-to-live in seconds.
	 *
	 * @var int
	 */
	public const DEFAULT_TTL = 60;

	/**
	 * Attempt to acquire an exclusive lock for a migration.
	 *
	 * @param int    $migration_id Migration ID.
	 * @param int    $ttl Time to live in seconds.
	 * @param string $existing_token Optional existing token held by the caller to re-acquire / extend.
	 * @return string|null Lock token string on success, null if lock could not be acquired.
	 */
	public static function acquire_lock( int $migration_id, int $ttl = 0, string $existing_token = '' ): ?string {
		global $wpdb;

		if ( $ttl <= 0 ) {
			$ttl = SettingsManager::get_int( 'lock_timeout_seconds', self::DEFAULT_TTL );
		}

		$migration = MigrationsTable::get( $migration_id );
		if ( ! $migration ) {
			return null;
		}

		// If caller already holds this lock, refresh it and return existing token!
		if ( ! empty( $existing_token ) && ! empty( $migration->lock_token ) && $migration->lock_token === $existing_token ) {
			self::refresh_lock( $migration_id, $existing_token, $ttl );
			return $existing_token;
		}

		// Check if active unexpired lock is held by another worker.
		if ( ! empty( $migration->lock_token ) && ! empty( $migration->lock_expires_at ) ) {
			$expires_time = strtotime( $migration->lock_expires_at );
			if ( $expires_time > time() && $migration->lock_token !== $existing_token ) {
				// Actively locked by another worker.
				return null;
			}
		}

		$table      = MigrationsTable::get_table();
		$now        = current_time( 'mysql', true );
		$expires_at = gmdate( 'Y-m-d H:i:s', time() + $ttl );
		$token      = ! empty( $existing_token ) ? $existing_token : wp_generate_uuid4();

		// Acquire / assign lock.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} 
				SET lock_token = %s, lock_expires_at = %s, updated_at = %s 
				WHERE id = %d",
				$token,
				$expires_at,
				$now,
				$migration_id
			)
		);

		return false !== $updated ? $token : null;
	}

	/**
	 * Refresh an existing lock to extend its lease during batch processing.
	 *
	 * @param int    $migration_id Migration ID.
	 * @param string $token Lock token previously acquired.
	 * @param int    $ttl Time to extend in seconds.
	 * @return bool True if successfully refreshed.
	 */
	public static function refresh_lock( int $migration_id, string $token, int $ttl = 0 ): bool {
		global $wpdb;

		if ( empty( $token ) ) {
			return false;
		}

		if ( $ttl <= 0 ) {
			$ttl = SettingsManager::get_int( 'lock_timeout_seconds', self::DEFAULT_TTL );
		}

		$migration = MigrationsTable::get( $migration_id );
		if ( ! $migration || empty( $migration->lock_token ) || $migration->lock_token !== $token ) {
			return false;
		}

		$table      = MigrationsTable::get_table();
		$now        = current_time( 'mysql', true );
		$expires_at = gmdate( 'Y-m-d H:i:s', time() + $ttl );

		// In MySQL, an UPDATE that results in identical values returns 0 affected rows.
		// Therefore, we check false !== $updated rather than $updated > 0.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} 
				SET lock_expires_at = %s, updated_at = %s 
				WHERE id = %d AND lock_token = %s",
				$expires_at,
				$now,
				$migration_id,
				$token
			)
		);

		return false !== $updated;
	}

	/**
	 * Release an exclusive lock.
	 *
	 * @param int    $migration_id Migration ID.
	 * @param string $token Lock token.
	 * @return bool True if released.
	 */
	public static function release_lock( int $migration_id, string $token = '' ): bool {
		global $wpdb;

		$table = MigrationsTable::get_table();
		$now   = current_time( 'mysql', true );

		if ( ! empty( $token ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} 
					SET lock_token = NULL, lock_expires_at = NULL, updated_at = %s 
					WHERE id = %d AND lock_token = %s",
					$now,
					$migration_id,
					$token
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} 
					SET lock_token = NULL, lock_expires_at = NULL, updated_at = %s 
					WHERE id = %d",
					$now,
					$migration_id
				)
			);
		}

		return false !== $updated;
	}

	/**
	 * Check if a migration currently has an active unexpired lock.
	 *
	 * @param int $migration_id Migration ID.
	 * @return bool True if actively locked.
	 */
	public static function is_locked( int $migration_id ): bool {
		$migration = MigrationsTable::get( $migration_id );
		if ( ! $migration || empty( $migration->lock_token ) || empty( $migration->lock_expires_at ) ) {
			return false;
		}

		$expires_time = strtotime( $migration->lock_expires_at );
		return $expires_time > time();
	}

	/**
	 * Force unlock a migration (administrator recovery action).
	 *
	 * @param int $migration_id Migration ID.
	 * @return bool
	 */
	public static function force_unlock( int $migration_id ): bool {
		return MigrationsTable::update(
			$migration_id,
			[
				'lock_token'      => null,
				'lock_expires_at' => null,
			]
		);
	}
}
