<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipping method
 */
$shipping_type = get_option( 'myd-delivery-mode' );
$shipping_options = get_option( 'myd-delivery-mode-options' );
$shipping_options = isset( $shipping_options[ $shipping_type ] ) ? $shipping_options[ $shipping_type ] : '';

// get payment methods options
$payments = get_option( 'fdm-payment-type' );
$payments = explode( ",", $payments );
$payments = array_map( 'trim', $payments );

// coupons
$coupons_args = [
	'post_type' => 'mydelivery-coupons',
	'no_found_rows' => true,
	'post_status' => 'publish',
];

$coupons_list = new \WP_Query( $coupons_args );
$coupons_list = $coupons_list->posts;

if ( ! empty( $coupons_list ) ) {
	foreach ( $coupons_list as $k => $v ) {
		$coupons[ $k ] = [ 'name' => $v->post_title ];
		$coupons[ $k ] = $coupons[ $k ] + [ 'type' => get_post_meta( $v->ID, 'myd_coupon_type', true ) ];
		$coupons[ $k ] = $coupons[ $k ] + [ 'format' => get_post_meta( $v->ID, 'myd_discount_format', true ) ];
		$coupons[ $k ] = $coupons[ $k ] + [ 'value' => get_post_meta( $v->ID, 'myd_discount_value', true ) ];
	}
}

$enable_autocomplete_address = get_option( 'fdm-business-country' ) === 'Brazil' ? 'true' : 'false';

/**
 * To legacy type of input mask.
 * TODO: remove soon.
 */
$map_legacy_mask_option = array(
	'fdm-tel-8dig' => '####-####',
	'myd-tel-9' => '#####-####',
	'myd-tel-8-ddd' => '(##)####-####',
	'myd-tel-9-ddd' => '(##)#####-####',
	'myd-tel-us' => '(###)###-####',
	'myd-tel-ven' => '(####)###-####',
);
$mask_option = \get_option( 'fdm-mask-phone' );
if ( isset( $map_legacy_mask_option[ $mask_option ] ) ) {
	\update_option( 'fdm-mask-phone', $map_legacy_mask_option[ $mask_option ] );
	$mask_option = \get_option( 'fdm-mask-phone' );
}

