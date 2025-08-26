<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders custom columns
 *
 * @param [type] $columns
 * @return void
 */
function myd_order_custom_columns ( $columns ) {
	// Crear nuevo array para mantener orden correcto
	$new_columns = array();
	
	// Agregar columnas en orden específico
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		
		// Después del título, agregar la columna de Estado de Pago
		if ( $key === 'title' ) {
			$new_columns["payment_status"] = '' . __( 'Payment Status', 'myd-delivery-pro' ) . '';
		}
	}
	
	// Agregar las demás columnas
	$new_columns["status"] = "Status";
	$new_columns["customer"] = '' . __( 'Customer', 'myd-delivery-pro' ) . '';
	$new_columns["phone"] = '' . __( 'Phone', 'myd-delivery-pro' ) . '';
	$new_columns["order_date"] = '' . __( 'Order Date', 'myd-delivery-pro' ) . '';

	unset($new_columns['date']);
	return $new_columns;
}

add_filter( 'manage_edit-mydelivery-orders_columns', 'MydPro\Includes\myd_order_custom_columns' );

/**
 * Orders custom columns content
 *
 * @param string $colname, int $cptid
 * @return void
 * @since 1.9.5
 */
function myd_order_custom_column_content ( $colname, $cptid ) {
	if ( $colname == 'payment_status') {
		$payment_status = get_post_meta( $cptid, 'order_payment_status', true );
		$payment_status_mapped = array(
			'waiting' => __( 'Waiting', 'myd-delivery-pro' ),
			'paid' => __( 'Paid', 'myd-delivery-pro' ),
			'failed' => __( 'Failed', 'myd-delivery-pro' ),
		);
		
		$status_display = $payment_status_mapped[ $payment_status ] ?? $payment_status;
		
		// Agregar clases CSS para colores
		$status_class = '';
		switch( $payment_status ) {
			case 'paid':
				$status_class = 'style="color: #46b450; font-weight: bold;"';
				break;
			case 'waiting':
				$status_class = 'style="color: #ffb900; font-weight: bold;"';
				break;
			case 'failed':
				$status_class = 'style="color: #dc3232; font-weight: bold;"';
				break;
		}
		
		echo '<span ' . $status_class . '>' . esc_html( $status_display ) . '</span>';
	}

	if ( $colname == 'status') {
		echo get_post_meta( $cptid, 'order_status', true );
	}

	if ( $colname == 'customer') {
		echo get_post_meta( $cptid, 'order_customer_name', true );
	}

	if ( $colname == 'phone') {
		echo get_post_meta( $cptid, 'customer_phone', true );
	}

	if ( $colname == 'order_date') {
		echo get_post_meta( $cptid, 'order_date', true );
	}
}

add_action( 'manage_mydelivery-orders_posts_custom_column', 'MydPro\Includes\myd_order_custom_column_content', 10, 2 );

/**
 * Products custom columns
 *
 * @param array $columns
 * @return void
 * @since 1.9.5
 */
function myd_products_custom_columns ( $columns ) {
	$columns["price"] = '' . __( 'Price', 'myd-delivery-pro' ) . '';
	$columns["product_categorie"] = '' . __( 'Category', 'myd-delivery-pro' ) . '';
	$columns["product_description"] = '' . __( 'Product Description', 'myd-delivery-pro' ) . '';

	unset( $columns['date'] );
	return $columns;
}

add_filter( 'manage_edit-mydelivery-produtos_columns', 'MydPro\Includes\myd_products_custom_columns' );

/**
 * Products custom colmns content
 *
 * @param string $colname
 * @param int $cptid
 * @return void
 */
function myd_products_custom_column_content ( $colname, $cptid ) {
	if ( $colname == 'price') {
		echo get_post_meta( $cptid, 'product_price', true );
	}

	if ( $colname == 'product_categorie') {
		echo get_post_meta( $cptid, 'product_type', true );
	}

	if ( $colname == 'product_description') {
		echo get_post_meta( $cptid, 'product_description', true );
	}
}

add_action( 'manage_mydelivery-produtos_posts_custom_column', 'MydPro\Includes\myd_products_custom_column_content', 10, 2 );
