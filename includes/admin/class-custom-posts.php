<?php

namespace MydPro\Includes\Admin;

use MydPro\Includes\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Posts class.
 */
class Custom_Posts {
	/**
	 * Custom posts
	 *
	 * @since 1.9.6
	 */
	protected $custom_posts;

	/**
	 * License status
	 *
	 * @since 1.9.6
	 */
	protected $license;

	/**
	 * Construct the class
	 *
	 * @since 1.9.6
	 */
	public function __construct() {
		$this->license = Plugin::instance()->license;

		$this->custom_posts = [
			'mydelivery-produtos' => [
				'condition' => true,
				'args' => [
					'label' => __('MyDelivery Products', 'myd-delivery-pro'),
					'labels' => [
						'name' => __('Products', 'myd-delivery-pro'),
						'singular_name' => __('Products', 'myd-delivery-pro'),
						'menu_name' => __('Products', 'myd-delivery-pro'),
						'all_items' => __('Products', 'myd-delivery-pro'),
						'add_new' => __('Add product', 'myd-delivery-pro'),
						'add_new_item' => __('Add product', 'myd-delivery-pro'),
						'edit_item' => __('Edit product', 'myd-delivery-pro'),
						'new_item' => __('New product', 'myd-delivery-pro'),
						'view_item' => __('View product', 'myd-delivery-pro'),
						'view_items' => __('View products', 'myd-delivery-pro'),
						'search_items' => __('Search products', 'myd-delivery-pro')
					],
					'description' => 'Plugin MyD Delivery products menu.',
					'public' => true,
					'publicly_queryable' => false,
					'show_ui' => true,
					'delete_with_user' => false,
					'show_in_rest' => true,
					'rest_base' => '',
					'rest_controller_class' => 'WP_REST_Posts_Controller',
					'has_archive' => false,
					'show_in_menu' => 'myd-delivery-dashoboard',
					'show_in_nav_menus' => true,
					'exclude_from_search' => false,
					'capability_type' => 'post',
					'map_meta_cap' => true,
					'hierarchical' => false,
					'rewrite' => [
						'slug' => 'mydelivery-produtos',
						'with_front' => true
					],
					'query_var' => true,
					'supports' => [
						'title'
					]
				]
			],
			'mydelivery-orders' => [
				'condition' => true,
				'args' => [
					'label' => __('MyDelivery Orders', 'myd-delivery-pro'),
					'labels' => [
						'name' => __('Orders', 'myd-delivery-pro'),
						'singular_name' => __('Order', 'myd-delivery-pro'),
						'menu_name' => __('Orders', 'myd-delivery-pro'),
						'all_items' => __('Orders', 'myd-delivery-pro'),
						'add_new' => __('Add order', 'myd-delivery-pro'),
						'add_new_item' => __('Add order', 'myd-delivery-pro'),
						'edit_item' => __('Edit order', 'myd-delivery-pro'),
						'new_item' => __('New order', 'myd-delivery-pro'),
						'view_item' => __('View order', 'myd-delivery-pro'),
						'view_items' => __('View orders', 'myd-delivery-pro'),
						'search_items' => __('Search orders', 'myd-delivery-pro'),
					],
					'description' => 'Plugin MyDelivery orders menu.',
					'public' => true,
					'publicly_queryable' => false,
					'show_ui' => true,
					'delete_with_user' => false,
					'show_in_rest' => true,
					'rest_base' => '',
					'rest_controller_class' => 'WP_REST_Posts_Controller',
					'has_archive' => false,
					'show_in_menu' => 'myd-delivery-dashoboard',
					'show_in_nav_menus' => true,
					'exclude_from_search' => false,
					'capability_type' => 'post',
					'map_meta_cap' => true,
					'hierarchical' => false,
					'rewrite' => [
						'slug' => 'mydelivery-orders',
						'with_front' => true
					],
					'query_var' => true,
					'supports' => [
						'title'
					]
				]
			],
			'mydelivery-coupons' => [
				'condition' => true,
				'args' => [
					'label' => __('MyDelivery Coupons', 'myd-delivery-pro'),
					'labels' => [
						'name' => __('Coupons', 'myd-delivery-pro'),
						'singular_name' => __('Coupons', 'myd-delivery-pro'),
						'menu_name' => __('Coupons', 'myd-delivery-pro'),
						'all_items' => __('Coupons', 'myd-delivery-pro'),
						'add_new' => __('Add coupon', 'myd-delivery-pro'),
						'add_new_item' => __('Add coupon', 'myd-delivery-pro'),
						'edit_item' => __('Edit coupon', 'myd-delivery-pro'),
						'new_item' => __('New coupon', 'myd-delivery-pro'),
						'view_item' => __('View coupon', 'myd-delivery-pro'),
						'view_items' => __('View coupons', 'myd-delivery-pro'),
						'search_items' => __('Search coupons', 'myd-delivery-pro'),
					],
					'description' => 'Coupons for MyD Delivery',
					'public' => true,
					'publicly_queryable' => false,
					'show_ui' => true,
					'delete_with_user' => false,
					'show_in_rest' => true,
					'rest_base' => '',
					'rest_controller_class' => 'WP_REST_Posts_Controller',
					'has_archive' => false,
					'show_in_menu' => 'myd-delivery-dashoboard',
					'show_in_nav_menus' => true,
					'exclude_from_search' => false,
					'capability_type' => 'post',
					'map_meta_cap' => true,
					'hierarchical' => false,
					'rewrite' => [
						'slug' => 'mydelivery-coupons',
						'with_front' => true
					],
					'query_var' => true,
					'supports' => [
						'title'
					]
				]
			]
		];
	}

	/**
	 * Register custom posts
	 *
	 * @since 1.9.6
	 */
	public function register_custom_posts() {
		$custom_posts = apply_filters( 'mydp_before_regigster_custom_posts', $this->custom_posts );

		foreach ( $custom_posts as $custom_post => $options ) {
			if ( $options['condition'] === false || $options['condition'] === true && $this->license->get_status() === 'active' || $this->license->get_status() === 'expired' || $this->license->get_status() === 'mismatch' ) {
				register_post_type( $custom_post, $options['args'] );
			}
		}
	}
}
