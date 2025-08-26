<?php

namespace MydPro\Includes\Ajax;

use MydPro\Includes\Custom_Message_Whatsapp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Place Payment
 */
class Place_Payment {
	/**
	 * Construct
	 */
	public function __construct() {
		\add_action( 'wp_ajax_myd_order_place_payment', array( $this, 'place_payment' ) );
		\add_action( 'wp_ajax_nopriv_myd_order_place_payment', array( $this, 'place_payment' ) );
	}

	/**
	 * Place Payment function
	 *
	 * @return void
	 */
	public function place_payment() {
		$nonce = $_POST['sec'] ?? null;
		if ( ! $nonce || ! \wp_verify_nonce( $nonce, 'myd-create-order' ) ) {
			die( \esc_html__( 'Ops! Security check failed.', 'my-delivey-wordpress' ) );
		}

		$data = json_decode( stripslashes( $_POST['data'] ), true );
		$order_id = (int) $data['id'];
		$payment = $data['payment'];

		\update_post_meta( $order_id, 'order_payment_type', \sanitize_text_field( $payment['type'] ?? '' ) );
		\update_post_meta( $order_id, 'order_payment_method', \sanitize_text_field( $payment['method'] ?? '' ) );
		\update_post_meta( $order_id, 'order_change', \sanitize_text_field( $payment['change'] ?? '' ) );

		$payment_error = array();
		if ( $payment['type'] === 'payment-integration' ) {
			$payment_error = \apply_filters( 'myd-delivery/order/validate-payment-integration', array(), $order_id );
		}

		$order_track_link = \get_permalink( \get_option( 'fdm-page-order-track' ) ) . '?hash=' . base64_encode( $order_id );

		$whatsapp_link = new Custom_Message_Whatsapp( $order_id );
		$whatsapp_link = $whatsapp_link->get_whatsapp_redirect_link();

		\do_action(
			'myd-delivery/order/after-place-payment',
			array(
				'id' => $order_id,
			)
		);

		if ( ! empty( $payment_error ) ) {
			$response_object = array(
				'order_id' => $order_id,
				'error' => $payment_error,
			);
		} else {
			\wp_update_post(
				array(
					'ID' => $order_id,
					'post_status' => 'publish',
				)
			);
			\update_post_meta( $order_id, 'order_status', 'new' );
			
			// Establecer estado de pago inicial si no existe
			if ( ! get_post_meta( $order_id, 'order_payment_status', true ) ) {
				\update_post_meta( $order_id, 'order_payment_status', 'waiting' );
			}

			$response_object = array(
				'id' => $order_id,
				'whatsappLink' => $whatsapp_link,
				'orderTrackLink' => $order_track_link,
			);
		}

		$response = \apply_filters( 'myd-delivery/order/place-payment/ajax-response', $response_object );

		echo json_encode( $response, true );
		\wp_die();
	}
}
