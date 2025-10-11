<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * TODO: refactor the class
 */
class Myd_Orders_Front_Panel {
	/**
	 * Queried orders object
	 *
	 * @var object
	 */
	protected $orders_object;

	/**
	 * Default args
	 *
	 * @var array
	 */
	protected $default_args = [
		'post_type' => 'mydelivery-orders',
		'posts_per_page' => 30,
		'no_found_rows' => true,
		'meta_query' => [
			'relation' => 'OR',
			[
				'key'     => 'order_status',
				'value'   => 'new',
				'compare' => '=',
			],
			[
				'key'     => 'order_status',
				'value'   => 'confirmed',
				'compare' => '=',
			],
			[
				'key'     => 'order_status',
				'value'   => 'in-delivery',
				'compare' => '=',
			],
			[
				'key'     => 'order_status',
				'value'   => 'done',
				'compare' => '=',
			],
			[
				'key'     => 'order_status',
				'value'   => 'waiting',
				'compare' => '=',
			],
			[
				'key'     => 'order_status',
				'value'   => 'in-process',
				'compare' => '=',
			],
		]
	];

	/**
	 * Construct the class
	 */
	public function __construct () {
		add_shortcode( 'mydelivery-orders', [ $this, 'show_orders_list'] );
		add_action( 'wp_ajax_reload_orders', [ $this, 'ajax_reload_orders'] );
		add_action( 'wp_ajax_nopriv_reload_orders', [ $this, 'ajax_reload_orders'] );
		add_action( 'wp_ajax_update_orders', [ $this, 'update_orders'] );
		add_action( 'wp_ajax_nopriv_update_orders', [ $this, 'update_orders'] );
		add_action( 'wp_ajax_print_orders', [ $this, 'ajax_print_order'] );
		add_action( 'wp_ajax_nopriv_print_orders', [ $this, 'ajax_print_order'] );
		add_action( 'wp_ajax_myd_check_new_orders', [ $this, 'ajax_check_new_orders'] );
		add_action( 'wp_ajax_nopriv_myd_check_new_orders', [ $this, 'ajax_check_new_orders'] );
	}

	/**
	 * Output template panel
	 *
	 * TODO: move to new class
	 *
	 * @return void
	 * @since 1.9.5
	 */
	public function show_orders_list () {
		if( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
			// Restaurar scripts originales para funcionalidad de botones
			\wp_enqueue_script( 'myd-orders-panel' );
			\wp_enqueue_script( 'myd-order-list-ajax' );
			\wp_enqueue_style( 'myd-order-panel-frontend' );
			\wp_enqueue_script( 'plugin_pdf' );
			\wp_enqueue_style( 'plugin_pdf_css' );
			
			// Enqueue WordPress REST API script para autenticación
			\wp_enqueue_script( 'wp-api' );

			// Localizar nonce para REST API
			\wp_localize_script( 'myd-orders-panel', 'wpApiSettings', array(
				'root' => \esc_url_raw( rest_url() ),
				'nonce' => \wp_create_nonce( 'wp_rest' )
			));

			// Evolution API assets si está habilitado
			if ( \get_option( 'myd-evolution-api-enabled' ) === 'yes' ) {
				\wp_enqueue_script( 'myd-evolution-panel' );
				\wp_enqueue_style( 'myd-evolution-panel-css' );
			}

			/**
			 * Query orders
			 */
			$orders = new \MydPro\Includes\Myd_Store_Orders( $this->default_args );
			$orders = $orders->get_orders_object();
			$this->orders_object = $orders;

			/**
			 * Include templates
			 */
			ob_start();
			include MYD_PLUGIN_PATH . 'templates/order/panel.php';
			
			// Agregar JavaScript inline para actualizaciones en tiempo real
			$this->add_realtime_js();
			
			return ob_get_clean();
		} else {
			return '<div class="fdm-not-logged">' . __( 'Sorry, you dont have access to this page.', 'myd-delivery-pro' ) . '</div>';
		}
	}

	/**
	 * Loop orders list
	 *
	 * TODO: move to new class
	 *
	 * @return void
	 * @since 1.9.5
	 */
	public function loop_orders_list () {
		$orders = $this->orders_object;

		ob_start();
		include MYD_PLUGIN_PATH . 'templates/order/order-list.php';
		return ob_get_clean();
	}

