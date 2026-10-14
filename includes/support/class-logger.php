<?php
/**
 * Plugin Logger
 *
 * @package WPCalibrate\UserContentMigrator\Support
 */

declare(strict_types=1);

namespace WPCalibrate\UserContentMigrator\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Diagnostic logger that strips sensitive tokens and PII before writing.
 */
class Logger {

	/**
	 * Log a message to debug log if WP_DEBUG is enabled.
	 *
	 * @param string               $message Log message.
	 * @param string               $level Log level ('error', 'warning', 'info', 'debug').
	 * @param array<string, mixed> $context Contextual data.
	 * @return void
	 */
	public static function log( string $message, string $level = 'info', array $context = [] ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$sanitized_context = self::sanitize_context( $context );
		$context_str       = ! empty( $sanitized_context ) ? ' ' . wp_json_encode( $sanitized_context ) : '';
		$formatted         = sprintf(
			'[WPCalibrate UCM] [%s] %s%s',
			strtoupper( $level ),
			sanitize_text_field( $message ),
			$context_str
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $formatted );
	}

	/**
	 * Strip sensitive keys (passwords, tokens, nonces, keys) from log context.
	 *
	 * @param array<string, mixed> $context Context array.
	 * @return array<string, mixed> Sanitized context.
	 */
	public static function sanitize_context( array $context ): array {
		$sensitive_keys = [
			'password',
			'pass',
			'pwd',
			'nonce',
			'_wpnonce',
			'token',
			'lock_token',
			'license_key',
			'api_key',
			'secret',
			'cookie',
			'post_content',
			'comment_content',
		];

		$clean = [];
		foreach ( $context as $k => $v ) {
			if ( in_array( strtolower( (string) $k ), $sensitive_keys, true ) ) {
				$clean[ $k ] = '[REDACTED]';
			} elseif ( is_array( $v ) ) {
				$clean[ $k ] = self::sanitize_context( $v );
			} else {
				$clean[ $k ] = $v;
			}
		}

		return $clean;
	}
}
