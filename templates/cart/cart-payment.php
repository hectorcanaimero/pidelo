<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$online_payment_enabled = defined( 'SUMUPMYD_CURRENT_VERSION' );
?>
<div id="myd-cart-payment" class="myd-cart__payment">
	<div id="myd-cart-total-summary" class="myd-cart__payment-amount-details"></div>

	<div class="myd-cart__checkout-payment">
		<h4 class="myd-cart__checkout-title">
			<?php esc_html_e( 'Payment', 'myd-delivery-pro' ); ?>
		</h4>

		<div class="myd-cart__payment-options-container">
			<!-- just if enabled the payment plugin -->
			<?php if ( $online_payment_enabled ) : ?>
				<details open data-type="payment-integration">
					<summary>
						<?php esc_html_e( 'Pay now', 'myd-delivery-pro' ); ?>
					</summary>
					<div
						class="myd-cart__checkout-payment-method"
						id="myd-checkout-payment-method"
					>
					</div>
				</details>
			<?php endif ?>

			<details <?php echo ! $online_payment_enabled ? 'open' : ''; ?> data-type="upon-delivery">
				<summary>
					<?php esc_html_e( 'Pay upon delivery', 'myd-delivery-pro' ); ?>
				</summary>

				<?php if ( get_option( 'fdm-payment-in-cash' ) === 'yes' ) : ?>
					<div class="myd-cart__payment-option-wrapper">
						<input
							type="radio"
							class="myd-cart__payment-input-option"
							id="cash"
							name="myd-payment-option"
							value="<?php esc_html_e( 'Cash', 'myd-delivery-pro' ); ?>"
						>

						<label for="cash">
							<?php esc_html_e( 'Cash', 'myd-delivery-pro' ); ?>
						</label>

						<svg class="myd-cart__payment-input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="32px" height="32px"><path fill="#c8e6c9" d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z"/><path fill="#4caf50" d="M34.586,14.586l-13.57,13.586l-5.602-5.586l-2.828,2.828l8.434,8.414l16.395-16.414L34.586,14.586z"/></svg>
					</div>
				<?php endif; ?>

				<?php foreach ( $payments as $k => $v ) : ?>
					<div class="myd-cart__payment-option-wrapper">
						<input
							type="radio"
							id="<?php echo esc_attr( $v ); ?>"
							class="myd-cart__payment-input-option"
							name="myd-payment-option"
							value="<?php echo esc_attr( $v ); ?>"
						>

						<label for="<?php echo esc_attr( $v ); ?>">
							<?php echo esc_html( $v ); ?>
						</label>

						<svg class="myd-cart__payment-input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="32px" height="32px"><path fill="#c8e6c9" d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z"/><path fill="#4caf50" d="M34.586,14.586l-13.57,13.586l-5.602-5.586l-2.828,2.828l8.434,8.414l16.395-16.414L34.586,14.586z"/></svg>
					</div>
				<?php endforeach; ?>

				<label
					class="myd-cart__checkout-label"
					id="label-payment-change"
					for="input-payment-change"
				>
					<?php esc_html_e( 'Change for', 'myd-delivery-pro' ); ?>
				</label>
				<input
					type="text"
					class="myd-cart__checkout-input"
					id="input-payment-change"
					name="input-payment-change"
					inputmode="numeric"
					data-mask="###.###.###,##"
					data-mask-reverse="true"
				>
			</details>
		</div>
	</div>
</div>
