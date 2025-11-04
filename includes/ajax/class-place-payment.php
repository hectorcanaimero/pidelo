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
		\add_action( 'wp_ajax_myd_order_update_status_in_store', array( $this, 'update_order_status_in_store' ) );
		\add_action( 'wp_ajax_nopriv_myd_order_update_status_in_store', array( $this, 'update_order_status_in_store' ) );
	}

	/**
	 * Place Payment function
	 *
	 * @return void
	 */
	public function place_payment() {
		// Logging para debug
		error_log( '[MYD Payment] place_payment called' );
		error_log( '[MYD Payment] POST data: ' . print_r( $_POST, true ) );
		error_log( '[MYD Payment] FILES data: ' . print_r( $_FILES, true ) );

		$nonce = $_POST['sec'] ?? null;
		if ( ! $nonce || ! \wp_verify_nonce( $nonce, 'myd-create-order' ) ) {
			error_log( '[MYD Payment] Security check failed' );
			\wp_send_json_error(
				array(
					'message' => \esc_html__( 'Ops! Security check failed.', 'my-delivey-wordpress' ),
				)
			);
			\wp_die();
		}

		$data = json_decode( stripslashes( $_POST['data'] ), true );
		$order_id = (int) $data['id'];
		$payment = $data['payment'];

		error_log( '[MYD Payment] Order ID: ' . $order_id );
		error_log( '[MYD Payment] Payment data: ' . print_r( $payment, true ) );

		// Validar comprobante de pago obligatorio
		$payment_receipt_required = \get_option( 'myd-payment-receipt-required' ) === 'yes';
		$payment_type = $payment['type'] ?? '';

		error_log( '[MYD Payment] Receipt required: ' . ( $payment_receipt_required ? 'yes' : 'no' ) );
		error_log( '[MYD Payment] Payment type: ' . $payment_type );

		// Solo validar si es pago "upon-delivery" (no aplica para payment-integration)
		if ( $payment_receipt_required && $payment_type === 'upon-delivery' ) {
			$has_file = ! empty( $_FILES['payment_receipt'] ) && $_FILES['payment_receipt']['error'] === UPLOAD_ERR_OK;

			error_log( '[MYD Payment] Has file: ' . ( $has_file ? 'yes' : 'no' ) );

			if ( ! $has_file ) {
				$upload_error = isset( $_FILES['payment_receipt']['error'] ) ? $_FILES['payment_receipt']['error'] : 'No file uploaded';
				error_log( '[MYD Payment] Validation failed - Upload error code: ' . $upload_error );

				\wp_send_json_error(
					array(
						'message' => \esc_html__( 'El comprobante de pago es obligatorio. Por favor, adjunta tu comprobante para continuar.', 'myd-delivery-pro' ),
					)
				);
				\wp_die();
			}
		}

		\update_post_meta( $order_id, 'order_payment_type', \sanitize_text_field( $payment['type'] ?? '' ) );
		\update_post_meta( $order_id, 'order_payment_method', \sanitize_text_field( $payment['method'] ?? '' ) );
		\update_post_meta( $order_id, 'order_change', \sanitize_text_field( $payment['change'] ?? '' ) );

		// Handle payment receipt file upload
		if ( ! empty( $_FILES['payment_receipt'] ) && $_FILES['payment_receipt']['error'] === UPLOAD_ERR_OK ) {
			error_log( '[MYD Payment] Processing file upload' );

			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			$file = $_FILES['payment_receipt'];
			$allowed_types = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf' );

			error_log( '[MYD Payment] File type: ' . $file['type'] );
			error_log( '[MYD Payment] File size: ' . $file['size'] );

			if ( in_array( $file['type'], $allowed_types ) ) {
				$upload = \wp_handle_upload( $file, array( 'test_form' => false ) );

				if ( ! isset( $upload['error'] ) && isset( $upload['file'] ) ) {
					error_log( '[MYD Payment] File uploaded successfully: ' . $upload['file'] );

					$attachment = array(
						'post_mime_type' => $file['type'],
						'post_title' => \sanitize_file_name( $file['name'] ),
						'post_content' => '',
						'post_status' => 'inherit'
					);

					$attachment_id = \wp_insert_attachment( $attachment, $upload['file'], $order_id );

					if ( ! \is_wp_error( $attachment_id ) ) {
						$attachment_data = \wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
						\wp_update_attachment_metadata( $attachment_id, $attachment_data );
						\update_post_meta( $order_id, 'order_payment_receipt', $attachment_id );
						error_log( '[MYD Payment] Attachment created with ID: ' . $attachment_id );
					} else {
						error_log( '[MYD Payment] Error creating attachment: ' . $attachment_id->get_error_message() );
					}
				} else {
					error_log( '[MYD Payment] Upload error: ' . ( $upload['error'] ?? 'Unknown error' ) );
				}
			} else {
				error_log( '[MYD Payment] Invalid file type: ' . $file['type'] );
			}
		}

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

		error_log( '[MYD Payment] Sending response: ' . print_r( $response, true ) );

		echo json_encode( $response, true );
		\wp_die();
	}

	/**
	 * Update order status to 'new' for order-in-store with skip payment
	 *
	 * @return void
	 */
	public function update_order_status_in_store() {
		error_log( '[MYD Payment] update_order_status_in_store called' );

		$nonce = $_POST['sec'] ?? null;
		if ( ! $nonce || ! \wp_verify_nonce( $nonce, 'myd-create-order' ) ) {
			error_log( '[MYD Payment] Security check failed for status update' );
			\wp_send_json_error(
				array(
					'message' => \esc_html__( 'Security check failed.', 'myd-delivery-pro' ),
				)
			);
			\wp_die();
		}

		$order_id = isset( $_POST['order_id'] ) ? (int) $_POST['order_id'] : 0;

		if ( ! $order_id ) {
			error_log( '[MYD Payment] Invalid order ID' );
			\wp_send_json_error(
				array(
					'message' => \esc_html__( 'Invalid order ID.', 'myd-delivery-pro' ),
				)
			);
			\wp_die();
		}

		error_log( '[MYD Payment] Updating order status to new for order: ' . $order_id );

		// Update post status to publish
		\wp_update_post(
			array(
				'ID' => $order_id,
				'post_status' => 'publish',
			)
		);

		// Update order status to 'new'
		\update_post_meta( $order_id, 'order_status', 'new' );

		error_log( '[MYD Payment] Order status updated successfully' );

		\wp_send_json_success(
			array(
				'order_id' => $order_id,
				'status' => 'new',
				'message' => \esc_html__( 'Order status updated successfully.', 'myd-delivery-pro' ),
			)
		);
		\wp_die();
	}
}
