<?php
/**
 * Main Template V2 - Modern product display with sticky navigation
 *
 * @package MydPro
 * @since 2.4.0
 */

use MydPro\Includes\Fdm_svg;
use MydPro\Includes\Store_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_category = isset( $args['product_category'] ) && $args['product_category'] !== 'all' ? array( $args['product_category'] ) : array();
$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );

?>
<?php if ( Store_Data::$template_dependencies_loaded === false ) : ?>
	<section class="my-delivery-wrap my-delivery-wrap--v2">
		<!-- Image Preview Popup -->
		<div class="fdm-lightbox-image myd-hide-element" id="myd-image-preview-popup">
			<div class="fdm-lightbox-image-close" id="myd-image-preview-popup-close">
				<?php echo Fdm_svg::svg_close(); ?>
			</div>
			<div class="fdm-lightbox-image-link" id="myd-image-preview-wrapper">
				<img id="myd-image-preview-image" src="" alt="<?php esc_attr_e( 'Product preview', 'myd-delivery-pro' ); ?>">
			</div>
		</div>

		<!-- Notification Popup -->
		<div class="myd-popup-notification" id="myd-popup-notification">
			<div class="myd-popup-notification__message" id="myd-popup-notification__message"></div>
		</div>

		<!-- Search Bar (Optional - can be shown/hidden) -->
		<div class="myd-content-v2">
			<?php if ( ! isset( $args['filter_type'] ) || isset( $args['filter_type'] ) && $args['filter_type'] !== 'hide_search' ) : ?>
				<div class="myd-content-filter-v2">
					<div class="myd-content-filter-v2__search">
						<div class="myd-content-filter-v2__search-icon" id="myd-content-filter__search-icon-v2">
							<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 511.999 511.999">
								<path d="M225.773,0.616C101.283,0.616,0,101.622,0,225.773s101.284,225.157,225.773,225.157s225.774-101.006,225.774-225.157S350.263,0.616,225.773,0.616z M225.773,413.917c-104.084,0-188.761-84.406-188.761-188.145c0-103.745,84.677-188.145,188.761-188.145s188.761,84.4,188.761,188.145C414.535,329.511,329.858,413.917,225.773,413.917z"/>
								<path d="M506.547,479.756L385.024,358.85c-7.248-7.205-18.963-7.174-26.174,0.068c-7.205,7.248-7.174,18.962,0.068,26.174l121.523,120.906c3.615,3.59,8.328,5.385,13.053,5.385c4.756,0,9.506-1.82,13.121-5.453C513.82,498.681,513.789,486.967,506.547,479.756z"/>
							</svg>
						</div>
						<input
							type="text"
							class="myd-search-products myd-search-products-v2"
							name="myd-search-products-v2"
							id="myd-search-products-v2"
							placeholder="<?php esc_attr_e( 'Search products...', 'myd-delivery-pro' ); ?>"
							aria-label="<?php esc_attr_e( 'Search products', 'myd-delivery-pro' ); ?>"
						>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<!-- Sticky Category Navigation -->
<?php echo $this->render_sticky_navigation(); ?>

<!-- Products Section -->
<section class="myd-products__wrapper-v2">
	<?php echo $this->fdm_loop_products_per_categorie_v2( $product_category ); ?>
</section>

