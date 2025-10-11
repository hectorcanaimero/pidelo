<?php
/**
 * Evolution API Client
 *
 * Cliente HTTP para comunicación con Evolution API v2.2.3
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
 * Evolution Client Class
 */
class Evolution_Client {
	/**
	 * API URL base
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * API Key
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Instance name
	 *
	 * @var string
	 */
	private $instance_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Credenciales globales hardcodeadas (configuración del sistema)
		$this->api_url = 'https://evo.guria.lat';
		$this->api_key = '5ab35f94cab7300af5f5ee90ed738bdeb2d0299cba052f8c7bcc343d49d0e39d';

		// Nombre de instancia auto-generado desde el nombre de la tienda
		$store_name = get_option( 'myd_business_name', get_bloginfo( 'name' ) );
		$this->instance_name = sanitize_title( $store_name );
	}

	/**
	 * Enviar mensaje de texto
	 *
	 * @param string $phone Número de teléfono
	 * @param string $message Mensaje a enviar
	 * @return array Resultado del envío
	 */
	public function send_text( $phone, $message ) {
		if ( empty( $this->instance_name ) ) {
			return [
				'success' => false,
				'error'   => __( 'Instance name not configured', 'myd-delivery-pro' ),
			];
		}

		$endpoint = $this->api_url . '/message/sendText/' . $this->instance_name;

		$body = [
			'number'      => $this->format_phone( $phone ),
			'text'        => $message,
			'delay'       => 0,
			'linkPreview' => true,
		];

		return $this->request( $endpoint, $body );
	}

	/**
	 * Enviar imagen con caption
	 *
	 * @param string $phone Número de teléfono
	 * @param string $media_url URL de la imagen
	 * @param string $caption Caption opcional
	 * @return array Resultado del envío
	 */
	public function send_media( $phone, $media_url, $caption = '' ) {
		if ( empty( $this->instance_name ) ) {
			return [
				'success' => false,
				'error'   => __( 'Instance name not configured', 'myd-delivery-pro' ),
			];
		}

		$endpoint = $this->api_url . '/message/sendMedia/' . $this->instance_name;

		$body = [
			'number'    => $this->format_phone( $phone ),
			'mediatype' => 'image',
			'media'     => $media_url,
		];

		if ( ! empty( $caption ) ) {
			$body['caption'] = $caption;
		}

		return $this->request( $endpoint, $body );
	}

	/**
	 * Obtener lista de instancias disponibles
	 *
	 * @return array Lista de instancias
	 */
	public function fetch_instances() {
		$endpoint = $this->api_url . '/instance/fetchInstances';

		$result = $this->request( $endpoint, [], 'GET' );

		if ( ! $result['success'] ) {
			return $result;
		}

		// Filtrar solo instancias con status "open"
		$instances = $result['data'] ?? [];
		$open_instances = array_filter(
			$instances,
			function( $instance ) {
				return isset( $instance['status'] ) && $instance['status'] === 'open';
			}
		);

		return [
			'success' => true,
			'data'    => array_values( $open_instances ),
		];
	}

	/**
	 * Verificar estado de instancia específica
	 *
	 * @param string $instance_name Nombre de la instancia (opcional)
	 * @return array Estado de la instancia
	 */
	public function check_instance_status( $instance_name = '' ) {
		$instance = ! empty( $instance_name ) ? $instance_name : $this->instance_name;

		if ( empty( $instance ) ) {
			return [
				'success' => false,
				'error'   => __( 'Instance name required', 'myd-delivery-pro' ),
			];
		}

		$endpoint = $this->api_url . '/instance/fetchInstances?instanceName=' . $instance;

		$result = $this->request( $endpoint, [], 'GET' );

		if ( ! $result['success'] ) {
			return $result;
		}

		$instances = $result['data'] ?? [];

		if ( empty( $instances ) ) {
			return [
				'success' => false,
				'error'   => __( 'Instance not found', 'myd-delivery-pro' ),
			];
		}

		$instance_data = $instances[0];
		$is_open = isset( $instance_data['status'] ) && $instance_data['status'] === 'open';

		return [
			'success'   => true,
			'is_open'   => $is_open,
			'status'    => $instance_data['status'] ?? 'unknown',
			'data'      => $instance_data,
		];
	}

	/**
	 * Request HTTP genérico
	 *
	 * @param string $url URL del endpoint
	 * @param array  $body Body del request (para POST)
	 * @param string $method Método HTTP
	 * @return array Resultado del request
	 */
	private function request( $url, $body = array(), $method = 'POST' ) {
		$args = [
			'method'  => $method,
			'headers' => [
				'apikey'       => $this->api_key,
				'Content-Type' => 'application/json',
			],
			'timeout' => 30,
		];

		if ( $method === 'POST' && ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'error'   => $response->get_error_message(),
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		$is_success = $status_code >= 200 && $status_code < 300;

		if ( ! $is_success ) {
			return [
				'success'     => false,
				'status_code' => $status_code,
				'error'       => $data['message'] ?? __( 'API request failed', 'myd-delivery-pro' ),
				'data'        => $data,
			];
		}

		return [
			'success'     => true,
			'status_code' => $status_code,
			'data'        => $data,
		];
	}

	/**
	 * Formatear teléfono para Evolution API
	 *
	 * Formato requerido: código país + número sin espacios ni símbolos
	 * Ejemplo: 5531999999999 (Brasil), 5491199999999 (Argentina)
	 *
	 * @param string $phone Número de teléfono
	 * @return string Teléfono formateado
	 */
	private function format_phone( $phone ) {
		// Remover todos los caracteres excepto dígitos
		$phone = preg_replace( '/[^0-9]/', '', $phone );

		// Remover el 0 inicial si existe (común en algunos países)
		// Ejemplo: 031999999999 → 31999999999
		if ( strlen( $phone ) === 11 && substr( $phone, 0, 1 ) === '0' ) {
			$phone = substr( $phone, 1 );
		}

		// Si no tiene código de país, intentar agregarlo
		// (esto es configurable según el país del negocio)
		$country_code = get_option( 'myd-evolution-phone-country-code', '' );

		if ( ! empty( $country_code ) && strlen( $phone ) < 12 ) {
			// Si el teléfono es corto, agregar código país
			// Ejemplo: 31999999999 → 5531999999999 (Brasil)
			$phone = $country_code . $phone;
		}

		return $phone;
	}

	/**
	 * Crear nueva instancia de WhatsApp
	 *
	 * @param string $instance_name Nombre de la instancia (opcional, usa el configurado si está vacío)
	 * @return array Resultado de la creación
	 */
	public function create_instance( $instance_name = '' ) {
		$instance = ! empty( $instance_name ) ? $instance_name : $this->instance_name;

		if ( empty( $instance ) ) {
			return [
				'success' => false,
				'error'   => __( 'Instance name required', 'myd-delivery-pro' ),
			];
		}

		$endpoint = $this->api_url . '/instance/create';

		$body = [
			'instanceName' => $instance,
			'qrcode'       => true,
			'integration'  => 'WHATSAPP-BAILEYS',
		];

		return $this->request( $endpoint, $body );
	}

	/**
	 * Obtener QR Code de una instancia
	 *
	 * @param string $instance_name Nombre de la instancia (opcional)
	 * @return array QR code data (base64 image)
	 */
	public function get_qr_code( $instance_name = '' ) {
		$instance = ! empty( $instance_name ) ? $instance_name : $this->instance_name;

		if ( empty( $instance ) ) {
			return [
				'success' => false,
				'error'   => __( 'Instance name required', 'myd-delivery-pro' ),
			];
		}

		$endpoint = $this->api_url . '/instance/connect/' . $instance;

		$result = $this->request( $endpoint, [], 'GET' );

		if ( ! $result['success'] ) {
			return $result;
		}

		$data = $result['data'] ?? [];

		// El QR viene en base64 dentro de data.base64 o data.qrcode.base64
		$qr_base64 = $data['base64'] ?? $data['qrcode']['base64'] ?? '';

		if ( empty( $qr_base64 ) ) {
			return [
				'success' => false,
				'error'   => __( 'QR code not available', 'myd-delivery-pro' ),
			];
		}

		return [
			'success' => true,
			'qr_code' => $qr_base64,
			'data'    => $data,
		];
	}

	/**
	 * Desconectar instancia (logout)
	 *
	 * @param string $instance_name Nombre de la instancia (opcional)
	 * @return array Resultado de la desconexión
	 */
	public function logout_instance( $instance_name = '' ) {
		$instance = ! empty( $instance_name ) ? $instance_name : $this->instance_name;

		if ( empty( $instance ) ) {
			return [
				'success' => false,
				'error'   => __( 'Instance name required', 'myd-delivery-pro' ),
			];
		}

		$endpoint = $this->api_url . '/instance/logout/' . $instance;

		return $this->request( $endpoint, [], 'DELETE' );
	}

	/**
	 * Eliminar instancia completamente
	 *
	 * @param string $instance_name Nombre de la instancia (opcional)
	 * @return array Resultado de la eliminación
	 */
	public function delete_instance( $instance_name = '' ) {
		$instance = ! empty( $instance_name ) ? $instance_name : $this->instance_name;

		if ( empty( $instance ) ) {
			return [
				'success' => false,
				'error'   => __( 'Instance name required', 'myd-delivery-pro' ),
			];
		}

		$endpoint = $this->api_url . '/instance/delete/' . $instance;

		return $this->request( $endpoint, [], 'DELETE' );
	}

	/**
	 * Verificar si la configuración es válida
	 *
	 * @return bool True si está configurado correctamente
	 */
	public function is_configured() {
		return ! empty( $this->api_url ) &&
		       ! empty( $this->api_key ) &&
		       ! empty( $this->instance_name );
	}
}
