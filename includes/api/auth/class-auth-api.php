<?php
/**
 * Authentication REST API endpoints
 *
 * @package MydPro
 * @subpackage Api
 * @since 2.3.9
 */

namespace MydPro\Includes\Api\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auth API Class
 *
 * Handles JWT authentication for mobile apps and external integrations
 */
class Auth_Api {
	/**
	 * JWT Secret Key
	 *
	 * @var string
	 */
	private $secret_key;

	/**
	 * Token expiration time (24 hours)
	 *
	 * @var int
	 */
	private $token_expiration = 86400;

	/**
	 * Construct the class.
	 */
	public function __construct() {
		// Generate secret key from WordPress salts
		$this->secret_key = wp_hash( AUTH_KEY . SECURE_AUTH_KEY );

		add_action( 'rest_api_init', [ $this, 'register_auth_routes' ] );
		add_filter( 'determine_current_user', [ $this, 'determine_current_user' ], 20 );
	}

	/**
	 * Register authentication routes
	 */
	public function register_auth_routes() {
		// POST /auth/login - Login and get JWT token
		\register_rest_route(
			'myd-delivery/v1',
			'/auth/login',
			array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'login' ],
					'permission_callback' => '__return_true',
					'args' => array(
						'username' => array(
							'description' => __( 'Username or email', 'myd-delivery-pro' ),
							'type' => 'string',
							'required' => true,
						),
						'password' => array(
							'description' => __( 'User password', 'myd-delivery-pro' ),
							'type' => 'string',
							'required' => true,
						),
					),
				),
			)
		);

		// POST /auth/refresh - Refresh JWT token
		\register_rest_route(
			'myd-delivery/v1',
			'/auth/refresh',
			array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'refresh_token' ],
					'permission_callback' => [ $this, 'check_jwt_token' ],
				),
			)
		);

		// GET /auth/validate - Validate JWT token
		\register_rest_route(
			'myd-delivery/v1',
			'/auth/validate',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'validate_token' ],
					'permission_callback' => [ $this, 'check_jwt_token' ],
				),
			)
		);

		// GET /auth/me - Get current user info
		\register_rest_route(
			'myd-delivery/v1',
			'/auth/me',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_current_user' ],
					'permission_callback' => [ $this, 'check_jwt_token' ],
				),
			)
		);
	}

	/**
	 * Login endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function login( $request ) {
		$username = sanitize_text_field( $request['username'] );
		$password = $request['password'];

		// Authenticate user
		$user = wp_authenticate( $username, $password );

		if ( is_wp_error( $user ) ) {
			return new \WP_Error(
				'invalid_credentials',
				__( 'Invalid username or password', 'myd-delivery-pro' ),
				array( 'status' => 401 )
			);
		}

		// Generate JWT token
		$token = $this->generate_token( $user );

		// Prepare user data
		$user_data = $this->prepare_user_data( $user );

		$response = array(
			'success' => true,
			'token' => $token,
			'user' => $user_data,
			'expires_in' => $this->token_expiration,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Refresh token endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function refresh_token( $request ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired token', 'myd-delivery-pro' ),
				array( 'status' => 401 )
			);
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return new \WP_Error(
				'user_not_found',
				__( 'User not found', 'myd-delivery-pro' ),
				array( 'status' => 404 )
			);
		}

		// Generate new token
		$token = $this->generate_token( $user );

		$response = array(
			'success' => true,
			'token' => $token,
			'expires_in' => $this->token_expiration,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Validate token endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function validate_token( $request ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return new \WP_Error(
				'invalid_token',
				__( 'Invalid or expired token', 'myd-delivery-pro' ),
				array( 'status' => 401 )
			);
		}

		$user = get_user_by( 'id', $user_id );
		$user_data = $this->prepare_user_data( $user );

		$response = array(
			'valid' => true,
			'user' => $user_data,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get current user endpoint
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_current_user( $request ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return new \WP_Error(
				'not_authenticated',
				__( 'User not authenticated', 'myd-delivery-pro' ),
				array( 'status' => 401 )
			);
		}

		$user = get_user_by( 'id', $user_id );
		$user_data = $this->prepare_user_data( $user );

		return rest_ensure_response( $user_data );
	}

	/**
	 * Generate JWT token
	 *
	 * @param \WP_User $user
	 * @return string
	 */
	private function generate_token( $user ) {
		$issued_at = time();
		$expiration = $issued_at + $this->token_expiration;

		$payload = array(
			'iss' => get_bloginfo( 'url' ),
			'iat' => $issued_at,
			'exp' => $expiration,
			'data' => array(
				'user_id' => $user->ID,
				'user_login' => $user->user_login,
				'user_email' => $user->user_email,
			),
		);

		return $this->encode_jwt( $payload );
	}

	/**
	 * Encode JWT token (simple implementation)
	 *
	 * @param array $payload
	 * @return string
	 */
	private function encode_jwt( $payload ) {
		$header = array(
			'typ' => 'JWT',
			'alg' => 'HS256',
		);

		$segments = array();
		$segments[] = $this->base64url_encode( json_encode( $header ) );
		$segments[] = $this->base64url_encode( json_encode( $payload ) );

		$signing_input = implode( '.', $segments );
		$signature = $this->sign( $signing_input );
		$segments[] = $this->base64url_encode( $signature );

		return implode( '.', $segments );
	}

	/**
	 * Decode JWT token
	 *
	 * @param string $token
	 * @return object|\WP_Error
	 */
	private function decode_jwt( $token ) {
		$segments = explode( '.', $token );

		if ( count( $segments ) !== 3 ) {
			return new \WP_Error( 'invalid_token', __( 'Invalid token format', 'myd-delivery-pro' ) );
		}

		list( $header_b64, $payload_b64, $signature_b64 ) = $segments;

		// Verify signature
		$signing_input = $header_b64 . '.' . $payload_b64;
		$signature = $this->base64url_decode( $signature_b64 );
		$expected_signature = $this->sign( $signing_input );

		if ( ! hash_equals( $expected_signature, $signature ) ) {
			return new \WP_Error( 'invalid_signature', __( 'Invalid token signature', 'myd-delivery-pro' ) );
		}

		// Decode payload
		$payload = json_decode( $this->base64url_decode( $payload_b64 ) );

		if ( ! $payload ) {
			return new \WP_Error( 'invalid_payload', __( 'Invalid token payload', 'myd-delivery-pro' ) );
		}

		// Check expiration
		if ( isset( $payload->exp ) && $payload->exp < time() ) {
			return new \WP_Error( 'token_expired', __( 'Token has expired', 'myd-delivery-pro' ) );
		}

		return $payload;
	}

	/**
	 * Sign data
	 *
	 * @param string $data
	 * @return string
	 */
	private function sign( $data ) {
		return hash_hmac( 'sha256', $data, $this->secret_key, true );
	}

	/**
	 * Base64 URL encode
	 *
	 * @param string $data
	 * @return string
	 */
	private function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64 URL decode
	 *
	 * @param string $data
	 * @return string
	 */
	private function base64url_decode( $data ) {
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}

	/**
	 * Check JWT token permission
	 *
	 * @return bool|\WP_Error
	 */
	public function check_jwt_token() {
		$token = $this->get_token_from_request();

		if ( ! $token ) {
			return new \WP_Error(
				'missing_token',
				__( 'Authorization token is required', 'myd-delivery-pro' ),
				array( 'status' => 401 )
			);
		}

		$payload = $this->decode_jwt( $token );

		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		return true;
	}

	/**
	 * Determine current user from JWT token
	 *
	 * @param int|bool $user_id
	 * @return int|bool
	 */
	public function determine_current_user( $user_id ) {
		// If user is already determined, return it
		if ( $user_id ) {
			return $user_id;
		}

		// Check if this is a REST API request
		$rest_api_slug = rest_get_url_prefix();
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

		if ( strpos( $request_uri, $rest_api_slug ) === false ) {
			return $user_id;
		}

		// Get token from request
		$token = $this->get_token_from_request();

		if ( ! $token ) {
			return $user_id;
		}

		// Decode token
		$payload = $this->decode_jwt( $token );

		if ( is_wp_error( $payload ) ) {
			return $user_id;
		}

		// Set current user
		if ( isset( $payload->data->user_id ) ) {
			return $payload->data->user_id;
		}

		return $user_id;
	}

	/**
	 * Get token from request
	 *
	 * @return string|null
	 */
	private function get_token_from_request() {
		$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

		// Support for different header formats
		if ( empty( $auth_header ) && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
			$auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
		}

		if ( empty( $auth_header ) ) {
			return null;
		}

		// Extract token from "Bearer <token>" format
		list( $type, $token ) = explode( ' ', $auth_header, 2 );

		if ( strtolower( $type ) !== 'bearer' ) {
			return null;
		}

		return $token;
	}

	/**
	 * Prepare user data for response
	 *
	 * @param \WP_User $user
	 * @return array
	 */
	private function prepare_user_data( $user ) {
		return array(
			'id' => $user->ID,
			'username' => $user->user_login,
			'email' => $user->user_email,
			'display_name' => $user->display_name,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'roles' => $user->roles,
			'capabilities' => array(
				'manage_options' => user_can( $user, 'manage_options' ),
				'manage_orders' => user_can( $user, 'edit_posts' ),
				'upload_files' => user_can( $user, 'upload_files' ),
			),
		);
	}
}

new Auth_Api();
