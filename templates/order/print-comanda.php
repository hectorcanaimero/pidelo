<?php

use MydPro\Includes\Store_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php if ( $orders->have_posts() ) : ?>
	<?php while ( $orders->have_posts() ) : ?>
		<?php $orders->the_post(); ?>
		<?php $postid = get_the_ID(); ?>
		<?php $date = get_post_meta( $postid, 'order_date', true ); ?>
		<?php $date = gmdate( 'd/m - H:i', strtotime( $date ) ); ?>

		<?php
			$order_type = \get_post_meta( $postid, 'order_ship_method', true );

			$map_type = array(
				'delivery' => __( 'Delivery', 'myd-delivery-pro' ),
				'take-away' => __( 'Take Away', 'myd-delivery-pro' ),
				'order-in-store' => __( 'Order in Store', 'myd-delivery-pro' ),
			);

			$order_type = $map_type[ $order_type ] ?? '';
		?>

		<div class="order-print-comanda" id="print-comanda-<?php echo esc_attr( $postid ); ?>">
			<div style="border-top: 1px dashed #000; margin: 5px 0;"></div>

			<div class="comanda-header">
				<strong><?php esc_html_e( 'Orden', 'myd-delivery-pro' ); ?>: <?php echo esc_html( get_the_title( $postid ) ); ?></strong>
			</div>

			<div style="border-top: 1px dashed #000; margin: 5px 0 10px 0;"></div>

			<div>
				<strong><?php esc_html_e( 'Type', 'myd-delivery-pro' ); ?>:</strong> <?php echo esc_html( $order_type ); ?>
			</div>

			<?php $table = get_post_meta( $postid, 'order_table', true ); ?>
			<?php if ( ! empty( $table ) ) : ?>
				<div>
					<strong><?php esc_html_e( 'Table', 'myd-delivery-pro' ); ?>:</strong> <?php echo esc_html( $table ); ?>
				</div>
			<?php endif; ?>

			<div>
				<?php echo esc_html( $date ); ?>
			</div>

			<div>
				<?php echo esc_html( get_post_meta( $postid, 'order_customer_name', true ) ); ?>
			</div>

			<div>
				<?php echo esc_html( get_post_meta( $postid, 'customer_phone', true ) ); ?>
			</div>

			<div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

			<?php $items = get_post_meta( $postid, 'myd_order_items', true ); ?>
			<?php if ( ! empty( $items ) ) : ?>
				<?php $items = get_post_meta( $postid, 'myd_order_items', true ); ?>
			<?php if ( ! empty( $items ) ) : ?>
				<?php foreach ( $items as $value ) : ?>
					<div>
						<div><?php echo esc_html( $value['product_name'] ); ?></div>

						<?php if ( $value['product_extras'] !== '' ) : ?>
							<div style="white-space: pre;"><?php echo esc_html( $value['product_extras'] ); ?></div>
						<?php endif; ?>

						<?php if ( ! empty( $value['product_note'] ) ) : ?>
							<div><?php echo esc_html__( 'Note', 'myd-delivery-pro' ) . ' ' . esc_html( $value['product_note'] ); ?></div>
						<?php endif; ?>
					</div>
					<div style="margin: 10px 0;"></div>
				<?php endforeach; ?>
			<?php endif; ?>

			<?php endif; ?>

			<div style="border-top: 1px dashed #000; margin: 10px 0;"></div>
		</div>
	<?php endwhile; ?>
	<?php wp_reset_postdata(); ?>
<?php endif; ?>