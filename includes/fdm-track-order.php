<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Track order page
 * TODO: Refactor!!!
 */
class Fdm_Track_Order {
	/**
	 * Construct
	 */
	public function __construct() {
		add_shortcode( 'mydelivery-track-order', [ $this, 'output_content' ] );
	}

	/**
	 * Output content for shortcode
	 *
	 * @return string
	 */
	public function output_content() {
		if ( empty( $this->get_order_id() ) ) {
			?>
				<div class="fdm-not-logged"><?php esc_html_e( 'Sorry, you don\'t have orders to show.', 'myd-delivery-pro' ); ?></div>
			<?php
		} else {
			?>
			<?php \wp_enqueue_style( 'myd-track-order-frontend' ); ?>
			<?php \wp_enqueue_style( 'myd-order-panel-frontend' ); ?>

			<?php $postid = $this->get_order_id(); ?>
			<?php $currency_simbol = Store_Data::get_store_data( 'currency_simbol' ); ?>
			<?php $date = get_post_meta( $postid, 'order_date', true ); ?>
			<?php $date = date( 'd/m - H:i', strtotime( $date ) ); ?>
			<?php $order_status = $this->get_order_info( 'order_status' ); ?>
			<?php $status_color = $this->get_status_color( $order_status ); ?>
			<?php $coupon = get_post_meta( $postid, 'order_coupon', true ); ?>
			<?php $change = get_post_meta( $postid, 'order_change', true ); ?>
			<?php $payment_type = get_post_meta( $postid, 'order_payment_type', true ); ?>
			<?php $payment_type = $payment_type === 'upon-delivery' ? __( 'Upon Delivery', 'myd-delivery-pro' ) : __( 'Payment Integration', 'myd-delivery-pro' ); ?>
			<?php $payment_status = get_post_meta( $postid, 'order_payment_status', true ); ?>
			<?php $payment_status_mapped = array(
				'waiting' => __( 'Waiting', 'myd-delivery-pro' ),
				'paid' => __( 'Pago', 'myd-delivery-pro' ),
				'failed' => __( 'Falhou', 'myd-delivery-pro' ),
			); ?>
			<?php $payment_status = $payment_status_mapped[ $payment_status ] ?? ''; ?>

			<div class="fdm-track-order-wrap">
				<div class="fdm-track-order-content">
					<div id="myd-track-order-status-bar" class="fdm-track-order-content-status <?php echo esc_attr( $status_color ); ?>">
						<?php echo esc_html( $this->convert_status_name( $order_status ) ); ?>
					</div>

					<div class="myd-track-order-update-wrapper">
						<span class="myd-pulsating-circle"></span>
						<span>
							<?php \esc_html_e( 'Live updates', 'myd-delivery-pro' ); ?>
						</span>
					</div>

					<div class="fdm-track-order-content-customer">
					<div class="fdm-order-list-items-type"><?php echo esc_html( $this->get_order_type() ); ?></div>
					<div class="fdm-order-list-items-order-number"><?php echo __( 'Order', 'myd-delivery-pro' ); ?> <?php echo esc_html( $postid ); ?></div>
					<div class="fdm-order-list-items-date"><?php echo esc_html( $date ); ?></div>
					<hr class="fdm-divider">

					<?php echo $this->get_order_type_data(); ?>

					</div>
						<div class="fdm-track-order-content-products">
							<?php echo $this->get_order_items( $this->get_order_id() ); ?>

							<hr class="fdm-divider">

							<div class="fdm-order-list-items-customer">
								<?php echo esc_html__( 'Delivery', 'myd-delivery-pro' ); ?>:
								<div class="myd-order-price-container">
									<span class="myd-order-price-usd">
										<?php echo esc_html( $currency_simbol ); ?> <?php echo $this->get_order_info( 'order_delivery_price' ); ?>
									</span>
									<?php 
									$delivery_price = $this->get_order_info( 'order_delivery_price' );
									if ( $delivery_price > 0 ) : ?>
										<?php echo \MydPro\Includes\Currency_Converter::get_conversion_display( $delivery_price, false ); ?>
									<?php endif; ?>
								</div>
							</div>

							<?php if ( ! empty( $coupon ) ) : ?>
								<div class="fdm-order-list-items-customer">
									<?php esc_html_e( 'Coupon code', 'myd-delivery-pro' ); ?>:
									<?php echo esc_html( $coupon ); ?>
								</div>
							<?php endif; ?>

							<?php echo esc_html__( 'Total', 'myd-delivery-pro' ); ?>:
							<div class="fdm-order-list-items-customer-name">
								<div class="myd-order-price-container">
									<span class="myd-order-price-usd">
										<?php echo esc_html( $currency_simbol ); ?> <?php echo $this->get_order_info('order_total'); ?>
									</span>
									<?php 
								$order_total = get_post_meta( $postid, 'order_total', true );

								if ( $order_total > 0 ) :
									$conversion_display = Currency_Converter::get_conversion_display( $order_total, false );
									if ( ! empty( $conversion_display ) ) :
										echo $conversion_display;
									endif;
								endif;
								?>
								</div>
							</div>

							<div class="fdm-order-list-items-customer">
								<?php esc_html_e( 'Payment Type', 'myd-delivery-pro' ); ?>:
								<?php echo esc_html( $payment_type ); ?>
							</div>

							<div class="fdm-order-list-items-customer">
								<?php echo esc_html__( 'Payment Method', 'myd-delivery-pro' ); ?>:
								<?php echo $this->get_order_info( 'order_payment_method' ); ?>
							</div>

							<div class="fdm-order-list-items-customer">
								<?php esc_html_e( 'Payment Status', 'myd-delivery-pro' ); ?>:
								<?php echo esc_html( $payment_status ); ?>
							</div>

							<?php
							// Mostrar comprobante de pago si la funcionalidad está activa y existe
							if ( get_option( 'myd-payment-receipt-required' ) === 'yes' ) :
								$payment_receipt_id = get_post_meta( $postid, 'order_payment_receipt', true );
								if ( ! empty( $payment_receipt_id ) ) :
									$receipt_url = wp_get_attachment_url( $payment_receipt_id );
									$receipt_type = get_post_mime_type( $payment_receipt_id );
									if ( $receipt_url ) :
								?>
									<div class="fdm-order-list-items-customer" style="margin-top: 15px;">
										<strong><?php esc_html_e( 'Comprobante de Pago', 'myd-delivery-pro' ); ?>:</strong>
										<div style="margin-top: 10px;">
											<?php if ( strpos( $receipt_type, 'image' ) !== false ) : ?>
												<a href="<?php echo esc_url( $receipt_url ); ?>" target="_blank" style="display: block; margin-bottom: 10px;">
													<img src="<?php echo esc_url( wp_get_attachment_image_url( $payment_receipt_id, 'medium' ) ); ?>"
														 style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 4px;"
														 alt="<?php esc_attr_e( 'Comprobante de Pago', 'myd-delivery-pro' ); ?>" />
												</a>
											<?php endif; ?>
											<a href="<?php echo esc_url( $receipt_url ); ?>"
											   target="_blank"
											   download
											   style="display: inline-block; padding: 8px 16px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
												📥 <?php esc_html_e( 'Descargar Comprobante', 'myd-delivery-pro' ); ?>
											</a>
										</div>
									</div>
								<?php
									endif;
								endif;
							endif;
							?>

							<?php if ( ! empty( $change ) ) : ?>
								<div class="fdm-order-list-items-customer">
									<?php esc_html_e( 'Change for', 'myd-delivery-pro' ); ?>:
									<div class="myd-order-price-container">
										<span class="myd-order-price-usd">
											<?php echo esc_html( $change ); ?>
										</span>
										<?php 
										$change_numeric = floatval( str_replace( array( '$', ' ', ',' ), '', $change ) );
										if ( $change_numeric > 0 ) : ?>
											<?php echo \MydPro\Includes\Currency_Converter::get_conversion_display( $change_numeric, false ); ?>
										<?php endif; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<script>
					const eventUrl = window.location.origin + '/wp-json/myd-delivery/v1/order/' + window.location.search + '&fields=status';
					const fetchOrderStatus = async () => {
						try {
							const response = await fetch(eventUrl, { cache: 'no-store' });
							if (!response.ok) {
								throw new Error('Network response was not ok');
							}
							const data = await response.json();
							const responseStatus = data?.data?.status || '';
							setOrderStatus(responseStatus);
						} catch (error) {
							console.error('Polling failed:', error);
						}
					};
					const pollingInterval = setInterval(fetchOrderStatus, 5000);

					function setOrderStatus(status) {
						const statusBar = document.getElementById('myd-track-order-status-bar');
						const statusMap = {
							new: {
								'class': 'myd-track-order-status--new',
								'name': '<?php echo \esc_html__( 'New', 'myd-delivery-pro' ); ?>',
							},
							confirmed: {
								'class': 'myd-track-order-status--confirmed',
								'name': '<?php echo \esc_html__( 'Confirmed', 'myd-delivery-pro' ); ?>',
							},
							'in-process': {
								'class': 'myd-track-order-status--inprocess',
								'name': '<?php echo \esc_html__( 'In Process', 'myd-delivery-pro' ); ?>',
							},
							'in-delivery': {
								'class': 'myd-track-order-status--indelivery',
								'name': '<?php echo \esc_html__( 'In Delivery', 'myd-delivery-pro' ); ?>',
							},
							finished:  {
								'class': 'myd-track-order-status--finished',
								'name': '<?php echo \esc_html__( 'Finished', 'myd-delivery-pro' ); ?>',
							},
							canceled:  {
								'class':  'myd-track-order-status--canceled',
								'name': '<?php echo \esc_html__( 'Canceled', 'myd-delivery-pro' ); ?>',
							},
							done: {
								'class': 'myd-track-order-status--done',
								'name': '<?php echo \esc_html__( 'Done', 'myd-delivery-pro' ); ?>',
							},
							waiting: {
								'class': 'myd-track-order-status--waiting',
								'name': '<?php echo \esc_html__( 'Wait in Delivery', 'myd-delivery-pro' ); ?>',
							},
						};

						if(statusBar && status) {
							const statusClass = statusMap[status].class;
							statusBar.className = 'fdm-track-order-content-status ' + statusMap[status].class;
							statusBar.innerText = statusMap[status].name;
						}
					}
				</script>
			<?php
		}
	}

