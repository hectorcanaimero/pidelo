<?php

namespace MydPro\Includes\Api\Products;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Products REST API endpoints
 */
class Products_Api {
	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_product_routes' ] );
	}

	/**
	 * Register product routes
	 */
	public function register_product_routes() {
		// GET /products - List products
		\register_rest_route(
			'myd-delivery/v1',
			'/products',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_products' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_collection_params(),
				),
			)
		);

		// POST /products - Create product
		\register_rest_route(
			'myd-delivery/v1',
			'/products',
			array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'create_product' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_product_schema(),
				),
			)
		);

		// GET /products/{id} - Get specific product
		\register_rest_route(
			'myd-delivery/v1',
			'/products/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_product' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'id' => array(
							'description' => __( 'Product ID', 'myd-delivery-pro' ),
							'type' => 'integer',
							'required' => true,
						),
					),
				),
			)
		);

		// PUT /products/{id} - Update product
		\register_rest_route(
			'myd-delivery/v1',
			'/products/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::EDITABLE,
					'callback' => [ $this, 'update_product' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_product_schema(),
				),
			)
		);

		// DELETE /products/{id} - Delete product
		\register_rest_route(
			'myd-delivery/v1',
			'/products/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::DELETABLE,
					'callback' => [ $this, 'delete_product' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'id' => array(
							'description' => __( 'Product ID', 'myd-delivery-pro' ),
							'type' => 'integer',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Get products list
	 */
	public function get_products( $request ) {
		$page = $request->get_param( 'page' ) ?: 1;
		$per_page = $request->get_param( 'per_page' ) ?: 10;
		$search = $request->get_param( 'search' ) ?: '';
		$category = $request->get_param( 'category' ) ?: '';
		$available = $request->get_param( 'available' ) ?: '';

		$args = array(
			'post_type' => 'mydelivery-produtos',
			'post_status' => 'publish',
			'posts_per_page' => $per_page,
			'paged' => $page,
			'orderby' => 'date',
			'order' => 'DESC',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = sanitize_text_field( $search );
		}

		$meta_query = array();

		if ( ! empty( $category ) ) {
			$meta_query[] = array(
				'key' => 'product_type',
				'value' => sanitize_text_field( $category ),
				'compare' => '=',
			);
		}

		if ( ! empty( $available ) ) {
			$meta_query[] = array(
				'key' => 'product_available',
				'value' => sanitize_text_field( $available ),
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $args );
		$products = array();

		foreach ( $query->posts as $post ) {
			$products[] = $this->prepare_product_data( $post );
		}

		$response = array(
			'products' => $products,
			'total' => $query->found_posts,
			'pages' => $query->max_num_pages,
			'current_page' => $page,
			'per_page' => $per_page,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get specific product
	 */
	public function get_product( $request ) {
		$product_id = $request['id'];
		$post = get_post( $product_id );

		if ( ! $post || $post->post_type !== 'mydelivery-produtos' ) {
			return new \WP_Error( 'product_not_found', __( 'Product not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$product_data = $this->prepare_product_data( $post );
		return rest_ensure_response( $product_data );
	}

	/**
	 * Create new product
	 */
	public function create_product( $request ) {
		$title = sanitize_text_field( $request['title'] );
		$content = wp_kses_post( $request['content'] );

		if ( empty( $title ) ) {
			return new \WP_Error( 'missing_title', __( 'Product title is required', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		$post_data = array(
			'post_type' => 'mydelivery-produtos',
			'post_title' => $title,
			'post_content' => $content,
			'post_status' => 'publish',
		);

		$product_id = wp_insert_post( $post_data );

		if ( is_wp_error( $product_id ) ) {
			return new \WP_Error( 'create_failed', __( 'Failed to create product', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		// Save product meta fields
		$this->save_product_meta( $product_id, $request );

		$post = get_post( $product_id );
		$product_data = $this->prepare_product_data( $post );

		return rest_ensure_response( $product_data );
	}

	/**
	 * Update product
	 */
	public function update_product( $request ) {
		$product_id = $request['id'];
		$post = get_post( $product_id );

		if ( ! $post || $post->post_type !== 'mydelivery-produtos' ) {
			return new \WP_Error( 'product_not_found', __( 'Product not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$post_data = array(
			'ID' => $product_id,
		);

		if ( isset( $request['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $request['title'] );
		}

		if ( isset( $request['content'] ) ) {
			$post_data['post_content'] = wp_kses_post( $request['content'] );
		}

		$result = wp_update_post( $post_data );

		if ( is_wp_error( $result ) ) {
			return new \WP_Error( 'update_failed', __( 'Failed to update product', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		// Update product meta fields
		$this->save_product_meta( $product_id, $request );

		$updated_post = get_post( $product_id );
		$product_data = $this->prepare_product_data( $updated_post );

		return rest_ensure_response( $product_data );
	}

	/**
	 * Delete product
	 */
	public function delete_product( $request ) {
		$product_id = $request['id'];
		$post = get_post( $product_id );

		if ( ! $post || $post->post_type !== 'mydelivery-produtos' ) {
			return new \WP_Error( 'product_not_found', __( 'Product not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$result = wp_delete_post( $product_id, true );

		if ( ! $result ) {
			return new \WP_Error( 'delete_failed', __( 'Failed to delete product', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'deleted' => true, 'id' => $product_id ) );
	}

	/**
	 * Prepare product data for API response
	 */
	private function prepare_product_data( $post ) {
		$product_data = array(
			'id' => $post->ID,
			'title' => $post->post_title,
			'content' => $post->post_content,
			'status' => $post->post_status,
			'date_created' => $post->post_date,
			'date_modified' => $post->post_modified,
			'image' => get_post_meta( $post->ID, 'product_image', true ),
			'available' => get_post_meta( $post->ID, 'product_available', true ),
			'category' => get_post_meta( $post->ID, 'product_type', true ),
			'price' => get_post_meta( $post->ID, 'product_price', true ),
			'price_label' => get_post_meta( $post->ID, 'product_price_label', true ),
			'description' => get_post_meta( $post->ID, 'product_description', true ),
			'extras' => get_post_meta( $post->ID, 'myd_product_extras', true ),
		);

		return $product_data;
	}

	/**
	 * Save product meta fields
	 */
	private function save_product_meta( $product_id, $request ) {
		$meta_fields = array(
			'product_image' => 'image',
			'product_available' => 'available',
			'product_type' => 'category',
			'product_price' => 'price',
			'product_price_label' => 'price_label',
			'product_description' => 'description',
			'myd_product_extras' => 'extras',
		);

		foreach ( $meta_fields as $meta_key => $request_key ) {
			if ( isset( $request[ $request_key ] ) ) {
				$value = $request[ $request_key ];
				
				if ( $request_key === 'price' ) {
					$value = floatval( $value );
				} elseif ( $request_key === 'extras' && is_array( $value ) ) {
					$value = $value; // Keep as array for extras
				} else {
					$value = sanitize_text_field( $value );
				}
				
				update_post_meta( $product_id, $meta_key, $value );
			}
		}
	}

	/**
	 * Check admin permissions
	 */
	public function check_admin_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You do not have permission to access this.', 'myd-delivery-pro' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Get collection parameters
	 */
	public function get_collection_params() {
		return array(
			'page' => array(
				'description' => __( 'Current page of the collection', 'myd-delivery-pro' ),
				'type' => 'integer',
				'default' => 1,
				'minimum' => 1,
			),
			'per_page' => array(
				'description' => __( 'Maximum number of items to be returned', 'myd-delivery-pro' ),
				'type' => 'integer',
				'default' => 10,
				'minimum' => 1,
				'maximum' => 100,
			),
			'search' => array(
				'description' => __( 'Limit results to those matching a string', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'category' => array(
				'description' => __( 'Filter by product category', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'available' => array(
				'description' => __( 'Filter by availability status', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'show', 'hide', 'not-available' ),
			),
		);
	}

	/**
	 * Get product schema for validation
	 */
	public function get_product_schema() {
		return array(
			'title' => array(
				'description' => __( 'Product title', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'content' => array(
				'description' => __( 'Product content', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'image' => array(
				'description' => __( 'Product image URL', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'available' => array(
				'description' => __( 'Product availability', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'show', 'hide', 'not-available' ),
			),
			'category' => array(
				'description' => __( 'Product category', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'price' => array(
				'description' => __( 'Product price', 'myd-delivery-pro' ),
				'type' => 'number',
			),
			'price_label' => array(
				'description' => __( 'Price label display', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'show', 'hide', 'from', 'consult' ),
			),
			'description' => array(
				'description' => __( 'Product description', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'extras' => array(
				'description' => __( 'Product extras', 'myd-delivery-pro' ),
				'type' => 'array',
			),
		);
	}
}

new Products_Api();