<?php

namespace MydPro\Includes\Plugin_Update;

use MydPro\Includes\License\License_Manage_Data;
use MydPro\Includes\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin update class.
 */
class Plugin_Update {
	/**
	 * Update server URL (GitHub Pages)
	 *
	 * @var string
	 */
	const URL = 'https://hectorcanaimero.github.io/pidelo/update-info.json';

	/**
	 * License Key
	 */
	private $license_key;

	/**
	 * Website url
	 */
	private $site_url;

	/**
	 * Already forced property.
	 */
	private $already_forced;

	public function __construct() {
		$this->already_forced = false;

		/**
		 * teste
		 */
		$license = Plugin::instance()->license;
		$this->license_key = $license->get_key();

		$this->site_url = \site_url();

		add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
		add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
		add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );
	}

	/**
	 * Get info to plugin details
	 *
	 * @param [type] $result
	 * @param [type] $action
	 * @param [type] $args
	 * @return void
	 */
	public function info( $result, $action = null, $args = null ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		if ( MYD_PLUGIN_DIRNAME !== $args->slug ) {
			return $result;
		}

		$plugin_data_from_server = $this->request();

		if ( ! is_array( $plugin_data_from_server ) ) {
			return $result;
		}

		$plugin_data_from_server = (object) $plugin_data_from_server;

		$result = new \stdClass();
		$result->name = isset( $plugin_data_from_server->name ) ? $plugin_data_from_server->name : '';
		$result->slug = isset( $plugin_data_from_server->slug ) ? $plugin_data_from_server->slug : '';
		$result->version = isset( $plugin_data_from_server->version ) ? $plugin_data_from_server->version : '';
		$result->tested = isset( $plugin_data_from_server->tested ) ? $plugin_data_from_server->tested : '';
		$result->requires = isset( $plugin_data_from_server->requires ) ? $plugin_data_from_server->requires : '';
		$result->author = isset( $plugin_data_from_server->author ) ? $plugin_data_from_server->author : '';
		$result->author_profile = isset( $plugin_data_from_server->author_profile ) ? $plugin_data_from_server->author_profile : '';
		$result->download_link = isset( $plugin_data_from_server->download_url ) ? $plugin_data_from_server->download_url : '';
		$result->trunk = isset( $plugin_data_from_server->download_url ) ? $plugin_data_from_server->download_url : '';
		$result->requires_php = isset( $plugin_data_from_server->requires_php ) ? $plugin_data_from_server->requires_php : '';
		$result->last_updated = isset( $plugin_data_from_server->last_updated ) ? $plugin_data_from_server->last_updated : '';

		$sections = array(
			'description' => '',
			'installation' => '',
			'changelog' => '',
			'upgrade_notice' => '',
		);

		foreach ( $sections as $key => $value ) {
			$sections[ $key ] = isset( $plugin_data_from_server->sections[ $key ] ) ? $plugin_data_from_server->sections[ $key ] : '';
		}

		/**
		 * Check if #result is object. create and object to add them.
		 * add all items with array and for each.
		 * unset license data to use in another feature.
		 */
		$result->sections = $sections;
		return $result;
	}

	/**
	 * Request update info from GitHub Pages
	 *
	 * @return array|false Update info or false on failure.
	 */
	public function request() {
		$data_from_server = get_transient( 'mydpro-update-data' );

		$force_update = isset( $_GET['force-check'] ) ? sanitize_text_field( $_GET['force-check'] ) : null;
		if ( $force_update === '1' && $this->already_forced === false ) {
			$data_from_server = false;
			$this->already_forced = true;
		}

		if ( $data_from_server === false ) {
			// Fetch from GitHub Pages (no license/domain params needed)
			$response = wp_remote_get(
				self::URL,
				array(
					'timeout' => 10,
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);

			if (
				is_wp_error( $response )
				|| 200 !== wp_remote_retrieve_response_code( $response )
				|| empty( wp_remote_retrieve_body( $response ) )
			) {
				// Log error for debugging
				if ( is_wp_error( $response ) ) {
					error_log( 'MyD Update Check Error: ' . $response->get_error_message() );
				}
				return false;
			}

			// Cache for 12 hours (half day)
			set_transient( 'mydpro-update-data', $response, 12 * HOUR_IN_SECONDS );
			$data_from_server = $response;
		}

		$body = wp_remote_retrieve_body( $data_from_server );
		$data = json_decode( $body, true );

		// Validate required fields
		if ( ! is_array( $data ) || ! isset( $data['version'] ) || ! isset( $data['download_url'] ) ) {
			error_log( 'MyD Update Check Error: Invalid response format' );
			return false;
		}

		return $data;
	}

	/**
	 * Real update place. Add info to WP get plugin and etc.
	 *
	 * @param object $transient WordPress update transient.
	 * @return object Modified transient.
	 */
	public function update( $transient ) {
		if ( ! isset( $transient->response ) ) {
			return $transient;
		}

		$plugin_update_data = $this->request();

		if ( ! is_array( $plugin_update_data ) ) {
			return $transient;
		}

		// Check if license is valid before showing update
		if ( ! $this->is_license_valid() ) {
			// Don't show update if license is invalid
			return $transient;
		}

		if ( version_compare( MYD_CURRENT_VERSION, $plugin_update_data['version'], '<' ) ) {
			$res = $this->get_plugin_data( $plugin_update_data );
			$transient->response[ MYD_PLUGIN_BASENAME ] = $res;
		} else {
			if ( isset( $transient->no_update ) ) {
				$res = $this->get_plugin_data( $plugin_update_data );
				$transient->no_update[ MYD_PLUGIN_BASENAME ] = $res;
			}
		}

		return $transient;
	}

	/**
	 * Check if license is valid
	 *
	 * @return bool True if license is valid or license check is disabled.
	 */
	private function is_license_valid() {
		// If no license key, allow updates (for testing)
		// In production, you may want to require a valid license
		if ( empty( $this->license_key ) ) {
			return true; // Change to false to require license
		}

		// Check license status from transient
		$license_data = License_Manage_Data::get_transient();

		if ( ! $license_data ) {
			return true; // Allow if no license data (for testing)
		}

		// Check if license is active
		return isset( $license_data['status'] ) && $license_data['status'] === 'active';
	}

	/**
	 * Get plugin data
	 *
	 * @param array $plugin_update_data Update data from server.
	 * @return object Plugin data object.
	 */
	public function get_plugin_data( $plugin_update_data ) {
		$res = new \stdClass();
		$res->slug = MYD_PLUGIN_DIRNAME;
		$res->plugin = MYD_PLUGIN_BASENAME;
		$res->new_version = isset( $plugin_update_data['version'] ) ? $plugin_update_data['version'] : '';
		$res->tested = isset( $plugin_update_data['tested'] ) ? $plugin_update_data['tested'] : '';
		$res->package = isset( $plugin_update_data['download_url'] ) ? $plugin_update_data['download_url'] : '';
		$res->requires_php = isset( $plugin_update_data['requires_php'] ) ? $plugin_update_data['requires_php'] : '';
		$res->requires = isset( $plugin_update_data['requires'] ) ? $plugin_update_data['requires'] : '';
		return $res;
	}

	/**
	 * Clean after update
	 *
	 * @return void
	 */
	public function purge( $upgrader, $options ) {
		if ( $options['action'] === 'update' && $options['type'] === 'plugin' && isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if( $plugin === MYD_PLUGIN_BASENAME ) {
					self::delete_plugin_update_transient();
				}
			}
		}
	}

	/**
	 * Delete update transient
	 *
	 * @return void
	 */
	public static function delete_plugin_update_transient() {
		delete_transient( 'mydpro-update-data' );
	}
}
