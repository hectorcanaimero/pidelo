<?php
/**
 * Sticky Category Navigation Menu
 *
 * @package MydPro
 * @since 2.4.0
 *
 * @var array  $categories   Categories data with name, slug, and count
 * @var string $position     Position: 'top' or 'bottom'
 * @var bool   $show_count   Whether to show product count
 * @var int    $offset       Pixels from top to activate sticky menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<nav
	class="myd-sticky-nav-v2 myd-sticky-nav-v2--<?php echo esc_attr( $position ); ?>"
	data-offset="<?php echo esc_attr( $offset ); ?>"
	data-position="<?php echo esc_attr( $position ); ?>"
	aria-label="<?php esc_attr_e( 'Category Navigation', 'myd-delivery-pro' ); ?>"
	style="display: none;"
>
	<div class="myd-sticky-nav-v2__container">
		<div class="myd-sticky-nav-v2__scroll">
			<?php foreach ( $categories as $category ) : ?>
				<button
					type="button"
					class="myd-sticky-nav-v2__item"
					data-category="<?php echo esc_attr( $category['slug'] ); ?>"
					data-anchor="fdm-<?php echo esc_attr( $category['slug'] ); ?>"
					aria-label="<?php echo esc_attr( sprintf( __( 'View %s category', 'myd-delivery-pro' ), $category['name'] ) ); ?>"
				>
					<span class="myd-sticky-nav-v2__item-text">
						<?php echo esc_html( $category['name'] ); ?>
					</span>
					<?php if ( $show_count ) : ?>
						<span class="myd-sticky-nav-v2__item-count" aria-label="<?php echo esc_attr( sprintf( __( '%d products', 'myd-delivery-pro' ), $category['count'] ) ); ?>">
							<?php echo esc_html( $category['count'] ); ?>
						</span>
					<?php endif; ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>
</nav>
