<?php

namespace MydPro\Includes\Plugin_Update;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update History Logger
 *
 * Records all plugin updates for tracking and debugging
 */
class Update_History {

	/**
	 * Option name for update history
	 */
	const OPTION_HISTORY = 'myd_update_history';

	/**
	 * Maximum number of history entries to keep
	 */
	const MAX_ENTRIES = 50;

	/**
	 * Initialize history logging
	 */
	public function __construct() {
		add_action( 'upgrader_process_complete', array( $this, 'log_update' ), 10, 2 );
	}

	/**
	 * Log a completed update
	 *
	 * @param object $upgrader WP_Upgrader instance
	 * @param array  $options  Update options
	 */
	public function log_update( $upgrader, $options ) {
		// Only log if this is our plugin
		if ( $options['action'] !== 'update' || $options['type'] !== 'plugin' ) {
			return;
		}

		if ( ! isset( $options['plugins'] ) || ! is_array( $options['plugins'] ) ) {
			return;
		}

		if ( ! in_array( MYD_PLUGIN_BASENAME, $options['plugins'], true ) ) {
			return;
		}

		// Get new version
		$new_version = MYD_CURRENT_VERSION;

		// Determine if update was successful
		$success = ! is_wp_error( $upgrader->skin->result );
		$error_message = '';

		if ( ! $success && is_wp_error( $upgrader->skin->result ) ) {
			$error_message = $upgrader->skin->result->get_error_message();
		}

		// Log the update
		$this->add_entry( array(
			'version' => $new_version,
			'success' => $success,
			'error' => $error_message,
			'user_id' => get_current_user_id(),
			'user_login' => wp_get_current_user()->user_login,
		) );
	}

	/**
	 * Add a history entry
	 *
	 * @param array $data Entry data
	 */
	public function add_entry( $data ) {
		$history = $this->get_history();

		$entry = array(
			'version' => isset( $data['version'] ) ? $data['version'] : '',
			'timestamp' => time(),
			'success' => isset( $data['success'] ) ? (bool) $data['success'] : true,
			'error' => isset( $data['error'] ) ? $data['error'] : '',
			'user_id' => isset( $data['user_id'] ) ? (int) $data['user_id'] : 0,
			'user_login' => isset( $data['user_login'] ) ? $data['user_login'] : 'unknown',
			'site_url' => get_site_url(),
			'wp_version' => get_bloginfo( 'version' ),
			'php_version' => phpversion(),
		);

		// Add to beginning of array
		array_unshift( $history, $entry );

		// Limit entries
		if ( count( $history ) > self::MAX_ENTRIES ) {
			$history = array_slice( $history, 0, self::MAX_ENTRIES );
		}

		update_option( self::OPTION_HISTORY, $history );

		// Log for debugging
		error_log( sprintf(
			'MyD Update History: Logged %s update to version %s',
			$entry['success'] ? 'successful' : 'failed',
			$entry['version']
		) );
	}

	/**
	 * Get update history
	 *
	 * @return array Array of history entries
	 */
	public static function get_history() {
		$history = get_option( self::OPTION_HISTORY, array() );

		if ( ! is_array( $history ) ) {
			$history = array();
		}

		return $history;
	}

	/**
	 * Get last successful update
	 *
	 * @return array|null Last successful update entry or null
	 */
	public static function get_last_successful_update() {
		$history = self::get_history();

		foreach ( $history as $entry ) {
			if ( $entry['success'] ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * Get failed updates
	 *
	 * @return array Array of failed update entries
	 */
	public static function get_failed_updates() {
		$history = self::get_history();

		return array_filter( $history, function( $entry ) {
			return ! $entry['success'];
		} );
	}

	/**
	 * Clear history
	 */
	public static function clear_history() {
		delete_option( self::OPTION_HISTORY );
	}

	/**
	 * Export history as CSV
	 *
	 * @return string CSV content
	 */
	public static function export_csv() {
		$history = self::get_history();

		$csv = "Version,Date,Time,Success,User,Error,WP Version,PHP Version\n";

		foreach ( $history as $entry ) {
			$date = date( 'Y-m-d', $entry['timestamp'] );
			$time = date( 'H:i:s', $entry['timestamp'] );
			$success = $entry['success'] ? 'Yes' : 'No';
			$error = ! empty( $entry['error'] ) ? str_replace( '"', '""', $entry['error'] ) : '';

			$csv .= sprintf(
				'"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
				$entry['version'],
				$date,
				$time,
				$success,
				$entry['user_login'],
				$error,
				$entry['wp_version'],
				$entry['php_version']
			);
		}

		return $csv;
	}

	/**
	 * Get statistics
	 *
	 * @return array Statistics data
	 */
	public static function get_statistics() {
		$history = self::get_history();

		$total = count( $history );
		$successful = count( array_filter( $history, function( $entry ) {
			return $entry['success'];
		} ) );
		$failed = $total - $successful;

		$last_update = null;
		if ( ! empty( $history ) ) {
			$last_update = $history[0];
		}

		return array(
			'total' => $total,
			'successful' => $successful,
			'failed' => $failed,
			'success_rate' => $total > 0 ? round( ( $successful / $total ) * 100, 2 ) : 0,
			'last_update' => $last_update,
		);
	}
}
