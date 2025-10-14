<?php
/**
 * Evolution API Settings Tab
 *
 * @package MydPro
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Obtener configuración actual
$is_enabled = get_option( 'myd-evolution-api-enabled' ) === 'yes';
$phone_country_code = get_option( 'myd-evolution-phone-country-code', '58' );
$auto_events = get_option( 'myd-evolution-auto-send-events', [] );

// Generar nombre de instancia desde el nombre de la empresa (siempre)
$store_name = get_option( 'fdm-business-name', get_bloginfo( 'name' ) );
$instance_name = sanitize_title( $store_name );

if ( ! is_array( $auto_events ) ) {
	$auto_events = [];
}
?>

<div id="tab-evolution-api-content" class="myd-tabs-content">
	<h2>
		<?php esc_html_e( 'Evolution API - WhatsApp Automático', 'myd-delivery-pro' ); ?>
	</h2>
	<p class="description">
		<?php esc_html_e( 'Configura la integración con Evolution API para enviar mensajes de WhatsApp automáticos en cada etapa del pedido.', 'myd-delivery-pro' ); ?>
	</p>

	<!-- Banner de Estado -->
	<div class="myd-evolution-status-banner">
		<div class="status-indicator" id="evolution-status-indicator">
			<span class="status-dot"></span>
			<span class="status-text"><?php esc_html_e( 'Verificando...', 'myd-delivery-pro' ); ?></span>
		</div>
		<div class="status-actions">
			<button type="button" class="button button-primary" id="btn-show-qr-section" style="display: none;">
				<span class="dashicons dashicons-smartphone"></span>
				<?php esc_html_e( 'Conectar WhatsApp', 'myd-delivery-pro' ); ?>
			</button>
			<button type="button" class="button button-secondary" id="btn-disconnect-delete-instance" style="display: none;">
				<span class="dashicons dashicons-dismiss"></span>
				<?php esc_html_e( 'Desconectar y Eliminar Instancia', 'myd-delivery-pro' ); ?>
			</button>
		</div>
	</div>

	<!-- Sección QR Code para Conexión WhatsApp -->
	<div class="myd-qr-section" id="qr-connection-section" style="display: none;">
		<h3><?php esc_html_e( 'Conectar WhatsApp', 'myd-delivery-pro' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Escanea este código QR con tu WhatsApp para vincular la cuenta.', 'myd-delivery-pro' ); ?>
		</p>

		<div class="qr-code-container">
			<div id="qr-code-display">
				<div class="qr-placeholder">
					<span class="dashicons dashicons-smartphone"></span>
					<p><?php esc_html_e( 'Haz clic en "Generar QR" para comenzar', 'myd-delivery-pro' ); ?></p>
				</div>
			</div>

			<div class="qr-actions">
				<button type="button" class="button button-primary" id="btn-create-instance">
					<span class="dashicons dashicons-plus-alt"></span>
					<?php esc_html_e( 'Generar QR', 'myd-delivery-pro' ); ?>
				</button>
				<button type="button" class="button button-secondary" id="btn-refresh-qr" style="display: none;">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Actualizar QR', 'myd-delivery-pro' ); ?>
				</button>
				<button type="button" class="button button-link-delete" id="btn-logout-instance" style="display: none;">
					<span class="dashicons dashicons-dismiss"></span>
					<?php esc_html_e( 'Desconectar', 'myd-delivery-pro' ); ?>
				</button>
			</div>

			<div class="qr-status-message" id="qr-status-message"></div>
		</div>

		<div class="qr-instructions">
			<h4><?php esc_html_e( 'Instrucciones:', 'myd-delivery-pro' ); ?></h4>
			<ol>
				<li><?php esc_html_e( 'Abre WhatsApp en tu teléfono', 'myd-delivery-pro' ); ?></li>
				<li><?php esc_html_e( 'Ve a Menú > Dispositivos vinculados', 'myd-delivery-pro' ); ?></li>
				<li><?php esc_html_e( 'Toca "Vincular un dispositivo"', 'myd-delivery-pro' ); ?></li>
				<li><?php esc_html_e( 'Escanea el código QR mostrado arriba', 'myd-delivery-pro' ); ?></li>
			</ol>
		</div>
	</div>

	<table class="form-table">
		<tbody>
			<!-- Toggle Activar/Desactivar -->
			<tr>
				<th scope="row">
					<label for="myd-evolution-api-enabled">
						<?php esc_html_e( 'Habilitar Evolution API', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<label class="myd-toggle-switch">
						<input
							type="checkbox"
							name="myd-evolution-api-enabled"
							id="myd-evolution-api-enabled"
							value="yes"
							<?php checked( $is_enabled, true ); ?>
						>
						<span class="slider"></span>
					</label>
					<p class="description">
						<?php esc_html_e( 'Activa el envío automático de mensajes de WhatsApp mediante Evolution API', 'myd-delivery-pro' ); ?>
					</p>
				</td>
			</tr>

			<!-- Código de País -->
			<tr>
				<th scope="row">
					<label for="myd-evolution-phone-country-code">
						<?php esc_html_e( 'Código de País', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<input
						type="text"
						name="myd-evolution-phone-country-code"
						id="myd-evolution-phone-country-code"
						value="<?php echo esc_attr( $phone_country_code ); ?>"
						class="small-text"
						placeholder="58"
					>
					<p class="description">
						<?php esc_html_e( 'Código de país para formatear teléfonos (ej: 55 para Brasil, 54 para Argentina, 58 para Venezuela)', 'myd-delivery-pro' ); ?>
					</p>
				</td>
			</tr>

			<!-- Botón Test Conexión -->
			<tr>
				<th scope="row"></th>
				<td>
					<button
						type="button"
						class="button button-secondary"
						id="myd-evolution-test-connection"
					>
						<span class="dashicons dashicons-admin-plugins"></span>
						<?php esc_html_e( 'Probar Conexión', 'myd-delivery-pro' ); ?>
					</button>
					<span id="test-connection-result" style="margin-left: 10px;"></span>
				</td>
			</tr>
		</tbody>
	</table>

	<hr>

	<!-- Eventos Automáticos -->
	<h3><?php esc_html_e( 'Eventos que disparan envío automático', 'myd-delivery-pro' ); ?></h3>
	<p class="description">
		<?php esc_html_e( 'Selecciona en qué eventos del pedido se debe enviar un mensaje automático al cliente', 'myd-delivery-pro' ); ?>
	</p>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Eventos', 'myd-delivery-pro' ); ?>
				</th>
				<td>
					<fieldset>
						<label style="display: block; margin-bottom: 8px;">
							<input
								type="checkbox"
								name="myd-evolution-auto-send-events[]"
								value="order_new"
								<?php checked( in_array( 'order_new', $auto_events, true ) ); ?>
							>
							<?php esc_html_e( 'Pedido Nuevo (Cliente realiza pedido)', 'myd-delivery-pro' ); ?>
						</label>

						<label style="display: block; margin-bottom: 8px;">
							<input
								type="checkbox"
								name="myd-evolution-auto-send-events[]"
								value="order_confirmed"
								<?php checked( in_array( 'order_confirmed', $auto_events, true ) ); ?>
							>
							<?php esc_html_e( 'Pedido Confirmado', 'myd-delivery-pro' ); ?>
						</label>

						<label style="display: block; margin-bottom: 8px;">
							<input
								type="checkbox"
								name="myd-evolution-auto-send-events[]"
								value="order_in_process"
								<?php checked( in_array( 'order_in_process', $auto_events, true ) ); ?>
							>
							<?php esc_html_e( 'En Preparación', 'myd-delivery-pro' ); ?>
						</label>

						<label style="display: block; margin-bottom: 8px;">
							<input
								type="checkbox"
								name="myd-evolution-auto-send-events[]"
								value="order_in_delivery"
								<?php checked( in_array( 'order_in_delivery', $auto_events, true ) ); ?>
							>
							<?php esc_html_e( 'En Camino / Delivery', 'myd-delivery-pro' ); ?>
						</label>

						<label style="display: block; margin-bottom: 8px;">
							<input
								type="checkbox"
								name="myd-evolution-auto-send-events[]"
								value="order_done"
								<?php checked( in_array( 'order_done', $auto_events, true ) ); ?>
							>
							<?php esc_html_e( 'Pedido Completado', 'myd-delivery-pro' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
		</tbody>
	</table>

	<hr>

	<!-- Templates de Mensajes -->
	<h3><?php esc_html_e( 'Templates de Mensajes', 'myd-delivery-pro' ); ?></h3>
	<p class="description">
		<?php esc_html_e( 'Personaliza los mensajes que se envían en cada evento. Puedes usar los tokens disponibles.', 'myd-delivery-pro' ); ?>
	</p>

	<div class="myd-templates-section">
		<!-- Template: Pedido Nuevo -->
		<div class="template-item">
			<h4><?php esc_html_e( 'Mensaje: Pedido Nuevo', 'myd-delivery-pro' ); ?></h4>
			<textarea
				name="myd-evolution-template-order-created"
				rows="5"
				class="large-text code"
			><?php echo esc_textarea( get_option( 'myd-evolution-template-order-created' ) ); ?></textarea>
		</div>

		<!-- Template: Pedido Confirmado -->
		<div class="template-item">
			<h4><?php esc_html_e( 'Mensaje: Pedido Confirmado', 'myd-delivery-pro' ); ?></h4>
			<textarea
				name="myd-evolution-template-order-confirmed"
				rows="5"
				class="large-text code"
			><?php echo esc_textarea( get_option( 'myd-evolution-template-order-confirmed' ) ); ?></textarea>
		</div>

		<!-- Template: En Preparación -->
		<div class="template-item">
			<h4><?php esc_html_e( 'Mensaje: En Preparación', 'myd-delivery-pro' ); ?></h4>
			<textarea
				name="myd-evolution-template-order-in-process"
				rows="5"
				class="large-text code"
			><?php echo esc_textarea( get_option( 'myd-evolution-template-order-in-process' ) ); ?></textarea>
		</div>

		<!-- Template: En Camino -->
		<div class="template-item">
			<h4><?php esc_html_e( 'Mensaje: En Camino / Delivery', 'myd-delivery-pro' ); ?></h4>
			<textarea
				name="myd-evolution-template-order-in-delivery"
				rows="5"
				class="large-text code"
			><?php echo esc_textarea( get_option( 'myd-evolution-template-order-in-delivery' ) ); ?></textarea>
		</div>

		<!-- Template: Completado -->
		<div class="template-item">
			<h4><?php esc_html_e( 'Mensaje: Pedido Completado', 'myd-delivery-pro' ); ?></h4>
			<textarea
				name="myd-evolution-template-order-completed"
				rows="5"
				class="large-text code"
			><?php echo esc_textarea( get_option( 'myd-evolution-template-order-completed' ) ); ?></textarea>
		</div>
	</div>

	<!-- Tokens Disponibles -->
	<div class="myd-tokens-info">
		<h4><?php esc_html_e( 'Tokens Disponibles', 'myd-delivery-pro' ); ?></h4>
		<ul>
			<li><code>{order-number}</code> - <?php esc_html_e( 'Número de pedido', 'myd-delivery-pro' ); ?></li>
			<li><code>{customer-name}</code> - <?php esc_html_e( 'Nombre del cliente', 'myd-delivery-pro' ); ?></li>
			<li><code>{customer-phone}</code> - <?php esc_html_e( 'Teléfono del cliente', 'myd-delivery-pro' ); ?></li>
			<li><code>{order-total}</code> - <?php esc_html_e( 'Total del pedido', 'myd-delivery-pro' ); ?></li>
			<li><code>{order-subtotal}</code> - <?php esc_html_e( 'Subtotal del pedido', 'myd-delivery-pro' ); ?></li>
			<li><code>{order-status}</code> - <?php esc_html_e( 'Estado actual del pedido', 'myd-delivery-pro' ); ?></li>
			<li><code>{order-track-page}</code> - <?php esc_html_e( 'Link de seguimiento', 'myd-delivery-pro' ); ?></li>
			<li><code>{business-name}</code> - <?php esc_html_e( 'Nombre del negocio', 'myd-delivery-pro' ); ?></li>
			<li><code>{customer-address}</code> - <?php esc_html_e( 'Dirección del cliente', 'myd-delivery-pro' ); ?></li>
			<li><code>{payment-method}</code> - <?php esc_html_e( 'Método de pago', 'myd-delivery-pro' ); ?></li>
			<li><code>{shipping-price}</code> - <?php esc_html_e( 'Precio del envío', 'myd-delivery-pro' ); ?></li>
		</ul>
	</div>
</div>

<style>
/* Estilos del banner de estado */
.myd-evolution-status-banner {
	background: #f5f5f5;
	border-left: 4px solid #ddd;
	padding: 15px;
	margin: 20px 0;
	border-radius: 4px;
	display: flex;
	justify-content: space-between;
	align-items: center;
	flex-wrap: wrap;
	gap: 15px;
}