<?php if ( Store_Data::$template_dependencies_loaded === false ) : ?>
	<!-- Floating Cart Button -->
	<section class="myd-float myd-float--v2">
		<div class="myd-float__button-subtotal">
			<span
				id="myd-float__price"
				data-currency="<?php echo esc_attr( $currency_simbol ); ?>">
				<?php echo esc_html( $currency_simbol ); ?>
			</span>
			<span id="myd_float__separator">&bull;</span>
			<span id="myd-float__qty">0</span>
			<span id="myd-float__qty-text">
				<?php esc_html_e( 'items', 'myd-delivery-pro' ); ?>
			</span>
		</div>

		<div class="myd-float__title">
			<?php esc_html_e( 'View Bag', 'myd-delivery-pro' ); ?>
			<svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 2.75C10.7574 2.75 9.75006 3.75736 9.75006 5V5.25447C10.1676 5.24999 10.6183 5.25 11.1053 5.25H12.8948C13.3819 5.25 13.8326 5.24999 14.2501 5.25447V5C14.2501 3.75736 13.2427 2.75 12.0001 2.75ZM15.7501 5.30694V5C15.7501 2.92893 14.0711 1.25 12.0001 1.25C9.929 1.25 8.25006 2.92893 8.25006 5V5.30694C8.11506 5.31679 7.98479 5.32834 7.85904 5.34189C6.98068 5.43657 6.24614 5.63489 5.59385 6.08197C5.3695 6.23574 5.15877 6.40849 4.96399 6.59833C4.39766 7.15027 4.05914 7.83166 3.79405 8.67439C3.53667 9.49258 3.32867 10.5327 3.06729 11.8396L3.04822 11.935C2.67158 13.8181 2.37478 15.302 2.28954 16.484C2.20244 17.6916 2.32415 18.7075 2.89619 19.588C3.08705 19.8817 3.30982 20.1534 3.56044 20.3982C4.31157 21.1318 5.28392 21.4504 6.48518 21.6018C7.66087 21.75 9.17418 21.75 11.0946 21.75H12.9055C14.826 21.75 16.3393 21.75 17.5149 21.6018C18.7162 21.4504 19.6886 21.1318 20.4397 20.3982C20.6903 20.1534 20.9131 19.8817 21.1039 19.588C21.676 18.7075 21.7977 17.6916 21.7106 16.484C21.6254 15.3021 21.3286 13.8182 20.9519 11.9351L20.9328 11.8396C20.6715 10.5327 20.4635 9.49259 20.2061 8.67439C19.941 7.83166 19.6025 7.15027 19.0361 6.59833C18.8414 6.40849 18.6306 6.23574 18.4063 6.08197C17.754 5.63489 17.0194 5.43657 16.1411 5.34189C16.0153 5.32834 15.8851 5.31679 15.7501 5.30694ZM8.01978 6.83326C7.27307 6.91374 6.81176 7.06572 6.44188 7.31924C6.28838 7.42445 6.1442 7.54265 6.01093 7.67254C5.68979 7.98552 5.45028 8.40807 5.22492 9.12449C4.99463 9.85661 4.80147 10.8172 4.52967 12.1762C4.14013 14.1239 3.8633 15.5153 3.78565 16.5919C3.70906 17.6538 3.83838 18.2849 4.15401 18.7707C4.2846 18.9717 4.43702 19.1576 4.60849 19.3251C5.02293 19.7298 5.61646 19.9804 6.67278 20.1136C7.74368 20.2486 9.1623 20.25 11.1486 20.25H12.8515C14.8378 20.25 16.2564 20.2486 17.3273 20.1136C18.3837 19.9804 18.9772 19.7298 19.3916 19.3251C19.5631 19.1576 19.7155 18.9717 19.8461 18.7707C20.1617 18.2849 20.2911 17.6538 20.2145 16.5919C20.1368 15.5153 19.86 14.1239 19.4705 12.1762C19.1987 10.8173 19.0055 9.85661 18.7752 9.12449C18.5498 8.40807 18.3103 7.98552 17.9892 7.67254C17.8559 7.54265 17.7118 7.42445 17.5582 7.31924C17.1884 7.06572 16.7271 6.91374 15.9803 6.83326C15.2173 6.75101 14.2374 6.75 12.8515 6.75H11.1486C9.76271 6.75 8.78285 6.75101 8.01978 6.83326ZM8.92103 14.2929C9.31156 14.1548 9.74006 14.3595 9.87809 14.7501C10.1873 15.625 11.0218 16.25 12.0003 16.25C12.9787 16.25 13.8132 15.625 14.1224 14.7501C14.2605 14.3595 14.6889 14.1548 15.0795 14.2929C15.47 14.4309 15.6747 14.8594 15.5367 15.2499C15.0222 16.7054 13.6342 17.75 12.0003 17.75C10.3663 17.75 8.97827 16.7054 8.46383 15.2499C8.3258 14.8594 8.53049 14.4309 8.92103 14.2929Z" fill="#fff"/>
			</svg>
		</div>
	</section>

	<!-- Checkout Sidebar (reuse existing) -->
	<section class="myd-checkout" id="myd-checkout">
		<?php include MYD_PLUGIN_PATH . 'templates/cart/index.php'; ?>
	</section>

	<div class="myd-overlay" id="myd-overlay"></div>
	<?php Store_Data::$template_dependencies_loaded = true; ?>
<?php endif; ?>
