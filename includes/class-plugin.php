<?php

namespace MydPro\Includes;

use MydPro\Includes\Store_Data;
use MydPro\Includes\Admin\Settings;
use MydPro\Includes\Admin\Custom_Posts;
use MydPro\Includes\Admin\Admin_Page;
use MydPro\Includes\License\License;
use MydPro\Includes\Plugin_Update\Plugin_Update;
use MydPro\Includes\Custom_Fields\Myd_Custom_Fields;
use MydPro\Includes\Custom_Fields\Register_Custom_Fields;
use MydPro\Includes\Ajax\Update_Cart;
use MydPro\Includes\Ajax\Create_Draft_Order;
use MydPro\Includes\Ajax\Place_Payment;
use MydPro\Includes\Ajax\Evolution_Ajax;
use MydPro\Includes\Integrations\Evolution_Api\Order_Hooks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin main class
 *
 * @since 1.9.6
 */
final class Plugin {

	/**
	 * Store data
	 *
	 * @since 1.9.6
	 *
	 * TODO: change to protected and create method to get
	 */
	public $store_data;

	/**
	 * License
	 *
	 * @since 1.9.6
	 *
	 * TODO: change to protected and create method to get
	 */
	public $license;

	/**
	 * License
	 *
	 * @since 1.9.6
	 */
	protected $admin_settings;

	/**
	 * Custom Posts
	 *
	 * @since 1.9.6
	 */
	protected $custom_posts;

	/**
	 * Admin menu pages
	 */
	protected $admin_menu_pages;

	/**
	 * Instance
	 *
	 * @since 1.9.4
	 *
	 * @access private
	 * @static
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.9.4
	 *
	 * @access public
	 * @static
	 *
	 * @return Plugin An instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Disable class cloning and throw an error on object clone.
	 *
	 * @access public
	 * @since 1.9.6
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'myd-delivery-pro' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @access public
	 * @since 1.9.6
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'myd-delivery-pro' ), '1.0' );
	}

	/**
	 * Construct class
	 *
	 * @since 1.2
	 * @return void
	 */
	private function __construct() {
		\do_action( 'myd_delivery_pro_init' );
		\add_action( 'init', [ $this, 'init' ] );
		\register_activation_hook( MYD_PLUGIN_MAIN_FILE, [ $this, 'activation' ] );
		\register_deactivation_hook( MYD_PLUGIN_MAIN_FILE, [ $this, 'deactivation' ] );
	}

	/**
	 * Init plugin
	 *
	 * @since 1.9.4
	 */
	public function init() {
		/**
		 * Check and solve plugin path name
		 */
		$this->check_plugin_path();

		/**
		 * Check if old version of plugin is active
		 */
		if ( $this->plugin_is_active( 'my-delivey-wordpress/my-delivey-wordpress.php' ) || $this->plugin_is_active( 'my-delivery-wordpress/my-delivery-wordpress.php' ) ) {

			$error_message = sprintf(
				esc_html__( '%1$s requires MyDelivery WordPress (our old version) to be deactivated.', 'myd-delivery-pro' ),
				'<strong>MyD Delivery Pro</strong>'
			);

			add_action( 'admin_notices', function( $message ) use ( $error_message ) {
					printf( '<div class="notice notice-error"><p>%1$s</p></div>', $error_message );
				}
			);
			return;
		}

		/**
		 * Required files (load classes)
		 */
		$this->set_required_files();
		\load_plugin_textdomain( 'myd-delivery-pro', false, MYD_PLUGIN_DIRNAME . '/languages' );

		new Update_Cart();
		new Create_Draft_Order();
		new Place_Payment();
		new Ajax\Customer_Details();

		// Evolution API Integration
		if ( get_option( 'myd-evolution-api-enabled' ) === 'yes' ) {
			new Ajax\Evolution_Ajax();
			new Integrations\Evolution_Api\Order_Hooks();
		}

		// Initialize API endpoints
		new Api\Products\Products_Api();
		new Api\Orders\Orders_Api();
		new Api\Customers\Customers_Api();
		new Api\Coupons\Coupons_Api();
		new Api\Reports\Reports_Api();
		new Api\Settings\Settings_Api();
		new Api\Media\Media_Api();
		// Note: Categories, Cart and Auth APIs are auto-instantiated in their respective files

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frondend_scripts' ] );

		$this->license = new License();

		if ( is_admin() ) {
			$this->admin_settings = new Settings();
			add_action( 'admin_init', [ $this->admin_settings, 'register_settings' ] );

			$this->admin_menu_pages = new Admin_Page();
			add_action( 'admin_menu', [ $this->admin_menu_pages, 'add_admin_pages' ] );
		}

		$this->custom_posts = new Custom_Posts();
		$this->custom_posts->register_custom_posts();

		Store_Data::set_store_data();
		$this->store_data = Store_Data::get_store_data();

		/**
		 * Plugin update checker
		 */
		$plugin_update = new Plugin_Update();
		add_filter( 'plugins_api', array( $plugin_update, 'info' ), 20, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $plugin_update, 'update' ) );
		add_action( 'upgrader_process_complete', array( $plugin_update, 'purge' ), 10, 2 );