.status-indicator {
	display: flex;
	align-items: center;
	gap: 10px;
	font-weight: 500;
}

.status-actions {
	display: flex;
	gap: 10px;
	align-items: center;
}

.status-dot {
	width: 12px;
	height: 12px;
	border-radius: 50%;
	background: #dc3545;
	display: inline-block;
}

.status-indicator.connected .status-dot {
	background: #28a745;
	animation: pulse 2s infinite;
}

.status-indicator.connected .myd-evolution-status-banner {
	border-left-color: #28a745;
}

@keyframes pulse {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.5; }
}

/* Toggle Switch */
.myd-toggle-switch {
	position: relative;
	display: inline-block;
	width: 50px;
	height: 24px;
}

.myd-toggle-switch input {
	opacity: 0;
	width: 0;
	height: 0;
}

.slider {
	position: absolute;
	cursor: pointer;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: #ccc;
	transition: .4s;
	border-radius: 24px;
}

.slider:before {
	position: absolute;
	content: "";
	height: 16px;
	width: 16px;
	left: 4px;
	bottom: 4px;
	background-color: white;
	transition: .4s;
	border-radius: 50%;
}

input:checked + .slider {
	background-color: #2196F3;
}

input:checked + .slider:before {
	transform: translateX(26px);
}

/* Templates */
.template-item {
	margin-bottom: 20px;
}

