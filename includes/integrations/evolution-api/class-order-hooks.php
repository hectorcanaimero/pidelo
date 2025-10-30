<?php
/**
 * Evolution API Order Hooks
 *
 * Hooks para detectar eventos de órdenes y disparar envío automático
 *
 * @package MydPro
 * @subpackage Integrations\Evolution_Api
 * @since 2.3.0
 */

namespace MydPro\Includes\Integrations\Evolution_Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Hooks Class
 */
class Order_Hooks {
	/**
	 * WhatsApp Service
	 *
	 * @var WhatsApp_Service
	 */
	private WhatsApp_Service $service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->service = new WhatsApp_Service();
		$this->init_hooks();
	}

	/**
	 * Inicializar hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Hook cuando cambia el meta de orden
		add_action( 'updated_post_meta', [ $this, 'on_order_status_change' ], 10, 4 );

		// Hook personalizado para cuando se completa el pago (si existe)
		add_action( 'myd_order_payment_completed', [ $this, 'on_payment_completed' ], 10, 1 );
	}

	/**
	 * Detectar cambio de status de orden
	 *
	 * @param int    $meta_id ID del meta
	 * @param int    $post_id ID del post
	 * @param string $meta_key Key del meta
	 * @param mixed  $meta_value Valor del meta
	 * @return void
	 */
	public function on_order_status_change( $meta_id, $post_id, $meta_key, $meta_value ): void {
		// Solo procesar si es una orden
		if ( get_post_type( $post_id ) !== 'mydelivery-orders' ) {
			return;
		}

		// Solo procesar cambios de status
		if ( $meta_key !== 'order_status' ) {
			return;
		}

		// Evitar procesamiento en AJAX no relacionado
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Permitir solo en ciertos AJAX actions
			$allowed_actions = [ 'update_orders', 'reload_orders' ];
			$current_action = $_POST['action'] ?? '';

			if ( ! in_array( $current_action, $allowed_actions, true ) ) {
				return;
			}
		}

		// Mapear status a evento
		$event = $this->map_status_to_event( $meta_value );

		if ( empty( $event ) ) {
			return;
		}

		// Evitar duplicados: verificar si ya se envió mensaje para este evento
		if ( $this->message_already_sent_for_event( $post_id, $event ) ) {
			return;
		}

		// Enviar notificación
		$result = $this->service->send_order_notification( $post_id, $event );

		// Log del resultado (opcional, ya lo hace el service)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! $result['success'] ) {
			error_log( sprintf(
				'[Evolution API] Failed to send notification for order #%d, event: %s, error: %s',
				$post_id,
				$event,
				$result['error'] ?? 'unknown'
			) );
		}
	}

	/**
	 * Cuando se completa el pago
	 *
	 * @param int $order_id ID de la orden
	 * @return void
	 */
	public function on_payment_completed( int $order_id ): void {
		$event = 'payment_completed';

		// Verificar si el evento debe enviar mensaje
		$auto_events = get_option( 'myd-evolution-auto-send-events', [] );

		if ( ! in_array( $event, $auto_events, true ) ) {
			return;
		}

		// Enviar notificación
		$this->service->send_order_notification( $order_id, $event );
	}

	/**
	 * Mapear status de orden a evento
	 *
	 * @param string $status Status de la orden
	 * @return string Evento
	 */
	private function map_status_to_event( string $status ): string {
		$status_map = [
			'new'         => 'order_new',
			'confirmed'   => 'order_confirmed',
			'in-process'  => 'order_in_process',
			'waiting'     => 'order_ready',          // Pedido en Espera del Delivery
			'in-delivery' => 'order_in_delivery',
			'done'        => 'order_done',           // Pedido Listo
			'finished'    => 'order_finished',       // Pedido Finalizado
		];

		return $status_map[ $status ] ?? '';
	}

	/**
	 * Verificar si ya se envió mensaje para este evento
	 *
	 * Evita duplicados si se actualiza el meta múltiples veces
	 *
	 * @param int    $order_id ID de la orden
	 * @param string $event Evento
	 * @return bool True si ya se envió
	 */
	private function message_already_sent_for_event( int $order_id, string $event ): bool {
		$logs = get_post_meta( $order_id, '_evolution_logs', true );

		if ( ! is_array( $logs ) || empty( $logs ) ) {
			return false;
		}

		// Buscar en logs si hay un mensaje exitoso para este evento
		foreach ( $logs as $log ) {
			if ( isset( $log['event'], $log['success'] ) &&
			     $log['event'] === $event &&
			     $log['success'] === true ) {

				// Verificar que no haya pasado mucho tiempo (evitar duplicados solo en los últimos 5 minutos)
				if ( isset( $log['timestamp'] ) ) {
					$timestamp = strtotime( $log['timestamp'] );
					$current_time = current_time( 'timestamp' );
					$diff_minutes = ( $current_time - $timestamp ) / 60;

					if ( $diff_minutes < 5 ) {
						return true;
					}
				}
			}
		}

		return false;
	}
}
