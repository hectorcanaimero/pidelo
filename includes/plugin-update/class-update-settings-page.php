<?php

namespace MydPro\Includes\Plugin_Update;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update Settings Page
 *
 * Admin page for configuring update notifications and history
 */
class Update_Settings_Page {

	/**
	 * Page slug
	 */
	const PAGE_SLUG = 'myd-update-settings';

	/**
	 * Initialize settings page
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add settings page to admin menu
	 */
	public function add_menu_page() {
		add_submenu_page(
			'myd-delivery-dashoboard',
			__( 'Configuración de Actualizaciones', 'myd-delivery-pro' ),
			__( 'Actualizaciones', 'myd-delivery-pro' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue page assets
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, self::PAGE_SLUG ) === false ) {
			return;
		}

		wp_enqueue_style(
			'myd-update-settings',
			MYD_PLUGN_URL . 'assets/css/admin.min.css',
			array(),
			MYD_CURRENT_VERSION
		);
	}

	/**
	 * Handle form actions
	 */
	public function handle_actions() {
		if ( ! isset( $_POST['myd_update_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'myd_update_settings' );

		$action = sanitize_text_field( $_POST['myd_update_action'] );

		switch ( $action ) {
			case 'save_settings':
				$this->save_settings();
				break;

			case 'test_email':
				$this->test_email();
				break;

			case 'clear_history':
				Update_History::clear_history();
				add_settings_error(
					'myd_update_settings',
					'history_cleared',
					__( 'Historial de actualizaciones limpiado exitosamente.', 'myd-delivery-pro' ),
					'success'
				);
				break;

			case 'export_history':
				$this->export_history();
				break;
		}
	}

	/**
	 * Save settings
	 */
	private function save_settings() {
		// Email notifications
		$email_enabled = isset( $_POST['email_notifications'] ) ? '1' : '0';
		update_option( Update_Email_Notification::OPTION_ENABLED, $email_enabled );

		// Auto-update
		$auto_update = isset( $_POST['auto_update'] ) ? '1' : '0';
		update_option( 'myd_auto_update_enabled', $auto_update );

		add_settings_error(
			'myd_update_settings',
			'settings_saved',
			__( 'Configuración guardada exitosamente.', 'myd-delivery-pro' ),
			'success'
		);
	}

	/**
	 * Send test email
	 */
	private function test_email() {
		$sent = Update_Email_Notification::send_test_email();

		if ( $sent ) {
			add_settings_error(
				'myd_update_settings',
				'test_email_sent',
				__( 'Email de prueba enviado exitosamente. Revisa tu bandeja de entrada.', 'myd-delivery-pro' ),
				'success'
			);
		} else {
			add_settings_error(
				'myd_update_settings',
				'test_email_failed',
				__( 'Error al enviar email de prueba. Verifica la configuración de email de WordPress.', 'myd-delivery-pro' ),
				'error'
			);
		}
	}

	/**
	 * Export history as CSV
	 */
	private function export_history() {
		$csv = Update_History::export_csv();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=myd-update-history-' . date( 'Y-m-d' ) . '.csv' );
		echo $csv;
		exit;
	}

	/**
	 * Render settings page
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$email_enabled = Update_Email_Notification::is_enabled();
		$auto_update_enabled = get_option( 'myd_auto_update_enabled', false ) === '1';
		$history = Update_History::get_history();
		$stats = Update_History::get_statistics();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Configuración de Actualizaciones', 'myd-delivery-pro' ); ?></h1>

			<?php settings_errors( 'myd_update_settings' ); ?>

			<style>
				.myd-settings-container {
					max-width: 1200px;
					margin-top: 20px;
				}
				.myd-settings-section {
					background: white;
					padding: 20px;
					margin-bottom: 20px;
					border: 1px solid #ccd0d4;
					box-shadow: 0 1px 1px rgba(0,0,0,.04);
				}
				.myd-settings-section h2 {
					margin-top: 0;
					border-bottom: 1px solid #eee;
					padding-bottom: 10px;
				}
				.myd-setting-row {
					padding: 15px 0;
					border-bottom: 1px solid #f0f0f1;
				}
				.myd-setting-row:last-child {
					border-bottom: none;
				}
				.myd-setting-row label {
					font-weight: 600;
					display: block;
					margin-bottom: 5px;
				}
				.myd-setting-row .description {
					color: #646970;
					font-size: 13px;
					margin-top: 5px;
				}
				.myd-stats-grid {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
					gap: 15px;
					margin: 20px 0;
				}
				.myd-stat-box {
					background: #f6f7f7;
					padding: 20px;
					border-radius: 4px;
					text-align: center;
					border-left: 4px solid #2271b1;
				}
				.myd-stat-box.success {
					border-color: #00a32a;
				}
				.myd-stat-box.error {
					border-color: #d63638;
				}
				.myd-stat-box .number {
					font-size: 32px;
					font-weight: bold;
					color: #2271b1;
					display: block;
					margin-bottom: 5px;
				}
				.myd-stat-box.success .number {
					color: #00a32a;
				}
				.myd-stat-box.error .number {
					color: #d63638;
				}
				.myd-stat-box .label {
					font-size: 13px;
					color: #646970;
					text-transform: uppercase;
				}
				.myd-history-table {
					width: 100%;
					margin-top: 15px;
				}
				.myd-history-table th {
					text-align: left;
					padding: 10px;
					background: #f6f7f7;
					border-bottom: 2px solid #ddd;
				}
				.myd-history-table td {
					padding: 10px;
					border-bottom: 1px solid #f0f0f1;
				}
				.myd-history-table tr:hover {
					background: #f9f9f9;
				}
				.myd-badge {
					display: inline-block;
					padding: 3px 8px;
					border-radius: 3px;
					font-size: 11px;
					font-weight: 600;
				}
				.myd-badge.success {
					background: #d4edda;
					color: #155724;
				}
				.myd-badge.error {
					background: #f8d7da;
					color: #721c24;
				}
			</style>

			<div class="myd-settings-container">
				<!-- Notification Settings -->
				<div class="myd-settings-section">
					<h2>⚙️ <?php esc_html_e( 'Configuración de Notificaciones', 'myd-delivery-pro' ); ?></h2>

					<form method="post" action="">
						<?php wp_nonce_field( 'myd_update_settings' ); ?>
						<input type="hidden" name="myd_update_action" value="save_settings">

						<div class="myd-setting-row">
							<label>
								<input type="checkbox" name="email_notifications" value="1" <?php checked( $email_enabled, true ); ?>>
								<?php esc_html_e( 'Enviar notificaciones por email', 'myd-delivery-pro' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Recibe un email cuando haya una nueva actualización disponible. Los emails se envían a todos los administradores del sitio.', 'myd-delivery-pro' ); ?>
							</p>
						</div>

						<div class="myd-setting-row">
							<label>
								<input type="checkbox" name="auto_update" value="1" <?php checked( $auto_update_enabled, true ); ?>>
								<?php esc_html_e( 'Habilitar actualizaciones automáticas', 'myd-delivery-pro' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'WordPress instalará automáticamente las actualizaciones del plugin. Recomendamos tener backups automáticos habilitados.', 'myd-delivery-pro' ); ?>
								<strong><?php esc_html_e( '⚠️ Usa con precaución en sitios de producción.', 'myd-delivery-pro' ); ?></strong>
							</p>
						</div>

						<p class="submit">
							<button type="submit" class="button button-primary">
								<?php esc_html_e( 'Guardar Configuración', 'myd-delivery-pro' ); ?>
							</button>
						</p>
					</form>

					<?php if ( $email_enabled ) : ?>
					<hr>
					<form method="post" action="">
						<?php wp_nonce_field( 'myd_update_settings' ); ?>
						<input type="hidden" name="myd_update_action" value="test_email">
						<p>
							<button type="submit" class="button button-secondary">
								<?php esc_html_e( '📧 Enviar Email de Prueba', 'myd-delivery-pro' ); ?>
							</button>
							<span class="description" style="margin-left: 10px;">
								<?php esc_html_e( 'Envía un email de prueba para verificar la configuración.', 'myd-delivery-pro' ); ?>
							</span>
						</p>
					</form>
					<?php endif; ?>
				</div>

				<!-- Statistics -->
				<div class="myd-settings-section">
					<h2>📊 <?php esc_html_e( 'Estadísticas de Actualizaciones', 'myd-delivery-pro' ); ?></h2>

					<div class="myd-stats-grid">
						<div class="myd-stat-box">
							<span class="number"><?php echo esc_html( $stats['total'] ); ?></span>
							<span class="label"><?php esc_html_e( 'Total Actualizaciones', 'myd-delivery-pro' ); ?></span>
						</div>
						<div class="myd-stat-box success">
							<span class="number"><?php echo esc_html( $stats['successful'] ); ?></span>
							<span class="label"><?php esc_html_e( 'Exitosas', 'myd-delivery-pro' ); ?></span>
						</div>
						<div class="myd-stat-box error">
							<span class="number"><?php echo esc_html( $stats['failed'] ); ?></span>
							<span class="label"><?php esc_html_e( 'Fallidas', 'myd-delivery-pro' ); ?></span>
						</div>
						<div class="myd-stat-box">
							<span class="number"><?php echo esc_html( $stats['success_rate'] ); ?>%</span>
							<span class="label"><?php esc_html_e( 'Tasa de Éxito', 'myd-delivery-pro' ); ?></span>
						</div>
					</div>

					<?php if ( $stats['last_update'] ) : ?>
					<p>
						<strong><?php esc_html_e( 'Última actualización:', 'myd-delivery-pro' ); ?></strong>
						<?php
						echo esc_html( sprintf(
							/* translators: %1$s: version, %2$s: date */
							__( 'Versión %1$s el %2$s', 'myd-delivery-pro' ),
							$stats['last_update']['version'],
							date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $stats['last_update']['timestamp'] )
						) );
						?>
					</p>
					<?php endif; ?>
				</div>

