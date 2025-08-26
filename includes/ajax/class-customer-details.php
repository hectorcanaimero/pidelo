<?php

namespace MydPro\Includes\Ajax;

use MydPro\Includes\Customer;
use MydPro\Includes\Repositories\Customer_Repository;
use Exception;
use Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customer Details AJAX Handler
 * 
 * Handles AJAX requests for customer details modal
 * 
 * @since 2.2.19
 */
class Customer_Details {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_get_customer_details', [ $this, 'get_customer_details' ] );
	}

	/**
	 * Get customer details via AJAX
	 */
	public function get_customer_details() {
		try {
			// Verify nonce
			if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'myd_customer_details' ) ) {
				wp_send_json_error( 'Invalid nonce' );
			}

			// Check permissions
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( 'Insufficient permissions' );
			}

			$phone = sanitize_text_field( $_POST['phone'] ?? '' );

			if ( empty( $phone ) ) {
				wp_send_json_error( 'Phone number required' );
			}

			// Debug: Check if Customer_Repository class exists
			if ( ! class_exists( '\MydPro\Includes\Repositories\Customer_Repository' ) ) {
				wp_send_json_error( 'Customer_Repository class not found' );
			}

			// Debug: Check if Customer class exists
			if ( ! class_exists( '\MydPro\Includes\Customer' ) ) {
				wp_send_json_error( 'Customer class not found' );
			}

			// Get customer data
			$customer = Customer::find_by_phone( $phone );

			if ( ! $customer ) {
				wp_send_json_error( 'Customer not found for phone: ' . $phone );
			}

			// Get customer orders and addresses
			$orders = $customer->get_orders( [ 'limit' => 10 ] );
			$addresses = $customer->get_addresses();

			// Generate HTML for modal content
			ob_start();
			$this->render_customer_details( $customer, $orders, $addresses );
			$html = ob_get_clean();

			if ( empty( $html ) ) {
				wp_send_json_error( 'Failed to generate customer details HTML' );
			}

			wp_send_json_success( $html );

		} catch ( Exception $e ) {
			wp_send_json_error( 'Exception: ' . $e->getMessage() );
		} catch ( Error $e ) {
			wp_send_json_error( 'Error: ' . $e->getMessage() );
		}
	}

	/**
	 * Format phone number for WhatsApp Venezuela
	 */
	private function format_whatsapp_phone( $phone ) {
		// Remove all non-numeric characters
		$clean_phone = preg_replace( '/[^0-9]/', '', $phone );
		
		// Remove leading 0 if present
		if ( substr( $clean_phone, 0, 1 ) === '0' ) {
			$clean_phone = substr( $clean_phone, 1 );
		}
		
		// Add Venezuela country code if not present
		if ( substr( $clean_phone, 0, 2 ) !== '58' ) {
			$clean_phone = '58' . $clean_phone;
		}
		
		return $clean_phone;
	}

	/**
	 * Render customer details HTML
	 * 
	 * @param Customer $customer Customer object
	 * @param array $orders Customer orders
	 * @param array $addresses Customer addresses
	 */
	private function render_customer_details( $customer, $orders, $addresses ) {
		$status = $customer->get_status();
		?>
		<div class="customer-details-modal">
			<!-- Customer Summary -->
			<div class="customer-summary">
				<div class="customer-summary-grid">
					<div class="customer-summary-item">
						<h4><?php esc_html_e( 'Información del Cliente', 'myd-delivery-pro' ); ?></h4>
						<div class="customer-info">
							<p><strong><?php esc_html_e( 'Nombre:', 'myd-delivery-pro' ); ?></strong> <?php echo esc_html( $customer->name ); ?></p>
							<p>
								<strong><?php esc_html_e( 'Teléfono:', 'myd-delivery-pro' ); ?></strong> 
								<?php echo esc_html( $customer->phone ); ?>
								<a href="https://wa.me/<?php echo esc_attr( $this->format_whatsapp_phone( $customer->phone ) ); ?>" 
								   target="_blank" 
								   class="whatsapp-button-small" 
								   title="<?php esc_attr_e( 'Conversar por WhatsApp', 'myd-delivery-pro' ); ?>">
									<span class="dashicons dashicons-format-chat"></span>
									<?php esc_html_e( 'WhatsApp', 'myd-delivery-pro' ); ?>
								</a>
							</p>
							<p><strong><?php esc_html_e( 'Tipo:', 'myd-delivery-pro' ); ?></strong> 
								<span class="customer-type <?php echo esc_attr( $customer->get_customer_type_class() ); ?>">
									<?php echo esc_html( $customer->get_customer_type() ); ?>
								</span>
							</p>
							<p><strong><?php esc_html_e( 'Estado:', 'myd-delivery-pro' ); ?></strong> 
								<span class="customer-status <?php echo esc_attr( $status['class'] ); ?>">
									<?php echo esc_html( $status['label'] ); ?>
								</span>
							</p>
							<p><strong><?php esc_html_e( 'Cliente desde:', 'myd-delivery-pro' ); ?></strong> 
								<?php echo esc_html( wp_date( 'd/m/Y', strtotime( $customer->first_order_date ) ) ); ?> 
								(<?php echo esc_html( $customer->customer_since ); ?>)
							</p>
						</div>
					</div>

					<div class="customer-summary-item">
						<h4><?php esc_html_e( 'Estadísticas', 'myd-delivery-pro' ); ?></h4>
						<div class="customer-stats">
							<div class="stat-item">
								<span class="stat-value"><?php echo $customer->total_orders; ?></span>
								<span class="stat-label"><?php esc_html_e( 'Pedidos', 'myd-delivery-pro' ); ?></span>
							</div>
							<div class="stat-item">
								<span class="stat-value"><?php echo esc_html( $customer->get_formatted_total_spent() ); ?></span>
								<span class="stat-label"><?php esc_html_e( 'Total Gastado', 'myd-delivery-pro' ); ?></span>
							</div>
							<div class="stat-item">
								<span class="stat-value"><?php echo esc_html( $customer->get_formatted_average_order_value() ); ?></span>
								<span class="stat-label"><?php esc_html_e( 'Promedio por Pedido', 'myd-delivery-pro' ); ?></span>
							</div>
							<div class="stat-item">
								<span class="stat-value"><?php echo $customer->get_days_since_last_order(); ?></span>
								<span class="stat-label"><?php esc_html_e( 'Días sin Pedidos', 'myd-delivery-pro' ); ?></span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Customer Addresses -->
			<?php if ( ! empty( $addresses ) ) : ?>
				<div class="customer-addresses">
					<h4><?php esc_html_e( 'Direcciones Utilizadas', 'myd-delivery-pro' ); ?></h4>
					<div class="addresses-list">
						<?php foreach ( array_slice( $addresses, 0, 3 ) as $address ) : ?>
							<div class="address-item">
								<div class="address-text">
									<?php echo esc_html( $address['full_address'] ); ?>
								</div>
								<div class="address-usage">
									<?php printf( 
										esc_html( _n( 'Usada %d vez', 'Usada %d veces', $address['usage_count'], 'myd-delivery-pro' ) ), 
										$address['usage_count'] 
									); ?>
								</div>
							</div>
						<?php endforeach; ?>
						<?php if ( count( $addresses ) > 3 ) : ?>
							<div class="address-item-more">
								<?php printf( 
									esc_html__( 'y %d direcciones más...', 'myd-delivery-pro' ), 
									count( $addresses ) - 3 
								); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Recent Orders -->
			<div class="customer-orders">
				<h4><?php esc_html_e( 'Pedidos Recientes', 'myd-delivery-pro' ); ?></h4>
				<?php if ( ! empty( $orders ) ) : ?>
					<div class="orders-table">
						<table class="wp-list-table widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Fecha', 'myd-delivery-pro' ); ?></th>
									<th><?php esc_html_e( 'Estado', 'myd-delivery-pro' ); ?></th>
									<th><?php esc_html_e( 'Total', 'myd-delivery-pro' ); ?></th>
									<th><?php esc_html_e( 'Pago', 'myd-delivery-pro' ); ?></th>
									<th><?php esc_html_e( 'Método', 'myd-delivery-pro' ); ?></th>
									<th><?php esc_html_e( 'Acción', 'myd-delivery-pro' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $orders as $order ) : ?>
									<tr>
										<td><?php echo esc_html( wp_date( 'd/m/Y H:i', strtotime( $order['date'] ) ) ); ?></td>
										<td>
											<span class="order-status order-status-<?php echo esc_attr( $order['status'] ); ?>">
												<?php echo esc_html( ucfirst( $order['status'] ) ); ?>
											</span>
										</td>
										<td>
											<strong><?php echo esc_html( $order['total'] ); ?></strong>
										</td>
										<td>
											<span class="payment-status payment-status-<?php echo esc_attr( $order['payment_status'] ); ?>">
												<?php 
												switch ( $order['payment_status'] ) {
													case 'paid':
														esc_html_e( 'Pagado', 'myd-delivery-pro' );
														break;
													case 'waiting':
														esc_html_e( 'Pendiente', 'myd-delivery-pro' );
														break;
													default:
														echo esc_html( ucfirst( $order['payment_status'] ) );
												}
												?>
											</span>
										</td>
										<td>
											<?php 
											switch ( $order['delivery_method'] ) {
												case 'delivery':
													esc_html_e( 'Entrega', 'myd-delivery-pro' );
													break;
												case 'pickup':
													esc_html_e( 'Recoger', 'myd-delivery-pro' );
													break;
												default:
													echo esc_html( ucfirst( $order['delivery_method'] ) );
											}
											?>
										</td>
										<td>
											<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order['ID'] . '&action=edit' ) ); ?>" 
											   class="button button-small" target="_blank">
												<?php esc_html_e( 'Ver Pedido', 'myd-delivery-pro' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						
						<?php if ( $customer->total_orders > 10 ) : ?>
							<div class="orders-more">
								<p>
									<?php printf( 
										esc_html__( 'Mostrando los últimos 10 pedidos de %d totales.', 'myd-delivery-pro' ), 
										$customer->total_orders 
									); ?>
								</p>
								<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mydelivery-orders&meta_key=customer_phone&meta_value=' . urlencode( $customer->phone ) ) ); ?>" 
								   class="button" target="_blank">
									<?php esc_html_e( 'Ver Todos los Pedidos', 'myd-delivery-pro' ); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<p><?php esc_html_e( 'No se encontraron pedidos para este cliente.', 'myd-delivery-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<style>
		.customer-details-modal {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		}

		.customer-summary {
			margin-bottom: 25px;
			padding: 20px;
			background: #f9f9f9;
			border-radius: 6px;
		}

		.customer-summary-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 30px;
		}

		.customer-summary-item h4 {
			margin: 0 0 15px 0;
			color: #333;
			font-size: 16px;
		}

		.customer-info p {
			margin: 8px 0;
			font-size: 14px;
		}

		.customer-stats {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 15px;
		}

		.stat-item {
			text-align: center;
			padding: 10px;
			background: white;
			border-radius: 4px;
			border: 1px solid #e5e5e5;
		}

		.stat-value {
			display: block;
			font-size: 18px;
			font-weight: 600;
			color: #333;
		}

		.stat-label {
			display: block;
			font-size: 12px;
			color: #666;
			margin-top: 4px;
		}

		.customer-addresses, .customer-orders {
			margin-bottom: 25px;
		}

		.customer-addresses h4, .customer-orders h4 {
			margin: 0 0 15px 0;
			color: #333;
			font-size: 16px;
			border-bottom: 1px solid #e5e5e5;
			padding-bottom: 8px;
		}

		.addresses-list {
			max-height: 150px;
			overflow-y: auto;
		}

		.address-item {
			padding: 8px 12px;
			margin-bottom: 8px;
			background: #f8f8f8;
			border-radius: 4px;
			border-left: 3px solid #0073aa;
		}

		.address-text {
			font-size: 14px;
			color: #333;
		}

		.address-usage {
			font-size: 12px;
			color: #666;
			margin-top: 4px;
		}

		.address-item-more {
			padding: 8px 12px;
			font-size: 13px;
			color: #666;
			font-style: italic;
		}

		.orders-table {
			margin-bottom: 15px;
		}

		.orders-table table {
			font-size: 13px;
		}

		.orders-table th {
			background: #f1f1f1;
			font-weight: 600;
		}

		.order-status, .payment-status {
			padding: 3px 8px;
			border-radius: 12px;
			font-size: 11px;
			font-weight: 500;
			text-transform: uppercase;
		}

		.order-status-new { background: #e3f2fd; color: #1976d2; }
		.order-status-confirmed { background: #e8f5e8; color: #388e3c; }
		.order-status-in-delivery { background: #fff3e0; color: #f57c00; }
		.order-status-done { background: #e8f5e8; color: #2e7d32; }
		.order-status-waiting { background: #f3e5f5; color: #7b1fa2; }

		.payment-status-paid { background: #e8f5e8; color: #2e7d32; }
		.payment-status-waiting { background: #fff3e0; color: #ef6c00; }

		.orders-more {
			text-align: center;
			padding: 15px;
			background: #f9f9f9;
			border-radius: 4px;
		}

		.orders-more p {
			margin: 0 0 10px 0;
			font-size: 13px;
			color: #666;
		}

		/* WhatsApp Button Small for Modal */
		.whatsapp-button-small {
			display: inline-flex;
			align-items: center;
			gap: 3px;
			padding: 2px 6px;
			background: #25D366;
			color: white !important;
			border-radius: 3px;
			text-decoration: none !important;
			font-size: 10px;
			font-weight: 500;
			margin-left: 8px;
			transition: all 0.2s ease;
		}

		.whatsapp-button-small:hover {
			background: #128C7E;
			color: white !important;
			text-decoration: none !important;
			transform: translateY(-1px);
		}

		.whatsapp-button-small .dashicons {
			font-size: 12px;
			width: 12px;
			height: 12px;
		}

		@media (max-width: 600px) {
			.customer-summary-grid {
				grid-template-columns: 1fr;
				gap: 20px;
			}
			
			.customer-stats {
				grid-template-columns: 1fr 1fr;
			}
		}
		</style>
		<?php
	}
}