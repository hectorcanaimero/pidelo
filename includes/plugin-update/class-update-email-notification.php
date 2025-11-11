<?php

namespace MydPro\Includes\Plugin_Update;

use MydPro\Includes\License\License_Manage_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email Notifications for Plugin Updates
 *
 * Sends email to administrators when updates are available
 */
class Update_Email_Notification {

	/**
	 * Option name for email notifications setting
	 */
	const OPTION_ENABLED = 'myd_update_email_enabled';

	/**
	 * Option name for last notification sent
	 */
	const OPTION_LAST_SENT = 'myd_update_email_last_sent';

	/**
	 * Initialize email notifications
	 */
	public function __construct() {
		// Hook into update check to send emails
		add_action( 'set_site_transient_update_plugins', array( $this, 'maybe_send_notification' ) );
	}

	/**
	 * Check if email notifications are enabled
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return get_option( self::OPTION_ENABLED, false ) === '1';
	}

	/**
	 * Enable email notifications
	 */
	public static function enable() {
		update_option( self::OPTION_ENABLED, '1' );
	}

	/**
	 * Disable email notifications
	 */
	public static function disable() {
		update_option( self::OPTION_ENABLED, '0' );
	}

	/**
	 * Maybe send update notification email
	 *
	 * @param object $transient WordPress update transient
	 */
	public function maybe_send_notification( $transient ) {
		// Check if notifications are enabled
		if ( ! self::is_enabled() ) {
			return;
		}

		// Check if there's an update for our plugin
		if ( ! isset( $transient->response[ MYD_PLUGIN_BASENAME ] ) ) {
			return;
		}

		$update = $transient->response[ MYD_PLUGIN_BASENAME ];
		$new_version = $update->new_version;

		// Check if we already sent notification for this version
		$last_sent = get_option( self::OPTION_LAST_SENT, '' );
		if ( $last_sent === $new_version ) {
			return; // Already notified about this version
		}

		// Check license validity
		$license_data = License_Manage_Data::get_transient();
		$license_valid = $license_data && isset( $license_data['status'] ) && $license_data['status'] === 'active';

		if ( ! $license_valid ) {
			return; // Don't send if license is invalid
		}

		// Send the email
		$sent = $this->send_update_email( $new_version );

		if ( $sent ) {
			// Update last sent version
			update_option( self::OPTION_LAST_SENT, $new_version );
		}
	}

	/**
	 * Send update notification email
	 *
	 * @param string $new_version New version number
	 * @return bool True if email was sent successfully
	 */
	private function send_update_email( $new_version ) {
		$current_version = MYD_CURRENT_VERSION;
		$site_name = get_bloginfo( 'name' );
		$site_url = get_site_url();

		// Get admin email(s)
		$admin_email = get_option( 'admin_email' );
		$to = array( $admin_email );

		// Add other admins
		$admins = get_users( array( 'role' => 'administrator' ) );
		foreach ( $admins as $admin ) {
			if ( $admin->user_email !== $admin_email ) {
				$to[] = $admin->user_email;
			}
		}

		// Subject
		$subject = sprintf(
			/* translators: %1$s: plugin name, %2$s: version number */
			__( '[%1$s] Nueva actualización disponible - v%2$s', 'myd-delivery-pro' ),
			$site_name,
			$new_version
		);

		// Get changelog
		$update_checker = new Plugin_Update();
		$update_info = $update_checker->request();
		$changelog = '';

		if ( is_array( $update_info ) && isset( $update_info['sections']['changelog'] ) ) {
			// Convert HTML to plain text
			$changelog = wp_strip_all_tags( $update_info['sections']['changelog'] );
			$changelog = trim( $changelog );
		}

		// Email body (HTML)
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					line-height: 1.6;
					color: #333;
					max-width: 600px;
					margin: 0 auto;
					padding: 20px;
				}
				.header {
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					color: white;
					padding: 30px;
					border-radius: 8px 8px 0 0;
					text-align: center;
				}
				.header h1 {
					margin: 0;
					font-size: 24px;
				}
				.content {
					background: #f9f9f9;
					padding: 30px;
					border-radius: 0 0 8px 8px;
				}
				.version-box {
					background: white;
					padding: 20px;
					border-radius: 6px;
					margin: 20px 0;
					border-left: 4px solid #667eea;
				}
				.version-box h3 {
					margin-top: 0;
					color: #667eea;
				}
				.version-info {
					display: flex;
					justify-content: space-around;
					margin: 20px 0;
				}
				.version-info div {
					text-align: center;
				}
				.version-info label {
					display: block;
					font-size: 12px;
					color: #666;
					text-transform: uppercase;
					margin-bottom: 5px;
				}
				.version-info span {
					display: block;
					font-size: 24px;
					font-weight: bold;
					color: #333;
				}
				.changelog {
					background: white;
					padding: 20px;
					border-radius: 6px;
					margin: 20px 0;
					white-space: pre-line;
					line-height: 1.8;
				}
				.button {
					display: inline-block;
					padding: 12px 30px;
					background: #667eea;
					color: white;
					text-decoration: none;
					border-radius: 4px;
					margin: 10px 5px;
					font-weight: 600;
				}
				.footer {
					text-align: center;
					margin-top: 30px;
					padding-top: 20px;
					border-top: 1px solid #ddd;
					color: #666;
					font-size: 12px;
				}
			</style>
		</head>
		<body>
			<div class="header">
				<h1>🎉 <?php esc_html_e( 'Nueva Actualización Disponible', 'myd-delivery-pro' ); ?></h1>
			</div>

