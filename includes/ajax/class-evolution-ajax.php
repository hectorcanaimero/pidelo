<?php
/**
 * Evolution API AJAX Handlers
 *
 * Manejadores AJAX para Evolution API
 *
 * @package MydPro
 * @subpackage Ajax
 * @since 2.3.0
 */

namespace MydPro\Includes\Ajax;

use MydPro\Includes\Integrations\Evolution_Api\Evolution_Client;
use MydPro\Includes\Integrations\Evolution_Api\WhatsApp_Service;
use MydPro\Includes\Integrations\Evolution_Api\Instance_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Evolution Ajax Class
 */
class Evolution_Ajax {
	/**
	 * Constructor
	 */
	public function __construct() {
		// AJAX para admin y usuarios autenticados
		add_action( 'wp_ajax_myd_evolution_test_connection', [ $this, 'test_connection' ] );
		add_action( 'wp_ajax_myd_evolution_send_manual', [ $this, 'send_manual' ] );
		add_action( 'wp_ajax_myd_evolution_create_instance', [ $this, 'create_instance' ] );
		add_action( 'wp_ajax_myd_evolution_get_qr_code', [ $this, 'get_qr_code' ] );
		add_action( 'wp_ajax_myd_evolution_logout_instance', [ $this, 'logout_instance' ] );

		// Nuevos endpoints para automatización
		add_action( 'wp_ajax_myd_evolution_auto_setup', [ $this, 'auto_setup' ] );
		add_action( 'wp_ajax_myd_evolution_check_status', [ $this, 'check_status' ] );
		add_action( 'wp_ajax_myd_evolution_reconnect', [ $this, 'reconnect' ] );
		add_action( 'wp_ajax_myd_evolution_reset', [ $this, 'reset' ] );
	}

