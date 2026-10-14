<?php
/**
 * Action Scheduler Integration
 *
 * @package WPCalibrate\UserContentMigrator\Integrations
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Integrations;

use WPCalibrate\UserContentMigrator\Migration\BatchProcessor;
use WPCalibrate\UserContentMigrator\Rollback\RollbackService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optional integration with Action Scheduler if provided by the environment.
 */
class ActionSchedulerIntegration {

	/**
	 * Hook name for async migration batch.
	 */
	public const MIGRATION_BATCH_HOOK = 'wpcalibrate_ucm_process_migration_batch';

	/**
	 * Hook name for async rollback batch.
	 */
	public const ROLLBACK_BATCH_HOOK = 'wpcalibrate_ucm_process_rollback_batch';

	/**
	 * Initialize Action Scheduler listeners if available.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( ! self::is_available() ) {
			return;
		}

		add_action( self::MIGRATION_BATCH_HOOK, [ self::class, 'handle_migration_batch' ], 10, 2 );
		add_action( self::ROLLBACK_BATCH_HOOK, [ self::class, 'handle_rollback_batch' ], 10, 2 );
	}

	/**
	 * Check if Action Scheduler functions are available in current WordPress environment.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return function_exists( 'as_enqueue_async_action' ) && function_exists( 'as_has_scheduled_action' );
	}

	/**
	 * Schedule next migration batch via Action Scheduler if available.
	 *
	 * @param int    $migration_id Migration ID.
	 * @param string $lock_token Active lock token.
	 * @return bool True if scheduled.
	 */
	public static function schedule_migration_batch( int $migration_id, string $lock_token = '' ): bool {
		if ( ! self::is_available() ) {
			return false;
		}

		as_enqueue_async_action(
			self::MIGRATION_BATCH_HOOK,
			[
				'migration_id' => $migration_id,
				'lock_token'   => $lock_token,
			],
			'wpcalibrate-ucm'
		);

		return true;
	}

	/**
	 * Schedule next rollback batch via Action Scheduler if available.
	 *
	 * @param int    $migration_id Migration ID.
	 * @param string $lock_token Active lock token.
	 * @return bool True if scheduled.
	 */
	public static function schedule_rollback_batch( int $migration_id, string $lock_token = '' ): bool {
		if ( ! self::is_available() ) {
			return false;
		}

		as_enqueue_async_action(
			self::ROLLBACK_BATCH_HOOK,
			[
				'migration_id' => $migration_id,
				'lock_token'   => $lock_token,
			],
			'wpcalibrate-ucm'
		);

		return true;
	}

	/**
	 * Action Scheduler worker for migration batch.
	 *
	 * @param int    $migration_id Migration ID.
	 * @param string $lock_token Lock token.
	 * @return void
	 */
	public static function handle_migration_batch( int $migration_id, string $lock_token = '' ): void {
		$result = BatchProcessor::process_batch( $migration_id, $lock_token );
		if ( is_array( $result ) && empty( $result['done'] ) ) {
			// Schedule subsequent batch.
			self::schedule_migration_batch( $migration_id, (string) ( $result['lock_token'] ?? '' ) );
		}
	}

	/**
	 * Action Scheduler worker for rollback batch.
	 *
	 * @param int    $migration_id Migration ID.
	 * @param string $lock_token Lock token.
	 * @return void
	 */
	public static function handle_rollback_batch( int $migration_id, string $lock_token = '' ): void {
		$result = RollbackService::process_batch( $migration_id, $lock_token );
		if ( is_array( $result ) && empty( $result['done'] ) ) {
			// Schedule subsequent batch.
			self::schedule_rollback_batch( $migration_id, (string) ( $result['lock_token'] ?? '' ) );
		}
	}
}