	/**
	 * Orders content
	 *
	 * TODO: move to new class
	 *
	 * @return void
	 * @since 1.9.5
	 */
	public function loop_orders_full () {
		$orders = $this->orders_object;

		ob_start();
		include MYD_PLUGIN_PATH . 'templates/order/order-content.php';
		return ob_get_clean();
	}

	/**
	 * Orders print
	 *
	 * TODO: move to new class
	 *
	 * @return void
	 * @since 1.9.5
	 */
	public function loop_print_order () {
		$orders = $this->orders_object;

		ob_start();
		include MYD_PLUGIN_PATH . 'templates/order/print.php';
		return ob_get_clean();
	}

	/**
	 * Orders comanda print
	 *
	 * @return void
	 * @since 2.2.19
	 */
	public function loop_print_comanda () {
		$orders = $this->orders_object;

		ob_start();
		include MYD_PLUGIN_PATH . 'templates/order/print-comanda.php';
		return ob_get_clean();
	}

	/**
	 * Count orders
	 *
	 * @return void
	 */
	public function count_orders() {
		$orders = $this->query_orders();
		$orders = $orders->get_posts();

		return count( $orders );
	}

	/**
	 * Ajax class items
	 *
	 * @return void
	 */
	public function ajax_reload_orders() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'myd-order-notification' ) ) {
			echo wp_json_encode(
				array(
					'error' => 'Security validation failed.',
				)
			);
			exit;
		}

		$order_id = sanitize_text_field( $_REQUEST['id'] );
		$order_action = sanitize_text_field( $_REQUEST['order_action'] );
		update_post_meta( $order_id, 'order_status', $order_action );
		
		// Si se confirma el pedido, actualizar estado de pago a "paid"
		if ( $order_action === 'confirmed' ) {
			update_post_meta( $order_id, 'order_payment_status', 'paid' );
		}
		
		/**
		 * Always refresh orders data after status updates to ensure UI reflects changes
		 */
		$orders = new \MydPro\Includes\Myd_Store_Orders( $this->default_args );
		$orders = $orders->get_orders_object();
		$this->orders_object = $orders;

		echo wp_json_encode( array(
			'loop' => $this->loop_orders_list(),
			'full' => $this->loop_orders_full(),
		));

		exit;
	}

	/**
	 * Ajax to reload order after update (new order)
	 *
	 * @return void
	 */
	public function update_orders() {
		$nonce = $_REQUEST['nonce'] ?? null;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'myd-order-notification' ) ) {
			echo wp_json_encode( array(
				'error' => esc_html__( 'Security check failed.', 'myd-delivery-pro' ),
			));
			wp_die();
		}

		// Siempre re-generar la consulta para obtener pedidos actualizados
		$orders = new \MydPro\Includes\Myd_Store_Orders( $this->default_args );
		$orders = $orders->get_orders_object();
		$this->orders_object = $orders;

		$response = array(
			'loop' => $this->loop_orders_list(),
			'full' => $this->loop_orders_full(),
			'print' => $this->loop_print_order(),
			'comanda' => $this->loop_print_comanda(),
			'debug' => array(
				'orders_found' => $orders->found_posts,
				'method' => 'update_orders'
			)
		);

		echo wp_json_encode( $response );
		wp_die();
	}

	/**
	 * Ajax print order handler
	 * Updates payment status to 'paid' when order is sent to print
	 *
	 * @return void
	 * @since 2.2.19
	 */
	public function ajax_print_order() {
		$nonce = $_REQUEST['nonce'] ?? null;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'myd-order-notification' ) ) {
			echo wp_json_encode( array(
				'error' => esc_html__( 'Security validation failed.', 'myd-delivery-pro' ),
			));
			wp_die();
		}

		$order_id = absint( $_REQUEST['order_id'] ?? 0 );
		if ( ! $order_id ) {
			echo wp_json_encode( array(
				'error' => esc_html__( 'Invalid order ID.', 'myd-delivery-pro' ),
			));
			wp_die();
		}

		// Actualizar estado de pago a "paid" cuando se envía a imprimir
		$current_payment_status = get_post_meta( $order_id, 'order_payment_status', true );
		if ( $current_payment_status === 'waiting' ) {
			update_post_meta( $order_id, 'order_payment_status', 'paid' );
		}

		// Disparar acción para otros plugins/funcionalidades
		do_action( 'myd_delivery_pro_order_printed', $order_id );

		// Refresh orders data to get updated payment status
		$orders = new \MydPro\Includes\Myd_Store_Orders( $this->default_args );
		$orders = $orders->get_orders_object();
		$this->orders_object = $orders;

		// Generate updated print content
		ob_start();
		include MYD_PLUGIN_PATH . 'templates/order/print.php';
		$updated_print_content = ob_get_clean();

		echo wp_json_encode( array(
			'success' => true,
			'message' => esc_html__( 'Order sent to print and payment status updated.', 'myd-delivery-pro' ),
			'order_id' => $order_id,
			'print_content' => $updated_print_content,
		));
		wp_die();
	}


	/**
	 * AJAX check new orders for shortcode
	 * 
	 * @since 2.2.19
	 * @return void
	 */
	public function ajax_check_new_orders() {
		$nonce = $_REQUEST['nonce'] ?? null;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'myd-order-notification' ) ) {
			echo wp_json_encode( array(
				'error' => esc_html__( 'Security validation failed.', 'myd-delivery-pro' ),
			));
			wp_die();
		}

		$current_id = absint( $_REQUEST['oid'] ?? 0 );

		$args = [
			'post_type' => 'mydelivery-orders',
			'posts_per_page' => 5, // Obtener más pedidos para comparar
			'no_found_rows' => true,
			'orderby' => 'ID',
			'order' => 'DESC',
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     => 'order_status',
					'value'   => 'new',
					'compare' => '=',
				),
				array(
					'key'     => 'order_status',
					'value'   => 'confirmed',
					'compare' => '=',
				),
				array(
					'key'     => 'order_status',
					'value'   => 'in-delivery',
					'compare' => '=',
				),
				array(
					'key'     => 'order_status',
					'value'   => 'done',
					'compare' => '=',
				),
				array(
					'key'     => 'order_status',
					'value'   => 'waiting',
					'compare' => '=',
				),
				array(
					'key'     => 'order_status',
					'value'   => 'in-process',
					'compare' => '=',
				),
			),
		];

		$orders = new \WP_Query( $args );
		$orders = $orders->get_posts();

		// Debug info
		$newest_id = !empty( $orders ) ? $orders[0]->ID : 0;
		
		if ( empty( $orders ) || $newest_id <= $current_id ) {
			$response = [ 
				'status' => 'atualizado',
				'debug' => [
					'current_id' => $current_id,
					'newest_id' => $newest_id,
					'orders_count' => count( $orders )
				]
			];
		} else {
			$response = [ 
				'status' => 'desatualizado',
				'debug' => [
					'current_id' => $current_id,
					'newest_id' => $newest_id,
					'orders_count' => count( $orders )
				]
			];
		}
		
		echo wp_json_encode( $response );
		wp_die();
	}

	/**
	 * Add realtime JavaScript for shortcode
	 * 
	 * @since 2.2.19
	 * @return void
	 */
	public function add_realtime_js() {
		?>
		<script type="text/javascript">
		console.log('MYD: Sistema de tiempo real activo cargado');
		
		// Sistema activo de polling que funciona independientemente
		(function() {
			let lastOrderId = 0;
			let pollTimer;
			let isPolling = false;
			let audioEnabled = true;
			let audioContext = null;
			
			// Función mejorada para reproducir sonido
			function playNotificationSound() {
				if (!audioEnabled) {
					console.log('MYD: Audio deshabilitado');
					return;
				}
				
				console.log('MYD: 🔊 Intentando reproducir sonido...');
				
				// Método 1: Audio HTML5
				const audio = new Audio('<?php echo MYD_PLUGN_URL; ?>assets/songs/trim.mp3');
				audio.volume = 0.8;
				audio.preload = 'auto';
				
				// Intentar reproducir
				const playPromise = audio.play();
				
				if (playPromise !== undefined) {
					playPromise.then(() => {
						console.log('MYD: ✅ Sonido reproducido exitosamente');
					}).catch(error => {
						console.warn('MYD: ⚠️ Error al reproducir sonido:', error.message);
						
						// Método alternativo: Tono generado
						if (error.name === 'NotAllowedError') {
							console.log('MYD: 🔇 Audio bloqueado por navegador. Haz click para habilitar.');
							showAudioPermissionMessage();
						} else {
							playBeepTone();
						}
					});
				}
			}
			
			// Tono generado como alternativa
			function playBeepTone() {
				try {
					if (!audioContext) {
						audioContext = new (window.AudioContext || window.webkitAudioContext)();
					}
					
					const oscillator = audioContext.createOscillator();
					const gainNode = audioContext.createGain();
					
					oscillator.connect(gainNode);
					gainNode.connect(audioContext.destination);
					
					oscillator.frequency.value = 800; // Frecuencia del beep
					gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
					gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
					
					oscillator.start(audioContext.currentTime);
					oscillator.stop(audioContext.currentTime + 0.5);
				} catch (e) {
					console.warn('MYD: Error con tono alternativo:', e);
				}
			}
			
			// Mostrar mensaje para habilitar audio
			function showAudioPermissionMessage() {
				if (!document.getElementById('myd-audio-permission')) {
					const message = document.createElement('div');
					message.id = 'myd-audio-permission';
					message.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #ff9800; color: white; padding: 15px; border-radius: 5px; z-index: 9999; cursor: pointer; max-width: 300px;';
					message.innerHTML = '🔊 Haz click aquí para habilitar notificaciones de audio';
					
					message.onclick = function() {
						// Reproducir audio después del click del usuario
						const audio = new Audio('<?php echo MYD_PLUGN_URL; ?>assets/songs/trim.mp3');
						audio.volume = 0.8;
						audio.play().then(() => {
							console.log('MYD: ✅ Audio habilitado por el usuario');
							message.remove();
						}).catch(e => {
							console.warn('MYD: Aún hay problemas con el audio:', e);
						});
					};
					
					document.body.appendChild(message);
					
					// Auto-remover después de 10 segundos
					setTimeout(() => {
						if (message.parentNode) {
							message.remove();
						}
					}, 10000);
				}
			}
			
			// Obtener ID de la primera orden actual
			function updateLastOrderId() {
				const firstOrder = document.querySelector('.fdm-orders-items');
				if (firstOrder) {
					lastOrderId = parseInt(firstOrder.id) || 0;
					console.log('MYD: lastOrderId inicializado a:', lastOrderId);
				}
			}
			
			// Verificar nuevos pedidos de forma activa
			function checkForNewOrders() {
				if (isPolling) return; // Evitar múltiples llamadas simultáneas
				
				isPolling = true;
				console.log('MYD: Verificando nuevos pedidos...');
				
				jQuery.ajax({
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					type: 'POST',
					dataType: 'json', // Forzar parseo JSON
					data: {
						action: 'myd_check_new_orders',
						oid: lastOrderId,
						nonce: '<?php echo wp_create_nonce( 'myd-order-notification' ); ?>'
					},
					success: function(data) {
						console.log('MYD: Respuesta:', data, 'Tipo:', typeof data);
						
						if (data && data.status === 'desatualizado') {
							console.log('MYD: ¡Nuevo pedido encontrado! Actualizando...');
							updateOrdersInterface();
						} else if (data && data.status === 'atualizado') {
							console.log('MYD: No hay nuevos pedidos (actualizado)');
						} else {
							console.warn('MYD: Respuesta inesperada:', data);
						}
					},
					error: function(xhr, status, error) {
						console.warn('MYD: Error al verificar:', error);
					},
					complete: function() {
						isPolling = false;
					}
				});
			}
			
			// Actualizar interfaz con nuevos pedidos
			function updateOrdersInterface() {
				console.log('MYD: Actualizando interfaz...');
				
				jQuery.ajax({
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					type: 'POST',
					dataType: 'json', // Forzar parseo JSON
					data: {
						action: 'update_orders',
						nonce: '<?php echo wp_create_nonce( 'myd-order-notification' ); ?>'
					},
					success: function(response) {
						console.log('MYD: Actualización recibida:', response, 'Tipo:', typeof response);
						
						if (response && response.loop && response.full && response.print && response.comanda) {
							// Limpiar contenido existente
							jQuery('.fdm-orders-items, .fdm-orders-full-items').remove();
							jQuery('.fdm-btn-order-action').attr('data-manage-order-id', '');
							jQuery('.order-print, .order-print-comanda').remove();
							
							// Insertar nuevo contenido
							jQuery('.fdm-orders-loop').prepend(response.loop);
							jQuery('.fdm-orders-full').prepend(response.full);
							jQuery('#hide-prints').prepend(response.print);
							jQuery('#hide-comanda-prints').prepend(response.comanda);
							
							// Actualizar ID de seguimiento
							const newFirstOrder = document.querySelector('.fdm-orders-items');
							if (newFirstOrder) {
								const newId = parseInt(newFirstOrder.id) || 0;
								if (newId > lastOrderId) {
									console.log('MYD: ¡NUEVO PEDIDO! ID:', newId, '(anterior:', lastOrderId + ')');
									
									// Reproducir sonido de notificación
									playNotificationSound();
									
									// Actualizar ID
									lastOrderId = newId;
								}
							}
							
							console.log('MYD: ✅ Interfaz actualizada exitosamente');
						} else {
							console.error('MYD: ❌ Respuesta inválida:', response);
						}
					},
					error: function(xhr, status, error) {
						console.error('MYD: ❌ Error al actualizar interfaz:', error);
					}
				});
			}
			
			// Iniciar polling
			function startPolling() {
				console.log('MYD: 🚀 Iniciando polling cada 6 segundos...');
				pollTimer = setInterval(checkForNewOrders, 6000);
				
				// Primera verificación después de 2 segundos
				setTimeout(checkForNewOrders, 2000);
			}
			
			// Pausar/reanudar según visibilidad
			document.addEventListener('visibilitychange', function() {
				if (document.hidden) {
					console.log('MYD: ⏸️ Pausando polling (página oculta)');
					clearInterval(pollTimer);
				} else {
					console.log('MYD: ▶️ Reanudando polling (página visible)');
					startPolling();
				}
			});
			
			// Inicializar
			jQuery(document).ready(function() {
				updateLastOrderId();
				startPolling();
				console.log('MYD: ✅ Sistema activo de tiempo real inicializado');
				
				// Exponer funciones para testing manual
				window.mydTestUpdate = updateOrdersInterface;
				window.mydTestSound = playNotificationSound;
				console.log('MYD: 🧪 Para probar manualmente:');
				console.log('MYD: - Actualización: mydTestUpdate()');
				console.log('MYD: - Sonido: mydTestSound()');
			});
			
		})();
		
		// Agregar estilos para el botón de comanda y ocultar el div de impresión
		const comandaStyles = `
			<style>
			#hide-comanda-prints {
				display: none !important;
			}
			.fdm-btn-comanda-print {
				background: #208e2a;
				color: #fff;
				border: none;
				padding: 10px 15px;
				margin: 5px;
				border-radius: 5px;
				cursor: pointer;
				display: inline-flex;
				align-items: center;
				gap: 8px;
				font-size: 14px;
				transition: background 0.3s ease;
			}
			.fdm-btn-comanda-print:hover {
				background: #1a7022;
			}
			.fdm-btn-comanda-print svg {
				fill: #fff;
			}
			</style>
		`;
		document.head.insertAdjacentHTML('beforeend', comandaStyles);
		
		// Extender la funcionalidad existente para incluir el botón de comanda
		jQuery(document).ready(function($) {
			console.log('MYD: Inicializando extensión para comanda');
			
			// Extender el event listener existente para incluir botón de comanda
			$(document).on('click', '.fdm-orders-items', function() {
				var orderId = $(this).attr('id');
				console.log('MYD: Orden seleccionada:', orderId);
				
				// Asignar ID también al botón de comanda
				$('.fdm-btn-comanda-print').attr('data-manage-order-id', orderId);
			});
			
			$(document).on('click', '.fdm-btn-comanda-print', function(e) {
				console.log('MYD: Click en botón comanda detectado');
				e.preventDefault();
				e.stopPropagation();
				
				var orderId = $(this).attr('data-manage-order-id');
				console.log('MYD: Order ID obtenido:', orderId);
				
				if (!orderId) {
					console.log('MYD: No hay orden seleccionada para imprimir comanda');
					alert('Por favor selecciona una orden primero');
					return;
				}
				
				var printSize = $(this).attr('data-print-size');
				var printFont = $(this).attr('data-print-font');
				
				console.log('MYD: Configuración de impresión - Size:', printSize, 'Font:', printFont);
				
				// Verificar si existe el elemento a imprimir
				var elementToPrint = document.getElementById('print-comanda-' + orderId);
				if (!elementToPrint) {
					console.error('MYD: No se encontró el elemento print-comanda-' + orderId);
					alert('Error: No se puede encontrar el contenido de la comanda para imprimir');
					return;
				}
				
				console.log('MYD: Elemento encontrado, procediendo a imprimir');
				
				// Imprimir la comanda directamente sin cambiar estatus
				printJS({
					printable: 'print-comanda-' + orderId,
					type: 'html',
					style: '@page { size:' + printSize + ' 200mm; margin: 0; } .order-print-comanda { font-size:' + printFont + 'px; } .comanda-header{ text-align: center; font-weight: bold; }'
				});
				
				console.log('MYD: Comanda enviada a imprimir para orden:', orderId);
			});
		});
		</script>
		<?php
	}
}

new Myd_Orders_Front_Panel();
