<?php

namespace MydPro\Includes\Api\Coupons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupons REST API endpoints
 */
class Coupons_Api {
	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_coupon_routes' ] );
	}

	/**
	 * Register coupon routes
	 */
	public function register_coupon_routes() {
		// GET /coupons - List coupons
		\register_rest_route(
			'myd-delivery/v1',
			'/coupons',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_coupons' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_collection_params(),
				),
			)
		);

		// POST /coupons - Create coupon
		\register_rest_route(
			'myd-delivery/v1',
			'/coupons',
			array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'create_coupon' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_coupon_schema(),
				),
			)
		);

		// GET /coupons/{id} - Get specific coupon
		\register_rest_route(
			'myd-delivery/v1',
			'/coupons/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_coupon' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'id' => array(
							'description' => __( 'Coupon ID', 'myd-delivery-pro' ),
							'type' => 'integer',
							'required' => true,
						),
					),
				),
			)
		);

		// PUT /coupons/{id} - Update coupon
		\register_rest_route(
			'myd-delivery/v1',
			'/coupons/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::EDITABLE,
					'callback' => [ $this, 'update_coupon' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_coupon_schema(),
				),
			)
		);

		// DELETE /coupons/{id} - Delete coupon
		\register_rest_route(
			'myd-delivery/v1',
			'/coupons/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::DELETABLE,
					'callback' => [ $this, 'delete_coupon' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'id' => array(
							'description' => __( 'Coupon ID', 'myd-delivery-pro' ),
							'type' => 'integer',
							'required' => true,
						),
					),
				),
			)
		);

		// GET /coupons/validate/{code} - Validate coupon by code
		\register_rest_route(
			'myd-delivery/v1',
			'/coupons/validate/(?P<code>[^/]+)',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'validate_coupon' ],
					'permission_callback' => '__return_true', // Public endpoint for validation
					'args' => array(
						'code' => array(
							'description' => __( 'Coupon code to validate', 'myd-delivery-pro' ),
							'type' => 'string',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Get coupons list
	 */
	public function get_coupons( $request ) {
		$page = $request->get_param( 'page' ) ?: 1;
		$per_page = $request->get_param( 'per_page' ) ?: 10;
		$search = $request->get_param( 'search' ) ?: '';
		$type = $request->get_param( 'type' ) ?: '';
		$format = $request->get_param( 'format' ) ?: '';

		$args = array(
			'post_type' => 'mydelivery-coupons',
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

		if ( ! empty( $type ) ) {
			$meta_query[] = array(
				'key' => 'myd_coupon_type',
				'value' => sanitize_text_field( $type ),
				'compare' => '=',
			);
		}

		if ( ! empty( $format ) ) {
			$meta_query[] = array(
				'key' => 'myd_discount_format',
				'value' => sanitize_text_field( $format ),
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $args );
		$coupons = array();

		foreach ( $query->posts as $post ) {
			$coupons[] = $this->prepare_coupon_data( $post );
		}

		$response = array(
			'coupons' => $coupons,
			'total' => $query->found_posts,
			'pages' => $query->max_num_pages,
			'current_page' => $page,
			'per_page' => $per_page,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get specific coupon
	 */
	public function get_coupon( $request ) {
		$coupon_id = $request['id'];
		$post = get_post( $coupon_id );

		if ( ! $post || $post->post_type !== 'mydelivery-coupons' ) {
			return new \WP_Error( 'coupon_not_found', __( 'Coupon not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$coupon_data = $this->prepare_coupon_data( $post );
		return rest_ensure_response( $coupon_data );
	}

	/**
	 * Create new coupon
	 */
	public function create_coupon( $request ) {
		$code = strtoupper( sanitize_text_field( $request['code'] ) );
		$content = wp_kses_post( $request['content'] ?: '' );

		if ( empty( $code ) ) {
			return new \WP_Error( 'missing_code', __( 'Coupon code is required', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Check if coupon code already exists
		$existing = get_posts( array(
			'post_type' => 'mydelivery-coupons',
			'title' => $code,
			'post_status' => 'publish',
			'numberposts' => 1
		) );

		if ( ! empty( $existing ) ) {
			return new \WP_Error( 'coupon_exists', __( 'Coupon code already exists', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		$post_data = array(
			'post_type' => 'mydelivery-coupons',
			'post_title' => $code,
			'post_content' => $content,
			'post_status' => 'publish',
		);

		$coupon_id = wp_insert_post( $post_data );

		if ( is_wp_error( $coupon_id ) ) {
			return new \WP_Error( 'create_failed', __( 'Failed to create coupon', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		// Save coupon meta fields
		$this->save_coupon_meta( $coupon_id, $request );

		$post = get_post( $coupon_id );
		$coupon_data = $this->prepare_coupon_data( $post );

		return rest_ensure_response( $coupon_data );
	}

	/**
	 * Update coupon
	 */
	public function update_coupon( $request ) {
		$coupon_id = $request['id'];
		$post = get_post( $coupon_id );

		if ( ! $post || $post->post_type !== 'mydelivery-coupons' ) {
			return new \WP_Error( 'coupon_not_found', __( 'Coupon not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$post_data = array(
			'ID' => $coupon_id,
		);

		if ( isset( $request['code'] ) ) {
			$new_code = strtoupper( sanitize_text_field( $request['code'] ) );
			
			// Check if new code already exists (excluding current coupon)
			$existing = get_posts( array(
				'post_type' => 'mydelivery-coupons',
				'title' => $new_code,
				'post_status' => 'publish',
				'numberposts' => 1,
				'exclude' => array( $coupon_id )
			) );

			if ( ! empty( $existing ) ) {
				return new \WP_Error( 'coupon_exists', __( 'Coupon code already exists', 'myd-delivery-pro' ), array( 'status' => 400 ) );
			}

			$post_data['post_title'] = $new_code;
		}

		if ( isset( $request['content'] ) ) {
			$post_data['post_content'] = wp_kses_post( $request['content'] );
		}

		$result = wp_update_post( $post_data );

		if ( is_wp_error( $result ) ) {
			return new \WP_Error( 'update_failed', __( 'Failed to update coupon', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		// Update coupon meta fields
		$this->save_coupon_meta( $coupon_id, $request );

		$updated_post = get_post( $coupon_id );
		$coupon_data = $this->prepare_coupon_data( $updated_post );

		return rest_ensure_response( $coupon_data );
	}

	/**
	 * Delete coupon
	 */
	public function delete_coupon( $request ) {
		$coupon_id = $request['id'];
		$post = get_post( $coupon_id );

		if ( ! $post || $post->post_type !== 'mydelivery-coupons' ) {
			return new \WP_Error( 'coupon_not_found', __( 'Coupon not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$result = wp_delete_post( $coupon_id, true );

		if ( ! $result ) {
			return new \WP_Error( 'delete_failed', __( 'Failed to delete coupon', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'deleted' => true, 'id' => $coupon_id ) );
	}

	/**
	 * Validate coupon by code
	 */
	public function validate_coupon( $request ) {
		$code = strtoupper( sanitize_text_field( $request['code'] ) );

		if ( empty( $code ) ) {
			return new \WP_Error( 'missing_code', __( 'Coupon code is required', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Find coupon by title (code)
		$coupon_posts = get_posts( array(
			'post_type' => 'mydelivery-coupons',
			'title' => $code,
			'post_status' => 'publish',
			'numberposts' => 1
		) );

		if ( empty( $coupon_posts ) ) {
			return rest_ensure_response( array(
				'valid' => false,
				'message' => __( 'Coupon code not found', 'myd-delivery-pro' )
			) );
		}

		$coupon = $coupon_posts[0];
		$coupon_data = $this->prepare_coupon_data( $coupon );

		// Additional validation logic can be added here
		// (expiration dates, usage limits, minimum order amounts, etc.)

		return rest_ensure_response( array(
			'valid' => true,
			'coupon' => $coupon_data,
			'message' => __( 'Coupon is valid', 'myd-delivery-pro' )
		) );
	}

	/**
	 * Prepare coupon data for API response
	 */
	private function prepare_coupon_data( $post ) {
		$coupon_data = array(
			'id' => $post->ID,
			'code' => $post->post_title,
			'content' => $post->post_content,
			'status' => $post->post_status,
			'date_created' => $post->post_date,
			'date_modified' => $post->post_modified,
			'type' => get_post_meta( $post->ID, 'myd_coupon_type', true ),
			'discount_format' => get_post_meta( $post->ID, 'myd_discount_format', true ),
			'discount_value' => get_post_meta( $post->ID, 'myd_discount_value', true ),
			'description' => get_post_meta( $post->ID, 'myd_coupon_description', true ),
		);

		return $coupon_data;
	}

	/**
	 * Save coupon meta fields
	 */
	private function save_coupon_meta( $coupon_id, $request ) {
		$meta_fields = array(
			'myd_coupon_type' => 'type',
			'myd_discount_format' => 'discount_format',
			'myd_discount_value' => 'discount_value',
			'myd_coupon_description' => 'description',
		);

		foreach ( $meta_fields as $meta_key => $request_key ) {
			if ( isset( $request[ $request_key ] ) ) {
				$value = $request[ $request_key ];
				
				if ( $request_key === 'discount_value' ) {
					$value = floatval( $value );
				} else {
					$value = sanitize_text_field( $value );
				}
				
				update_post_meta( $coupon_id, $meta_key, $value );
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
			'type' => array(
				'description' => __( 'Filter by coupon type', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'discount-total', 'discount-delivery' ),
			),
			'format' => array(
				'description' => __( 'Filter by discount format', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'amount', 'percent' ),
			),
		);
	}

	/**
	 * Get coupon schema for validation
	 */
	public function get_coupon_schema() {
		return array(
			'code' => array(
				'description' => __( 'Coupon code', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'content' => array(
				'description' => __( 'Coupon content/notes', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'type' => array(
				'description' => __( 'Coupon type', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'discount-total', 'discount-delivery' ),
			),
			'discount_format' => array(
				'description' => __( 'Discount format', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'amount', 'percent' ),
			),
			'discount_value' => array(
				'description' => __( 'Discount value', 'myd-delivery-pro' ),
				'type' => 'number',
				'minimum' => 0,
			),
			'description' => array(
				'description' => __( 'Coupon description', 'myd-delivery-pro' ),
				'type' => 'string',
			),
		);
	}
}

new Coupons_Api();