.template-item h4 {
	margin-bottom: 8px;
}

.template-item textarea {
	font-family: monospace;
}

.myd-tokens-info {
	background: #fff;
	border: 1px solid #ddd;
	padding: 15px;
	margin-top: 20px;
	border-radius: 4px;
}

.myd-tokens-info h4 {
	margin-top: 0;
}

.myd-tokens-info ul {
	list-style: none;
	margin: 0;
	padding: 0;
	column-count: 2;
	column-gap: 20px;
}

.myd-tokens-info li {
	padding: 5px 0;
	break-inside: avoid;
}

.myd-tokens-info code {
	background: #f0f0f0;
	padding: 2px 6px;
	border-radius: 3px;
	font-weight: 600;
}

#instances-list {
	padding: 10px;
	background: #f9f9f9;
	border-radius: 4px;
	max-height: 200px;
	overflow-y: auto;
}

#instances-list .instance-item {
	padding: 8px;
	margin: 5px 0;
	background: white;
	border: 1px solid #ddd;
	border-radius: 3px;
	cursor: pointer;
	transition: background 0.2s;
}

#instances-list .instance-item:hover {
	background: #e8f4f8;
}

#instances-list .instance-item.open {
	border-left: 3px solid #28a745;
}

#instances-list .instance-item.close {
	border-left: 3px solid #dc3545;
	opacity: 0.6;
}

