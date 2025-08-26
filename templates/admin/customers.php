<?php

use MydPro\Includes\Repositories\Customer_Repository;
use MydPro\Includes\Customer;
use MydPro\Includes\Myd_Store_Formatting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle AJAX requests
if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	return;
}

// Get query parameters
$current_page = max( 1, intval( $_GET['paged'] ?? 1 ) );
$per_page = 20;
$search = sanitize_text_field( $_GET['search'] ?? '' );
$orderby = sanitize_key( $_GET['orderby'] ?? 'total_spent' );
$order = sanitize_key( $_GET['order'] ?? 'DESC' );

// Calculate offset
$offset = ( $current_page - 1 ) * $per_page;

// Get customers data
$args = [
	'search' => $search,
	'limit' => $per_page,
	'offset' => $offset,
	'orderby' => $orderby,
	'order' => $order
];

$customers_data = \MydPro\Includes\Repositories\Customer_Repository::get_all_customers( $args );
$customers = \MydPro\Includes\Customer::create_from_data( $customers_data );
$total_customers = \MydPro\Includes\Repositories\Customer_Repository::get_customers_count( ['search' => $search] );
$stats = \MydPro\Includes\Repositories\Customer_Repository::get_customers_statistics();

// Debug: Verificar si las estadísticas se obtuvieron correctamente
if ( empty( $stats ) || ! is_array( $stats ) ) {
	$stats = [
		'total_customers' => 0,
		'avg_orders_per_customer' => 0,
		'avg_spent_per_customer' => 0,
		'repeat_rate' => 0
	];
}

// Asegurar que todas las claves necesarias existen
$stats = array_merge([
	'total_customers' => 0,
	'avg_orders_per_customer' => 0,
	'avg_spent_per_customer' => 0,
	'repeat_rate' => 0
], $stats);

// Get currency symbol with fallback
$currency_symbol = '$'; // Fallback
if ( class_exists( '\MydPro\Includes\Store_Data' ) ) {
	$currency_symbol = \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' ) ?: '$';
}

// Debug: Log statistics to browser console (remove in production)
if ( current_user_can( 'manage_options' ) ) {
	echo '<script>console.log("Customer Stats:", ' . json_encode( $stats ) . ');</script>';
}

/**
 * Format phone number for WhatsApp Venezuela
 * Removes spaces, dots, and leading 0, adds 58 prefix
 */
