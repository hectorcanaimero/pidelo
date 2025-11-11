<?php

namespace MydPro\Includes\Plugin_Update;

use MydPro\Includes\License\License_Manage_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Widget for Plugin Updates
 *
 * Shows update status and information in WordPress dashboard
 */
class Update_Dashboard_Widget {

	/**
	 * Initialize the widget
	 */
	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
	}

	/**
	 * Register the dashboard widget
	 */
	public function add_dashboard_widget() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'myd_update_status_widget',
			__( 'MyD Delivery Pro - Estado de Actualización', 'myd-delivery-pro' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Render the dashboard widget content
	 */
	public function render_widget() {
		$update_data = $this->get_update_status();
		$license_data = License_Manage_Data::get_transient();

		?>
		<div class="myd-update-widget">
			<style>
				.myd-update-widget {
					font-size: 13px;
				}
				.myd-update-status {
					display: flex;
					align-items: center;
					padding: 15px;
					background: #f6f7f7;
					border-radius: 4px;
					margin-bottom: 15px;
				}
				.myd-update-status.up-to-date {
					background: #d4edda;
					border-left: 4px solid #28a745;
				}
				.myd-update-status.update-available {
					background: #fff3cd;
					border-left: 4px solid #ffc107;
				}
				.myd-update-status.error {
					background: #f8d7da;
					border-left: 4px solid #dc3545;
				}
				.myd-update-icon {
					font-size: 24px;
					margin-right: 15px;
				}
				.myd-update-content {
					flex: 1;
				}
				.myd-update-content h4 {
					margin: 0 0 5px 0;
					font-size: 14px;
				}
				.myd-update-content p {
					margin: 0;
					color: #666;
				}
				.myd-version-info {
					display: flex;
					gap: 20px;
					padding: 10px 0;
					border-top: 1px solid #ddd;
					border-bottom: 1px solid #ddd;
					margin: 15px 0;
				}
				.myd-version-box {
					flex: 1;
				}
				.myd-version-box label {
					display: block;
					font-size: 11px;
					color: #666;
					text-transform: uppercase;
					margin-bottom: 5px;
				}
				.myd-version-box span {
					display: block;
					font-size: 18px;
					font-weight: 600;
					color: #2271b1;
				}
				.myd-features-list {
					margin: 10px 0;
					padding-left: 20px;
				}
				.myd-features-list li {
					margin: 5px 0;
				}
				.myd-widget-actions {
					display: flex;
					gap: 10px;
					margin-top: 15px;
				}
				.myd-license-warning {
					background: #fff3cd;
					border-left: 4px solid #ffc107;
					padding: 10px;
					margin-bottom: 15px;
				}
			</style>

			<?php if ( $update_data['license_valid'] === false ) : ?>
				<div class="myd-license-warning">
					<p>
						<strong><?php esc_html_e( '⚠️ Licencia Requerida', 'myd-delivery-pro' ); ?></strong><br>
						<?php esc_html_e( 'Activa tu licencia para recibir actualizaciones.', 'myd-delivery-pro' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=myd-license' ) ); ?>">
							<?php esc_html_e( 'Activar ahora', 'myd-delivery-pro' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<div class="myd-update-status <?php echo esc_attr( $update_data['status_class'] ); ?>">
				<div class="myd-update-icon">
					<?php echo $update_data['icon']; ?>
				</div>
				<div class="myd-update-content">
					<h4><?php echo esc_html( $update_data['title'] ); ?></h4>
					<p><?php echo esc_html( $update_data['message'] ); ?></p>
				</div>
			</div>

			<div class="myd-version-info">
				<div class="myd-version-box">
					<label><?php esc_html_e( 'Versión Actual', 'myd-delivery-pro' ); ?></label>
					<span><?php echo esc_html( $update_data['current_version'] ); ?></span>
				</div>
				<?php if ( $update_data['has_update'] ) : ?>
				<div class="myd-version-box">
					<label><?php esc_html_e( 'Versión Disponible', 'myd-delivery-pro' ); ?></label>
					<span style="color: #d63638;"><?php echo esc_html( $update_data['new_version'] ); ?></span>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( $update_data['has_update'] && ! empty( $update_data['features'] ) ) : ?>
				<div class="myd-new-features">
					<h4><?php esc_html_e( '✨ Novedades en esta versión:', 'myd-delivery-pro' ); ?></h4>
					<ul class="myd-features-list">
						<?php foreach ( $update_data['features'] as $feature ) : ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="myd-widget-actions">
				<?php if ( $update_data['has_update'] && $update_data['license_valid'] ) : ?>
					<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Actualizar Ahora', 'myd-delivery-pro' ); ?>
					</a>
					<a href="<?php echo esc_url( $update_data['changelog_url'] ); ?>" class="button button-secondary" target="_blank">
						<?php esc_html_e( 'Ver Changelog Completo', 'myd-delivery-pro' ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( admin_url( 'plugins.php?force-check=1' ) ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Verificar Actualizaciones', 'myd-delivery-pro' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php
			// Show last check time
			$last_check = get_transient( 'mydpro-update-data' );
			if ( $last_check !== false ) {
				$last_check_time = get_option( '_transient_timeout_mydpro-update-data' );
				if ( $last_check_time ) {
					$checked_ago = human_time_diff( time() - ( 12 * HOUR_IN_SECONDS ), time() );
					echo '<p style="margin-top: 15px; color: #999; font-size: 11px;">';
					printf(
						/* translators: %s: time ago */
						esc_html__( 'Última verificación: hace %s', 'myd-delivery-pro' ),
						esc_html( $checked_ago )
					);
					echo '</p>';
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get update status information
	 *
	 * @return array Update status data
	 */
	private function get_update_status() {
		$current_version = MYD_CURRENT_VERSION;
		$license_data = License_Manage_Data::get_transient();
		$license_valid = $license_data && isset( $license_data['status'] ) && $license_data['status'] === 'active';

		// Get update info
		$update_checker = new Plugin_Update();
		$update_info = $update_checker->request();

		$has_update = false;
		$new_version = '';
		$features = array();

		if ( is_array( $update_info ) && isset( $update_info['version'] ) ) {
			$new_version = $update_info['version'];
			$has_update = version_compare( $current_version, $new_version, '<' );

			// Extract features from changelog
			if ( $has_update && isset( $update_info['sections']['changelog'] ) ) {
				$features = $this->extract_features_from_changelog( $update_info['sections']['changelog'] );
			}
		}

		// Determine status
		if ( ! $license_valid ) {
			$status = array(
				'status_class' => 'error',
				'icon' => '🔒',
				'title' => __( 'Licencia Requerida', 'myd-delivery-pro' ),
				'message' => __( 'Activa tu licencia para recibir actualizaciones.', 'myd-delivery-pro' ),
			);
		} elseif ( $has_update ) {
			$status = array(
				'status_class' => 'update-available',
				'icon' => '🔔',
				'title' => __( 'Actualización Disponible', 'myd-delivery-pro' ),
				'message' => sprintf(
					/* translators: %s: new version number */
					__( 'Hay una nueva versión disponible. ¡Actualiza para obtener las últimas mejoras!', 'myd-delivery-pro' ),
					$new_version
				),
			);
		} else {
			$status = array(
				'status_class' => 'up-to-date',
				'icon' => '✅',
				'title' => __( 'Plugin Actualizado', 'myd-delivery-pro' ),
				'message' => __( 'Estás usando la última versión disponible.', 'myd-delivery-pro' ),
			);
		}

		return array_merge(
			$status,
			array(
				'current_version' => $current_version,
				'new_version' => $new_version,
				'has_update' => $has_update,
				'license_valid' => $license_valid,
				'features' => $features,
				'changelog_url' => 'https://github.com/hectorcanaimero/pidelo/releases/tag/v' . $new_version,
			)
		);
	}

	/**
	 * Extract features from HTML changelog
	 *
	 * @param string $changelog HTML changelog content
	 * @return array Array of feature strings
	 */
	private function extract_features_from_changelog( $changelog ) {
		$features = array();

		// Parse HTML to extract list items
		if ( preg_match_all( '/<li>(.*?)<\/li>/s', $changelog, $matches ) ) {
			$features = array_slice( array_map( 'strip_tags', $matches[1] ), 0, 5 ); // Max 5 features
		}

		return $features;
	}
}
