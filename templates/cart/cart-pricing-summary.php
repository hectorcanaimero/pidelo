<?php

use MydPro\Includes\Store_Data;
use MydPro\Includes\Myd_Store_Formatting;
use MydPro\Includes\Currency_Converter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="myd-card__flex-row">
	<h4 id="myd-cart-payment-subtotal-label" class="myd-cart__title-inline">
		<?php esc_html_e( 'Subtotal', 'myd-delivery-pro' ); ?>
	</h4>
	<span id="myd-cart-payment-subtotal-value">
		<div class="myd-cart__summary-price-container">
			<span class="myd-cart__summary-price-usd">
				<?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) . ' ' . Myd_Store_Formatting::format_price( $this->subtotal ) ); ?>
			</span>
			<?php if ( $this->subtotal > 0 ) : ?>
				<?php echo Currency_Converter::get_conversion_display( $this->subtotal, false ); ?>
			<?php endif; ?>
		</div>
	</span>
</div>

<?php if ( isset( $this->shipping['price'] ) ) : ?>
	<div class="myd-card__flex-row">
		<h4 id="myd-cart-payment-delivery-fee-label" class="myd-cart__title-inline">
			<?php esc_html_e( 'Delivery Fee', 'myd-delivery-pro' ); ?>
		</h4>
		<span id="myd-cart-payment-delivery-fee-value">
			<div class="myd-cart__summary-price-container">
				<span class="myd-cart__summary-price-usd">
					<?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) . ' ' . Myd_Store_Formatting::format_price( $this->shipping['price'] ?? 0 ) ); ?>
				</span>
				<?php if ( ( $this->shipping['price'] ?? 0 ) > 0 ) : ?>
					<?php echo Currency_Converter::get_conversion_display( $this->shipping['price'] ?? 0, false ); ?>
				<?php endif; ?>
			</div>
		</span>
	</div>
<?php endif; ?>

<?php if ( isset( $this->coupon->code ) ) : ?>
	<div class="myd-card__flex-row">
		<h4 id="myd-cart-payment-coupon-label" class="myd-cart__title-inline">
			<?php esc_html_e( 'Coupon', 'myd-delivery-pro' ); ?>
		</h4>
		<span id="myd-cart-payment-coupon-value">
			<?php echo esc_html( $this->coupon->code ); ?>
		</span>
	</div>
<?php endif; ?>

<div class="myd-card__flex-row">
	<h4 id="myd-cart-payment-total-label" class="myd-cart__title-inline">
		<?php esc_html_e( 'Total', 'myd-delivery-pro' ); ?>
	</h4>
	<span id="myd-cart-payment-total-value">
		<div class="myd-cart__summary-price-container">
			<span class="myd-cart__summary-price-usd">
				<?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) . ' ' . Myd_Store_Formatting::format_price( $this->total ) ); ?>
			</span>
			<?php if ( $this->total > 0 ) : ?>
				<?php echo Currency_Converter::get_conversion_display( $this->total, false ); ?>
			<?php endif; ?>
		</div>
	</span>
</div>