	/**
	 * Get order type data
	 */
	public function get_order_type_data() {
		$postid = $this->get_order_id();
		$table = get_post_meta( $postid, 'order_table', true );
		$address = get_post_meta( $postid, 'order_address', true );

		if ( ! empty( $table ) ) {
			return '<div class="fdm-order-list-items-customer-name">'.get_post_meta( $postid, 'order_customer_name', true ).'</div>
                    <div class="fdm-order-list-items-customer">'.get_post_meta( $postid, 'customer_phone', true ).'</div>
                    <div class="fdm-order-list-items-customer">'.esc_html__('Table','myd-delivery-pro').' '.get_post_meta( $postid, 'order_table', true ).'</div>';
		}

		if ( ! empty( $address ) ) {
			return '<div class="fdm-order-list-items-customer-name">'.get_post_meta( $postid, 'order_customer_name', true ).'</div>
                    <div class="fdm-order-list-items-customer">'.get_post_meta( $postid, 'customer_phone', true ).'</div>
					<div class="fdm-order-list-items-customer">'.get_post_meta( $postid, 'order_address', true ).', '.get_post_meta( $postid, 'order_address_number', true ).' | '.get_post_meta( $postid, 'order_address_comp', true ).'</div>
                    <div class="fdm-order-list-items-customer">'.get_post_meta( $postid, 'order_neighborhood', true ).' | '.get_post_meta( $postid, 'order_zipcode', true ).'</div>';
		}

		if ( empty( $address ) and empty( $table ) ) {
			return '<div class="fdm-order-list-items-customer-name">'.get_post_meta( $postid, 'order_customer_name', true ).'</div>
                    <div class="fdm-order-list-items-customer">'.get_post_meta( $postid, 'customer_phone', true ).'</div>';
		}
	}

