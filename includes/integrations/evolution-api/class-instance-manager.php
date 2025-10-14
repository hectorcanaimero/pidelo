<?php
/**
 * Evolution API Instance Manager
 *
 * Sistema automatizador para creación, conexión y verificación de instancias
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
 * Instance Manager Class
 *
 * Automatiza el proceso completo de:
 * 1. Crear instancia
 * 2. Conectar instancia
 * 3. Verificar estado de instancia
 */
class Instance_Manager {
	/**
	 * Evolution Client
	 *
	 * @var Evolution_Client
	 */
	private $client;

	/**
	 * Logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Nombre de instancia generado
	 *
	 * @var string
	 */
	private $instance_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->client = new Evolution_Client();
		$this->logger = new Logger();

		// Nombre de instancia generado siempre desde el nombre de la empresa
		$store_name = get_option( 'fdm-business-name', get_bloginfo( 'name' ) );
		$this->instance_name = sanitize_title( $store_name );
	}

	/**
	 * Proceso completo de auto-setup de instancia
	 *
	 * Este método implementa el flujo del diagrama:
	 * Usuario → Sistema → Crear → Conectar → Verificar → Retornar
	 *
	 * @return array Resultado del proceso con estado de la instancia
	 */
	public function auto_setup_instance() {
		$steps = [];

		// PASO 1: Verificar si ya existe instancia
		$steps['check_existing'] = $this->check_existing_instance();

		if ( $steps['check_existing']['exists'] && $steps['check_existing']['is_open'] ) {
			// Instancia ya existe y está conectada
			return [
				'success' => true,
				'message' => __( 'Instance already exists and is connected', 'myd-delivery-pro' ),
				'status'  => 'connected',
				'steps'   => $steps,
			];
		}

		// PASO 2: Crear instancia si no existe
		if ( ! $steps['check_existing']['exists'] ) {
			$steps['create'] = $this->create_instance_step();

			if ( ! $steps['create']['success'] ) {
				return [
					'success' => false,
					'message' => __( 'Failed to create instance', 'myd-delivery-pro' ),
					'error'   => $steps['create']['error'],
					'steps'   => $steps,
				];
			}
		}

		// PASO 3: Conectar instancia (obtener QR)
		$steps['connect'] = $this->connect_instance_step();

		if ( ! $steps['connect']['success'] ) {
			return [
				'success' => false,
				'message' => __( 'Instance created but failed to get QR code', 'myd-delivery-pro' ),
				'error'   => $steps['connect']['error'],
				'steps'   => $steps,
			];
		}

		// PASO 4: Verificar estado final
		$steps['verify'] = $this->verify_instance_step();

		// Guardar configuración en opciones
		$this->save_instance_config();

		return [
			'success'   => true,
			'message'   => __( 'Instance setup completed. Please scan the QR code.', 'myd-delivery-pro' ),
			'status'    => $steps['verify']['status'],
			'qr_code'   => $steps['connect']['qr_code'] ?? null,
			'instance'  => $this->instance_name,
			'steps'     => $steps,
		];
	}

	/**
	 * Paso 1: Verificar si existe instancia
	 *
	 * @return array
	 */
	private function check_existing_instance() {
		$result = $this->client->check_instance_status( $this->instance_name );

		if ( $result['success'] ) {
			return [
				'exists'  => true,
				'is_open' => $result['is_open'],
				'status'  => $result['status'],
				'data'    => $result['data'],
			];
		}

		return [
			'exists'  => false,
			'is_open' => false,
			'status'  => 'not_found',
		];
	}

	/**
	 * Paso 2: Crear instancia
	 *
	 * @return array
	 */
	private function create_instance_step() {
		$result = $this->client->create_instance( $this->instance_name );

		if ( $result['success'] ) {
			// Log de creación
			error_log( sprintf(
				'[Evolution API] Instance created: %s',
				$this->instance_name
			) );

			return [
				'success' => true,
				'data'    => $result['data'],
			];
		}

		return [
			'success' => false,
			'error'   => $result['error'],
		];
	}

	/**
	 * Paso 3: Conectar instancia (obtener QR)
	 *
	 * @return array
	 */
	private function connect_instance_step() {
		$result = $this->client->get_qr_code( $this->instance_name );

		if ( $result['success'] ) {
			return [
				'success' => true,
				'qr_code' => $result['qr_code'],
			];
		}

		return [
			'success' => false,
			'error'   => $result['error'],
		];
	}

	/**
	 * Paso 4: Verificar estado de instancia
	 *
	 * @return array
	 */
	private function verify_instance_step() {
		$result = $this->client->check_instance_status( $this->instance_name );

		if ( $result['success'] ) {
			return [
				'success' => true,
				'status'  => $result['status'],
				'is_open' => $result['is_open'],
			];
		}

		return [
			'success' => false,
			'status'  => 'unknown',
			'is_open' => false,
		];
	}

	/**
	 * Guardar configuración de instancia en opciones
	 *
	 * @return void
	 */
	private function save_instance_config() {
		// Solo guardamos metadatos temporales, el nombre se genera siempre desde fdm-business-name
		update_option( 'myd-evolution-instance-created-at', current_time( 'mysql' ) );
		update_option( 'myd-evolution-instance-auto-setup', 'yes' );
	}

	/**
	 * Verificar estado actual de la instancia configurada
	 *
	 * @return array Estado actual
	 */
	public function check_instance_status() {
		// Usar siempre el nombre de instancia generado desde el nombre de empresa
		$instance_name = $this->instance_name;

		if ( empty( $instance_name ) ) {
			return [
				'success' => false,
				'message' => __( 'No instance configured', 'myd-delivery-pro' ),
				'status'  => 'not_configured',
			];
		}

		$result = $this->client->check_instance_status( $instance_name );

		if ( ! $result['success'] ) {
			return [
				'success' => false,
				'message' => $result['error'],
				'status'  => 'error',
			];
		}

		return [
			'success'  => true,
			'status'   => $result['status'],
			'is_open'  => $result['is_open'],
			'instance' => $instance_name,
			'data'     => $result['data'],
		];
	}

	/**
	 * Reconectar instancia (obtener nuevo QR)
	 *
	 * @return array QR code data
	 */
	public function reconnect_instance() {
		// Usar siempre el nombre generado desde el nombre de empresa
		$instance_name = $this->instance_name;

		if ( empty( $instance_name ) ) {
			return [
				'success' => false,
				'message' => __( 'No instance configured', 'myd-delivery-pro' ),
			];
		}

		// Intentar obtener QR
		$result = $this->client->get_qr_code( $instance_name );

		if ( $result['success'] ) {
			return [
				'success' => true,
				'message' => __( 'QR code generated. Please scan with WhatsApp.', 'myd-delivery-pro' ),
				'qr_code' => $result['qr_code'],
			];
		}

		return [
			'success' => false,
			'message' => $result['error'],
		];
	}

	/**
	 * Resetear instancia (logout + crear nueva)
	 *
	 * @return array Resultado del reset
	 */
	public function reset_instance() {
		// Usar siempre el nombre generado desde el nombre de empresa
		$instance_name = $this->instance_name;

		if ( ! empty( $instance_name ) ) {
			// Intentar hacer logout de la instancia actual
			$this->client->logout_instance( $instance_name );
		}

		// Ejecutar auto-setup de nuevo
		return $this->auto_setup_instance();
	}

	/**
	 * Obtener información completa de la instancia
	 *
	 * @return array Información de la instancia
	 */
	public function get_instance_info() {
		// Usar siempre el nombre generado desde el nombre de empresa
		$instance_name = $this->instance_name;
		$created_at = get_option( 'myd-evolution-instance-created-at', '' );
		$auto_setup = get_option( 'myd-evolution-instance-auto-setup', 'no' );

		if ( empty( $instance_name ) ) {
			return [
				'configured' => false,
				'message'    => __( 'No instance configured. Click "Auto Setup" to create one.', 'myd-delivery-pro' ),
			];
		}

		// Verificar estado actual
		$status_check = $this->check_instance_status();

		return [
			'configured'  => true,
			'name'        => $instance_name,
			'created_at'  => $created_at,
			'auto_setup'  => $auto_setup === 'yes',
			'status'      => $status_check['status'] ?? 'unknown',
			'is_open'     => $status_check['is_open'] ?? false,
			'message'     => $this->get_status_message( $status_check ),
		];
	}

	/**
	 * Obtener mensaje según el estado
	 *
	 * @param array $status_check Resultado de verificación de estado
	 * @return string Mensaje
	 */
	private function get_status_message( $status_check ) {
		if ( ! $status_check['success'] ) {
			return __( 'Failed to check instance status', 'myd-delivery-pro' );
		}

		$status = $status_check['status'];

		switch ( $status ) {
			case 'open':
				return '🟢 ' . __( 'Connected and ready to send messages', 'myd-delivery-pro' );
			case 'close':
				return '🔴 ' . __( 'Disconnected. Click "Reconnect" to get QR code.', 'myd-delivery-pro' );
			case 'connecting':
				return '🟡 ' . __( 'Connecting... Please scan QR code with WhatsApp.', 'myd-delivery-pro' );
			default:
				return '⚪ ' . sprintf( __( 'Unknown status: %s', 'myd-delivery-pro' ), $status );
		}
	}
}
