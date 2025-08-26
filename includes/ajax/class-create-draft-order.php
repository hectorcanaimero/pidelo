<?php

namespace MydPro\Includes\Ajax;

use MydPro\Includes\Create_Draft_Order as Temp_Create_Draft;
use MydPro\Includes\Repositories\Coupon_Repository;
use MydPro\Includes\Cart;
use MydPro\Includes\Myd_Currency;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create Draft Order
 */
class Create_Draft_Order {
	/**
	 * Construct
	 */
	public function __construct() {
		add_action( 'wp_ajax_myd_create_draft_order', array( $this, 'create_draft_order' ) );
		add_action( 'wp_ajax_nopriv_myd_create_draft_order', array( $this, 'create_draft_order' ) );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function create_draft_order() {
		$nonce = $_POST['sec'] ?? null;
		if ( ! $nonce || ! \wp_verify_nonce( $nonce, 'myd-create-order' ) ) {
			die( \esc_html__( 'Ops! Security check failed.', 'my-delivey-wordpress' ) );
		}

		$data = json_decode( stripslashes( $_POST['data'] ), true );

		$cart = new Cart( $data['cart']['items'] ?? array() );
		$coupon = Coupon_Repository::get_by_name( $data['coupon']['code'] ?? '' );

		$order = new Temp_Create_Draft( $data );
		$order->create();
		$order->set_type( $data['type'] ?? '' );
		$order->set_cart( $cart );
		$order->set_shipping( $data['shipping'] ?? array() );
		$order->set_customer( $data['customer'] ?? array() );
		$order->set_coupon( $coupon );
		$order->save();

		\do_action(
			'myd-delivery/order/after-create',
			array(
				'id' => $order->id,
				'data' => $order,
				'currency_code' => Myd_Currency::get_currency_code(),
			)
		);

		$response = \apply_filters( 'myd-delivery/order/after-create/ajax-response',
			array(
				'order_id' => $order->id,
				'data' => $order,
				'template' => $order->get_total_summary_template(),
			)
		);

		echo json_encode( $response, true );
		wp_die();
	}
}
