<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API endpoint to check new orders.
 */
class Myd_Api {
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
			'my-delivery/v1',
			'/orders',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'check_orders' ],
					'permission_callback' => [ $this, 'api_permissions_check' ],
					'args' => $this->get_parameters(),
				),
			)
		);
	}

	/**
	 * Check orders and retrive status
	 */
	public function check_orders( $request ) {
		$current_id = $request['oid'];

		$args = [
			'post_type' => 'mydelivery-orders',
			'posts_per_page' => 1,
			'no_found_rows' => true,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     => 'order_status',
					'value'   => 'new',
					'compare' => '=',
				),
				array(
					'key'     => 'order_status',
					'value'   => 'confirmed',
					'compare' => '=',
				),
				array(
					'key'     => 'order_status',
					'value'   => 'in-delivery',
					'compare' => '=',
				),
			),
		];

		$orders = new \WP_Query( $args );
		$orders = $orders->get_posts();

		if ( $orders[0]->ID <= $current_id ) {

			$response = [ 'status' => 'atualizado' ];
			return rest_ensure_response( $response );
		} else {

			$response = [ 'status' => 'desatualizado' ];
			return rest_ensure_response( $response );
		}
	}

	/**
	 * Check API permissions
	 */
	public function api_permissions_check() {
		// Permitir acceso a usuarios logueados con permisos de editor
		if ( \current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new \WP_Error( 'rest_forbidden', esc_html__( 'You do not have permission to access this.', 'myd-delivery-pro' ), array( 'status' => 401 ) );
	}

	/**
	 * Define parameters
	 */
	public function get_parameters() {
		$args = array();

		$args['oid'] = array(
			'description' => esc_html__( 'The filter parameter is used to filter number', 'myd-delivery-pro' ),
			'type'        => 'integer',
			'required' => true,
			'validate_callback' => [ $this, 'validate_parameter' ],
		);

		return $args;
	}

	/**
	 * Validate parameters
	 */
	public function validate_parameter( $value, $request, $param ) {
		if ( ! is_numeric( $value ) ) {
			return new \WP_Error( 'rest_invalid_param', esc_html__( 'Sorry this parameter its not valid or empty', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}
	}
}

new Myd_Api();
