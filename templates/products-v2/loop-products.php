<?php
/**
 * Product Loop V2 - Modern product card with shadcn design
 *
 * @package MydPro
 * @since 2.4.0
 */

use MydPro\Includes\Store_Data;
use MydPro\Includes\Myd_Store_Formatting;
use MydPro\Includes\Currency_Converter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$postid = get_the_ID();
$image_id = get_post_meta( $postid, 'product_image', true );
$image_url = wp_get_attachment_image_url( $image_id, 'large' );
$product_price = get_post_meta( $postid, 'product_price', true );
$product_price = empty( $product_price ) ? 0 : $product_price;
$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );
$is_available = get_post_meta( $postid, 'product_available', true );
$price_label = get_post_meta( $postid, 'product_price_label', true );
$product_description = get_post_meta( $postid, 'product_description', true );

?>
<article
	class="myd-product-card-v2 <?php echo $is_available === 'not-available' ? 'myd-product-card-v2--unavailable' : ''; ?>"
	itemscope
	itemtype="http://schema.org/Product"
	data-id="<?php echo esc_attr( $postid ); ?>"
>
	<!-- Product Image -->
	<div class="myd-product-card-v2__image-wrapper" data-image="<?php echo esc_attr( $image_url ); ?>">
		<?php if ( $image_id ) : ?>
			<?php
			echo wp_get_attachment_image(
				$image_id,
				'medium',
				false,
				array(
					'class'   => 'myd-product-card-v2__image',
					'alt'     => get_the_title(),
					'loading' => 'lazy',
				)
			);
			?>
		<?php else : ?>
			<div class="myd-product-card-v2__image-placeholder">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect width="24" height="24" rx="4" fill="hsl(var(--muted))"/>
					<path d="M8 8L16 16M16 8L8 16" stroke="hsl(var(--muted-foreground))" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</div>
		<?php endif; ?>

		<!-- Availability Badge -->
		<?php if ( $is_available === 'not-available' ) : ?>
			<div class="myd-product-card-v2__badge myd-product-card-v2__badge--unavailable">
				<?php esc_html_e( 'Not Available', 'myd-delivery-pro' ); ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- Product Content -->
	<div class="myd-product-card-v2__content">
		<!-- Title -->
		<h3 class="myd-product-card-v2__title" itemprop="name">
			<?php echo esc_html( get_the_title() ); ?>
		</h3>

		<!-- Description -->
		<?php if ( ! empty( $product_description ) ) : ?>
			<p class="myd-product-card-v2__description" itemprop="description">
				<?php echo esc_html( $product_description ); ?>
			</p>
		<?php endif; ?>

		<!-- Spacer -->
		<div class="myd-product-card-v2__spacer"></div>

		<!-- Footer with Price and Button -->
		<div class="myd-product-card-v2__footer">
			<!-- Price -->
			<div class="myd-product-card-v2__price-wrapper">
				<?php if ( $price_label !== 'hide' ) : ?>
					<div class="myd-product-card-v2__price" itemprop="price">
						<?php if ( $price_label === 'consult' ) : ?>
							<span class="myd-product-card-v2__price-text">
								<?php esc_html_e( 'By Consult', 'myd-delivery-pro' ); ?>
							</span>
						<?php elseif ( $price_label === 'from' ) : ?>
							<span class="myd-product-card-v2__price-label">
								<?php esc_html_e( 'From', 'myd-delivery-pro' ); ?>
							</span>
							<span class="myd-product-card-v2__price-amount">
								<?php echo esc_html( $currency_simbol . ' ' . Myd_Store_Formatting::format_price( $product_price ) ); ?>
							</span>
						<?php else : ?>
							<span class="myd-product-card-v2__price-amount">
								<?php echo esc_html( $currency_simbol . ' ' . Myd_Store_Formatting::format_price( $product_price ) ); ?>
							</span>
						<?php endif; ?>
					</div>

					<!-- Currency Conversion -->
					<?php if ( ( $price_label === 'show' || $price_label === '' || $price_label === 'from' ) && $product_price > 0 ) : ?>
						<div class="myd-product-card-v2__price-conversion">
							<?php echo Currency_Converter::get_conversion_display( $product_price, false ); ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<!-- Add to Cart Button -->
			<?php if ( $is_available !== 'not-available' ) : ?>
				<button
					type="button"
					class="myd-product-card-v2__add-button"
					aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to cart', 'myd-delivery-pro' ), get_the_title() ) ); ?>"
				>
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			<?php endif; ?>
		</div>
	</div>
</article>