	/**
	 * Get order id
	 */
	public function get_order_id() {
		if ( ! empty( $_GET['hash'] ) ) {
			$parameter = sanitize_text_field( $_GET['hash'] );
			return base64_decode( $parameter );
		} else {
			return;
		}
	}

	/**
	 * Get order info
	 */
	public function get_order_info( $meta ) {
		$order_meta = get_post_meta( $this->get_order_id(), $meta, true );

		if ( ! empty( $order_meta ) ) {
			return $order_meta;
		} else {
			return;
		}
	}

	protected function get_order_type() {
		$order_type = \get_post_meta( $this->get_order_id(), 'order_ship_method', true );

		$map_type = array(
			'delivery' => __( 'Delivery', 'myd-delivery-pro' ),
			'take-away' => __( 'Take Away', 'myd-delivery-pro' ),
			'order-in-store' => __( 'Delivery', 'myd-delivery-pro' ),
		);

		return $map_type[ $order_type ] ?? '';
	}

	/**
	 * Prepare items
	 */
	public function get_order_items( $postid ) {
		/**
		 * TODO: check this with new model.
		 */
		$items = get_post_meta( $postid, 'myd_order_items', true );
		$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );
		$list = '';

		if ( ! empty( $items ) ) {
			foreach ( $items as $value ) {
				$list .= '<div class="fdm-products-order-loop">';
				$list .= '<div class="fdm-order-list-items-product">' . esc_html( $value['product_name'] ) . '</div>';
				if ( $value['product_extras'] !== '' ) {
					$list .= '<div class="fdm-order-list-items-product-extra">- ' . esc_html( $value['product_extras'] ) . '</div>';
				}

				if ( ! empty( $value['product_note'] ) ) {
					$list .= '<div class="fdm-order-list-items-customer">' . __( 'Note', 'myd-delivery-pro' ) . ' ' . esc_html( $value['product_note'] ) . '</div>';
				}

				$list .= '<div class="fdm-order-list-items-product-extra">';
				$list .= '<div class="myd-order-price-container">';
				$list .= '<span class="myd-order-price-usd">' . esc_html( $currency_simbol ) . ' ' . esc_html( $value['product_price'] ) . '</span>';
				if ( $value['product_price'] > 0 ) {
					$list .= \MydPro\Includes\Currency_Converter::get_conversion_display( $value['product_price'], false );
				}
				$list .= '</div>';
				$list .= '</div>';
				$list .= '</div>';
			}
		}

