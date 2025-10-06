<?php

use MydPro\Includes\Myd_Currency;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$currency_list = Myd_Currency::get_currency_list();
$saved_currency_code = Myd_Currency::get_currency_code();

?>
<div id="tab-payment-content" class="myd-tabs-content">
	<h2><?php esc_html_e( 'Payment Settings', 'myd-delivery-pro' ); ?></h2>
	<p><?php esc_html_e( 'In this section you can configure the payment methods and others settings.', 'myd-delivery-pro' ); ?></p>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="myd-currency"><?php esc_html_e( 'Currency', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<select name="myd-currency" id="myd-currency">
						<option value=""><?php esc_html_e( 'Select', 'myd-delivery-pro' ); ?></option>
						<?php foreach ( $currency_list as $currency_code => $currency_value ) : ?>
							<?php $currency_name = $currency_value['name'] . ' (' . $currency_value['symbol'] . ')'; ?>
							<option
								value="<?php echo esc_attr( $currency_code ); ?>"
								<?php selected( $saved_currency_code, $currency_code ); ?>
								>
								<?php echo esc_html( $currency_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fdm-number-decimal"><?php esc_html_e( 'Number of decimals', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input name="fdm-number-decimal" type="number" id="fdm-number-decimal" value="<?php echo esc_attr( get_option( 'fdm-number-decimal' ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'This sets the number of decimal points show in displayed price.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fdm-decimal-separator"><?php esc_html_e( 'Decimal separator', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input name="fdm-decimal-separator" type="text" id="fdm-decimal-separator" value="<?php echo esc_attr( get_option( 'fdm-decimal-separator' ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'This sets the decimal separator of displayed prices ', 'myd-delivery-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fdm-thousands-separator"><?php esc_html_e( 'Thousands separator', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input name="fdm-thousands-separator" type="text" id="fdm-thousands-separator" value="<?php echo esc_attr( get_option( 'fdm-thousands-separator' ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'This sets the thousands separator of displayed prices', 'myd-delivery-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fdm-payment-in-cash">
						<?php esc_html_e( 'Accept payment in cash?', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<select name="fdm-payment-in-cash" id="fdm-payment-in-cash">
						<option value="">
							<?php esc_html_e( 'Select', 'myd-delivery-pro' ); ?>
						</option>
						<option
							value="yes"
							<?php selected( get_option( 'fdm-payment-in-cash' ), 'yes' ); ?>
						>
							<?php esc_html_e( 'Yes, my store accept cash payments on delivery', 'myd-delivery-pro' ); ?>
						</option>
						<option
							value="no"
							<?php selected( get_option( 'fdm-payment-in-cash' ), 'no' ); ?>
						>
							<?php esc_html_e( 'No, my store does not accept cash payments on delivery', 'myd-delivery-pro' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fdm-payment-type">
						<?php esc_html_e( 'Payment upon delivery', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<textarea
						placeholder="<?php esc_html_e( 'Cash, Credit Card, Debit Card...', 'myd-delivery-pro' ); ?>"
						id="fdm-payment-type"
						name="fdm-payment-type"
						cols="50"
						rows="5"
						class="large-text"
					><?php esc_html_e( get_option( 'fdm-payment-type' ) ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'These are payment options to be used directly upon delivery and not while placing the order in the system.', 'myd-delivery-pro' ); ?>
					</p>

					<p class="description">
						<?php esc_html_e( 'List all payment methods separated by comma (,). Like: Credit card, Debit Card, Voucher(...).', 'myd-delivery-pro' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="myd-payment-receipt-required">
						<?php esc_html_e( 'Comprobante de Pago', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<input
						type="checkbox"
						name="myd-payment-receipt-required"
						id="myd-payment-receipt-required"
						value="yes"
						<?php checked( get_option( 'myd-payment-receipt-required' ), 'yes' ); ?>
					>
					<label for="myd-payment-receipt-required">
						<?php esc_html_e( 'Requerir comprobante de pago obligatorio en el checkout', 'myd-delivery-pro' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Si está activado, el cliente deberá subir obligatoriamente un comprobante de pago al hacer el pedido. Si está desactivado, el campo de comprobante estará oculto.', 'myd-delivery-pro' ); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<?php do_action( 'myd-delivery/settings/payment/after-fields' ); ?>
</div>