function format_whatsapp_phone( $phone ) {
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

// Calculate pagination
$total_pages = ceil( $total_customers / $per_page );

// Get base URL for pagination
$base_url = admin_url( 'admin.php?page=myd-delivery-customers' );
if ( ! empty( $search ) ) {
	$base_url .= '&search=' . urlencode( $search );
}
if ( $orderby !== 'total_spent' ) {
	$base_url .= '&orderby=' . $orderby;
}
if ( $order !== 'DESC' ) {
	$base_url .= '&order=' . $order;
}

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Clientes', 'myd-delivery-pro' ); ?></h1>

	<!-- Statistics Cards -->
	<section class="myd-custom-content-page" style="margin-bottom: 15px;">
		<div class="myd-admin-cards myd-card-4columns">
			<div class="myd-admin-cards__item">
				<div class="myd-admin-cards__content">
					<div class="myd-admin-cards__header">
						<h3><?php echo number_format( $stats['total_customers'] ); ?></h3>
						<span class="myd-admin-cards__subtitle"><?php esc_html_e( 'Total Clientes', 'myd-delivery-pro' ); ?></span>
					</div>
					<div class="myd-admin-cards__icon">
						<span class="dashicons dashicons-groups"></span>
					</div>
				</div>
			</div>

			<div class="myd-admin-cards__item">
				<div class="myd-admin-cards__content">
					<div class="myd-admin-cards__header">
						<h3><?php echo number_format( $stats['avg_orders_per_customer'], 1 ); ?></h3>
						<span class="myd-admin-cards__subtitle"><?php esc_html_e( 'Pedidos por Cliente', 'myd-delivery-pro' ); ?></span>
					</div>
					<div class="myd-admin-cards__icon">
						<span class="dashicons dashicons-cart"></span>
					</div>
				</div>
			</div>

			<div class="myd-admin-cards__item">
				<div class="myd-admin-cards__content">
					<div class="myd-admin-cards__header">
						<h3><?php echo $currency_symbol . Myd_Store_Formatting::format_price( $stats['avg_spent_per_customer'] ); ?></h3>
						<span class="myd-admin-cards__subtitle"><?php esc_html_e( 'Gasto Promedio', 'myd-delivery-pro' ); ?></span>
					</div>
					<div class="myd-admin-cards__icon">
						<span class="dashicons dashicons-money-alt"></span>
					</div>
				</div>
			</div>

			<div class="myd-admin-cards__item">
				<div class="myd-admin-cards__content">
					<div class="myd-admin-cards__header">
						<h3><?php echo $stats['repeat_rate']; ?>%</h3>
						<span class="myd-admin-cards__subtitle"><?php esc_html_e( 'Tasa de Retorno', 'myd-delivery-pro' ); ?></span>
					</div>
					<div class="myd-admin-cards__icon">
						<span class="dashicons dashicons-update"></span>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Filters and Search -->
	<div class="myd-admin-filter">
		<form method="GET" action="">
			<input type="hidden" name="page" value="myd-delivery-customers">
			
			<div class="myd-filter-group">
				<input type="search" 
				       name="search" 
				       value="<?php echo esc_attr( $search ); ?>" 
				       placeholder="<?php esc_attr_e( 'Buscar por nombre o teléfono...', 'myd-delivery-pro' ); ?>"
				       class="regular-text">
				
				<select name="orderby">
					<option value="total_spent" <?php selected( $orderby, 'total_spent' ); ?>><?php esc_html_e( 'Gasto Total', 'myd-delivery-pro' ); ?></option>
					<option value="total_orders" <?php selected( $orderby, 'total_orders' ); ?>><?php esc_html_e( 'Número de Pedidos', 'myd-delivery-pro' ); ?></option>
					<option value="last_order_date" <?php selected( $orderby, 'last_order_date' ); ?>><?php esc_html_e( 'Último Pedido', 'myd-delivery-pro' ); ?></option>
					<option value="name" <?php selected( $orderby, 'name' ); ?>><?php esc_html_e( 'Nombre', 'myd-delivery-pro' ); ?></option>
				</select>
				
				<select name="order">
					<option value="DESC" <?php selected( $order, 'DESC' ); ?>><?php esc_html_e( 'Descendente', 'myd-delivery-pro' ); ?></option>
					<option value="ASC" <?php selected( $order, 'ASC' ); ?>><?php esc_html_e( 'Ascendente', 'myd-delivery-pro' ); ?></option>
				</select>
				
				<input type="submit" value="<?php esc_attr_e( 'Filtrar', 'myd-delivery-pro' ); ?>" class="button">
				
				<?php if ( ! empty( $search ) || $orderby !== 'total_spent' || $order !== 'DESC' ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=myd-delivery-customers' ) ); ?>" class="button">
						<?php esc_html_e( 'Limpiar', 'myd-delivery-pro' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</form>
	</div>

	<!-- Customers Table -->
	<div class="myd-customers-table-container">
		<?php if ( ! empty( $customers ) ) : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" class="manage-column">
							<a href="<?php echo esc_url( add_query_arg( ['orderby' => 'name', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'] ) ); ?>">
								<?php esc_html_e( 'Cliente', 'myd-delivery-pro' ); ?>
								<?php if ( $orderby === 'name' ) : ?>
									<span class="sorting-indicator"><?php echo $order === 'ASC' ? '↑' : '↓'; ?></span>
								<?php endif; ?>
							</a>
						</th>
						<th scope="col" class="manage-column">
							<?php esc_html_e( 'Teléfono', 'myd-delivery-pro' ); ?>
						</th>
						<th scope="col" class="manage-column">
							<a href="<?php echo esc_url( add_query_arg( ['orderby' => 'total_orders', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'] ) ); ?>">
								<?php esc_html_e( 'Pedidos', 'myd-delivery-pro' ); ?>
								<?php if ( $orderby === 'total_orders' ) : ?>
									<span class="sorting-indicator"><?php echo $order === 'ASC' ? '↑' : '↓'; ?></span>
								<?php endif; ?>
							</a>
						</th>
						<th scope="col" class="manage-column">
							<a href="<?php echo esc_url( add_query_arg( ['orderby' => 'total_spent', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'] ) ); ?>">
								<?php esc_html_e( 'Gasto Total', 'myd-delivery-pro' ); ?>
								<?php if ( $orderby === 'total_spent' ) : ?>
									<span class="sorting-indicator"><?php echo $order === 'ASC' ? '↑' : '↓'; ?></span>
								<?php endif; ?>
							</a>
						</th>
						<th scope="col" class="manage-column">
							<?php esc_html_e( 'Promedio', 'myd-delivery-pro' ); ?>
						</th>
						<th scope="col" class="manage-column">
							<a href="<?php echo esc_url( add_query_arg( ['orderby' => 'last_order_date', 'order' => $order === 'ASC' ? 'DESC' : 'ASC'] ) ); ?>">
								<?php esc_html_e( 'Último Pedido', 'myd-delivery-pro' ); ?>
								<?php if ( $orderby === 'last_order_date' ) : ?>
									<span class="sorting-indicator"><?php echo $order === 'ASC' ? '↑' : '↓'; ?></span>
								<?php endif; ?>
							</a>
						</th>
						<th scope="col" class="manage-column">
							<?php esc_html_e( 'Estado', 'myd-delivery-pro' ); ?>
						</th>
						<th scope="col" class="manage-column">
							<?php esc_html_e( 'Acciones', 'myd-delivery-pro' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $customers as $customer ) : 
						$status = $customer->get_status();
						$most_used_address = $customer->get_most_used_address();
					?>
						<tr>
							<td class="column-name">
								<strong><?php echo esc_html( $customer->name ); ?></strong>
								<div class="customer-meta">
									<span class="customer-type <?php echo esc_attr( $customer->get_customer_type_class() ); ?>">
										<?php echo esc_html( $customer->get_customer_type() ); ?>
									</span>
									<?php if ( $customer->is_at_risk() ) : ?>
										<span class="customer-at-risk"><?php esc_html_e( 'En Riesgo', 'myd-delivery-pro' ); ?></span>
									<?php endif; ?>
								</div>
								<?php if ( $most_used_address ) : ?>
									<div class="customer-address">
										<small><?php echo esc_html( $most_used_address['full_address'] ); ?></small>
									</div>
								<?php endif; ?>
							</td>
							<td class="column-phone">
								<div class="phone-info">
									<div class="phone-number"><?php echo esc_html( $customer->phone ); ?></div>
									<a href="https://wa.me/<?php echo esc_attr( format_whatsapp_phone( $customer->phone ) ); ?>" 
									   target="_blank" 
									   class="whatsapp-button" 
									   title="<?php esc_attr_e( 'Conversar por WhatsApp', 'myd-delivery-pro' ); ?>">
										<span class="dashicons dashicons-format-chat"></span>
										<?php esc_html_e( 'WhatsApp', 'myd-delivery-pro' ); ?>
									</a>
								</div>
							</td>
							<td class="column-orders">
								<strong><?php echo $customer->total_orders; ?></strong>
								<div class="customer-since">
									<?php printf( 
										esc_html__( 'Cliente desde hace %s', 'myd-delivery-pro' ), 
										esc_html( $customer->customer_since ) 
									); ?>
								</div>
							</td>
							<td class="column-total-spent">
								<strong><?php echo esc_html( $customer->get_formatted_total_spent() ); ?></strong>
							</td>
							<td class="column-avg-order">
								<?php echo esc_html( $customer->get_formatted_average_order_value() ); ?>
							</td>
							<td class="column-last-order">
								<?php if ( ! empty( $customer->last_order_date ) ) : ?>
									<?php echo esc_html( wp_date( 'd/m/Y', strtotime( $customer->last_order_date ) ) ); ?>
									<div class="days-ago">
										<?php printf( 
											esc_html__( 'Hace %d días', 'myd-delivery-pro' ), 
											$customer->get_days_since_last_order() 
										); ?>
									</div>
								<?php else : ?>
									<em><?php esc_html_e( 'N/A', 'myd-delivery-pro' ); ?></em>
								<?php endif; ?>
							</td>
							<td class="column-status">
								<span class="customer-status <?php echo esc_attr( $status['class'] ); ?>">
									<?php echo esc_html( $status['label'] ); ?>
								</span>
							</td>
							<td class="column-actions">
								<button type="button" 
								        class="button button-small view-customer-details" 
								        data-customer-phone="<?php echo esc_attr( $customer->phone ); ?>"
								        data-customer-name="<?php echo esc_attr( $customer->name ); ?>">
									<?php esc_html_e( 'Ver Detalles', 'myd-delivery-pro' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php printf( 
								esc_html( _n( '%s cliente', '%s clientes', $total_customers, 'myd-delivery-pro' ) ), 
								number_format( $total_customers ) 
							); ?>
						</span>
						
						<?php if ( $current_page > 1 ) : ?>
							<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1, $base_url ) ); ?>">
								<span class="screen-reader-text"><?php esc_html_e( 'Página anterior', 'myd-delivery-pro' ); ?></span>
								<span aria-hidden="true">‹</span>
							</a>
						<?php endif; ?>
						
						<span class="paging-input">
							<label for="current-page-selector" class="screen-reader-text"><?php esc_html_e( 'Página actual', 'myd-delivery-pro' ); ?></label>
							<input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $current_page; ?>" size="1" aria-describedby="table-paging">
							<span class="tablenav-paging-text">
								<?php printf( esc_html__( 'de %s', 'myd-delivery-pro' ), number_format( $total_pages ) ); ?>
							</span>
						</span>
						
						<?php if ( $current_page < $total_pages ) : ?>
							<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1, $base_url ) ); ?>">
								<span class="screen-reader-text"><?php esc_html_e( 'Página siguiente', 'myd-delivery-pro' ); ?></span>
								<span aria-hidden="true">›</span>
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

		<?php else : ?>
			<div class="myd-empty-state">
				<div class="myd-empty-state__icon">
					<span class="dashicons dashicons-groups"></span>
				</div>
				<h3><?php esc_html_e( 'No se encontraron clientes', 'myd-delivery-pro' ); ?></h3>
				<?php if ( ! empty( $search ) ) : ?>
					<p><?php esc_html_e( 'No hay clientes que coincidan con tu búsqueda.', 'myd-delivery-pro' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=myd-delivery-customers' ) ); ?>" class="button">
						<?php esc_html_e( 'Ver todos los clientes', 'myd-delivery-pro' ); ?>
					</a>
				<?php else : ?>
					<p><?php esc_html_e( 'Aún no tienes clientes registrados.', 'myd-delivery-pro' ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<!-- Customer Details Modal -->
<div id="customer-details-modal" class="myd-modal" style="display: none;">
	<div class="myd-modal-content">
		<div class="myd-modal-header">
			<h2 id="customer-modal-title"><?php esc_html_e( 'Detalles del Cliente', 'myd-delivery-pro' ); ?></h2>
			<button type="button" class="myd-modal-close" aria-label="<?php esc_attr_e( 'Cerrar modal', 'myd-delivery-pro' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="myd-modal-body">
			<div id="customer-details-loading" class="myd-loading">
				<?php esc_html_e( 'Cargando detalles del cliente...', 'myd-delivery-pro' ); ?>
			</div>
			<div id="customer-details-content"></div>
		</div>
	</div>
</div>

<!-- Styles -->
<style>
.myd-customers-table-container {
	margin-top: 20px;
}

.customer-meta {
	margin-top: 5px;
}

.customer-type {
	padding: 2px 6px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: 500;
	text-transform: uppercase;
}

.customer-type.customer-new { background: #e3f2fd; color: #1976d2; }
.customer-type.customer-regular { background: #f3e5f5; color: #7b1fa2; }
.customer-type.customer-frequent { background: #e8f5e8; color: #388e3c; }
.customer-type.customer-vip { background: #fff3e0; color: #f57c00; }

.customer-at-risk {
	background: #ffebee;
	color: #d32f2f;
	padding: 2px 6px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: 500;
	margin-left: 5px;
}

.customer-address {
	margin-top: 3px;
}

.customer-since, .days-ago {
	font-size: 12px;
	color: #666;
	margin-top: 2px;
}

.customer-status {
	padding: 4px 8px;
	border-radius: 12px;
	font-size: 11px;
	font-weight: 500;
	text-transform: uppercase;
}

.customer-status.status-active { background: #e8f5e8; color: #2e7d32; }
.customer-status.status-regular { background: #e3f2fd; color: #1565c0; }
.customer-status.status-inactive { background: #fff3e0; color: #ef6c00; }
.customer-status.status-at-risk { background: #ffebee; color: #c62828; }

.myd-empty-state {
	text-align: center;
	padding: 60px 20px;
	background: #fff;
	border: 1px solid #e5e5e5;
	border-radius: 4px;
}

.myd-empty-state__icon .dashicons {
	font-size: 64px;
	color: #ddd;
	width: auto;
	height: auto;
}

.myd-filter-group {
	display: flex;
	gap: 10px;
	align-items: center;
	margin-bottom: 20px;
}

.myd-filter-group input[type="search"] {
	min-width: 200px;
}

.sorting-indicator {
	font-size: 12px;
	margin-left: 3px;
}

/* WhatsApp Button Styles */
.phone-info {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.phone-number {
	font-size: 13px;
	color: #333;
	font-weight: 500;
}

.whatsapp-button {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	padding: 4px 8px;
	background: #25D366;
	color: white !important;
	border-radius: 4px;
	text-decoration: none !important;
	font-size: 11px;
	font-weight: 500;
	transition: all 0.2s ease;
	width: fit-content;
}

.whatsapp-button:hover {
	background: #128C7E;
	color: white !important;
	text-decoration: none !important;
	transform: translateY(-1px);
	box-shadow: 0 2px 4px rgba(37, 211, 102, 0.3);
}

.whatsapp-button .dashicons {
	font-size: 14px;
	width: 14px;
	height: 14px;
}

/* Modal Styles */
.myd-modal {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.5);
	z-index: 999999;
	display: flex;
	align-items: center;
	justify-content: center;
}

.myd-modal-content {
	background: white;
	border-radius: 4px;
	max-width: 800px;
	width: 90%;
	max-height: 90%;
	overflow: hidden;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
	position: relative;
}

.myd-modal-header {
	padding: 20px;
	border-bottom: 1px solid #e5e5e5;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.myd-modal-header h2 {
	margin: 0;
	font-size: 18px;
}

.myd-modal-close {
	background: none;
	border: none;
	font-size: 24px;
	cursor: pointer;
	padding: 5px;
	color: #666;
	border-radius: 3px;
	transition: all 0.2s ease;
	display: flex;
	align-items: center;
	justify-content: center;
	width: 30px;
	height: 30px;
}

.myd-modal-close:hover {
	color: #000;
	background: #f0f0f0;
}

.myd-modal-body {
	padding: 20px;
	max-height: 500px;
	overflow-y: auto;
}

.myd-loading {
	text-align: center;
	padding: 40px;
	color: #666;
}
</style>

<!-- JavaScript -->
<script type="text/javascript">
jQuery(document).ready(function($) {
	// Ensure we have the ajaxurl variable
	if (typeof ajaxurl === 'undefined') {
		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	}
	
	// Handle customer details modal
	$('.view-customer-details').on('click', function() {
		const phone = $(this).data('customer-phone');
		const name = $(this).data('customer-name');
		
		$('#customer-modal-title').text('Detalles de ' + name);
		$('#customer-details-loading').show();
		$('#customer-details-content').hide();
		$('#customer-details-modal').show();
		
		// Load customer details via AJAX
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'get_customer_details',
				phone: phone,
				nonce: '<?php echo wp_create_nonce( 'myd_customer_details' ); ?>'
			},
			success: function(response) {
				console.log('AJAX Success:', response); // Debugging
				$('#customer-details-loading').hide();
				
				if (response.success) {
					$('#customer-details-content').html(response.data).show();
				} else {
					console.error('Server Error:', response.data || 'Unknown error');
					var errorMsg = response.data || 'Error desconocido';
					if (typeof errorMsg === 'object') {
						errorMsg = JSON.stringify(errorMsg);
					}
					$('#customer-details-content').html('<p><strong>Error:</strong> ' + errorMsg + '</p>').show();
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', status, error, xhr.responseText); // Debugging
				$('#customer-details-loading').hide();
				$('#customer-details-content').html('<p>Error de conexión: ' + error + '</p>').show();
			}
		});
	});
	
	// Close modal
	$('.myd-modal-close').on('click', function(e) {
		e.preventDefault();
		$('#customer-details-modal').hide();
	});
	
	// Close modal when clicking on backdrop
	$('.myd-modal').on('click', function(e) {
		if (e.target === this) {
			$('#customer-details-modal').hide();
		}
	});
	
	// Close modal with Escape key
	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && $('#customer-details-modal').is(':visible')) {
			$('#customer-details-modal').hide();
		}
	});
	
	// Handle pagination input
	$('#current-page-selector').on('keypress', function(e) {
		if (e.which === 13) {
			const page = parseInt($(this).val());
			if (page > 0 && page <= <?php echo $total_pages; ?>) {
				window.location.href = '<?php echo esc_js( $base_url ); ?>&paged=' + page;
			}
		}
	});
});
</script>