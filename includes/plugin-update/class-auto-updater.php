<?php

namespace MydPro\Includes\Plugin_Update;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auto Updater
 *
 * Enables automatic updates for the plugin
 */
class Auto_Updater {

	/**
	 * Option name for auto-update setting
	 */
	const OPTION_ENABLED = 'myd_auto_update_enabled';

	/**
	 * Initialize auto-updater
	 */
	public function __construct() {
		add_filter( 'auto_update_plugin', array( $this, 'enable_auto_update' ), 10, 2 );
		add_filter( 'auto_updater_disabled', array( $this, 'check_global_setting' ) );
	}

	/**
	 * Check if auto-update is enabled
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return get_option( self::OPTION_ENABLED, false ) === '1';
	}

	/**
	 * Enable auto-update for this plugin
	 *
	 * @param bool   $update Whether to update
	 * @param object $item   Update object
	 * @return bool
	 */
	public function enable_auto_update( $update, $item ) {
		// Check if this is our plugin
		if ( isset( $item->plugin ) && $item->plugin === MYD_PLUGIN_BASENAME ) {
			// Only enable if setting is on
			if ( self::is_enabled() ) {
				return true;
			}
		}

		return $update;
	}

	/**
	 * Check if global auto-updater is disabled
	 *
	 * @param bool $disabled Whether auto-updater is disabled
	 * @return bool
	 */
	public function check_global_setting( $disabled ) {
		// If global auto-updater is disabled, respect it
		return $disabled;
	}
}
