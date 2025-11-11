<?php

namespace MydPro\Includes\Plugin_Update;

use MydPro\Includes\License\License_Manage_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update Menu Badge
 *
 * Adds update notification badge to plugin menu
 */
class Update_Menu_Badge {

	/**
	 * Initialize menu badge
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_update_badge' ), 999 );
		add_action( 'admin_head', array( $this, 'add_badge_styles' ) );
	}

	/**
	 * Add update badge to menu
	 */
	public function add_update_badge() {
		global $menu, $submenu;

		// Check if there's an update available
		if ( ! $this->has_update() ) {
			return;
		}

		// Find our menu item
		foreach ( $menu as $key => $item ) {
			// Check if this is our plugin menu
			if ( isset( $item[2] ) && $item[2] === 'myd-delivery-dashoboard' ) {
				// Add badge to menu title
				$badge = ' <span class="update-plugins myd-update-badge"><span class="plugin-count">1</span></span>';
				$menu[ $key ][0] .= $badge;
				break;
			}
		}

		// Also add to Settings submenu if exists
		if ( isset( $submenu['myd-delivery-dashoboard'] ) ) {
			foreach ( $submenu['myd-delivery-dashoboard'] as $key => $item ) {
				if ( isset( $item[2] ) && $item[2] === 'myd-update-settings' ) {
					$badge = ' <span class="update-plugins myd-update-badge"><span class="plugin-count">1</span></span>';
					$submenu['myd-delivery-dashoboard'][ $key ][0] .= $badge;
					break;
				}
			}
		}
	}

	/**
	 * Add badge styles
	 */
	public function add_badge_styles() {
		if ( ! $this->has_update() ) {
			return;
		}

		?>
		<style>
			.myd-update-badge {
				display: inline-block;
				margin-left: 5px;
				vertical-align: top;
			}
			/* Ensure badge shows correctly in collapsed menu */
			#adminmenu .wp-menu-image img + .myd-update-badge {
				position: absolute;
				top: 7px;
				right: 10px;
			}
		</style>
		<?php
	}

	/**
	 * Check if update is available
	 *
	 * @return bool
	 */
	private function has_update() {
		// Check license validity
		$license_data = License_Manage_Data::get_transient();
		$license_valid = $license_data && isset( $license_data['status'] ) && $license_data['status'] === 'active';

		if ( ! $license_valid ) {
			return false;
		}

		// Check for updates
		$update_checker = new Plugin_Update();
		$update_info = $update_checker->request();

		if ( ! is_array( $update_info ) || ! isset( $update_info['version'] ) ) {
			return false;
		}

		$new_version = $update_info['version'];
		$current_version = MYD_CURRENT_VERSION;

		return version_compare( $current_version, $new_version, '<' );
	}
}
