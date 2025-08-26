<?php

namespace MydPro\Includes\Api\Customers;

use MydPro\Includes\Repositories\Customer_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customers REST API endpoints
 */
class Customers_Api {
	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_customer_routes' ] );
	}

	/**
	 * Register customer routes
	 */
	public function register_customer_routes() {
		// GET /customers - List customers
		\register_rest_route(
			'myd-delivery/v1',
			'/customers',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_customers' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_collection_params(),
				),
			)
		);

		// GET /customers/{phone} - Get specific customer by phone
		\register_rest_route(
			'myd-delivery/v1',
			'/customers/(?P<phone>[^/]+)',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_customer' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'phone' => array(
							'description' => __( 'Customer phone number', 'myd-delivery-pro' ),
							'type' => 'string',
							'required' => true,
						),
					),
				),
			)
		);

		// PUT /customers/{phone} - Update customer info (via most recent order)
		\register_rest_route(
			'myd-delivery/v1',
			'/customers/(?P<phone>[^/]+)',
			array(
				array(
					'methods'  => \WP_REST_Server::EDITABLE,
					'callback' => [ $this, 'update_customer' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_customer_schema(),
				),
			)
		);

		// GET /customers/{phone}/orders - Get customer orders
		\register_rest_route(
			'myd-delivery/v1',
			'/customers/(?P<phone>[^/]+)/orders',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_customer_orders' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'phone' => array(
							'description' => __( 'Customer phone number', 'myd-delivery-pro' ),
							'type' => 'string',
							'required' => true,
						),
						'page' => array(
							'description' => __( 'Current page of orders', 'myd-delivery-pro' ),
							'type' => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'description' => __( 'Maximum number of orders to return', 'myd-delivery-pro' ),
							'type' => 'integer',
							'default' => 10,
							'minimum' => 1,
							'maximum' => 100,
						),
					),
				),
			)
		);

		// GET /customers/{phone}/addresses - Get customer addresses
		\register_rest_route(
			'myd-delivery/v1',
			'/customers/(?P<phone>[^/]+)/addresses',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_customer_addresses' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'phone' => array(
							'description' => __( 'Customer phone number', 'myd-delivery-pro' ),
							'type' => 'string',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Get customers list
	 */
	public function get_customers( $request ) {
		$page = $request->get_param( 'page' ) ?: 1;
		$per_page = $request->get_param( 'per_page' ) ?: 20;
		$search = $request->get_param( 'search' ) ?: '';
		$orderby = $request->get_param( 'orderby' ) ?: 'last_order_date';
		$order = $request->get_param( 'order' ) ?: 'DESC';
		$date_from = $request->get_param( 'date_from' ) ?: '';
		$date_to = $request->get_param( 'date_to' ) ?: '';

		$args = array(
			'search' => $search,
			'limit' => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
			'orderby' => $orderby,
			'order' => $order,
			'date_from' => $date_from,
			'date_to' => $date_to,
		);

		$customers = Customer_Repository::get_all_customers( $args );
		$total_customers = Customer_Repository::get_customers_count( $args );

		$response = array(
			'customers' => $customers,
			'total' => $total_customers,
			'pages' => ceil( $total_customers / $per_page ),
			'current_page' => $page,
			'per_page' => $per_page,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get specific customer by phone
	 */
	public function get_customer( $request ) {
		$phone = urldecode( $request['phone'] );
		
		if ( empty( $phone ) ) {
			return new \WP_Error( 'missing_phone', __( 'Customer phone is required', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Get customer data
		$customers = Customer_Repository::get_all_customers( array(
			'search' => $phone,
			'limit' => 1
		) );

		if ( empty( $customers ) ) {
			return new \WP_Error( 'customer_not_found', __( 'Customer not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$customer = $customers[0];

		// Get additional customer details
		$recent_orders = Customer_Repository::get_customer_orders( $phone, array( 'limit' => 5 ) );
		$addresses = Customer_Repository::get_customer_addresses( $phone );

		$customer_data = array(
			'phone' => $customer['phone'],
			'name' => $customer['name'],
			'total_orders' => $customer['total_orders'],
			'total_spent' => $customer['total_spent'],
			'last_order_date' => $customer['last_order_date'],
			'first_order_date' => $customer['first_order_date'],
			'customer_since' => $customer['customer_since'],
			'recent_orders' => $recent_orders,
			'addresses' => $addresses,
		);

		return rest_ensure_response( $customer_data );
	}

	/**
	 * Update customer information
	 * Note: Since customers are derived from orders, we update the most recent order
	 */
	public function update_customer( $request ) {
		$phone = urldecode( $request['phone'] );
		
		if ( empty( $phone ) ) {
			return new \WP_Error( 'missing_phone', __( 'Customer phone is required', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Get customer's most recent order to update
		$recent_orders = Customer_Repository::get_customer_orders( $phone, array( 'limit' => 1 ) );
		
		if ( empty( $recent_orders ) ) {
			return new \WP_Error( 'customer_not_found', __( 'Customer not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$order_id = $recent_orders[0]['ID'];

		// Update customer information in the most recent order
		$meta_fields = array(
			'order_customer_name' => 'name',
			'customer_phone' => 'phone',
			'order_address' => 'address',
			'order_address_number' => 'address_number',
			'order_address_comp' => 'address_complement',
			'order_neighborhood' => 'neighborhood',
			'order_zipcode' => 'zipcode',
		);

		foreach ( $meta_fields as $meta_key => $request_key ) {
			if ( isset( $request[ $request_key ] ) ) {
				$value = sanitize_text_field( $request[ $request_key ] );
				update_post_meta( $order_id, $meta_key, $value );
			}
		}

		// Return updated customer data
		return $this->get_customer( $request );
	}

	/**
	 * Get customer orders
	 */
	public function get_customer_orders( $request ) {
		$phone = urldecode( $request['phone'] );
		$page = $request->get_param( 'page' ) ?: 1;
		$per_page = $request->get_param( 'per_page' ) ?: 10;

		if ( empty( $phone ) ) {
			return new \WP_Error( 'missing_phone', __( 'Customer phone is required', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		$args = array(
			'limit' => $per_page,
			'offset' => ( $page - 1 ) * $per_page,
			'orderby' => 'date',
			'order' => 'DESC'
		);

		$orders = Customer_Repository::get_customer_orders( $phone, $args );

		// Get total count for pagination
		$total_args = array(
			'post_type' => 'mydelivery-orders',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'customer_phone',
					'value' => $phone,
					'compare' => '='
				)
			),
			'fields' => 'ids'
		);

		$total_orders = count( get_posts( $total_args ) );

		$response = array(
			'orders' => $orders,
			'total' => $total_orders,
			'pages' => ceil( $total_orders / $per_page ),
			'current_page' => $page,
			'per_page' => $per_page,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get customer addresses
	 */
	public function get_customer_addresses( $request ) {
		$phone = urldecode( $request['phone'] );

		if ( empty( $phone ) ) {
			return new \WP_Error( 'missing_phone', __( 'Customer phone is required', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		$addresses = Customer_Repository::get_customer_addresses( $phone );

		return rest_ensure_response( array( 'addresses' => $addresses ) );
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
				'default' => 20,
				'minimum' => 1,
				'maximum' => 100,
			),
			'search' => array(
				'description' => __( 'Limit results to those matching a string (name or phone)', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'orderby' => array(
				'description' => __( 'Sort collection by customer attribute', 'myd-delivery-pro' ),
				'type' => 'string',
				'default' => 'last_order_date',
				'enum' => array( 'name', 'phone', 'total_orders', 'total_spent', 'last_order_date' ),
			),
			'order' => array(
				'description' => __( 'Order sort attribute ascending or descending', 'myd-delivery-pro' ),
				'type' => 'string',
				'default' => 'DESC',
				'enum' => array( 'ASC', 'DESC' ),
			),
			'date_from' => array(
				'description' => __( 'Filter customers from this date (YYYY-MM-DD)', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'date_to' => array(
				'description' => __( 'Filter customers to this date (YYYY-MM-DD)', 'myd-delivery-pro' ),
				'type' => 'string',
			),
		);
	}

	/**
	 * Get customer schema for validation
	 */
	public function get_customer_schema() {
		return array(
			'name' => array(
				'description' => __( 'Customer full name', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'phone' => array(
				'description' => __( 'Customer phone number', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'address' => array(
				'description' => __( 'Customer address', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'address_number' => array(
				'description' => __( 'Customer address number', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'address_complement' => array(
				'description' => __( 'Customer address complement', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'neighborhood' => array(
				'description' => __( 'Customer neighborhood', 'myd-delivery-pro' ),
				'type' => 'string',
			),
			'zipcode' => array(
				'description' => __( 'Customer zipcode', 'myd-delivery-pro' ),
				'type' => 'string',
			),
		);
	}
}

new Customers_Api();