?>
<div class="myd-cart__checkout">
	<div class="myd-cart__checkout-type">

		<div class="myd-cart__checkout-title"><?php esc_html_e( 'Order Type', 'myd-delivery-pro' ); ?></div>

			<div class="myd-cart__checkout-option-wrap">

				<?php if( get_option( 'myd-operation-mode-delivery' ) === 'delivery' ) : ?>

					<div class="myd-cart__checkout-option myd-cart__checkout-option--active" data-type="delivery" data-content=".myd-cart__checkout-customer, .myd-cart__checkout-delivery">
						<div class="myd-cart__checkout-option-delivery" data-type="delivery"><?php esc_html_e( 'Delivery', 'myd-delivery-pro' ); ?></div>
					</div>

				<?php endif; ?>

				<?php if( get_option( 'myd-operation-mode-take-away' ) === 'take-away' ) : ?>

				<div class="myd-cart__checkout-option" data-type="take-away" data-content=".myd-cart__checkout-customer">
					<div class="myd-cart__checkout-option-order-in-store" data-type="take-away"><?php esc_html_e( 'Take Away', 'myd-delivery-pro' ); ?></div>
				</div>

				<?php endif; ?>

				<?php if( get_option( 'myd-operation-mode-in-store' ) === 'order-in-store' ) : ?>

					<div class="myd-cart__checkout-option" data-type="order-in-store" data-content=".myd-cart__checkout-customer, .myd-cart__checkout-in-store">
					<div class="myd-cart__checkout-option-order-in-store" data-type="order-in-store"><?php esc_html_e( 'Order in Store', 'myd-delivery-pro' ); ?></div>
				</div>

				<?php endif; ?>
			</div>
		</div>

	<div class="myd-cart__checkout-customer myd-cart__checkout-field-group--active">

		<div class="myd-cart__checkout-title"><?php esc_html_e( 'Customer Info', 'myd-delivery-pro' ); ?></div>

		<label class="myd-cart__checkout-label" for="input-customer-name"><?php esc_html_e( 'Name', 'myd-delivery-pro' ); ?></label>
		<input type="text" class="myd-cart__checkout-input" id="input-customer-name" name="input-customer-name" required>

		<label class="myd-cart__checkout-label" for="input-customer-phone"><?php esc_html_e( 'Phone', 'myd-delivery-pro' ); ?></label>
		<input
			type="text"
			class="myd-cart__checkout-input"
			id="input-customer-phone"
			name="input-customer-phone"
			required
			data-mask="<?php echo esc_attr( $mask_option ); ?>"
			inputmode="numeric"
		>
	</div>

	<div class="myd-cart__checkout-delivery myd-cart__checkout-field-group--active">
		<div class="myd-cart__checkout-title">
			<?php esc_html_e( 'Delivery Info', 'myd-delivery-pro' ); ?>
		</div>

		<?php if ( $shipping_type === 'per-distance' ) : ?>
			<label
				class="myd-cart__checkout-label"
				for="input-delivery-autocomplete-address"
				>
					<?php esc_html_e( 'Enter your address with number', 'myd-delivery-pro' ); ?>
			</label>
			<input
				type="text"
				class="myd-cart__checkout-input"
				id="input-delivery-autocomplete-address"
				name="input-delivery-autocomplete-address"
				autocomplete="none"
				value=""
			>

			<label
				class="myd-cart__checkout-label"
				for="input-delivery-address-number"
			>
				<?php esc_html_e( 'Address Number', 'myd-delivery-pro' ); ?>
			</label>
			<input
				type="number"
				class="myd-cart__checkout-input"
				id="input-delivery-address-number"
				name="input-delivery-address-number"
				required
			>

			<label
				class="myd-cart__checkout-label"
				for="input-delivery-comp"
				>
					<?php esc_html_e( 'Apartment, suite, etc.', 'myd-delivery-pro' ); ?>
			</label>
			<input
				type="text"
				class="myd-cart__checkout-input"
				id="input-delivery-comp"
				name="input-delivery-comp"
			>

			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-latitude"
				name="input-delivery-latitude"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-longitude"
				name="input-delivery-longitude"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-formated-address"
				name="input-delivery-formated-address"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-zipcode"
				name="input-delivery-zipcode"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-street-name"
				name="input-delivery-street-name"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-neighborhood"
				name="input-delivery-neighborhood"
			>
		<?php else : ?>
			<?php if ( get_option( 'myd-form-hide-zipcode' ) != 'yes' ) : ?>
				<label class="myd-cart__checkout-label" for="input-delivery-zipcode"><?php esc_html_e( 'Zipcode', 'myd-delivery-pro' ); ?></label>
				<input
					type="text"
					class="myd-cart__checkout-input"
					id="input-delivery-zipcode"
					name="input-delivery-zipcode"
					autocomplete="none"
					data-autocomplete="<?php echo \esc_attr( $enable_autocomplete_address ); ?>"
					inputmode="numeric"
					required
				>
			<?php endif; ?>

			<label class="myd-cart__checkout-label" for="input-delivery-street-name"><?php esc_html_e( 'Street Name', 'myd-delivery-pro' ); ?></label>
			<input type="text"class="myd-cart__checkout-input" id="input-delivery-street-name" name="input-delivery-street-name" required>

			<?php if( get_option( 'myd-form-hide-address-number' ) != 'yes' ) : ?>
				<label class="myd-cart__checkout-label" for="input-delivery-address-number"><?php esc_html_e( 'Address Number', 'myd-delivery-pro' ); ?></label>
				<input type="number" class="myd-cart__checkout-input" id="input-delivery-address-number" name="input-delivery-address-number" required>
			<?php endif; ?>

			<label class="myd-cart__checkout-label" for="input-delivery-comp"><?php esc_html_e( 'Apartment, suite, etc.', 'myd-delivery-pro' ); ?></label>
			<input type="text" class="myd-cart__checkout-input" id="input-delivery-comp" name="input-delivery-comp">

			<?php if ( get_option( 'fdm-business-country' ) == 'Brazil' && $shipping_type == 'per-cep-range' || $shipping_type == 'fixed-per-cep' ) : ?>
				<label class="myd-cart__checkout-label" for="input-delivery-neighborhood"><?php esc_html_e( 'Neighborhood', 'myd-delivery-pro' ); ?></label>
				<input type="text" class="myd-cart__checkout-input" id="input-delivery-neighborhood" name="input-delivery-neighborhood" required>
			<?php endif; ?>

			<?php if ( $shipping_type == 'fixed-per-neighborhood' || $shipping_type == 'per-neighborhood' ) : ?>
				<label class="myd-cart__checkout-label" for="input-delivery-neighborhood"><?php esc_html_e( 'Neighborhood', 'myd-delivery-pro' ); ?></label>
				<select class="" id="input-delivery-neighborhood" name="input-delivery-neighborhood" required>
					<option value=""><?php esc_html_e( 'Select', 'myd-delivery-pro' ); ?></option>
					<?php if ( isset( $shipping_options['options'] ) ) :
						foreach( $shipping_options['options'] as $k => $v ) : ?>
							<option value="<?php echo esc_attr( $v['from'] ); ?>"><?php echo esc_html( $v['from'] ); ?></option>
						<?php endforeach;
					endif; ?>
				</select>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<div class="myd-cart__checkout-in-store">
		<div class="myd-cart__checkout-title"><?php esc_html_e( 'Store Info', 'myd-delivery-pro' ); ?></div>
		<label class="myd-cart__checkout-label" for="input-in-store-table"><?php esc_html_e( 'Table number', 'myd-delivery-pro' ); ?></label>
		<input type="text" class="myd-cart__checkout-input" id="input-in-store-table" name="input-in-store-table">
	</div>

	<div class="myd-cart__checkout-coupon">
		<label class="myd-cart__checkout-label" for="input-checkout-coupon"><?php esc_html_e( 'Coupon', 'myd-delivery-pro' ); ?></label>
		<input type="text" class="myd-cart__checkout-input" id="input-coupon" name="input-checkout-coupon">
		<p><?php esc_html_e( 'If you have a discount coupon, add it here.', 'myd-delivery-pro' ); ?></p>

		<?php if ( ! empty( $coupons ) ) : ?>
			<div class="myd-cart__coupons-obj" id="myd-cart__coupons-obj">
				<?php echo json_encode( $coupons ); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
