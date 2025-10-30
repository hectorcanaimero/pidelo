<?php
/**
 * Plugin Name: MyD Delivery Pro
 * Plugin URI: https://pideai.com
 * Description: MyD Delivery create a complete system to delivery with products, orders, clients, support to send order on WhatsApp and more.
 * Author: EduardoVillao.me
 * Author URI: https://pideai.com
 * Version: 2.3.4
 * Requires PHP: 7.4
 * Requires at least: 5.5
 * Text Domain: myd-delivery-pro
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package Myd_Delivery_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MYD_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MYD_PLUGN_URL', plugin_dir_url( __FILE__ ) );
define( 'MYD_PLUGIN_MAIN_FILE', __FILE__ );
define( 'MYD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'MYD_PLUGIN_DIRNAME', plugin_basename( __DIR__ ) );
define( 'MYD_CURRENT_VERSION', '2.3.4' );
define( 'MYD_MINIMUM_PHP_VERSION', '7.4' );
define( 'MYD_MINIMUM_WP_VERSION', '5.5' );
define( 'MYD_PLUGIN_NAME', 'MyD Delivery Pro' );

/**
 * Check PHP and WP version before include plugin main class
 *
 * @since 1.9.6
 */
if ( ! version_compare( PHP_VERSION, MYD_MINIMUM_PHP_VERSION, '>=' ) ) {

	add_action( 'admin_notices', 'mydp_admin_notice_php_version_fail' );
	return;
}

if ( ! version_compare( get_bloginfo( 'version' ), MYD_MINIMUM_WP_VERSION, '>=' ) ) {

	add_action( 'admin_notices', 'mydp_admin_notice_wp_version_fail' );
	return;
}

include_once MYD_PLUGIN_PATH . 'includes/class-plugin.php';
MydPro\Includes\Plugin::instance();

/**
 * Admin notice PHP version fail
 *
 * @since 1.9.6
 * @return void
 */
function mydp_admin_notice_php_version_fail() {

	$message = sprintf(
		esc_html__( '%1$s requires PHP version %2$s or greater.', 'myd-delivery-pro' ),
		'<strong>MyD Delivery Pro</strong>',
		MYD_MINIMUM_PHP_VERSION
	);

	$html_message = sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message );

	echo wp_kses_post( $html_message );
}

update_option('fdm-license', ['set_status' => 'active']); set_transient('myd_license_data', ['key' => '*************', 'status' => 'active', 'site_url' => get_site_url()], 30 * DAY_IN_SECONDS);
/**
 * Admin notice WP version fail
 *
 * @since 1.9.6
 * @return void
 */
function mydp_admin_notice_wp_version_fail() {

	$message = sprintf(
		esc_html__( '%1$s requires WordPress version %2$s or greater.', 'myd-delivery-pro' ),
		'<strong>MyD Delivery Pro</strong>',
		MYD_MINIMUM_WP_VERSION
	);

	$html_message = sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message );

	echo wp_kses_post( $html_message );
}
