<?php

use MydPro\Includes\Store_Data;
use MydPro\Includes\Currency_Converter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<style>
.fdm-order-list-items-product-extra {
    white-space: inherit !important;
}
</style>
<?php if ( $orders->have_posts() ) : ?>
	<?php $currency_simbol = Store_Data::get_store_data( 'currency_simbol' ); ?>
	<?php while ( $orders->have_posts() ) : ?>
		<?php $orders->the_post(); ?>
		<?php $postid = get_the_ID(); ?>
		<?php $date = get_post_meta( $postid, 'order_date', true ); ?>
		<?php $date = gmdate( 'd/m - H:i', strtotime( $date ) ); ?>
		<?php $coupon = get_post_meta( $postid, 'order_coupon', true ); ?>
		<?php $change = get_post_meta( $postid, 'order_change', true ); ?>
		<?php $payment_type = get_post_meta( $postid, 'order_payment_type', true ); ?>
		<?php $payment_type = $payment_type === 'upon-delivery' ? __( 'Upon Delivery', 'myd-delivery-pro' ) : __( 'Payment Integration', 'myd-delivery-pro' ); ?>
		<?php $payment_status = get_post_meta( $postid, 'order_payment_status', true ); ?>
		<?php $payment_status_mapped = array(
			'waiting' => __( 'Waiting', 'myd-delivery-pro' ),
			'paid' => __( 'Paid', 'myd-delivery-pro' ),
			'failed' => __( 'Failed', 'myd-delivery-pro' ),
		); ?>
		<?php $payment_status = $payment_status_mapped[ $payment_status ] ?? ''; ?>

		<?php
			$order_type = get_post_meta( $postid, 'order_ship_method', true );

			$map_type = array(
				'delivery' => __( 'Delivery', 'myd-delivery-pro' ),
				'take-away' => __( 'Take Away', 'myd-delivery-pro' ),
				'order-in-store' => __( 'Delivery', 'myd-delivery-pro' ),
			);

			$order_type = $map_type[ $order_type ] ?? '';
		?>

		<div class="fdm-orders-full-items" id="content-<?php echo esc_attr( $postid ); ?>">
			<div>
				<div class="fdm-orders-items-order">
					<div class="fdm-order-list-items">
						<div class="fdm-order-list-items-type">
							<?php echo esc_html( $order_type ); ?>
						</div>

						<div class="fdm-order-list-items-order-number">
							<?php esc_html_e( 'Order', 'myd-delivery-pro' ); ?> <?php echo esc_html( get_the_title( $postid ) ); ?>
						</div>

						<div class="fdm-order-list-items-date">
							<?php echo esc_html( $date ); ?>
						</div>

						<hr class="fdm-divider">

						<?php if ( ! empty( get_post_meta( $postid, 'order_ship_method', true ) ) ) : ?>
							<?php $table = get_post_meta( $postid, 'order_table', true ); ?>
							<?php $address = get_post_meta( $postid, 'order_address', true ); ?>

							<?php if ( ! empty( $table ) ) : ?>
								<div class="fdm-order-list-items-customer-name">
									<?php echo esc_html( get_post_meta( $postid, 'order_customer_name', true ) ); ?>
								</div>

								<div class="fdm-order-list-items-customer">
									<?php echo esc_html( get_post_meta( $postid, 'customer_phone', true ) ); ?>
								</div>

								<div class="fdm-order-list-items-customer">
									<?php echo esc_html__( 'Table', 'myd-delivery-pro' ) . ' ' . esc_html( get_post_meta( $postid, 'order_table', true ) ); ?>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $address ) ) : ?>
								<div class="fdm-order-list-items-customer-name">
									<?php echo esc_html( get_post_meta( $postid, 'order_customer_name', true ) ); ?>
								</div>

								<div class="fdm-order-list-items-customer">
									<?php echo esc_html( get_post_meta( $postid, 'customer_phone', true ) ); ?>
								</div>

								<div class="fdm-order-list-items-customer">
									<?php 
									$address_parts = array_filter( array(
										get_post_meta( $postid, 'order_address', true ),
										get_post_meta( $postid, 'order_address_number', true ),
										get_post_meta( $postid, 'order_address_comp', true )
									) );
									echo esc_html( implode( ', ', $address_parts ) );
									?>
								</div>

								<div class="fdm-order-list-items-customer">
									<?php 
									$location_parts = array_filter( array(
										get_post_meta( $postid, 'order_neighborhood', true ),
										get_post_meta( $postid, 'order_zipcode', true )
									) );
									echo esc_html( implode( ' | ', $location_parts ) );
									?>
								</div>
							<?php endif; ?>

							<?php if ( empty( $address ) && empty( $table ) ) : ?>
								<div class="fdm-order-list-items-customer-name">
									<?php echo esc_html( get_post_meta( $postid, 'order_customer_name', true ) ); ?>
								</div>
								<div class="fdm-order-list-items-customer">
									<?php echo esc_html( get_post_meta( $postid, 'customer_phone', true ) ); ?>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>

				<div class="fdm-orders-items-products">
					<div class="fdm-order-list-items">
						<?php $items = get_post_meta( $postid, 'myd_order_items', true ); ?>

						<?php if ( ! empty( $items ) ) : ?>
							<?php foreach ( $items as $value ) : ?>
								<div class="fdm-products-order-loop">
									<div class="fdm-order-list-items-product"><?php echo esc_html( $value['product_name'] ); ?></div>

									<?php if ( ! empty( $value['product_extras'] ) ) : ?>
										<div class="fdm-order-list-items-product-extra"><?php echo esc_html( trim( $value['product_extras'] ) ); ?></div>
									<?php endif; ?>

									<?php if ( ! empty( $value['product_note'] ) ) : ?>
										<div class="fdm-order-list-items-customer"><?php echo esc_html__( 'Note', 'myd-delivery-pro' ) . ' ' . esc_html( $value['product_note'] ); ?></div>
									<?php endif; ?>

									<div class="fdm-order-list-items-product-extra">
										<div class="myd-order-price-container">
											<span class="myd-order-price-usd">
												<?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) . ' ' . $value['product_price'] ); ?>
											</span>
											<?php if ( $value['product_price'] > 0 ) : ?>
												<?php echo Currency_Converter::get_conversion_display( $value['product_price'], false ); ?>
											<?php endif; ?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>

						<hr class="fdm-divider">

						<div class="fdm-order-list-items-customer">
							<?php esc_html_e( 'Delivery', 'myd-delivery-pro' ); ?>:
							<div class="myd-order-price-container">
								<span class="myd-order-price-usd">
									<?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( get_post_meta( $postid, 'order_delivery_price', true ) ); ?>
								</span>
								<?php 
								$delivery_price = get_post_meta( $postid, 'order_delivery_price', true );
								if ( $delivery_price > 0 ) : ?>
									<?php echo Currency_Converter::get_conversion_display( $delivery_price, false ); ?>
								<?php endif; ?>
							</div>
						</div>

						<?php if ( ! empty( $coupon ) ) : ?>
							<div class="fdm-order-list-items-customer">
								<?php esc_html_e( 'Coupon code', 'myd-delivery-pro' ); ?>:
								<?php echo esc_html( $coupon ); ?>
							</div>
						<?php endif; ?>

						<?php esc_html_e( 'Total', 'myd-delivery-pro'); ?>:
						<div class="fdm-order-list-items-customer-name">
							<div class="myd-order-price-container">
								<span class="myd-order-price-usd">
									<?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( get_post_meta( $postid, 'order_total', true ) ); ?>
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
							<?php esc_html_e( 'Payment Method', 'myd-delivery-pro' ); ?>:
							<?php echo esc_html( get_post_meta( $postid, 'order_payment_method', true ) ); ?>
						</div>

						<div class="fdm-order-list-items-customer">
							<?php esc_html_e( 'Payment Status', 'myd-delivery-pro' ); ?>:
							<?php echo esc_html( $payment_status ); ?>
						</div>

						<?php
						// Mostrar comprobante de pago si la funcionalidad está activa, existe y la orden está en estado 'new'
						if ( get_option( 'myd-payment-receipt-required' ) === 'yes' ) :
							$order_status = get_post_meta( $postid, 'order_status', true );
							$payment_receipt_id = get_post_meta( $postid, 'order_payment_receipt', true );

							if ( $order_status === 'new' && ! empty( $payment_receipt_id ) ) :
								$receipt_url = wp_get_attachment_url( $payment_receipt_id );

								if ( $receipt_url ) :
							?>
								<div class="fdm-order-list-items-customer">
									<a href="<?php echo esc_url( $receipt_url ); ?>"
									   target="_blank"
									   style="display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 10px;">
										📋 <?php esc_html_e( 'Ver Comprobante de Pago', 'myd-delivery-pro' ); ?>
									</a>
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
										<?php echo Currency_Converter::get_conversion_display( $change_numeric, false ); ?>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	<?php endwhile; ?>
	<?php \wp_reset_postdata(); ?>
<?php endif; ?>
