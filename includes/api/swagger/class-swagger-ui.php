<?php
/**
 * Swagger UI Integration
 *
 * @package MydPro
 * @subpackage Api
 * @since 2.4.0
 */

namespace MydPro\Includes\Api\Swagger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Swagger UI Class
 *
 * Provides Swagger UI interface for API documentation
 */
class Swagger_UI {
	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_swagger_routes' ] );
		add_action( 'admin_menu', [ $this, 'add_swagger_menu' ] );
	}

	/**
	 * Register Swagger routes
	 */
	public function register_swagger_routes() {
		// Serve OpenAPI spec as JSON
		\register_rest_route(
			'myd-delivery/v1',
			'/swagger.json',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_openapi_spec' ],
					'permission_callback' => '__return_true', // Public
				),
			)
		);
	}

	/**
	 * Get OpenAPI specification as YAML
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_openapi_spec( $request ) {
		$yaml_file = MYD_PLUGIN_PATH . 'docs/api/openapi.yaml';

		if ( ! file_exists( $yaml_file ) ) {
			return new \WP_Error(
				'spec_not_found',
				__( 'OpenAPI specification not found', 'myd-delivery-pro' ),
				array( 'status' => 404 )
			);
		}

		// Read YAML content
		$yaml_content = file_get_contents( $yaml_file );

		// Return raw YAML with proper headers
		header( 'Content-Type: text/yaml; charset=utf-8' );
		header( 'Access-Control-Allow-Origin: *' );
		echo $yaml_content;
		exit;
	}

	/**
	 * Add Swagger UI menu in WordPress admin
	 */
	public function add_swagger_menu() {
		add_menu_page(
			__( 'API Documentation', 'myd-delivery-pro' ),
			__( 'API Docs', 'myd-delivery-pro' ),
			'manage_options',
			'myd-api-docs',
			[ $this, 'render_swagger_ui' ],
			'dashicons-book-alt',
			80
		);
	}

	/**
	 * Render Swagger UI page
	 */
	public function render_swagger_ui() {
		// Use REST endpoint instead of static file
		$yaml_url = rest_url( 'myd-delivery/v1/swagger.json' );
		?>
		<!DOCTYPE html>
		<html lang="es">
		<head>
			<meta charset="UTF-8">
			<title>MyD Delivery Pro - API Documentation</title>
			<link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui.css">
			<style>
				html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
				*, *:before, *:after { box-sizing: inherit; }
				body { margin: 0; padding: 0; }
				#swagger-ui { max-width: 1460px; margin: 0 auto; }
				.swagger-ui .topbar { display: none; }
			</style>
		</head>
		<body>
			<div id="swagger-ui"></div>

			<script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-bundle.js"></script>
			<script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js"></script>
			<script>
				window.onload = function() {
					console.log('Loading Swagger UI from:', "<?php echo esc_url( $yaml_url ); ?>");

					const ui = SwaggerUIBundle({
						url: "<?php echo esc_url( $yaml_url ); ?>",
						dom_id: '#swagger-ui',
						deepLinking: true,
						presets: [
							SwaggerUIBundle.presets.apis,
							SwaggerUIStandalonePreset
						],
						plugins: [
							SwaggerUIBundle.plugins.DownloadUrl
						],
						layout: "StandaloneLayout",
						defaultModelsExpandDepth: 1,
						defaultModelExpandDepth: 1,
						docExpansion: "list",
						filter: true,
						validatorUrl: null,
						onComplete: function() {
							console.log('Swagger UI loaded successfully');
						},
						onFailure: function(error) {
							console.error('Failed to load Swagger UI:', error);
							document.getElementById('swagger-ui').innerHTML =
								'<div style="padding: 20px; color: red;">' +
								'<h2>Error al cargar la documentación</h2>' +
								'<p>Error: ' + error + '</p>' +
								'<p>URL: <?php echo esc_url( $yaml_url ); ?></p>' +
								'<p>Revisa la consola del navegador para más detalles.</p>' +
								'</div>';
						}
					});

					window.ui = ui;
				};
			</script>
		</body>
		</html>
		<?php
	}
}

new Swagger_UI();