		// Initialize update notification system
		new \MydPro\Includes\Plugin_Update\Update_Dashboard_Widget();
		new \MydPro\Includes\Plugin_Update\Update_Email_Notification();
		new \MydPro\Includes\Plugin_Update\Update_History();
		new \MydPro\Includes\Plugin_Update\Auto_Updater();
		new \MydPro\Includes\Plugin_Update\Update_Menu_Badge();

		if ( is_admin() ) {
			new \MydPro\Includes\Plugin_Update\Update_Settings_Page();
		}

		/**
		 * TODO: Move to license class
		 */
		add_action( 'in_plugin_update_message-myd-delivery-pro/myd-delivery-pro.php', [ $this, 'update_notice_invalid_license' ], 10, 2 );

		if ( is_admin() ) {
			new Myd_Custom_Fields( Register_Custom_Fields::get_registered_fields() );
		}

		Currency_Converter::register_shortcode();
	}

	/**
	 * Load required files
	 *
	 * @since 1.2
	 * @return void
	 */
	public function set_required_files() {
		if ( is_admin() ) {
			include_once MYD_PLUGIN_PATH . 'includes/admin/class-admin-page.php';
			include_once MYD_PLUGIN_PATH . 'includes/admin/abstract-class-admin-settings.php';
			include_once MYD_PLUGIN_PATH . 'includes/admin/class-settings.php';
			include_once MYD_PLUGIN_PATH . 'includes/class-reports.php';
		}

		include_once MYD_PLUGIN_PATH . 'includes/legacy/class-legacy-repeater.php';
		include_once MYD_PLUGIN_PATH . 'includes/custom-fields/class-register-custom-fields.php';
		include_once MYD_PLUGIN_PATH . 'includes/custom-fields/class-label.php';
		include_once MYD_PLUGIN_PATH . 'includes/custom-fields/class-custom-fields.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-store-data.php';
		include_once MYD_PLUGIN_PATH . 'includes/admin/class-custom-posts.php';
		include_once MYD_PLUGIN_PATH . 'includes/fdm-products-list.php';
		include_once MYD_PLUGIN_PATH . 'includes/myd-manage-cpt-columns.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-orders-front-panel.php';
		include_once MYD_PLUGIN_PATH . 'includes/fdm-track-order.php';
		include_once MYD_PLUGIN_PATH . 'includes/api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/class-rate-limiter.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/sse/class-order-status-tracking.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/order/class-get-order.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/products/class-products-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/orders/class-orders-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/customers/class-customers-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/coupons/class-coupons-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/reports/class-reports-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/settings/class-settings-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/media/class-media-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/categories/class-categories-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/cart/class-cart-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/auth/class-auth-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/swagger/class-swagger-ui.php';
		include_once MYD_PLUGIN_PATH . 'includes/set-custom-styles.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-legacy.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-store-orders.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-store-formatting.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/abstract-class-license-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/interface-license-action.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/class-license-manage-data.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/class-license.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/class-license-activate.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/class-license-deactivate.php';
		include_once MYD_PLUGIN_PATH . 'includes/plugin-update/class-plugin-update.php';
		include_once MYD_PLUGIN_PATH . 'includes/plugin-update/class-update-dashboard-widget.php';
		include_once MYD_PLUGIN_PATH . 'includes/plugin-update/class-update-email-notification.php';
		include_once MYD_PLUGIN_PATH . 'includes/plugin-update/class-update-history.php';
		include_once MYD_PLUGIN_PATH . 'includes/plugin-update/class-auto-updater.php';
		include_once MYD_PLUGIN_PATH . 'includes/plugin-update/class-update-menu-badge.php';
		include_once MYD_PLUGIN_PATH . 'includes/plugin-update/class-update-settings-page.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-currency.php';
		include_once MYD_PLUGIN_PATH . 'includes/l10n/class-countries.php';
		include_once MYD_PLUGIN_PATH . 'includes/l10n/class-country.php';
		include_once MYD_PLUGIN_PATH . 'includes/ajax/class-update-cart.php';
		include_once MYD_PLUGIN_PATH . 'includes/ajax/class-create-draft-order.php';
		include_once MYD_PLUGIN_PATH . 'includes/ajax/class-place-payment.php';
		include_once MYD_PLUGIN_PATH . 'includes/ajax/class-customer-details.php';

		// Evolution API Integration
		include_once MYD_PLUGIN_PATH . 'includes/integrations/evolution-api/class-evolution-client.php';
		include_once MYD_PLUGIN_PATH . 'includes/integrations/evolution-api/class-whatsapp-service.php';
		include_once MYD_PLUGIN_PATH . 'includes/integrations/evolution-api/class-logger.php';
		include_once MYD_PLUGIN_PATH . 'includes/integrations/evolution-api/class-order-hooks.php';
		include_once MYD_PLUGIN_PATH . 'includes/integrations/evolution-api/class-instance-manager.php';
		include_once MYD_PLUGIN_PATH . 'includes/ajax/class-evolution-ajax.php';

		include_once MYD_PLUGIN_PATH . 'includes/class-cart.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-create-draft-order.php';
		include_once MYD_PLUGIN_PATH . 'includes/repositories/class-coupon-repository.php';
		include_once MYD_PLUGIN_PATH . 'includes/repositories/class-customer-repository.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-coupon.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-customer.php';
		include_once MYD_PLUGIN_PATH . '/includes/class-create-draft-order.php';
		include_once MYD_PLUGIN_PATH . '/includes/class-custom-message-whatsapp.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-currency-converter.php';
	}

	/**
	 * Enqueu admin styles/scripts
	 *
	 * @since 1.2
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		wp_register_script( 'myd-admin-scritps', MYD_PLUGN_URL . 'assets/js/admin/admin-scripts.min.js', [], MYD_CURRENT_VERSION, true );
		wp_enqueue_script( 'myd-admin-scritps' );

		wp_register_script( 'myd-admin-cf-media-library', MYD_PLUGN_URL . 'assets/js/admin/custom-fields/media-library.min.js', [], MYD_CURRENT_VERSION, true );
		wp_register_script( 'myd-admin-cf-repeater', MYD_PLUGN_URL . 'assets/js/admin/custom-fields/repeater.min.js', [], MYD_CURRENT_VERSION, true );

		wp_register_style( 'myd-admin-style', MYD_PLUGN_URL . 'assets/css/admin/admin-style.min.css', [], MYD_CURRENT_VERSION );
		wp_enqueue_style( 'myd-admin-style' );

		wp_register_script( 'myd-chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), MYD_CURRENT_VERSION, true );

		// Evolution API assets (cargar en página de settings)
		$screen = get_current_screen();
		$should_load = false;

		// Verificar por screen ID o por parámetro GET
		if ( $screen && $screen->id === 'mydelivery-orders_page_myd-delivery-settings' ) {
			$should_load = true;
		} elseif ( isset( $_GET['page'] ) && $_GET['page'] === 'myd-delivery-settings' ) {
			$should_load = true;
		}

		if ( $should_load ) {
			wp_register_script(
				'myd-evolution-admin',
				MYD_PLUGN_URL . 'assets/js/evolution-admin.js',
				array( 'jquery' ),
				MYD_CURRENT_VERSION,
				true
			);

			wp_localize_script(
				'myd-evolution-admin',
				'mydEvolutionData',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'myd-evolution-send' ),
					'i18n'    => array(
						'testing'          => __( 'Testing...', 'myd-delivery-pro' ),
						'connected'        => __( 'Connected', 'myd-delivery-pro' ),
						'disconnected'     => __( 'Disconnected', 'myd-delivery-pro' ),
						'connectionError'  => __( 'Connection error', 'myd-delivery-pro' ),
						'confirmSend'      => __( 'Send WhatsApp message to customer?', 'myd-delivery-pro' ),
						'sentNow'          => __( 'Sent now', 'myd-delivery-pro' ),
						'sendError'        => __( 'Error sending message', 'myd-delivery-pro' ),
						'creating'         => __( 'Creating...', 'myd-delivery-pro' ),
						'creatingInstance' => __( 'Creating WhatsApp instance...', 'myd-delivery-pro' ),
						'scanQr'           => __( 'Scan the QR code with your WhatsApp', 'myd-delivery-pro' ),
						'qrError'          => __( 'Error loading QR code', 'myd-delivery-pro' ),
						'confirmLogout'    => __( 'Disconnect WhatsApp instance?', 'myd-delivery-pro' ),
						'disconnecting'    => __( 'Disconnecting...', 'myd-delivery-pro' ),
						'clickToGenerate'  => __( 'Click "Generate QR" to start', 'myd-delivery-pro' ),
					),
				)
			);

			wp_enqueue_script( 'myd-evolution-admin' );

			wp_register_style(
				'myd-evolution-api',
				MYD_PLUGN_URL . 'assets/css/evolution-api.css',
				array(),
				MYD_CURRENT_VERSION
			);
			wp_enqueue_style( 'myd-evolution-api' );
		}
	}

	/**
	 * Enqueue front end styles/scripts
	 *
	 * @since 1.2
	 * @return void
	 */
	public function enqueue_frondend_scripts() {
		wp_register_script( 'plugin_pdf', 'https://printjs-4de6.kxcdn.com/print.min.js', array(), MYD_CURRENT_VERSION, true );
		wp_register_style( 'plugin_pdf_css', 'https://printjs-4de6.kxcdn.com/print.min.css', array(), MYD_CURRENT_VERSION, true );

		// jQuery Mask Plugin para máscaras de teléfono
		wp_register_script( 'jquery-mask', MYD_PLUGN_URL . 'assets/lib/js/jquery.mask.js', array( 'jquery' ), '1.14.16', true );

		// Inicializar jQuery Mask para todos los campos con data-mask
		$mask_init_script = "
		jQuery(document).ready(function($) {
			// Inicializar máscara para todos los campos con data-mask
			$('[data-mask]').each(function() {
				var maskPattern = $(this).attr('data-mask');
				var maskReverse = $(this).attr('data-mask-reverse') === 'true';

				if (maskPattern && maskPattern !== '') {
					$(this).mask(maskPattern, {
						reverse: maskReverse
					});
				}
			});
		});
		";
		wp_add_inline_script( 'jquery-mask', $mask_init_script );

		wp_register_script( 'myd-create-order', MYD_PLUGN_URL . 'assets/js/order.min.js', array( 'jquery-mask' ), MYD_CURRENT_VERSION, true );
		wp_localize_script(
			'myd-create-order',
			'ajax_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'order_nonce' => wp_create_nonce( 'myd-create-order' ),
			)
		);

		// Agregar mydStoreInfo siempre que se registre el script
		wp_add_inline_script( 'myd-create-order', $this->get_store_info_js(), 'before' );

		// Register payment receipt upload handler
		wp_register_script( 'myd-payment-receipt', MYD_PLUGN_URL . 'assets/js/payment-receipt.js', array( 'myd-create-order' ), MYD_CURRENT_VERSION, true );

		// Register skip payment in store handler
		wp_register_script( 'myd-skip-payment-in-store', MYD_PLUGN_URL . 'assets/js/skip-payment-in-store.js', array( 'myd-create-order' ), MYD_CURRENT_VERSION, true );

		wp_register_style( 'myd-delivery-frontend', MYD_PLUGN_URL . 'assets/css/delivery-frontend.min.css', array(), MYD_CURRENT_VERSION );
		wp_register_style( 'myd-order-panel-frontend', MYD_PLUGN_URL . 'assets/css/order-panel-frontend.min.css', array(), MYD_CURRENT_VERSION );
		wp_register_style( 'myd-track-order-frontend', MYD_PLUGN_URL . 'assets/css/track-order-frontend.min.css', array(), MYD_CURRENT_VERSION );

		/**
		 * Orders Panel
		 * TODO: refactor Jquery and merge scripts
		 */
		wp_register_script( 'myd-orders-panel', MYD_PLUGN_URL . 'assets/js/orders-panel/frontend.min.js', array(), MYD_CURRENT_VERSION, true );
		wp_localize_script(
			'myd-orders-panel',
			'order_ajax_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'myd-order-notification' ),
				'domain' => esc_attr( home_url() ),
			)
		);

		wp_register_script( 'myd-order-list-ajax', MYD_PLUGN_URL . 'assets/js/order-list-ajax.min.js', array( 'jquery' ), MYD_CURRENT_VERSION, true );

		// Script personalizado para manejar impresión de órdenes
		wp_add_inline_script( 'myd-orders-panel', $this->get_print_handler_js() );

		// Script mejorado para notificaciones de audio
		wp_add_inline_script( 'myd-orders-panel', $this->get_enhanced_notification_js() );

		// Evolution API assets para panel de órdenes
		if ( get_option( 'myd-evolution-api-enabled' ) === 'yes' ) {
			wp_register_script(
				'myd-evolution-panel',
				MYD_PLUGN_URL . 'assets/js/evolution-admin.js',
				array( 'jquery', 'myd-orders-panel' ),
				MYD_CURRENT_VERSION,
				true
			);

			wp_localize_script(
				'myd-evolution-panel',
				'mydEvolutionData',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'myd-evolution-send' ),
					'i18n'    => array(
						'confirmSend' => __( 'Send WhatsApp message to customer?', 'myd-delivery-pro' ),
						'sentNow'     => __( 'Sent now', 'myd-delivery-pro' ),
						'sendError'   => __( 'Error sending message', 'myd-delivery-pro' ),
					),
				)
			);

			wp_register_style(
				'myd-evolution-panel-css',
				MYD_PLUGN_URL . 'assets/css/evolution-api.css',
				array(),
				MYD_CURRENT_VERSION
			);
		}
		
		// Script mejorado para actualizaciones en tiempo real
		wp_add_inline_script( 'myd-orders-panel', $this->get_realtime_updates_js() );
		/**
		 * END Orders Panel
		 */
	}

	/**
	 * Fix plugin path name error
	 *
	 * Solve problem caused in old version ipdate
	 *
	 * @since 1.9.4
	 */
	public function check_plugin_path() {
		if ( is_admin() ) {

			$current_path = MYD_PLUGIN_PATH;

			if ( strpos( $current_path, 'my-delivey-wordpress' ) !== false ) {

				if ( ! function_exists( 'deactivate_plugins' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				$deactive = deactivate_plugins( 'my-delivey-wordpress/myd-delivery-pro.php' );
				if ( is_wp_error( $deactive ) ) {
					esc_html_e( 'Error to deactive, contato MyD Delivery support.', 'myd-delivery-pro' );
					return;
				}

				$new_path = str_replace( 'my-delivey-wordpress', 'myd-delivery-pro', $current_path );
				rename( $current_path, $new_path );

				wp_safe_redirect( site_url( '/wp-admin/plugins.php' ) );
				exit;
			}
		}
	}

	/**
	 * Update notice
	 *
	 * @since 1.9.4
	 * @return void
	 */
	public function update_notice_invalid_license( $plugin_data, $new_data ) {

		if ( empty( $new_data->package ) ) {
			printf(
				'<br><span><strong>%1s</strong> %2s.</span>',
				esc_html__( 'Important:', 'myd-delivery-pro' ),
				esc_html__( 'Update is not available because your license is invalid', 'myd-delivery-pro' )
			);
		}
	}

	/**
	 * Check if plugin is activated
	 *
	 * @since 1.9.4
	 * @return boolean
	 * @param string $plugin
	 */
	public function plugin_is_active( $plugin ) {
		return function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin ) : in_array( $plugin, (array) get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Activation hook
	 *
	 * @since 1.9.6
	 * @return void
	 */
	public function activation() {
		\flush_rewrite_rules();
	}

	/**
	 * Deactivation hook
	 *
	 * @since 1.9.6
	 * @return void
	 */
	public function deactivation() {
		\flush_rewrite_rules();
	}

	/**
	 * Get store info JavaScript variables
	 * 
	 * @since 2.2.19
	 * @return string
	 */
	public function get_store_info_js() {
		/**
		 * Delivery time
		 */
		$date = current_time( 'Y-m-d' );
		$current_week_day = strtolower( date( 'l', strtotime( $date ) ) );
		$delivery_time = get_option( 'myd-delivery-time' );
		if( isset( $delivery_time[$current_week_day] ) ) {
			$current_delivery_time = $delivery_time[ $current_week_day ];
		} else {
			$current_delivery_time = 'false';
		}

		/**
		 * Delivery mode and options
		 */
		$shipping_type = get_option( 'myd-delivery-mode' );
		$shipping_options = get_option( 'myd-delivery-mode-options' );
		if ( isset( $shipping_options[ $shipping_type ] ) ) {
			if ( $shipping_type === 'per-distance' ) {
				$shipping_options[ $shipping_type ]['originAddress'] = array(
					'latitude' => get_option( 'myd-shipping-distance-address-latitude' ),
					'longitude' => get_option( 'myd-shipping-distance-address-longitude' ),
				);
				$shipping_options[ $shipping_type ]['googleApi'] = array(
					'key' => get_option( 'myd-shipping-distance-google-api-key' ),
				);
			}

			$shipping_options = $shipping_options[ $shipping_type ];
		} else {
			$shipping_options = 'false';
		}

		$store_data = array(
			'currency' => array(
				'symbol' => Store_Data::get_store_data( 'currency_simbol' ),
				'decimalSeparator' => get_option( 'fdm-decimal-separator' ),
				'decimalNumbers' => intval( get_option( 'fdm-number-decimal' ) ),
			),
			'countryCode' => Store_Data::get_store_data( 'country_code' ),
			'forceStore' => get_option( 'myd-delivery-force-open-close-store' ),
			'deliveryTime' => $current_delivery_time,
			'deliveryShipping' => array(
				'method' => \esc_attr( $shipping_type ),
				'options' => $shipping_options,
			),
			'minimumPurchase' => get_option( 'myd-option-minimum-price' ),
			'autoRedirect' => get_option( 'myd-option-redirect-whatsapp' ),
			'messages' => array(
				'storeClosed' => esc_html__( 'The store is closed', 'myd-delivery-pro' ),
				'cartEmpty' => esc_html__( 'Cart empty', 'myd-delivery-pro' ),
				'addToCard' => esc_html__( 'Added to cart', 'myd-delivery-pro' ),
				'deliveryAreaError' => esc_html__( 'Sorry, delivery area is not supported', 'myd-delivery-pro' ),
				'invalidCoupon' => esc_html__( 'Invalid coupon', 'myd-delivery-pro' ),
				'removedFromCart' => esc_html__( 'Removed from cart', 'myd-delivery-pro' ),
				'extraRequired' => esc_html__( 'Select required extra', 'myd-delivery-pro' ),
				'extraMin' => esc_html__( 'Select the minimum required for the extra', 'myd-delivery-pro' ),
				'inputRequired' => esc_html__( 'Required inputs empty', 'myd-delivery-pro' ),
				'minimumPrice' => esc_html__( 'The minimum order is', 'myd-delivery-pro' ),
				'template' => false,
				'shipping' => array(
					'mapApiError' => esc_html__( 'Sorry, error on request to calculate delivery distance', 'myd-delivery-pro' ),
					'outOfArea' => esc_html__( 'Sorry, your address is out of our delivery area', 'myd-delivery-pro' ),
				),
			),
		);

		$store_data = \wp_json_encode( $store_data, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE );
		return 'const mydStoreInfo = ' . $store_data . ';';
	}

	/**
	 * Get print handler JavaScript
	 * 
	 * @since 2.2.19
	 * @return string
	 */
	public function get_print_handler_js() {
		return "
		// Función para actualizar estado de pago al imprimir
		function mydUpdatePaymentOnPrint(orderId, callback) {
			jQuery.ajax({
				url: '" . admin_url( 'admin-ajax.php' ) . "',
				type: 'POST',
				data: {
					action: 'print_orders',
					order_id: orderId,
					nonce: order_ajax_object.nonce
				},
				success: function(response) {
					if (response.success && response.print_content) {
						// Update the print content with fresh data
						jQuery('#hide-prints').html(response.print_content);
						console.log('MYD: Payment status updated and print content refreshed');
					}
					if (callback) callback(response);
				},
				error: function(xhr, status, error) {
					console.error('MYD: Error al actualizar estado de pago:', error);
					if (callback) callback(null);
				}
			});
		}
		
		// Variable to prevent double-click on print button
		let printInProgress = false;
		
		// Use document ready to ensure we override after all scripts load
		jQuery(document).ready(function() {
			// Remove existing event handlers and add our own
			jQuery(document).off('click', '.fdm-btn-order-action');
			jQuery('.fdm-btn-order-action').off('click');
			
			jQuery(document).on('click', '.fdm-btn-order-action', function(e) {
				jQuery(document).ajaxStart(function() {
					jQuery('.fdm-load-ajax').css('display', 'flex');
				});
				
				var orderId = jQuery(this).attr('data-manage-order-id');
				var orderAction = jQuery(this).attr('data-manage-order-action');
				
				if (orderId !== '') {
					if (orderAction !== 'print') {
						// Handle non-print actions (status changes) - original logic
						jQuery.ajax({
							method: 'post',
							url: order_ajax_object.ajax_url,
							data: {
								action: 'reload_orders',
								id: orderId,
								order_action: orderAction,
								nonce: order_ajax_object.nonce
							}
						}).done(function(response) {
							var data = JSON.parse(response);
							jQuery('.fdm-orders-items, .fdm-orders-full-items').remove();
							jQuery('.fdm-orders-loop').append(data.loop);
							jQuery('.fdm-orders-full').append(data.full);
							// Refresh print content too after status change
							if (data.full) {
								// Extract print content from the updated full content
								var printContent = jQuery(data.full).find('[id^=\"print-\"]').parent().html();
								if (printContent) {
									jQuery('#hide-prints').html(printContent);
								}
							}
							jQuery('.fdm-btn-order-action').attr('data-manage-order-id', '');
							if (jQuery(window).width() <= 768) {
								jQuery('.fdm-orders-full-details').hide();
							}
						});
					} else {
						// Handle print action with payment status update
						e.preventDefault();
						e.stopPropagation();
						
						if (printInProgress) return;
						printInProgress = true;
						
						var printSize = jQuery(this).attr('data-print-size');
						var fontSize = jQuery(this).attr('data-print-font');
						
						// First update payment status and refresh content
						mydUpdatePaymentOnPrint(orderId, function(response) {
							// Then execute print with updated content
							setTimeout(function() {
								if (typeof printJS !== 'undefined') {
									printJS({
										printable: 'print-' + orderId,
										type: 'html',
										style: '@page { size:' + printSize + ' 200mm; margin: 0; } .order-print { font-size:' + fontSize + 'px; } .order-header{ text-align: center }'
									});
								}
								printInProgress = false;
							}, 300);
						});
					}
				}
				
				jQuery(document).ajaxStop(function() {
					jQuery('.fdm-load-ajax').hide();
				});
			});
		});
		";
	}

	/**
	 * Get enhanced notification JavaScript
	 * Mejora el sistema de notificaciones con más opciones de audio
	 * 
	 * @since 2.2.19
	 * @return string
	 */
	public function get_enhanced_notification_js() {
		$plugin_url = MYD_PLUGN_URL;
		return "
		// Sistema simple de notificaciones mejoradas
		window.MydEnhancedNotifications = {
			audioEnabled: true,
			audioPath: '{$plugin_url}assets/songs/trim.mp3',
			
			playNotification: function() {
				if (!this.audioEnabled) {
					return;
				}
				
				const audio = new Audio(this.audioPath);
				audio.volume = 0.8;
				
				audio.play().catch(error => {
					console.warn('MYD: Error al reproducir audio:', error);
				});
			},
			
			init: function() {
				// Agregar botón de prueba simple
				const notificationArea = document.querySelector('.myd-orders-panel__notification-status');
				if (notificationArea) {
					const testButton = document.createElement('button');
					testButton.textContent = 'Probar Sonido';
					testButton.style.marginTop = '10px';
					testButton.onclick = () => this.playNotification();
					notificationArea.appendChild(testButton);
				}
			}
		};
		
		// Interceptar nuevos pedidos para notificaciones
		jQuery(document).ajaxSuccess(function(event, xhr, settings) {
			if (settings.data && settings.data.includes('action=update_orders')) {
				try {
					const response = JSON.parse(xhr.responseText);
					if (response.loop && response.loop.trim() !== '') {
						window.MydEnhancedNotifications.playNotification();
					}
				} catch (e) {
					console.warn('MYD: Error al parsear respuesta de pedidos:', e);
				}
			}
		});
		
		// Inicializar cuando esté listo
		jQuery(document).ready(function() {
			window.MydEnhancedNotifications.init();
		});
		";
	}

	/**
	 * Get realtime updates JavaScript
	 * Mejora el sistema de actualizaciones en tiempo real
	 * 
	 * @since 2.2.19
	 * @return string
	 */
	public function get_realtime_updates_js() {
		return "
		// Sistema mejorado de actualizaciones en tiempo real
		window.MydRealtimeUpdates = {
			updateInterval: 8000,
			retryAttempts: 0,
			maxRetries: 5,
			isUpdating: false,
			lastOrderCount: 0,
			connectionStatus: 'disconnected',
			
			init: function() {
				this.addConnectionIndicator();
				this.startUpdates();
				this.handleVisibilityChange();
			},
			
			// Agregar indicador de conexión
			addConnectionIndicator: function() {
				const notificationArea = document.querySelector('.myd-orders-panel__notification-status');
				if (!notificationArea) return;
				
				const indicatorHtml = \`
					<div class='myd-connection-status' style='margin-top: 5px; padding: 5px; border-radius: 3px; font-size: 12px;'>
						<span id='myd-connection-indicator' style='display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; background: #ccc;'></span>
						<span id='myd-connection-text'>Conectando...</span>
					</div>
				\`;
				
				notificationArea.insertAdjacentHTML('beforeend', indicatorHtml);
			},
			
			// Actualizar indicador de conexión
			updateConnectionStatus: function(status, message) {
				this.connectionStatus = status;
				const indicator = document.getElementById('myd-connection-indicator');
				const text = document.getElementById('myd-connection-text');
				
				if (!indicator || !text) return;
				
				const statusColors = {
					'connected': '#4CAF50',
					'reconnecting': '#FF9800', 
					'disconnected': '#F44336',
					'error': '#F44336'
				};
				
				indicator.style.background = statusColors[status] || '#ccc';
				text.textContent = message || status;
			},
			
			// Manejar cambios de visibilidad de la página
			handleVisibilityChange: function() {
				document.addEventListener('visibilitychange', () => {
					if (document.hidden) {
						this.pauseUpdates();
					} else {
						this.resumeUpdates();
					}
				});
			},
			
			// Iniciar actualizaciones
			startUpdates: function() {
				if (this.updateTimer) {
					clearInterval(this.updateTimer);
				}
				
				this.updateTimer = setInterval(() => {
					this.checkForUpdates();
				}, this.updateInterval);
				
				this.checkForUpdates();
			},
			
			// Pausar actualizaciones
			pauseUpdates: function() {
				if (this.updateTimer) {
					clearInterval(this.updateTimer);
					this.updateTimer = null;
				}
				this.updateConnectionStatus('paused', 'Pausado');
			},
			
			// Reanudar actualizaciones
			resumeUpdates: function() {
				this.startUpdates();
				this.updateConnectionStatus('reconnecting', 'Reconectando...');
			},
			
			// Verificar actualizaciones
			checkForUpdates: function() {
				if (this.isUpdating) return;
				
				this.isUpdating = true;
				this.updateConnectionStatus('connected', 'Verificando...');
				
				const controller = new AbortController();
				const timeoutId = setTimeout(() => controller.abort(), 15000);
				
				let orderListElement = document.querySelector('.fdm-orders-items');
				let currentOrderId = orderListElement ? orderListElement.id : 0;
				
				const apiUrl = order_ajax_object.domain + '/wp-json/my-delivery/v1/orders?oid=' + currentOrderId;
				
				fetch(apiUrl, {
					signal: controller.signal,
					headers: {
						'Cache-Control': 'no-cache',
						'Pragma': 'no-cache'
					}
				})
				.then(response => {
					clearTimeout(timeoutId);
					
					if (!response.ok) {
						throw new Error(\`HTTP \${response.status}: \${response.statusText}\`);
					}
					
					return response.json();
				})
				.then(data => {
					this.retryAttempts = 0;
					
					if (data.status === 'desatualizado') {
						this.updateOrdersInterface();
						this.updateConnectionStatus('connected', 'Nuevo pedido detectado');
					} else {
						this.updateConnectionStatus('connected', 'Conectado - ' + new Date().toLocaleTimeString());
					}
				})
				.catch(error => {
					clearTimeout(timeoutId);
					
					if (error.name === 'AbortError') {
						this.updateConnectionStatus('error', 'Timeout de conexión');
					} else {
						console.error('Error al verificar pedidos:', error);
						this.retryAttempts++;
						
						if (this.retryAttempts >= this.maxRetries) {
							this.updateConnectionStatus('error', 'Error de conexión persistente');
							setTimeout(() => {
								this.retryAttempts = 0;
								this.updateConnectionStatus('reconnecting', 'Reintentando...');
							}, 30000);
						} else {
							this.updateConnectionStatus('reconnecting', \`Reintento \${this.retryAttempts}/\${this.maxRetries}\`);
						}
					}
				})
				.finally(() => {
					this.isUpdating = false;
				});
			},
			
			// Actualizar interfaz de pedidos
			updateOrdersInterface: function() {
				fetch(order_ajax_object.ajax_url, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
						'Cache-Control': 'no-cache',
					},
					body: 'action=update_orders&nonce=' + order_ajax_object.nonce
				})
				.then(response => response.json())
				.then(data => {
					if (data.loop && data.full && data.print) {
						document.querySelectorAll('.fdm-orders-items, .fdm-orders-full-items').forEach(el => el.remove());
						document.querySelectorAll('.fdm-btn-order-action').forEach(el => el.setAttribute('data-manage-order-id', ''));
						document.querySelectorAll('.order-print').forEach(el => el.remove());
						
						const ordersLoop = document.querySelector('.fdm-orders-loop');
						const ordersFull = document.querySelector('.fdm-orders-full');
						const hidePrints = document.querySelector('#hide-prints');
						
						if (ordersLoop) ordersLoop.insertAdjacentHTML('afterbegin', data.loop);
						if (ordersFull) ordersFull.insertAdjacentHTML('afterbegin', data.full);
						if (hidePrints) hidePrints.insertAdjacentHTML('afterbegin', data.print);
						
						if (window.MydEnhancedNotifications) {
							window.MydEnhancedNotifications.playNotification();
						}
						
						if (window.MydNotification && window.MydNotification.supportNotification() && window.MydNotification.hasNotificationPermission()) {
							const notificationLabel = document.getElementById('myd-orders-panel-notification-status-label');
							const notificationData = {};
							
							if (notificationLabel) {
								notificationData.title = notificationLabel.dataset.notificationTitle || 'Nuevo Pedido!';
								notificationData.body = notificationLabel.dataset.notificationBody || 'Hey, tienes un nuevo pedido. ¡Revísalo ahora!';
							}
							
							window.MydNotification.sendNotification(notificationData);
						}
					}
				})
				.catch(error => {
					console.error('Error al actualizar interfaz de pedidos:', error);
				});
			}
		};
		
		// Inicializar al cargar la página
		document.addEventListener('DOMContentLoaded', function() {
			if (typeof order_ajax_object !== 'undefined') {
				window.MydRealtimeUpdates.init();
			}
		});
		";
	}
}
