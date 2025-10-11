<?php
/**
 * WhatsApp Service
 *
 * Servicio principal para envío de mensajes transaccionales
 *
 * @package MydPro
 * @subpackage Integrations\Evolution_Api
 * @since 2.3.0
 */

namespace MydPro\Includes\Integrations\Evolution_Api;

use MydPro\Includes\Custom_Message_Whatsapp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WhatsApp Service Class
 */
class WhatsApp_Service {
	/**
	 * Evolution Client
	 *
	 * @var Evolution_Client
	 */
	private Evolution_Client $client;

	/**
	 * Logger
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->client = new Evolution_Client();
		$this->logger = new Logger();
	}

	/**
	 * Enviar notificación de orden
	 *
	 * @param int    $order_id ID de la orden
	 * @param string $event Evento que dispara el envío
	 * @return array Resultado del envío
	 */
	public function send_order_notification( int $order_id, string $event = 'manual' ): array {
		// Verificar si Evolution está habilitado
		if ( ! $this->is_enabled() ) {
			return [
				'success' => false,
				'error'   => __( 'Evolution API is disabled', 'myd-delivery-pro' ),
			];
		}

		// Verificar si el cliente está configurado
		if ( ! $this->client->is_configured() ) {
			return [
				'success' => false,
				'error'   => __( 'Evolution API not configured properly', 'myd-delivery-pro' ),
			];
		}

		// Verificar si el evento debe enviar mensaje automático
		if ( $event !== 'manual' && ! $this->should_send_for_event( $event ) ) {
			return [
				'success' => false,
				'error'   => __( 'Event not configured for auto-send', 'myd-delivery-pro' ),
			];
		}

		// Obtener teléfono del cliente
		$phone = get_post_meta( $order_id, 'customer_phone', true );

		if ( empty( $phone ) ) {
			return [
				'success' => false,
				'error'   => __( 'No phone number found', 'myd-delivery-pro' ),
			];
		}

		// Generar mensaje
		$message = $this->generate_message( $order_id, $event );

		if ( empty( $message ) ) {
			return [
				'success' => false,
				'error'   => __( 'Failed to generate message', 'myd-delivery-pro' ),
			];
		}

		// Permitir modificar mensaje antes de enviar
		$message = apply_filters( 'myd_evolution_message_before_send', $message, $order_id, $event );

		// Enviar mensaje
		$result = $this->client->send_text( $phone, $message );

		// Log del resultado
		$this->logger->log_message( $order_id, $event, $result );

		// Si fue exitoso, actualizar meta de orden
		if ( $result['success'] ) {
			$this->update_order_message_meta( $order_id, $event, $result );
		}

		// Hook después de enviar
		do_action( 'myd_evolution_message_sent', $result, $order_id, $event );

		return $result;
	}

	/**
	 * Generar mensaje para la orden
	 *
	 * @param int    $order_id ID de la orden
	 * @param string $event Evento
	 * @return string Mensaje generado
	 */
	private function generate_message( int $order_id, string $event ): string {
		// Obtener template específico para el evento
		$template = $this->get_template_for_event( $event, $order_id );

		if ( empty( $template ) ) {
			// Si no hay template específico, usar el sistema actual
			$template = $this->get_default_message( $order_id );
		}

		// Procesar tokens
		$message = $this->process_tokens( $template, $order_id );

		return $message;
	}

	/**
	 * Obtener template para evento
	 *
	 * @param string $event Evento
	 * @param int    $order_id ID de la orden
	 * @return string Template
	 */
	private function get_template_for_event( string $event, int $order_id ): string {
		$template_map = [
			'order_new'         => 'myd-evolution-template-order-created',
			'order_confirmed'   => 'myd-evolution-template-order-confirmed',
			'order_in_process'  => 'myd-evolution-template-order-in-process',
			'order_in_delivery' => 'myd-evolution-template-order-in-delivery',
			'order_done'        => 'myd-evolution-template-order-completed',
			'manual'            => '', // Usar default
		];

		$template_option = $template_map[ $event ] ?? '';

		if ( empty( $template_option ) ) {
			return '';
		}

		$template = get_option( $template_option, '' );

		// Si está vacío, usar template por tipo de orden
		if ( empty( $template ) ) {
			$order_type = get_post_meta( $order_id, 'order_ship_method', true );
			$template = $this->get_template_by_order_type( $order_type );
		}

		return $template;
	}