			<div class="content">
				<p><?php esc_html_e( 'Hola,', 'myd-delivery-pro' ); ?></p>

				<p>
					<?php
					printf(
						/* translators: %s: site name */
						esc_html__( 'Hay una nueva versión de MyD Delivery Pro disponible para tu sitio %s.', 'myd-delivery-pro' ),
						'<strong>' . esc_html( $site_name ) . '</strong>'
					);
					?>
				</p>

				<div class="version-info">
					<div>
						<label><?php esc_html_e( 'Versión Actual', 'myd-delivery-pro' ); ?></label>
						<span><?php echo esc_html( $current_version ); ?></span>
					</div>
					<div>
						<label><?php esc_html_e( 'Nueva Versión', 'myd-delivery-pro' ); ?></label>
						<span style="color: #667eea;"><?php echo esc_html( $new_version ); ?></span>
					</div>
				</div>

				<?php if ( ! empty( $changelog ) ) : ?>
				<div class="version-box">
					<h3>✨ <?php esc_html_e( 'Novedades en esta versión', 'myd-delivery-pro' ); ?></h3>
					<div class="changelog">
						<?php echo esc_html( $changelog ); ?>
					</div>
				</div>
				<?php endif; ?>

				<div style="text-align: center; margin: 30px 0;">
					<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button">
						<?php esc_html_e( 'Actualizar Ahora', 'myd-delivery-pro' ); ?>
					</a>
					<a href="<?php echo esc_url( 'https://github.com/hectorcanaimero/pidelo/releases/tag/v' . $new_version ); ?>" class="button" style="background: #6c757d;">
						<?php esc_html_e( 'Ver Changelog Completo', 'myd-delivery-pro' ); ?>
					</a>
				</div>

				<p style="color: #666; font-size: 14px;">
					<?php esc_html_e( '💡 Recomendamos hacer un respaldo antes de actualizar.', 'myd-delivery-pro' ); ?>
				</p>
			</div>

			<div class="footer">
				<p>
					<?php
					printf(
						/* translators: %s: site URL */
						esc_html__( 'Este email fue enviado automáticamente desde %s', 'myd-delivery-pro' ),
						'<a href="' . esc_url( $site_url ) . '">' . esc_html( $site_url ) . '</a>'
					);
					?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=myd-update-settings' ) ); ?>">
						<?php esc_html_e( 'Desactivar notificaciones por email', 'myd-delivery-pro' ); ?>
					</a>
				</p>
			</div>
		</body>
		</html>
		<?php
		$message = ob_get_clean();

		// Email headers
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $site_name . ' <' . $admin_email . '>',
		);

		// Send email
		$sent = wp_mail( $to, $subject, $message, $headers );

		// Log for debugging
		if ( ! $sent ) {
			error_log( 'MyD Update Email: Failed to send notification for version ' . $new_version );
		} else {
			error_log( 'MyD Update Email: Sent notification for version ' . $new_version . ' to ' . count( $to ) . ' recipients' );
		}

		return $sent;
	}

	/**
	 * Send test email
	 *
	 * @return bool
	 */
	public static function send_test_email() {
		$admin_email = get_option( 'admin_email' );
		$site_name = get_bloginfo( 'name' );
		$current_version = MYD_CURRENT_VERSION;

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Email de prueba - Notificaciones de actualización', 'myd-delivery-pro' ),
			$site_name
		);

		$message = sprintf(
			__( 'Este es un email de prueba del sistema de notificaciones de MyD Delivery Pro.

Si recibes este email, significa que las notificaciones están configuradas correctamente.

Versión actual: %1$s
Sitio: %2$s

Puedes desactivar estas notificaciones en: %3$s', 'myd-delivery-pro' ),
			$current_version,
			get_site_url(),
			admin_url( 'admin.php?page=myd-update-settings' )
		);

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
		);

		return wp_mail( $admin_email, $subject, $message, $headers );
	}
}
