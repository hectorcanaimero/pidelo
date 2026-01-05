<?php
/**
 * Products Display V2 - Modern shortcode with sticky category navigation
 *
 * @package MydPro
 * @since 2.4.0
 */

namespace MydPro\Includes;

use MydPro\Includes\Legacy\Legacy_Repeater;
use MydPro\Includes\Custom_Fields\Register_Custom_Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to show products with modern design and sticky navigation
 *
 * @since 2.4.0
 */
class Fdm_products_show_v2 extends Fdm_products_show {

	/**
	 * Shortcode attributes
	 *
	 * @var array
	 */
	private $atts = array();

	/**
	 * Register shortcode
	 *
	 * @return void
	 * @since 2.4.0
	 */
	public function register_shortcode() {
		add_shortcode( 'mydelivery-products-v2', array( $this, 'fdm_list_products_v2' ) );
	}

	/**
	 * Shortcode handler
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 * @since 2.4.0
	 */
	public function fdm_list_products_v2( $atts ) {
		$this->atts = shortcode_atts(
			array(
				'sticky_position' => 'top',        // top or bottom
				'sticky_offset'   => '100',        // Pixels from top to show sticky menu
				'show_count'      => 'yes',        // Show product count per category
				'design'          => 'modern',     // modern or classic
			),
			$atts,
			'mydelivery-products-v2'
		);

		return $this->fdm_list_products_html_v2();
	}

	/**
	 * Get categories with product count
	 *
	 * @return array|null
	 * @since 2.4.0
	 */
	public function get_categories_with_count() {
		$categories = $this->get_categories();

		if ( $categories === null ) {
			return null;
		}

		$products_object = $this->get_products();
		$categories_data = array();

		foreach ( $categories as $category ) {
			$count = 0;

			if ( $products_object->have_posts() ) {
				$products_object->rewind_posts();

				while ( $products_object->have_posts() ) {
					$products_object->the_post();
					$product_category = get_post_meta( get_the_ID(), 'product_type', true );
					$is_available = get_post_meta( get_the_ID(), 'product_available', true );

					if ( $product_category === $category && $is_available !== 'hide' ) {
						$count++;
					}
				}
			}

			if ( $count > 0 ) {
				$categories_data[] = array(
					'name'  => $category,
					'slug'  => str_replace( ' ', '-', $category ),
					'count' => $count,
				);
			}
		}

		wp_reset_postdata();
		return $categories_data;
	}

	/**
	 * Render sticky category navigation
	 *
	 * @return string
	 * @since 2.4.0
	 */
	private function render_sticky_navigation() {
		$categories = $this->get_categories_with_count();

		if ( empty( $categories ) ) {
			return '';
		}

		$position = $this->atts['sticky_position'];
		$show_count = $this->atts['show_count'] === 'yes';
		$offset = intval( $this->atts['sticky_offset'] );

		ob_start();
		include MYD_PLUGIN_PATH . '/templates/products-v2/sticky-menu.php';
		return ob_get_clean();
	}

	/**
	 * Loop products by category (v2)
	 *
	 * @param array $categories Categories array
	 * @return string
	 * @since 2.4.0
	 */
	public function fdm_loop_products_per_categorie_v2( $categories = array() ) {
		$categories_data = ! empty( $categories ) ? $categories : $this->get_categories_with_count();

		if ( $categories_data === null || empty( $categories_data ) ) {
			return esc_html__( 'For show correct products, create categories on plugin settings and add in products.', 'myd-delivery-pro' );
		}

		$grid_columns = get_option( 'myd-products-list-columns', 'myd-product-list--three-columns' );
		$products_object = $this->get_products();
		$products = '';

		foreach ( $categories_data as $category_data ) {
			$category = $category_data['name'];
			$category_tag = $category_data['slug'];
			$product_by_categorie = $this->fdm_loop_products_v2( $products_object, $category );

			if ( $product_by_categorie !== null && ! empty( $product_by_categorie ) ) {
				$products .= '<section class="myd-product-section-v2" id="fdm-' . esc_attr( $category_tag ) . '" data-category="' . esc_attr( $category_tag ) . '">';
				$products .= '<h2 class="myd-product-section-v2__title">' . esc_html( $category ) . '</h2>';
				$products .= '<div class="myd-product-list-v2 myd-' . esc_attr( $category_tag ) . ' ' . esc_attr( $grid_columns ) . '">';
				$products .= $product_by_categorie;
				$products .= '</div>';
				$products .= '</section>';
			}
		}

		return $products;
	}

	/**
	 * Loop products for a specific category (v2)
	 *
	 * @param \WP_Query $products Products query
	 * @param string    $categorie Category name
	 * @return string|null
	 * @since 2.4.0
	 */
	public function fdm_loop_products_v2( $products, $categorie ) {
		if ( ! $products->have_posts() ) {
			return null;
		}

		$products->rewind_posts();
		ob_start();

		while ( $products->have_posts() ) :
			$products->the_post();
			$product_category = get_post_meta( get_the_ID(), 'product_type', true );
			$is_available = get_post_meta( get_the_ID(), 'product_available', true );

			if ( $product_category === $categorie && $is_available !== 'hide' ) {
				include MYD_PLUGIN_PATH . '/templates/products-v2/loop-products.php';
			}
		endwhile;

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Create front end template (v2)
	 *
	 * @return string
	 * @since 2.4.0
	 */
	public function fdm_list_products_html_v2() {
		// Enqueue v2 styles and scripts
		wp_enqueue_style( 'myd-delivery-frontend-v2' );
		wp_enqueue_script( 'myd-products-v2' );

		// Also enqueue required base scripts
		wp_enqueue_script( 'myd-create-order' );
		wp_enqueue_script( 'myd-payment-receipt' );
		wp_enqueue_script( 'myd-skip-payment-in-store' );

		// Add inline configuration
		$config = array(
			'stickyPosition' => $this->atts['sticky_position'],
			'stickyOffset'   => intval( $this->atts['sticky_offset'] ),
			'showCount'      => $this->atts['show_count'] === 'yes',
		);

		wp_localize_script( 'myd-products-v2', 'mydProductsV2Config', $config );

		ob_start();
		include MYD_PLUGIN_PATH . '/templates/products-v2/template.php';
		return ob_get_clean();
	}
}

// Initialize shortcode
$delivery_page_shortcode_v2 = new Fdm_products_show_v2();
$delivery_page_shortcode_v2->register_shortcode();