		return $list;
	}

	/**
	 * Check status color
	 *
	 * @return string
	 */
	public function get_status_color( $status ) {
		switch ( $status ) {
			case 'new':
				return 'myd-track-order-status--new';
				break;
			case 'confirmed':
				return 'myd-track-order-status--confirmed';
				break;
			case 'in-process':
				return 'myd-track-order-status--inprocess';
				break;
			case 'in-delivery':
				return 'myd-track-order-status--indelivery';
				break;
			case 'finished':
				return 'myd-track-order-status--finished';
				break;
			case 'canceled':
				return 'myd-track-order-status--canceled';
				break;
			case 'done':
				return 'myd-track-order-status--done';
				break;
			case 'waiting':
				return 'myd-track-order-status--waiting';
				break;
		}
	}

	/**
	 * Convert order status
	 */
	public function convert_status_name( $status ) {
		switch ( $status ) {
			case 'new':
				return esc_html__( 'New', 'myd-delivery-pro' );
				break;
			case 'confirmed':
				return esc_html__( 'Confirmed', 'myd-delivery-pro' );
				break;
			case 'in-process':
				return esc_html__( 'In Process', 'myd-delivery-pro' );
				break;
			case 'in-delivery':
				return esc_html__( 'In Delivery', 'myd-delivery-pro' );
				break;
			case 'finished':
				return esc_html__( 'Finished', 'myd-delivery-pro' );
				break;
			case 'canceled':
				return esc_html__( 'Canceled', 'myd-delivery-pro' );
				break;
			case 'done':
				return esc_html__( 'Done', 'myd-delivery-pro' );
				break;
			case 'waiting':
				return esc_html__( 'Wait in Delivery', 'myd-delivery-pro' );
				break;
		}
	}
}

new Fdm_Track_Order();