				<!-- Update History -->
				<div class="myd-settings-section">
					<h2>📜 <?php esc_html_e( 'Historial de Actualizaciones', 'myd-delivery-pro' ); ?></h2>

					<?php if ( ! empty( $history ) ) : ?>
						<table class="myd-history-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Versión', 'myd-delivery-pro' ); ?></th>
									<th><?php esc_html_e( 'Fecha', 'myd-delivery-pro' ); ?></th>
									<th><?php esc_html_e( 'Estado', 'myd-delivery-pro' ); ?></th>
									<th><?php esc_html_e( 'Usuario', 'myd-delivery-pro' ); ?></th>
									<th><?php esc_html_e( 'Entorno', 'myd-delivery-pro' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( array_slice( $history, 0, 20 ) as $entry ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $entry['version'] ); ?></strong></td>
									<td>
										<?php
										echo esc_html( date_i18n(
											get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
											$entry['timestamp']
										) );
										?>
										<br>
										<small style="color: #999;">
											<?php
											/* translators: %s: time ago */
											printf( esc_html__( 'Hace %s', 'myd-delivery-pro' ), human_time_diff( $entry['timestamp'], time() ) );
											?>
										</small>
									</td>
									<td>
										<?php if ( $entry['success'] ) : ?>
											<span class="myd-badge success">✓ <?php esc_html_e( 'Exitosa', 'myd-delivery-pro' ); ?></span>
										<?php else : ?>
											<span class="myd-badge error">✗ <?php esc_html_e( 'Fallida', 'myd-delivery-pro' ); ?></span>
											<?php if ( ! empty( $entry['error'] ) ) : ?>
												<br><small style="color: #d63638;"><?php echo esc_html( $entry['error'] ); ?></small>
											<?php endif; ?>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $entry['user_login'] ); ?></td>
									<td>
										<small>
											WP <?php echo esc_html( $entry['wp_version'] ); ?><br>
											PHP <?php echo esc_html( $entry['php_version'] ); ?>
										</small>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<p class="submit">
							<form method="post" action="" style="display: inline;">
								<?php wp_nonce_field( 'myd_update_settings' ); ?>
								<input type="hidden" name="myd_update_action" value="export_history">
								<button type="submit" class="button button-secondary">
									<?php esc_html_e( '📥 Exportar como CSV', 'myd-delivery-pro' ); ?>
								</button>
							</form>

							<form method="post" action="" style="display: inline;" onsubmit="return confirm('<?php esc_attr_e( '¿Estás seguro de que quieres limpiar el historial?', 'myd-delivery-pro' ); ?>');">
								<?php wp_nonce_field( 'myd_update_settings' ); ?>
								<input type="hidden" name="myd_update_action" value="clear_history">
								<button type="submit" class="button button-secondary">
									<?php esc_html_e( '🗑️ Limpiar Historial', 'myd-delivery-pro' ); ?>
								</button>
							</form>
						</p>

					<?php else : ?>
						<p><?php esc_html_e( 'No hay historial de actualizaciones aún.', 'myd-delivery-pro' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}
}