	/**
	 * Test de conexión con Evolution API
	 *
	 * @return void
	 */
	public function test_connection(): void {
		check_ajax_referer( 'myd-evolution-send', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Permission denied', 'myd-delivery-pro' ),
			] );
		}

		$client = new Evolution_Client();

		// Verificar si está configurado
		if ( ! $client->is_configured() ) {
			wp_send_json_error( [
				'message' => __( 'Evolution API not configured. Please fill all fields.', 'myd-delivery-pro' ),
			] );
		}

		// Verificar estado de la instancia
		$result = $client->check_instance_status();

		if ( ! $result['success'] ) {
			wp_send_json_error( [
				'message' => $result['error'] ?? __( 'Unknown error', 'myd-delivery-pro' ),
			] );
		}

		if ( ! $result['is_open'] ) {
			wp_send_json_error( [
				'message' => sprintf(
					__( 'Instance status: %s. Please ensure your WhatsApp is connected.', 'myd-delivery-pro' ),
					$result['status']
				),
			] );
		}

		wp_send_json_success( [
			'message' => __( 'Connection successful! Instance is active.', 'myd-delivery-pro' ),
			'data'    => $result['data'],
		] );
	}

	/**
	 * Cargar lista de instancias disponibles
	 *
	 * @return void
	 */
	public function load_instances(): void {
		check_ajax_referer( 'myd-evolution-send', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Permission denied', 'myd-delivery-pro' ),
			] );
		}

		$client = new Evolution_Client();
		$result = $client->fetch_instances();

		if ( ! $result['success'] ) {
			wp_send_json_error( [
				'message' => $result['error'] ?? __( 'Failed to fetch instances', 'myd-delivery-pro' ),
			] );
		}

		$instances = $result['data'] ?? [];

		if ( empty( $instances ) ) {
			wp_send_json_error( [
				'message' => __( 'No active instances found. Please create an instance in Evolution API.', 'myd-delivery-pro' ),
			] );
		}

		wp_send_json_success( [
			'message'   => sprintf(
				__( 'Found %d active instance(s)', 'myd-delivery-pro' ),
				count( $instances )
			),
			'instances' => $instances,
		] );
	}

	/**
	 * Envío manual de mensaje desde panel de órdenes
	 *
	 * @return void
	 */
	public function send_manual(): void {
		check_ajax_referer( 'myd-evolution-send', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [
				'message' => __( 'Permission denied', 'myd-delivery-pro' ),
			] );
		}

		$order_id = isset( $_POST['order_id'] ) ? (int) $_POST['order_id'] : 0;

		if ( empty( $order_id ) ) {
			wp_send_json_error( [
				'message' => __( 'Invalid order ID', 'myd-delivery-pro' ),
			] );
		}

		// Verificar que sea una orden válida
		if ( get_post_type( $order_id ) !== 'mydelivery-orders' ) {
			wp_send_json_error( [
				'message' => __( 'Invalid order', 'myd-delivery-pro' ),
			] );
		}

		$service = new WhatsApp_Service();
		$result = $service->send_order_notification( $order_id, 'manual' );

		if ( $result['success'] ) {
			// Obtener timestamp formateado
			$sent_time = current_time( 'mysql' );
			$time_diff = human_time_diff( strtotime( $sent_time ), current_time( 'timestamp' ) );

			wp_send_json_success( [
				'message'   => __( 'WhatsApp message sent successfully', 'myd-delivery-pro' ),
				'sent_time' => $sent_time,
				'time_diff' => $time_diff,
			] );
		} else {
			wp_send_json_error( [
				'message' => $result['error'] ?? __( 'Failed to send message', 'myd-delivery-pro' ),
			] );
		}
	}

	/**
	 * Crear nueva instancia de WhatsApp
	 *
	 * @return void
	 */
	public function create_instance(): void {
		check_ajax_referer( 'myd-evolution-send', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Permission denied', 'myd-delivery-pro' ),
			] );
		}

		$client = new Evolution_Client();
		$result = $client->create_instance();

		if ( ! $result['success'] ) {
			wp_send_json_error( [
				'message' => $result['error'] ?? __( 'Failed to create instance', 'myd-delivery-pro' ),
			] );
		}

		wp_send_json_success( [
			'message' => __( 'Instance created successfully. Scan the QR code with WhatsApp.', 'myd-delivery-pro' ),
			'data'    => $result['data'],
		] );
	}

	/**
	 * Obtener QR Code de la instancia
	 *
	 * @return void
	 */
	public function get_qr_code(): void {
		check_ajax_referer( 'myd-evolution-send', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Permission denied', 'myd-delivery-pro' ),
			] );
		}

		$client = new Evolution_Client();
		$result = $client->get_qr_code();

		if ( ! $result['success'] ) {
			wp_send_json_error( [
				'message' => $result['error'] ?? __( 'Failed to get QR code', 'myd-delivery-pro' ),
			] );
		}

		wp_send_json_success( [
			'qr_code' => $result['qr_code'],
			'data'    => $result['data'],
		] );
	}

	/**
	 * Desconectar instancia (logout)
	 *
	 * @return void
	 */
	public function logout_instance(): void {
		check_ajax_referer( 'myd-evolution-send', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Permission denied', 'myd-delivery-pro' ),
			] );
		}

		$client = new Evolution_Client();
		$result = $client->logout_instance();

		if ( ! $result['success'] ) {
			wp_send_json_error( [
				'message' => $result['error'] ?? __( 'Failed to logout instance', 'myd-delivery-pro' ),
			] );
		}

		wp_send_json_success( [
			'message' => __( 'Instance logged out successfully', 'myd-delivery-pro' ),
		] );
	}

	/**
	 * Auto-setup de instancia (crear, conectar, verificar)
	 *
	 * Implementa el flujo del diagrama Mermaid:
	 * Usuario → Sistema → Crear → Conectar → Verificar → Retornar
	 *
	 * @return void
	 */
	public function auto_setup(): void {
		check_ajax_referer( 'myd-evolution-send', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Permission denied', 'myd-delivery-pro' ),
			] );
		}

		$manager = new Instance_Manager();
		$result = $manager->auto_setup_instance();

		if ( $result['success'] ) {
			wp_send_json_success( [
				'message'  => $result['message'],
				'status'   => $result['status'],
				'qr_code'  => $result['qr_code'] ?? null,
				'instance' => $result['instance'] ?? null,
				'steps'    => $result['steps'] ?? [],
			] );
		} else {
			wp_send_json_error( [
				'message' => $result['message'],
				'error'   => $result['error'] ?? null,
				'steps'   => $result['steps'] ?? [],
			] );
		}
	}

	/**
	 * Verificar estado de instancia
	 *
	 * @return void
	 */
	public function check_status(): void {
		check_ajax_referer( 'myd-evolution-send', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Permission denied', 'myd-delivery-pro' ),
			] );
		}

		$manager = new Instance_Manager();
		$result = $manager->check_instance_status();

		if ( $result['success'] ) {
			wp_send_json_success( [
				'status'   => $result['status'],
				'is_open'  => $result['is_open'],
				'instance' => $result['instance'] ?? null,
				'message'  => $result['message'] ?? '',
			] );
		} else {
			wp_send_json_error( [
				'message' => $result['message'],
				'status'  => $result['status'],
			] );
		}
	}

	/**
	 * Reconectar instancia (obtener nuevo QR)
	 *
	 * @return void
	 */
	public function reconnect(): void {
		check_ajax_referer( 'myd-evolution-send', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Permission denied', 'myd-delivery-pro' ),
			] );
		}

		$manager = new Instance_Manager();
		$result = $manager->reconnect_instance();

		if ( $result['success'] ) {
			wp_send_json_success( [
				'message' => $result['message'],
				'qr_code' => $result['qr_code'],
			] );
		} else {
			wp_send_json_error( [
				'message' => $result['message'],
			] );
		}
	}

	/**
	 * Resetear instancia (logout + crear nueva)
	 *
	 * @return void
	 */
	public function reset(): void {
		check_ajax_referer( 'myd-evolution-send', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Permission denied', 'myd-delivery-pro' ),
			] );
		}

		$manager = new Instance_Manager();
		$result = $manager->reset_instance();

		if ( $result['success'] ) {
			wp_send_json_success( [
				'message'  => $result['message'],
				'qr_code'  => $result['qr_code'] ?? null,
				'instance' => $result['instance'] ?? null,
			] );
		} else {
			wp_send_json_error( [
				'message' => $result['message'],
			] );
		}
	}
}
