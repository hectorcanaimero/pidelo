<?php

namespace MydPro\Includes;

use MydPro\Includes\Legacy\Legacy_Repeater;
use MydPro\Includes\Custom_Fields\Register_Custom_Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once dirname(__FILE__) . '/fdm-custom-svg.php';
include_once dirname(__FILE__) . '/class-myd-product-extra.php';
include_once dirname(__FILE__) . '/class-myd-product.php';
include_once MYD_PLUGIN_PATH . 'includes/legacy/class-legacy-repeater.php';

/**
 * Class to show products
 *
 * TODO: Refactor!
 */
class Fdm_products_show {
	/**
	 * Register shortcode with template.
	 *
	 * @return void
	 * @since 1.9.15
	 */
	public function register_shortcode() {
		add_shortcode( 'mydelivery-products', array( $this, 'fdm_list_products' ) );
	}

	/*
	*
	* Return functions to shortcode
	*
	*/
	public function fdm_list_products () {
		return $this->fdm_list_products_html();
	}

	/**
	 * Get product extra
	 *
	 * @param int $id
	 * @return void|array
	 * @since 1.6
	 */
	public function get_product_extra( $id ) {
		$product_extra = get_post_meta( $id, 'myd_product_extras', true );
		$product_extra_legacy = get_post_meta( $id, 'product_extras', true );

		/**
		 * Check if is necessary migrate legacy data to new.
		 */
		$args = Register_Custom_Fields::get_registered_fields();
		$args = isset( $args['myd_product_extras']['fields']['myd_product_extras'] ) ? $args['myd_product_extras']['fields']['myd_product_extras'] : array();
		$update_db = Legacy_Repeater::need_update_db( $product_extra_legacy, $product_extra );
		if ( $update_db && ! empty( $args ) ) {
			$product_extra = Legacy_Repeater::update_repeater_database( $product_extra_legacy, $args, $id );
		}

		if ( empty( $product_extra ) ) {
			return array();
		}

		foreach ( $product_extra as $item ) {
			$formated_extras[] = array(
				'extra_available' => $item['extra_available'] ?? '',
				'extra_limit' => $item['extra_max_limit'],
				'extra_min_limit' => $item['extra_min_limit'] ?? '',
				'extra_required' => $item['extra_required'],
				'extra_title' => $item['extra_title'],
				'extra_options' => $item['myd_extra_options'],
			);
		}

		return $formated_extras;
	}

	/**
	 * Formar product extra
	 *
	 * @param int $id
	 * @return void
	 * @since 1.6
	 */
	public function format_product_extra( $id ) {
		$extras = $this->get_product_extra( $id );
		$product_extra = new Myd_product_extra();
		$product = new Myd_product();

		ob_start();
		include MYD_PLUGIN_PATH . '/templates/products/product-extra.php';
		return ob_get_clean();
	}

	/**
	 * Products by categorie
	 *
	 * @since 1.9.8
	 */
	public function fdm_loop_products( $products, $categorie ) {
		if ( ! $products->have_posts() ) {
			return null;
		}

		ob_start();
		while ( $products->have_posts() ) :
			$products->the_post();
			$product_category = get_post_meta( get_the_ID(), 'product_type', true );
			$is_available = get_post_meta( get_the_ID(), 'product_available', true );
			if ( $product_category === $categorie && $is_available !== 'hide' ) {
				/**
				 * Loop products
				 */
				include MYD_PLUGIN_PATH . '/templates/products/loop-products.php';
			}
		endwhile;
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Query products
	 *
	 * @since 1.9.8
	 * @return array
	 */
	public function get_products() {
		$args = [
			'post_type' => 'mydelivery-produtos',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'no_found_rows' => true
		];

		return new \WP_Query( $args );
	}

	/**
	 * Get categories
	 *
	 * @return arrray
	 * @since 1.9.8
	 */
	public function get_categories() {

		$categories = get_option( 'fdm-list-menu-categories' );

		if ( empty( $categories ) ) {
			return NULL;
		}

		$categories = explode( ",", $categories );
		$categories = array_map( 'trim', $categories );
		return $categories;
	}

	/**
	 * Loop products
	 *
	 * @return void
	 */
	public function fdm_loop_products_per_categorie( $categories = array() ) {
		$categories = ! empty( $categories ) ? $categories : $this->get_categories();

		if ( $categories === null ) {
			return esc_html__( 'For show correct produts, create categories on plugin settings and add in produtcs.', 'myd-delivery-pro' );
		}

		$grid_columns = get_option( 'myd-products-list-columns' );
		$products_object = $this->get_products();
		$products = '';
		foreach ( $categories as $categorie ) {
			$categorie_tag = str_replace( ' ', '-', $categorie );
			$product_by_categorie = $this->fdm_loop_products( $products_object, $categorie );

			if ( $product_by_categorie !== NULL && ! empty( $product_by_categorie ) ) {
				$products .= '<h2 class="myd-product-list__title" id="fdm-' . $categorie_tag . '">' . $categorie . '</h2><div class="myd-product-list myd-' . $categorie_tag . ' ' . $grid_columns . '">' . $product_by_categorie . '</div>';
			}
		}

		return $products;
	}

	/*
	*
	* Get categories options
	*
	*/
	public function fdm_list_categories () {

		$categories = get_option('fdm-list-menu-categories');

		if( !empty($categories) ) {

			$categories = get_option('fdm-list-menu-categories');
			$categories = explode(",", $categories);
			$categories = array_map('trim', $categories);

			return $categories;
		}
	}

	/**
	 * TEMP. code add JS dependencies to footer
	 *
	 * @return string
	 */
	public function add_js_dependencies() {
		/**
		 * Delivery time to move to Class
		 *
		 * TODO: Remove to class/method
		 *
		 * @return JSON
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
		 *
		 * TODO: move to class/method
		 *
		 * @since 1.9.4
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
	 * Creat front end template
	 *
	 * @since 1.8
	 * @access public
	 */
	public function fdm_list_products_html( $args = array() ) {
		// Ya no es necesario agregar mydStoreInfo aquí, se hace globalmente en class-plugin.php
		
		\wp_enqueue_script( 'myd-create-order' );
		\wp_enqueue_style( 'myd-delivery-frontend' );

		ob_start();

		/**
		 * Include templates
		 *
		 * @since 1.9
		 */
		include MYD_PLUGIN_PATH . '/templates/template.php';

		return ob_get_clean();
	}
}

$delivery_page_shortcode = new Fdm_products_show();
$delivery_page_shortcode->register_shortcode();
