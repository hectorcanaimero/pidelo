<?php

use MydPro\Includes\Myd_Legacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delivery mode
 */
$delivery_mode = get_option( 'myd-delivery-mode' );
if ( empty( $delivery_mode ) ) {
	$old_delivery_mode = Myd_Legacy::get_old_delivery_type();
	switch ( $old_delivery_mode ) {
		case 'unique-zipcode':
			update_option( 'myd-delivery-mode', 'fixed-per-cep' );
			break;

		case 'unique-neighborhood':
			update_option( 'myd-delivery-mode', 'fixed-per-neighborhood' );
			break;

		case 'per_zipcode':
			update_option( 'myd-delivery-mode', 'per-cep-range' );
			break;

		case 'per_neighborhood':
			update_option( 'myd-delivery-mode', 'per-neighborhood' );
			break;
	}

	$delivery_mode = get_option( 'myd-delivery-mode' );
}

/**
 * Delivery mode options
 */
$delivery_mode_options = get_option( 'myd-delivery-mode-options' );
if ( isset( $delivery_mode_options[0] ) && $delivery_mode_options[0] === 'initial' ) {
	$old_delivery_area = Myd_Legacy::get_old_delivery_area();
	update_option( 'myd-delivery-mode-options', $old_delivery_area );
	$delivery_mode_options = get_option( 'myd-delivery-mode-options' );
}

?>
<div id="tab-delivery-content" class="myd-tabs-content">
	<h2>
		<?php esc_html_e( 'Delivery Settings', 'myd-delivery-pro' );?>
	</h2>
	<p>
		<?php esc_html_e( 'In this section you can configure all delivery settings.', 'myd-delivery-pro' );?>
	</p>

	<table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="fdm-estimate-time-delivery"><?php esc_html_e( 'Estimate Delivery time', 'myd-delivery-pro' );?></label>
                </th>
                <td>
                    <input name="fdm-estimate-time-delivery" type="text" id="fdm-estimate-time-delivery" value="<?php echo esc_attr( get_option( 'fdm-estimate-time-delivery' ) );?>" class="regular-text">
                    <p class="description"><?php esc_html_e( 'This option is showing in order page.', 'myd-delivery-pro' );?></p>
                </td>
            </tr>

			<tr>
				<th scope="row">
					<label for="myd-skip-payment-in-store"><?php esc_html_e( 'Skip payment for Order in Store', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<label>
						<input
							type="checkbox"
							name="myd-skip-payment-in-store"
							id="myd-skip-payment-in-store"
							value="yes"
							<?php checked( get_option( 'myd-skip-payment-in-store' ), 'yes' ); ?>
						>
						<?php esc_html_e( 'Enable to skip payment section when order type is "Order in Store"', 'myd-delivery-pro' );?>
					</label>
					<p class="description"><?php esc_html_e( 'When enabled, orders placed as "Order in Store" (digital menu) will skip the payment section and be marked as paid automatically.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="myd-free-delivery-enabled"><?php esc_html_e( 'Delivery gratis por monto mínimo', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<label>
						<input
							type="checkbox"
							name="myd-free-delivery-enabled"
							id="myd-free-delivery-enabled"
							value="yes"
							<?php checked( get_option( 'myd-free-delivery-enabled' ), 'yes' ); ?>
						>
						<?php esc_html_e( 'Activar delivery gratis cuando la compra supera un monto', 'myd-delivery-pro' );?>
					</label>
					<p class="description"><?php esc_html_e( 'Cuando está activado, si el subtotal del pedido supera el monto configurado, el costo de delivery será 0.', 'myd-delivery-pro' );?></p>

					<div id="myd-free-delivery-amount-field" style="margin-top: 15px; <?php echo ( get_option( 'myd-free-delivery-enabled' ) !== 'yes' ) ? 'display:none;' : ''; ?>">
						<label for="myd-free-delivery-amount">
							<?php esc_html_e( 'Monto mínimo para delivery gratis:', 'myd-delivery-pro' ); ?>
						</label><br>
						<input
							type="number"
							step="0.01"
							min="0"
							name="myd-free-delivery-amount"
							id="myd-free-delivery-amount"
							value="<?php echo esc_attr( get_option( 'myd-free-delivery-amount', '0' ) ); ?>"
							class="regular-text"
							placeholder="<?php esc_attr_e( 'Ej: 50.00', 'myd-delivery-pro' ); ?>"
						>
						<p class="description">
							<?php esc_html_e( 'Ingrese el monto mínimo del subtotal. Si el pedido supera este monto, el delivery será gratis. El monto debe estar en la moneda configurada en la tienda.', 'myd-delivery-pro' ); ?>
						</p>
					</div>

					<script>
					document.getElementById('myd-free-delivery-enabled').addEventListener('change', function() {
						document.getElementById('myd-free-delivery-amount-field').style.display = this.checked ? 'block' : 'none';
					});
					</script>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="myd-delivery-mode"><?php esc_html_e( 'Delivery price mode', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<select name="myd-delivery-mode" id="myd-delivery-mode" onchange="window.MydAdmin.mydSelectDeliveryPrice(this)">
						<option
							value="select">
							<?php esc_html_e( 'Select', 'myd-delivery-pro' ); ?>
						</option>
						<option
							value="fixed-per-cep"
							<?php selected( $delivery_mode, 'fixed-per-cep' ); ?>
						>
							<?php esc_html_e( 'Fixed price (Limit by Zipcode range)', 'myd-delivery-pro' ); ?>
						</option>
						<option
							value="fixed-per-neighborhood"
							<?php selected( $delivery_mode, 'fixed-per-neighborhood' ); ?>
						>
							<?php esc_html_e( 'Fixed price (Limit by Neighborhood)', 'myd-delivery-pro' ); ?>
						</option>
						<option
							value="per-cep-range"
							<?php selected( $delivery_mode, 'per-cep-range' ); ?>
						>
							<?php esc_html_e( 'Price per Zipcode range', 'myd-delivery-pro' ); ?>
						</option>
						<option
							value="per-neighborhood"
							<?php selected( $delivery_mode, 'per-neighborhood' ); ?>
						>
							<?php esc_html_e( 'Price per Neighborhood', 'myd-delivery-pro' ); ?>
						</option>
						<option
							value="per-distance"
							<?php selected( $delivery_mode, 'per-distance' ); ?>
						>
							<?php esc_html_e( 'Price per Distance (Beta)', 'myd-delivery-pro' ); ?>
						</option>
					</select>

					<p class="description">
						<?php esc_html_e( 'Select the delivery mode to see more options.', 'myd-delivery-pro' ); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/delivery/delivery-fixed-per-cep.php'; ?>
	<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/delivery/delivery-fixed-per-neighborhood.php'; ?>
	<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/delivery/delivery-per-cep-range.php'; ?>
	<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/delivery/delivery-per-neighborhood.php'; ?>
	<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/delivery/delivery-per-distance.php'; ?>
</div>