#test-connection-result .dashicons {
	font-size: 16px;
	width: 16px;
	height: 16px;
	vertical-align: middle;
}

/* QR Code Section */
.myd-qr-section {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	margin: 20px 0;
}

.myd-qr-section h3 {
	margin-top: 0;
	color: #25D366;
	display: flex;
	align-items: center;
	gap: 8px;
}

.qr-code-container {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 20px;
	padding: 20px;
	background: #f9f9f9;
	border-radius: 8px;
	margin: 15px 0;
}

#qr-code-display {
	width: 280px;
	height: 280px;
	background: white;
	border: 3px solid #25D366;
	border-radius: 12px;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 10px;
	box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

#qr-code-display img {
	max-width: 100%;
	max-height: 100%;
	display: block;
}

.qr-placeholder {
	text-align: center;
	color: #666;
}

.qr-placeholder .dashicons {
	font-size: 64px;
	width: 64px;
	height: 64px;
	color: #ccc;
	margin-bottom: 10px;
}

.qr-placeholder p {
	margin: 0;
	font-size: 14px;
}

.qr-actions {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
	justify-content: center;
}

.qr-status-message {
	width: 100%;
	text-align: center;
	padding: 10px;
	border-radius: 4px;
	font-weight: 500;
	display: none;
}

.qr-status-message.success {
	background: #d4edda;
	color: #155724;
	border: 1px solid #c3e6cb;
	display: block;
}

.qr-status-message.error {
	background: #f8d7da;
	color: #721c24;
	border: 1px solid #f5c6cb;
	display: block;
}

.qr-status-message.info {
	background: #d1ecf1;
	color: #0c5460;
	border: 1px solid #bee5eb;
	display: block;
}

.qr-instructions {
	background: #f0f8ff;
	border-left: 4px solid #2196F3;
	padding: 15px;
	border-radius: 4px;
}

.qr-instructions h4 {
	margin-top: 0;
	color: #2196F3;
}

.qr-instructions ol {
	margin: 10px 0 0 20px;
	line-height: 1.8;
}

.qr-instructions li {
	padding: 4px 0;
}

/* Test connection result */
#test-connection-result {
	display: inline-block;
	font-weight: 500;
}

#test-connection-result.success {
	color: #28a745;
}

#test-connection-result.error {
	color: #dc3545;
}

/* Loading animation for QR refresh */
@keyframes spin {
	from { transform: rotate(0deg); }
	to { transform: rotate(360deg); }
}

.dashicons.spin {
	animation: spin 1s linear infinite;
}

.button .dashicons.spin {
	animation: spin 1s linear infinite;
}
</style>
