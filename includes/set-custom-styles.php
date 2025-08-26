<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function myd_add_settings_sytles() {
	?>
	<style>
		.myd-cart__button, .my-delivery-add-to-cart-popup, .myd-cart__finished-track-order, .fdm-add-to-cart-popup, .myd-cart__nav-back, .myd-cart__nav-close, .myd-cart__checkout-option--active, .myd-float { background: <?php echo esc_html( get_option( 'fdm-principal-color' ) ); ?>}
		.myd-cart__nav--active .myd-cart__nav-desc, #myd-float__qty { color: <?php echo esc_html( get_option( 'fdm-principal-color' ) ); ?> }
		.myd-cart__nav--active svg { fill: <?php echo esc_html( get_option( 'fdm-principal-color' ) ); ?> !important }
		.myd-extra-price, .myd-product-item__price { color: <?php echo esc_html( get_option( 'myd-price-color' ) ); ?> }
	</style>
	<?php
}

add_action( 'wp_head', 'myd_add_settings_sytles', 99 );
