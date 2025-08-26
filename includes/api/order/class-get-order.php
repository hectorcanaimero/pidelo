<?php

namespace MydPro\Includes\Api\Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API endpoint to get order data.
 */
class Get_Order {
	/**
	 * Construct the class.
	 */
	public function __construct () {
		add_action( 'rest_api_init', [ $this, 'register_order_routes' ] );
	}

	/**
	 * Register plugin routes
	 */
	public function register_order_routes() {
		\register_rest_route(
			'myd-delivery/v1',
			'/order',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_order_data' ],
					'permission_callback' => '__return_true',
					'args' => $this->get_parameters(),
				),
			)
		);
	}

	/**
	 * Check orders and retrive status
	 */
	public function get_order_data( $request ) {
		$order_id = base64_decode( $request['hash'] );
		$data_response = array(
			'status' => get_post_meta( $order_id, 'order_status', true ),
		);
		return \wp_send_json_success( $data_response, 200 );
	}

	/**
	 * Define parameters
	 */
	public function get_parameters() {
		$args = array();

		$args['fields'] = array(
			'description' => esc_html__( 'The order fields to retrive', 'myd-delivery-pro' ),
			'type'        => 'string',
			'required' => true,
			'validate_callback' => array( $this, 'validate_parameter' ),
		);

		$args['hash'] = array(
			'description' => esc_html__( 'The order hash', 'myd-delivery-pro' ),
			'type'        => 'string',
			'required' => true,
			// 'validate_callback' => array( $this, 'validate_parameter' ),
		);

		return $args;
	}

	/**
	 * Validate parametes
	 */
	public function validate_parameter( $value, $request, $param ) {
		$allowed_parameters = array(
			'status',
		);

		if ( ! in_array( $value, $allowed_parameters ) ) {
			$error = array(
				'error_message' => esc_html__( 'Invalid or not allowed parameter', 'myd-delivery-pro' ),
			);
			return \wp_send_json_error( $error, 400 );
		}
	}
}

new Get_Order();
