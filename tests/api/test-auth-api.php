<?php
/**
 * Authentication API Tests
 *
 * @package MydPro
 * @subpackage Tests
 * @since 2.3.9
 */

namespace MydPro\Tests\Api;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Test Authentication API endpoints
 */
class Test_Auth_Api extends WP_UnitTestCase {

	/**
	 * Test users
	 *
	 * @var array
	 */
	protected $users = array();

	/**
	 * Test password
	 *
	 * @var string
	 */
	protected $password = 'testpassword123';

	/**
	 * Valid JWT token
	 *
	 * @var string
	 */
	protected $valid_token;

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test users with different roles
		$this->users['admin'] = $this->factory->user->create( array(
			'user_login' => 'testadmin',
			'user_email' => 'admin@test.com',
			'user_pass' => $this->password,
			'role' => 'administrator',
			'display_name' => 'Test Admin',
		) );

		$this->users['editor'] = $this->factory->user->create( array(
			'user_login' => 'testeditor',
			'user_email' => 'editor@test.com',
			'user_pass' => $this->password,
			'role' => 'editor',
			'display_name' => 'Test Editor',
		) );

		$this->users['subscriber'] = $this->factory->user->create( array(
			'user_login' => 'testsubscriber',
			'user_email' => 'subscriber@test.com',
			'user_pass' => $this->password,
			'role' => 'subscriber',
			'display_name' => 'Test Subscriber',
		) );
	}

	/**
	 * Test POST /auth/login - Successful login with username
	 */
	public function test_login_success_with_username() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$request->set_param( 'username', 'testadmin' );
		$request->set_param( 'password', $this->password );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'token', $data );
		$this->assertArrayHasKey( 'user', $data );
		$this->assertArrayHasKey( 'expires_in', $data );

		// Verify token is a valid JWT format (3 parts separated by dots)
		$token_parts = explode( '.', $data['token'] );
		$this->assertCount( 3, $token_parts );

		// Verify user data
		$this->assertEquals( $this->users['admin'], $data['user']['id'] );
		$this->assertEquals( 'testadmin', $data['user']['username'] );
		$this->assertEquals( 'admin@test.com', $data['user']['email'] );
		$this->assertEquals( 'Test Admin', $data['user']['display_name'] );
		$this->assertContains( 'administrator', $data['user']['roles'] );

		// Verify capabilities
		$this->assertTrue( $data['user']['capabilities']['manage_options'] );
		$this->assertTrue( $data['user']['capabilities']['upload_files'] );

		// Store valid token for later tests
		$this->valid_token = $data['token'];
	}

	/**
	 * Test POST /auth/login - Successful login with email
	 */
	public function test_login_success_with_email() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$request->set_param( 'username', 'admin@test.com' );
		$request->set_param( 'password', $this->password );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'token', $data );
	}

	/**
	 * Test POST /auth/login - Invalid username
	 */
	public function test_login_invalid_username() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$request->set_param( 'username', 'nonexistent' );
		$request->set_param( 'password', $this->password );

		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'invalid_credentials', $data['code'] );
		$this->assertStringContainsString( 'Invalid username or password', $data['message'] );
	}

	/**
	 * Test POST /auth/login - Invalid password
	 */
	public function test_login_invalid_password() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$request->set_param( 'username', 'testadmin' );
		$request->set_param( 'password', 'wrongpassword' );

		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'invalid_credentials', $data['code'] );
	}

	/**
	 * Test POST /auth/login - Empty credentials
	 */
	public function test_login_empty_credentials() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$request->set_param( 'username', '' );
		$request->set_param( 'password', '' );

		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test POST /auth/login - SQL injection attempt
	 */
	public function test_login_sql_injection() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$request->set_param( 'username', "admin' OR '1'='1" );
		$request->set_param( 'password', "' OR '1'='1" );

		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status() );
		$this->assertArrayHasKey( 'code', $response->get_data() );
	}

	/**
	 * Test GET /auth/validate - Valid token
	 */
	public function test_validate_valid_token() {
		// First login to get a token
		$login_request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$login_request->set_param( 'username', 'testadmin' );
		$login_request->set_param( 'password', $this->password );
		$login_response = rest_do_request( $login_request );
		$token = $login_response->get_data()['token'];

		// Validate the token
		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/auth/validate' );
		$request->add_header( 'Authorization', 'Bearer ' . $token );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['valid'] );
		$this->assertArrayHasKey( 'user', $data );
		$this->assertEquals( $this->users['admin'], $data['user']['id'] );
	}

	/**
	 * Test GET /auth/validate - Missing token
	 */
	public function test_validate_missing_token() {
		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/auth/validate' );
		// No Authorization header

		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'missing_token', $data['code'] );
	}

	/**
	 * Test GET /auth/validate - Malformed token
	 */
	public function test_validate_malformed_token() {
		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/auth/validate' );
		$request->add_header( 'Authorization', 'Bearer invalid.token' );

		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test GET /auth/validate - Tampered token (invalid signature)
	 */
	public function test_validate_tampered_token() {
		// Get a valid token first
		$login_request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$login_request->set_param( 'username', 'testadmin' );
		$login_request->set_param( 'password', $this->password );
		$login_response = rest_do_request( $login_request );
		$token = $login_response->get_data()['token'];

		// Tamper with the token (change last character)
		$tampered_token = substr( $token, 0, -1 ) . 'X';

		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/auth/validate' );
		$request->add_header( 'Authorization', 'Bearer ' . $tampered_token );

		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'invalid_signature', $data['code'] );
	}

	/**
	 * Test POST /auth/refresh - Refresh valid token
	 */
	public function test_refresh_token() {
		// Get initial token
		$login_request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$login_request->set_param( 'username', 'testadmin' );
		$login_request->set_param( 'password', $this->password );
		$login_response = rest_do_request( $login_request );
		$old_token = $login_response->get_data()['token'];

		// Wait a second to ensure new token has different timestamp
		sleep( 1 );

		// Refresh token
		$refresh_request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/refresh' );
		$refresh_request->add_header( 'Authorization', 'Bearer ' . $old_token );

		$response = rest_do_request( $refresh_request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'token', $data );
		$this->assertArrayHasKey( 'expires_in', $data );

		// New token should be different
		$this->assertNotEquals( $old_token, $data['token'] );
	}

	/**
	 * Test POST /auth/refresh - Without token
	 */
	public function test_refresh_without_token() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/refresh' );
		// No Authorization header

		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test GET /auth/me - Get current user
	 */
	public function test_get_current_user() {
		// Login first
		$login_request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$login_request->set_param( 'username', 'testadmin' );
		$login_request->set_param( 'password', $this->password );
		$login_response = rest_do_request( $login_request );
		$token = $login_response->get_data()['token'];

		// Get current user
		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/auth/me' );
		$request->add_header( 'Authorization', 'Bearer ' . $token );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( $this->users['admin'], $data['id'] );
		$this->assertEquals( 'testadmin', $data['username'] );
		$this->assertEquals( 'admin@test.com', $data['email'] );
		$this->assertArrayHasKey( 'capabilities', $data );
	}

	/**
	 * Test GET /auth/me - Without authentication
	 */
	public function test_get_current_user_without_auth() {
		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/auth/me' );
		// No Authorization header

		$response = rest_do_request( $request );

		$this->assertEquals( 401, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'missing_token', $data['code'] );
	}

	/**
	 * Test login with different user roles
	 */
	public function test_login_different_roles() {
		$roles = array( 'admin', 'editor', 'subscriber' );

		foreach ( $roles as $role ) {
			$username = 'test' . $role;

			$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
			$request->set_param( 'username', $username );
			$request->set_param( 'password', $this->password );

			$response = rest_do_request( $request );

			$this->assertEquals( 200, $response->get_status(), "Login should succeed for $role" );

			$data = $response->get_data();
			$this->assertTrue( $data['success'] );

			// Verify role-specific capabilities
			if ( $role === 'admin' ) {
				$this->assertTrue( $data['user']['capabilities']['manage_options'] );
			} elseif ( $role === 'editor' ) {
				$this->assertTrue( $data['user']['capabilities']['manage_orders'] );
				$this->assertFalse( $data['user']['capabilities']['manage_options'] );
			} elseif ( $role === 'subscriber' ) {
				$this->assertFalse( $data['user']['capabilities']['manage_options'] );
			}
		}
	}

	/**
	 * Test JWT token structure
	 */
	public function test_jwt_token_structure() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$request->set_param( 'username', 'testadmin' );
		$request->set_param( 'password', $this->password );

		$response = rest_do_request( $request );
		$token = $response->get_data()['token'];

		// JWT should have 3 parts: header.payload.signature
		$parts = explode( '.', $token );
		$this->assertCount( 3, $parts );

		// Decode header
		$header = json_decode( base64_decode( strtr( $parts[0], '-_', '+/' ) ), true );
		$this->assertEquals( 'JWT', $header['typ'] );
		$this->assertEquals( 'HS256', $header['alg'] );

		// Decode payload
		$payload = json_decode( base64_decode( strtr( $parts[1], '-_', '+/' ) ), true );
		$this->assertArrayHasKey( 'iss', $payload ); // Issuer
		$this->assertArrayHasKey( 'iat', $payload ); // Issued at
		$this->assertArrayHasKey( 'exp', $payload ); // Expiration
		$this->assertArrayHasKey( 'data', $payload ); // User data

		// Verify expiration is in the future
		$this->assertGreaterThan( time(), $payload['exp'] );

		// Verify user data
		$this->assertEquals( $this->users['admin'], $payload['data']['user_id'] );
		$this->assertEquals( 'testadmin', $payload['data']['user_login'] );
	}

	/**
	 * Test token expiration time
	 */
	public function test_token_expiration_time() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$request->set_param( 'username', 'testadmin' );
		$request->set_param( 'password', $this->password );

		$response = rest_do_request( $request );
		$data = $response->get_data();

		// Should expire in 24 hours (86400 seconds)
		$this->assertEquals( 86400, $data['expires_in'] );

		// Decode token to verify expiration timestamp
		$token = $data['token'];
		$parts = explode( '.', $token );
		$payload = json_decode( base64_decode( strtr( $parts[1], '-_', '+/' ) ), true );

		$expected_exp = $payload['iat'] + 86400;
		$this->assertEquals( $expected_exp, $payload['exp'] );
	}

	/**
	 * Test authorization header variations
	 */
	public function test_authorization_header_variations() {
		// Get a valid token
		$login_request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$login_request->set_param( 'username', 'testadmin' );
		$login_request->set_param( 'password', $this->password );
		$login_response = rest_do_request( $login_request );
		$token = $login_response->get_data()['token'];

		// Test case variations of "Bearer"
		$variations = array( 'Bearer', 'bearer', 'BEARER', 'BeArEr' );

		foreach ( $variations as $bearer ) {
			$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/auth/validate' );
			$request->add_header( 'Authorization', $bearer . ' ' . $token );

			$response = rest_do_request( $request );

			// Should accept case-insensitive "Bearer"
			$this->assertEquals( 200, $response->get_status(), "Should accept '$bearer'" );
		}
	}

	/**
	 * Test determine_current_user filter integration
	 */
	public function test_determine_current_user_filter() {
		// Get a valid token
		$login_request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$login_request->set_param( 'username', 'testadmin' );
		$login_request->set_param( 'password', $this->password );
		$login_response = rest_do_request( $login_request );
		$token = $login_response->get_data()['token'];

		// Simulate a REST API request with Authorization header
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
		$_SERVER['REQUEST_URI'] = '/wp-json/myd-delivery/v1/auth/me';

		// The determine_current_user filter should set the current user
		$user_id = apply_filters( 'determine_current_user', false );

		$this->assertEquals( $this->users['admin'], $user_id );

		// Cleanup
		unset( $_SERVER['HTTP_AUTHORIZATION'] );
		unset( $_SERVER['REQUEST_URI'] );
	}

	/**
	 * Test user data sanitization
	 */
	public function test_user_data_sanitization() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$request->set_param( 'username', 'testadmin' );
		$request->set_param( 'password', $this->password );

		$response = rest_do_request( $request );
		$data = $response->get_data();

		// Sensitive data should not be included
		$this->assertArrayNotHasKey( 'user_pass', $data['user'] );
		$this->assertArrayNotHasKey( 'user_activation_key', $data['user'] );

		// Only safe user data should be included
		$this->assertArrayHasKey( 'id', $data['user'] );
		$this->assertArrayHasKey( 'username', $data['user'] );
		$this->assertArrayHasKey( 'email', $data['user'] );
		$this->assertArrayHasKey( 'display_name', $data['user'] );
		$this->assertArrayHasKey( 'roles', $data['user'] );
		$this->assertArrayHasKey( 'capabilities', $data['user'] );
	}

	/**
	 * Test multiple login attempts (same user)
	 */
	public function test_multiple_login_attempts() {
		$tokens = array();

		// Login 3 times
		for ( $i = 0; $i < 3; $i++ ) {
			$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
			$request->set_param( 'username', 'testadmin' );
			$request->set_param( 'password', $this->password );

			$response = rest_do_request( $request );
			$tokens[] = $response->get_data()['token'];

			sleep( 1 ); // Ensure different timestamps
		}

		// All tokens should be different
		$unique_tokens = array_unique( $tokens );
		$this->assertCount( 3, $unique_tokens );

		// All tokens should be valid
		foreach ( $tokens as $token ) {
			$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/auth/validate' );
			$request->add_header( 'Authorization', 'Bearer ' . $token );

			$response = rest_do_request( $request );
			$this->assertEquals( 200, $response->get_status() );
		}
	}

	/**
	 * Test XSS protection in username
	 */
	public function test_xss_protection() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$request->set_param( 'username', '<script>alert("xss")</script>' );
		$request->set_param( 'password', $this->password );

		$response = rest_do_request( $request );

		// Should fail safely (not execute script)
		$this->assertEquals( 401, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
	}

	/**
	 * Test token with missing Authorization header but present in $_SERVER
	 */
	public function test_token_from_server_variable() {
		// Get a valid token
		$login_request = new WP_REST_Request( 'POST', '/myd-delivery/v1/auth/login' );
		$login_request->set_param( 'username', 'testadmin' );
		$login_request->set_param( 'password', $this->password );
		$login_response = rest_do_request( $login_request );
		$token = $login_response->get_data()['token'];

		// Set in $_SERVER (some server configurations use this)
		$_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/auth/validate' );
		// Don't set header on request, rely on $_SERVER

		$_SERVER['REQUEST_URI'] = '/wp-json/myd-delivery/v1/auth/validate';

		$response = rest_do_request( $request );

		// Should work with REDIRECT_HTTP_AUTHORIZATION
		$this->assertEquals( 200, $response->get_status() );

		// Cleanup
		unset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] );
		unset( $_SERVER['REQUEST_URI'] );
	}
}
