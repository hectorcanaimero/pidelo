<?php

namespace MydPro\Includes\Admin;

use MydPro\Includes\Admin\Admin_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to register plugin admin settings
 *
 * @since 1.9.6
 */
class Settings extends Admin_Settings {
	/**
	 * Config group
	 *
	 * @since 1.9.6
	 */
	private const CONFIG_GROUP = 'fmd-settings-group';

	/**
	 * License group
	 *
	 * @since 1.9.6
	 */
	private const LICENSE_GROUP = 'fmd-license-group';

	/**
	 * Construct the class
	 *
	 * @since 1.9.6
	 */
	public function __construct() {
		$this->settings = [
			[
				'name' => 'myd-currency',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
			[
				'name' => 'fdm-payment-type',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-business-name',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-business-country',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-mask-phone',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-estimate-time-delivery',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-list-menu-categories',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-payment-in-cash',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-payment-receipt-required',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => 'no',
				]
			],
			[
				'name' => 'fdm-principal-color',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => '#ea1d2b',
				]
			],
			[
				'name' => 'myd-price-color',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-number-decimal',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'intval',
					'default' => '2'
				]
			],
			[
				'name' => 'fdm-decimal-separator',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => ','
				]
			],
			[
				'name' => 'fdm-page-order-track',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-print-size',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-print-font-size',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-operation-mode-delivery',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => 'delivery',
				]
			],
			[
				'name' => 'myd-operation-mode-take-away',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-operation-mode-in-store',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-products-list-columns',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => 'myd-product-list--2columns'
				]
			],
			[
				'name' => 'myd-products-list-boxshadow',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => 'myd-product-item--boxshadow'
				]
			],
			[
				'name' => 'myd-form-hide-zipcode',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-form-hide-address-number',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-option-minimum-price',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-option-redirect-whatsapp',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-delivery-time',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => ['initial'] //TODO: sanitize custom array
				]
			],
			[
				'name' => 'myd-delivery-mode',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-delivery-mode-options',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => ['initial'] //TODO: sanitize custom array
				]
			],
			[
				'name' => 'myd-business-mail',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-business-whatsapp',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
			[
				'name' => 'myd-currency-conversion-enabled',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => '0',
				],
			],
			[
				'name' => 'fdm-license',
				'option_group' => self::LICENSE_GROUP,
				'args' => []
			],
			[
				'name' => 'myd-delivery-force-open-close-store',
				'option_group' => self::CONFIG_GROUP,
				'args' => [],
			],
			[
				'name' => 'myd-shipping-distance-google-api-key',
				'option_group' => self::CONFIG_GROUP,
				'args' => [],
			],
			[
				'name' => 'myd-shipping-distance-address-latitude',
				'option_group' => self::CONFIG_GROUP,
				'args' => [],
			],
			[
				'name' => 'myd-shipping-distance-address-longitude',
				'option_group' => self::CONFIG_GROUP,
				'args' => [],
			],
			[
				'name' => 'myd-shipping-distance-formated-address',
				'option_group' => self::CONFIG_GROUP,
				'args' => [],
			],
			[
				'name' => 'myd-template-order-custom-message-list-products',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '{product-qty} {product-name}' . PHP_EOL .
					'{product-extras}' . PHP_EOL .
					esc_html__( 'Note', 'myd-delivery-pro' ) . ': {product-note}',
				],
			],
			[
				'name' => 'myd-template-order-custom-message-delivery',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '===== ' . esc_html__( 'Order', 'myd-delivery-pro' ) . ' {order-number} ====='
					. PHP_EOL . PHP_EOL .
					'{order-products}' . PHP_EOL .
					esc_html__( 'Delivery', 'myd-delivery-pro' ) . ': {shipping-price}' . PHP_EOL .
					esc_html__( 'Order Total', 'myd-delivery-pro' ) . ': {order-total}' . PHP_EOL .
					esc_html__( 'Payment Method', 'myd-delivery-pro' ) . ': {payment-method}' . PHP_EOL .
					esc_html__( 'Change', 'myd-delivery-pro' ) . ': {payment-change}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Customer', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					'{customer-name}' . PHP_EOL .
					'{customer-phone}' . PHP_EOL .
					'{customer-address}, {customer-address-number}' . PHP_EOL .
					'{customer-address-complement}' . PHP_EOL .
					'{customer-address-neighborhood}' . PHP_EOL .
					'{customer-address-zipcode}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Track Order', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					'{order-track-page}',
				],
			],
			[
				'name' => 'myd-template-order-custom-message-take-away',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '===== ' . esc_html__( 'Order', 'myd-delivery-pro' ) . ' {order-number} ====='
					. PHP_EOL . PHP_EOL .
					'{order-products}' . PHP_EOL .
					esc_html__( 'Order Total', 'myd-delivery-pro' ) . ': {order-total}' . PHP_EOL .
					esc_html__( 'Payment Method', 'myd-delivery-pro' ) . ': {payment-method}' . PHP_EOL .
					esc_html__( 'Change', 'myd-delivery-pro' ) . ': {payment-change}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Customer', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					'{customer-name}' . PHP_EOL .
					'{customer-phone}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Track Order', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					'{order-track-page}',
				],
			],
			[
				'name' => 'myd-template-order-custom-message-digital-menu',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '===== ' . esc_html__( 'Order', 'myd-delivery-pro' ) . ' {order-number} ====='
					. PHP_EOL . PHP_EOL .
					'{order-products}' . PHP_EOL .
					esc_html__( 'Order Total', 'myd-delivery-pro' ) . ': {order-total}' . PHP_EOL .
					esc_html__( 'Payment Method', 'myd-delivery-pro' ) . ': {payment-method}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Customer', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					esc_html__( 'Table', 'myd-delivery-pro' ) . ': {order-table}' . PHP_EOL .
					'{customer-name}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Track Order', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					'{order-track-page}',
				],
			],
			[
				'name' => 'myd-notification-audio-enabled',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => 'yes',
				],
			],
			[
				'name' => 'myd-notification-audio-volume',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => '0.8',
				],
			],
			[
				'name' => 'myd-notification-repeat-count',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'intval',
					'default' => '3',
				],
			],
			// Evolution API Settings
			[
				'name' => 'myd-evolution-api-enabled',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => 'no',
				],
			],
			[
				'name' => 'myd-evolution-phone-country-code',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => '55', // Brasil por defecto
				],
			],
			[
				'name' => 'myd-evolution-auto-send-events',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => [],
				],
			],
			// Templates Evolution API por evento
			[
				'name' => 'myd-evolution-template-order-created',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '¡Hola {customer-name}! ' . PHP_EOL .
					'Tu pedido #{order-number} ha sido recibido correctamente.' . PHP_EOL . PHP_EOL .
					'Total: {order-total}' . PHP_EOL . PHP_EOL .
					'Seguimiento: {order-track-page}',
				],
			],
			[
				'name' => 'myd-evolution-template-order-confirmed',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '¡Hola {customer-name}! ' . PHP_EOL .
					'Tu pedido #{order-number} ha sido confirmado.' . PHP_EOL . PHP_EOL .
					'Estamos preparando todo para ti.' . PHP_EOL . PHP_EOL .
					'Seguimiento: {order-track-page}',
				],
			],
			[
				'name' => 'myd-evolution-template-order-in-process',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '¡Hola {customer-name}! ' . PHP_EOL .
					'Tu pedido #{order-number} está siendo preparado.' . PHP_EOL . PHP_EOL .
					'Pronto estará listo.' . PHP_EOL . PHP_EOL .
					'Seguimiento: {order-track-page}',
				],
			],
			[
				'name' => 'myd-evolution-template-order-in-delivery',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '¡Hola {customer-name}! ' . PHP_EOL .
					'Tu pedido #{order-number} está en camino.' . PHP_EOL . PHP_EOL .
					'Llegará pronto a tu dirección.' . PHP_EOL . PHP_EOL .
					'Seguimiento: {order-track-page}',
				],
			],
			[
				'name' => 'myd-evolution-template-order-completed',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '¡Gracias {customer-name}! ' . PHP_EOL .
					'Tu pedido #{order-number} ha sido completado.' . PHP_EOL . PHP_EOL .
					'Esperamos que lo disfrutes. ¡Vuelve pronto!',
				],
			],
		];
	}
}
