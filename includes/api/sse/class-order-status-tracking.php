<?php

namespace MydPro\Includes\Api\Sse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API endpoint to track order status (SSE)
 */
class Order_Status_Tracking {
	/**
	 * Construct the class.
	 */
	public function __construct () {
		add_action( 'rest_api_init', [ $this, 'register_order_routes' ] );
	}

	/**
	 * Register plugin routes
	 */
	public function register_order_routes() {
		\register_rest_route(
			'myd-delivery/v1',
			'/order-status-tracking',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'order_status_tracking' ],
					'permission_callback' => '__return_true',
					'args' => $this->get_parameters(),
				),
			)
		);
	}

	/**
	 * Check orders and retrive status
	 */
	public function order_status_tracking( $request ) {
		// Configurar headers para SSE
		header( 'Cache-Control: no-store' );
		header( 'Content-Type: text/event-stream' );
		header( 'X-Accel-Buffering: no' );
		header( 'Connection: keep-alive' );
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Headers: Cache-Control' );

		// Deshabilitar buffering
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		// Variables para optimización
		$last_status = '';
		$retry_count = 0;
		$max_retries = 120; // 10 minutos máximo (120 * 5 segundos)
		
		// Enviar configuración inicial
		echo "retry: 5000\n\n";

		while ( $retry_count < $max_retries ) {
			// Obtener status directamente de la base de datos (más eficiente)
			$order_id = $this->get_order_id_from_hash( $request['hash'] );
			
			if ( ! $order_id ) {
				echo "event: error\n";
				echo 'data: {"error": "Invalid order hash"}' . "\n\n";
				break;
			}

			$current_status = get_post_meta( $order_id, 'order_status', true );
			$payment_status = get_post_meta( $order_id, 'order_payment_status', true );
			
			// Solo enviar actualizaciones si ha cambiado el estado
			if ( $current_status !== $last_status ) {
				$event_response = array(
					'status' => $current_status,
					'payment_status' => $payment_status,
					'timestamp' => current_time( 'timestamp' ),
					'order_id' => $order_id,
				);

				echo "event: order-status-update\n";
				echo 'data: ' . json_encode( $event_response, JSON_UNESCAPED_UNICODE );
				echo "\n\n";
				
				$last_status = $current_status;
			} else {
				// Heartbeat para mantener la conexión
				echo "event: heartbeat\n";
				echo 'data: {"timestamp": ' . current_time( 'timestamp' ) . '}';
				echo "\n\n";
			}

			// Forzar envío de datos
			if ( ob_get_level() > 0 ) {
				ob_end_flush();
			}
			flush();

			// Verificar si la conexión sigue activa
			if ( connection_aborted() ) {
				break;
			}

			$retry_count++;
			sleep( 5 );
		}

		// Cerrar conexión después del tiempo máximo
		echo "event: close\n";
		echo 'data: {"message": "Connection timeout"}' . "\n\n";
		flush();
	}

	/**
	 * Get order ID from hash
	 * 
	 * @param string $hash
	 * @return int|false
	 */
	private function get_order_id_from_hash( $hash ) {
		// Decodificar hash base64
		$order_id = base64_decode( $hash );
		
		// Verificar que sea un ID válido
		if ( ! is_numeric( $order_id ) || ! get_post( $order_id ) ) {
			return false;
		}
		
		// Verificar que sea del tipo correcto
		if ( get_post_type( $order_id ) !== 'mydelivery-orders' ) {
			return false;
		}
		
		return (int) $order_id;
	}

	/**
	 * Define parameters
	 */
	public function get_parameters() {
		$args = array();

		$args['hash'] = array(
			'description' => esc_html__( 'The order hash', 'myd-delivery-pro' ),
			'type' => 'string',
			'required' => true,
		);

		return $args;
	}
}

new Order_Status_Tracking();
