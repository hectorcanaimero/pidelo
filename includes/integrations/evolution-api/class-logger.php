<?php
/**
 * Evolution API Logger
 *
 * Sistema de logging para mensajes de WhatsApp
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
 * Logger Class
 */
class Logger {
	/**
	 * Log mensaje enviado
	 *
	 * @param int    $order_id ID de la orden
	 * @param string $event Evento que disparó el envío
	 * @param array  $result Resultado del envío
	 * @return void
	 */
	public function log_message( int $order_id, string $event, array $result ): void {
		$log_entry = [
			'order_id'   => $order_id,
			'event'      => $event,
			'timestamp'  => current_time( 'mysql' ),
			'success'    => $result['success'],
			'message_id' => $result['data']['key']['id'] ?? '',
			'status'     => $result['data']['status'] ?? '',
			'error'      => $result['error'] ?? '',
		];

		// Log en error_log de PHP para debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Evolution API] ' . wp_json_encode( $log_entry ) );
		}

		// Guardar en meta de orden
		$this->save_to_order_meta( $order_id, $log_entry );
	}

	/**
	 * Guardar log en meta de orden
	 *
	 * @param int   $order_id ID de la orden
	 * @param array $log_entry Entrada de log
	 * @return void
	 */
	private function save_to_order_meta( int $order_id, array $log_entry ): void {
		// Obtener logs existentes
		$existing_logs = get_post_meta( $order_id, '_evolution_logs', true );

		if ( ! is_array( $existing_logs ) ) {
			$existing_logs = [];
		}

		// Agregar nuevo log
		$existing_logs[] = $log_entry;

		// Limitar a últimos 50 logs para no sobrecargar
		if ( count( $existing_logs ) > 50 ) {
			$existing_logs = array_slice( $existing_logs, -50 );
		}

		// Guardar
		update_post_meta( $order_id, '_evolution_logs', $existing_logs );
	}

	/**
	 * Obtener logs de una orden
	 *
	 * @param int $order_id ID de la orden
	 * @return array Array de logs
	 */
	public function get_order_logs( int $order_id ): array {
		$logs = get_post_meta( $order_id, '_evolution_logs', true );

		return is_array( $logs ) ? $logs : [];
	}

	/**
	 * Limpiar logs antiguos de una orden
	 *
	 * @param int $order_id ID de la orden
	 * @return bool True si se limpiaron
	 */
	public function clear_order_logs( int $order_id ): bool {
		return delete_post_meta( $order_id, '_evolution_logs' );
	}
}