	/**
	 * Obtener template por tipo de orden (reutiliza sistema actual)
	 *
	 * @param string $order_type Tipo de orden
	 * @return string Template
	 */
	private function get_template_by_order_type( string $order_type ): string {
		$template_map = [
			'delivery'        => 'myd-template-order-custom-message-delivery',
			'take-away'       => 'myd-template-order-custom-message-take-away',
			'order-in-store'  => 'myd-template-order-custom-message-digital-menu',
		];

		$option = $template_map[ $order_type ] ?? 'myd-template-order-custom-message-delivery';

		return get_option( $option, '' );
	}

	/**
	 * Obtener mensaje default usando Custom_Message_Whatsapp
	 *
	 * @param int $order_id ID de la orden
	 * @return string Mensaje
	 */
	private function get_default_message( int $order_id ): string {
		$message_generator = new Custom_Message_Whatsapp( $order_id );
		$wa_link = $message_generator->get_whatsapp_redirect_link();

		// Extraer el mensaje del link wa.me
		$parsed_url = parse_url( $wa_link );

		if ( isset( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $params );
			return urldecode( $params['text'] ?? '' );
		}

		return '';
	}

	/**
	 * Procesar tokens en el template
	 *
	 * @param string $template Template con tokens
	 * @param int    $order_id ID de la orden
	 * @return string Template procesado
	 */
	private function process_tokens( string $template, int $order_id ): string {
		// Reutilizar Custom_Message_Whatsapp para obtener los datos
		$message_generator = new Custom_Message_Whatsapp( $order_id );

		// Ya que Custom_Message_Whatsapp tiene el método convert_custom_message
		// pero es protected, vamos a replicar la lógica de tokens aquí

		$order_data = $this->get_order_data( $order_id );

		$tokens = [
			'{order-number}'                => $order_id,
			'{order-date-time}'             => $order_data['date'],
			'{order-coupon-code}'           => $order_data['coupon_code'],
			'{order-total}'                 => \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' ) . ' ' . $order_data['total'],
			'{order-subtotal}'              => \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' ) . ' ' . $order_data['subtotal'],
			'{order-table}'                 => $order_data['table'],
			'{order-track-page}'            => get_permalink( get_option( 'fdm-page-order-track' ) ) . '?hash=' . base64_encode( $order_id ),
			'{payment-type}'                => $order_data['payment_type'],
			'{payment-status}'              => $order_data['payment_status'],
			'{payment-method}'              => $order_data['payment_method'],
			'{payment-change}'              => \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' ) . ' ' . $order_data['payment_change'],
			'{customer-name}'               => $order_data['customer_name'],
			'{customer-phone}'              => $order_data['customer_phone'],
			'{customer-address}'            => $order_data['address_street'],
			'{customer-address-number}'     => $order_data['address_number'],
			'{customer-address-complement}' => $order_data['address_complement'],
			'{customer-address-neighborhood}' => $order_data['address_neighborhood'],
			'{customer-address-zipcode}'    => $order_data['address_zipcode'],
			'{shipping-price}'              => \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' ) . ' ' . $order_data['shipping_price'],
			'{business-name}'               => get_option( 'fdm-business-name', '' ),
			'{order-status}'                => $this->get_order_status_label( $order_data['status'] ),
		];

		// Reemplazar tokens
		foreach ( $tokens as $token => $value ) {
			$template = str_replace( $token, $value, $template );
		}

		return $template;
	}

