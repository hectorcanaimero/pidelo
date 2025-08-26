<?php

namespace MydPro\Includes\Api\Orders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders REST API endpoints
 */
class Orders_Api {
	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_order_routes' ] );
	}

	/**
	 * Register order routes
	 */
	public function register_order_routes() {
		// GET /orders - List orders
		\register_rest_route(
			'myd-delivery/v1',
			'/orders',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_orders' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_collection_params(),
				),
			)
		);

		// POST /orders - Create order
		\register_rest_route(
			'myd-delivery/v1',
			'/orders',
			array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'create_order' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_order_schema(),
				),
			)
		);

		// GET /orders/{id} - Get specific order
		\register_rest_route(
			'myd-delivery/v1',
			'/orders/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_order' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'id' => array(
							'description' => __( 'Order ID', 'myd-delivery-pro' ),
							'type' => 'integer',
							'required' => true,
						),
					),
				),
			)
		);

		// PUT /orders/{id} - Update order
		\register_rest_route(
			'myd-delivery/v1',
			'/orders/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::EDITABLE,
					'callback' => [ $this, 'update_order' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_order_schema(),
				),
			)
		);

		// DELETE /orders/{id} - Delete order
		\register_rest_route(
			'myd-delivery/v1',
			'/orders/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::DELETABLE,
					'callback' => [ $this, 'delete_order' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'id' => array(
							'description' => __( 'Order ID', 'myd-delivery-pro' ),
							'type' => 'integer',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Get orders list
	 */
	public function get_orders( $request ) {
		$page = $request->get_param( 'page' ) ?: 1;
		$per_page = $request->get_param( 'per_page' ) ?: 10;
		$search = $request->get_param( 'search' ) ?: '';
		$status = $request->get_param( 'status' ) ?: '';
		$payment_status = $request->get_param( 'payment_status' ) ?: '';
		$date_from = $request->get_param( 'date_from' ) ?: '';
		$date_to = $request->get_param( 'date_to' ) ?: '';

		$args = array(
			'post_type' => 'mydelivery-orders',
			'post_status' => 'publish',
			'posts_per_page' => $per_page,
			'paged' => $page,
			'orderby' => 'date',
			'order' => 'DESC',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = sanitize_text_field( $search );
		}

		// Date range filter
		if ( ! empty( $date_from ) || ! empty( $date_to ) ) {
			$date_query = array();
			
			if ( ! empty( $date_from ) ) {
				$date_query['after'] = sanitize_text_field( $date_from );
			}
			
			if ( ! empty( $date_to ) ) {
				$date_query['before'] = sanitize_text_field( $date_to );
			}
			
			$args['date_query'] = array( $date_query );
		}

		$meta_query = array();

		if ( ! empty( $status ) ) {
			$meta_query[] = array(
				'key' => 'order_status',
				'value' => sanitize_text_field( $status ),
				'compare' => '=',
			);
		}

		if ( ! empty( $payment_status ) ) {
			$meta_query[] = array(
				'key' => 'order_payment_status',
				'value' => sanitize_text_field( $payment_status ),
				'compare' => '=',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $args );
		$orders = array();

		foreach ( $query->posts as $post ) {
			$orders[] = $this->prepare_order_data( $post );
		}

		$response = array(
			'orders' => $orders,
			'total' => $query->found_posts,
			'pages' => $query->max_num_pages,
			'current_page' => $page,
			'per_page' => $per_page,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get specific order
	 */
	public function get_order( $request ) {
		$order_id = $request['id'];
		$post = get_post( $order_id );

		if ( ! $post || $post->post_type !== 'mydelivery-orders' ) {
			return new \WP_Error( 'order_not_found', __( 'Order not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$order_data = $this->prepare_order_data( $post );
		return rest_ensure_response( $order_data );
	}

	/**
	 * Create new order
	 */
	public function create_order( $request ) {
		$title = sanitize_text_field( $request['title'] ?: 'Order #' . time() );
		$content = wp_kses_post( $request['content'] ?: '' );

		$post_data = array(
			'post_type' => 'mydelivery-orders',
			'post_title' => $title,
			'post_content' => $content,
			'post_status' => 'publish',
		);

		$order_id = wp_insert_post( $post_data );

		if ( is_wp_error( $order_id ) ) {
			return new \WP_Error( 'create_failed', __( 'Failed to create order', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		// Save order meta fields
		$this->save_order_meta( $order_id, $request );

		$post = get_post( $order_id );
		$order_data = $this->prepare_order_data( $post );

		return rest_ensure_response( $order_data );
	}

	/**
	 * Update order
	 */
	public function update_order( $request ) {
		$order_id = $request['id'];
		$post = get_post( $order_id );

		if ( ! $post || $post->post_type !== 'mydelivery-orders' ) {
			return new \WP_Error( 'order_not_found', __( 'Order not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$post_data = array(
			'ID' => $order_id,
		);

		if ( isset( $request['title'] ) ) {
			$post_data['post_title'] = sanitize_text_field( $request['title'] );
		}

		if ( isset( $request['content'] ) ) {
			$post_data['post_content'] = wp_kses_post( $request['content'] );
		}

		$result = wp_update_post( $post_data );

		if ( is_wp_error( $result ) ) {
			return new \WP_Error( 'update_failed', __( 'Failed to update order', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		// Update order meta fields
		$this->save_order_meta( $order_id, $request );

		$updated_post = get_post( $order_id );
		$order_data = $this->prepare_order_data( $updated_post );

		return rest_ensure_response( $order_data );
	}

	/**
	 * Delete order
	 */
	public function delete_order( $request ) {
		$order_id = $request['id'];
		$post = get_post( $order_id );

		if ( ! $post || $post->post_type !== 'mydelivery-orders' ) {
			return new \WP_Error( 'order_not_found', __( 'Order not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$result = wp_delete_post( $order_id, true );

		if ( ! $result ) {
			return new \WP_Error( 'delete_failed', __( 'Failed to delete order', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'deleted' => true, 'id' => $order_id ) );
	}

	/**
	 * Prepare order data for API response
	 */
	private function prepare_order_data( $post ) {
		$order_data = array(
			'id' => $post->ID,
			'title' => $post->post_title,
			'content' => $post->post_content,
			'status' => $post->post_status,
			'date_created' => $post->post_date,
			'date_modified' => $post->post_modified,
			
			// Order data
			'order_status' => get_post_meta( $post->ID, 'order_status', true ),
			'order_date' => get_post_meta( $post->ID, 'order_date', true ),
			'order_ship_method' => get_post_meta( $post->ID, 'order_ship_method', true ),
			
			// Customer data
			'customer' => array(
				'name' => get_post_meta( $post->ID, 'order_customer_name', true ),
				'phone' => get_post_meta( $post->ID, 'customer_phone', true ),
				'address' => get_post_meta( $post->ID, 'order_address', true ),
				'address_number' => get_post_meta( $post->ID, 'order_address_number', true ),
				'address_complement' => get_post_meta( $post->ID, 'order_address_comp', true ),
				'neighborhood' => get_post_meta( $post->ID, 'order_neighborhood', true ),
				'zipcode' => get_post_meta( $post->ID, 'order_zipcode', true ),
			),
			
			// Payment data
			'payment' => array(
				'status' => get_post_meta( $post->ID, 'order_payment_status', true ),
				'type' => get_post_meta( $post->ID, 'order_payment_type', true ),
				'method' => get_post_meta( $post->ID, 'order_payment_method', true ),
				'delivery_price' => get_post_meta( $post->ID, 'order_delivery_price', true ),
				'coupon' => get_post_meta( $post->ID, 'order_coupon', true ),
				'subtotal' => get_post_meta( $post->ID, 'order_subtotal', true ),
				'total' => get_post_meta( $post->ID, 'order_total', true ),
				'change' => get_post_meta( $post->ID, 'order_change', true ),
			),
			
			// Store data
			'table' => get_post_meta( $post->ID, 'order_table', true ),
			'notes' => get_post_meta( $post->ID, 'order_notes', true ),
			'items' => get_post_meta( $post->ID, 'myd_order_items', true ),
		);

		return $order_data;
	}

	/**
	 * Save order meta fields
	 */
	private function save_order_meta( $order_id, $request ) {
		$meta_fields = array(
			// Order data
			'order_status' => 'order_status',
			'order_date' => 'order_date',
			'order_ship_method' => 'ship_method',
			
			// Customer data
			'order_customer_name' => 'customer_name',
			'customer_phone' => 'customer_phone',
			'order_address' => 'customer_address',
			'order_address_number' => 'customer_address_number',
			'order_address_comp' => 'customer_address_complement',
			'order_neighborhood' => 'customer_neighborhood',
			'order_zipcode' => 'customer_zipcode',
			
			// Payment data
			'order_payment_status' => 'payment_status',
			'order_payment_type' => 'payment_type',
			'order_payment_method' => 'payment_method',
			'order_delivery_price' => 'delivery_price',
			'order_coupon' => 'coupon',
			'order_subtotal' => 'subtotal',
			'order_total' => 'total',
			'order_change' => 'change',
			
			// Store data
			'order_table' => 'table',
			'order_notes' => 'notes',
			'myd_order_items' => 'items',
		);

		foreach ( $meta_fields as $meta_key => $request_key ) {
			if ( isset( $request[ $request_key ] ) ) {
				$value = $request[ $request_key ];
				
				if ( in_array( $request_key, array( 'delivery_price', 'subtotal', 'total', 'change' ) ) ) {
					$value = floatval( $value );
				} elseif ( $request_key === 'items' && is_array( $value ) ) {
					$value = $value; // Keep as array for items
				} else {
					$value = sanitize_text_field( $value );
				}
				
				update_post_meta( $order_id, $meta_key, $value );
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
			'status' => array(
				'description' => __( 'Filter by order status', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'new', 'confirmed', 'in-process', 'done', 'waiting', 'in-delivery', 'finished', 'canceled' ),
			),
			'payment_status' => array(
				'description' => __( 'Filter by payment status', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'waiting', 'paid', 'failed' ),
			),
			'date_from' => array(
				'description' => __( 'Filter orders from this date (YYYY-MM-DD)', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'date_to' => array(
				'description' => __( 'Filter orders to this date (YYYY-MM-DD)', 'myd-delivery-pro' ),
				'type' => 'string',
			),
		);
	}

	/**
	 * Get order schema for validation
	 */
	public function get_order_schema() {
		return array(
			'title' => array(
				'description' => __( 'Order title', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'content' => array(
				'description' => __( 'Order content/notes', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'order_status' => array(
				'description' => __( 'Order status', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'new', 'confirmed', 'in-process', 'done', 'waiting', 'in-delivery', 'finished', 'canceled' ),
			),
			'order_date' => array(
				'description' => __( 'Order date', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'ship_method' => array(
				'description' => __( 'Shipping method', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'customer_name' => array(
				'description' => __( 'Customer full name', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'customer_phone' => array(
				'description' => __( 'Customer phone', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'customer_address' => array(
				'description' => __( 'Customer address', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'customer_address_number' => array(
				'description' => __( 'Customer address number', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'customer_address_complement' => array(
				'description' => __( 'Customer address complement', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'customer_neighborhood' => array(
				'description' => __( 'Customer neighborhood', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'customer_zipcode' => array(
				'description' => __( 'Customer zipcode', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'payment_status' => array(
				'description' => __( 'Payment status', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'waiting', 'paid', 'failed' ),
			),
			'payment_type' => array(
				'description' => __( 'Payment type', 'myd-delivery-pro' ),
				'type' => 'string',
				'enum' => array( 'upon-delivery', 'payment-integration' ),
			),
			'payment_method' => array(
				'description' => __( 'Payment method', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'delivery_price' => array(
				'description' => __( 'Delivery price', 'myd-delivery-pro' ),
				'type' => 'number',
			),
			'coupon' => array(
				'description' => __( 'Coupon code', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'subtotal' => array(
				'description' => __( 'Order subtotal', 'myd-delivery-pro' ),
				'type' => 'number',
			),
			'total' => array(
				'description' => __( 'Order total', 'myd-delivery-pro' ),
				'type' => 'number',
			),
			'change' => array(
				'description' => __( 'Change amount', 'myd-delivery-pro' ),
				'type' => 'number',
			),
			'table' => array(
				'description' => __( 'Table number for in-store orders', 'myd-delivery-pro' ),
				'type' => 'integer',
			),
			'notes' => array(
				'description' => __( 'Order notes', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'items' => array(
				'description' => __( 'Order items', 'myd-delivery-pro' ),
				'type' => 'array',
			),
		);
	}
}

new Orders_Api();