	/**
	 * Obtener datos de la orden
	 *
	 * @param int $order_id ID de la orden
	 * @return array Datos de la orden
	 */
	private function get_order_data( int $order_id ): array {
		return [
			'date'                  => get_post_meta( $order_id, 'order_date', true ),
			'status'                => get_post_meta( $order_id, 'order_status', true ),
			'coupon_code'           => get_post_meta( $order_id, 'order_coupon', true ),
			'total'                 => get_post_meta( $order_id, 'order_total', true ),
			'subtotal'              => get_post_meta( $order_id, 'order_subtotal', true ),
			'payment_type'          => get_post_meta( $order_id, 'order_payment_type', true ),
			'payment_status'        => get_post_meta( $order_id, 'order_payment_status', true ),
			'payment_method'        => get_post_meta( $order_id, 'order_payment_method', true ),
			'payment_change'        => get_post_meta( $order_id, 'order_change', true ),
			'customer_name'         => get_post_meta( $order_id, 'order_customer_name', true ),
			'customer_phone'        => get_post_meta( $order_id, 'customer_phone', true ),
			'address_street'        => get_post_meta( $order_id, 'order_address', true ),
			'address_number'        => get_post_meta( $order_id, 'order_address_number', true ),
			'address_complement'    => get_post_meta( $order_id, 'order_address_comp', true ),
			'address_neighborhood'  => get_post_meta( $order_id, 'order_neighborhood', true ),
			'address_zipcode'       => get_post_meta( $order_id, 'order_zipcode', true ),
			'shipping_price'        => get_post_meta( $order_id, 'order_delivery_price', true ),
			'table'                 => get_post_meta( $order_id, 'order_table', true ),
		];
	}

	/**
	 * Obtener label de status de orden
	 *
	 * @param string $status Status de la orden
	 * @return string Label traducido
	 */
	private function get_order_status_label( string $status ): string {
		$status_map = [
			'new'         => __( 'Nuevo', 'myd-delivery-pro' ),
			'confirmed'   => __( 'Confirmado', 'myd-delivery-pro' ),
			'in-process'  => __( 'En Preparación', 'myd-delivery-pro' ),
			'in-delivery' => __( 'En Camino', 'myd-delivery-pro' ),
			'done'        => __( 'Completado', 'myd-delivery-pro' ),
			'cancelled'   => __( 'Cancelado', 'myd-delivery-pro' ),
		];

		return $status_map[ $status ] ?? $status;
	}

	/**
	 * Actualizar meta de orden con info de mensaje enviado
	 *
	 * @param int    $order_id ID de la orden
	 * @param string $event Evento
	 * @param array  $result Resultado del envío
	 * @return void
	 */
	private function update_order_message_meta( int $order_id, string $event, array $result ): void {
		// Actualizar timestamp del último envío
		update_post_meta( $order_id, '_last_whatsapp_sent', current_time( 'mysql' ) );

		// Actualizar info del último mensaje
		$message_info = [
			'event'      => $event,
			'timestamp'  => current_time( 'mysql' ),
			'message_id' => $result['data']['key']['id'] ?? '',
		];

		update_post_meta( $order_id, '_last_whatsapp_message_info', $message_info );
	}

	/**
	 * Verificar si Evolution está habilitado
	 *
	 * @return bool True si está habilitado
	 */
	private function is_enabled(): bool {
		return get_option( 'myd-evolution-api-enabled' ) === 'yes';
	}

	/**
	 * Verificar si un evento debe disparar envío automático
	 *
	 * @param string $event Evento
	 * @return bool True si debe enviar
	 */
	private function should_send_for_event( string $event ): bool {
		$auto_events = get_option( 'myd-evolution-auto-send-events', [] );

		if ( ! is_array( $auto_events ) ) {
			return false;
		}

		return in_array( $event, $auto_events, true );
	}
}
