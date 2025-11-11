<?php
/**
 * Rate Limiter Tests
 *
 * @package MydPro
 * @subpackage Tests
 * @since 2.4.0
 */

namespace MydPro\Tests\Api;

use WP_REST_Request;
use WP_UnitTestCase;
use MydPro\Includes\Api\Rate_Limiter;

/**
 * Test Rate Limiter functionality
 */
class Test_Rate_Limiter extends WP_UnitTestCase {

	/**
	 * Rate limiter instance
	 *
	 * @var Rate_Limiter
	 */
	protected $rate_limiter;

	/**
	 * Test user ID
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();

		$this->rate_limiter = new Rate_Limiter();

		// Create test user
		$this->user_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );

		// Clear all rate limits
		$this->rate_limiter->clear_all_limits();

		// Clear whitelist
		update_option( 'myd_rate_limit_whitelist', array() );
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up
		$this->rate_limiter->clear_all_limits();
		update_option( 'myd_rate_limit_whitelist', array() );
	}

	/**
	 * Test basic rate limiting - should allow requests under limit
	 */
	public function test_allows_requests_under_limit() {
		wp_set_current_user( $this->user_id );

		// Make 10 requests (well under limit of 100)
		for ( $i = 0; $i < 10; $i++ ) {
			$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
			$response = rest_do_request( $request );

			$this->assertNotEquals( 429, $response->get_status(), "Request $i should not be rate limited" );
		}
	}

	/**
	 * Test rate limit exceeded - should return 429
	 */
	public function test_blocks_requests_over_limit() {
		wp_set_current_user( $this->user_id );

		// Make exactly 100 requests (the limit)
		for ( $i = 0; $i < 100; $i++ ) {
			$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
			$response = rest_do_request( $request );

			$this->assertNotEquals( 429, $response->get_status(), "Request $i should be allowed" );
		}

		// 101st request should be blocked
		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
		$response = rest_do_request( $request );

		$this->assertEquals( 429, $response->get_status(), 'Request 101 should be rate limited' );

		$data = $response->get_data();
		$this->assertEquals( 'rate_limit_exceeded', $data['code'] );
	}

	/**
	 * Test rate limit headers are added to response
	 */
	public function test_adds_rate_limit_headers() {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
		$response = rest_do_request( $request );

		$headers = $response->get_headers();

		$this->assertArrayHasKey( 'X-RateLimit-Limit', $headers );
		$this->assertArrayHasKey( 'X-RateLimit-Remaining', $headers );
		$this->assertArrayHasKey( 'X-RateLimit-Reset', $headers );

		$this->assertEquals( 100, $headers['X-RateLimit-Limit'] );
		$this->assertEquals( 99, $headers['X-RateLimit-Remaining'] ); // After 1 request
		$this->assertGreaterThan( time(), $headers['X-RateLimit-Reset'] );
	}

	/**
	 * Test Retry-After header is added when limit exceeded
	 */
	public function test_adds_retry_after_header_when_limited() {
		wp_set_current_user( $this->user_id );

		// Exhaust the limit
		for ( $i = 0; $i < 100; $i++ ) {
			rest_do_request( new WP_REST_Request( 'GET', '/myd-delivery/v1/products' ) );
		}

		// Next request should have Retry-After
		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$headers = $data['data']['headers'];

		$this->assertArrayHasKey( 'Retry-After', $headers );
		$this->assertGreaterThan( 0, $headers['Retry-After'] );
		$this->assertLessThanOrEqual( 60, $headers['Retry-After'] );
	}

	/**
	 * Test whitelist functionality - whitelisted users bypass limit
	 */
	public function test_whitelist_bypasses_rate_limit() {
		wp_set_current_user( $this->user_id );

		// Add user to whitelist
		$this->rate_limiter->add_to_whitelist( 'user:' . $this->user_id );

		// Make 150 requests (more than limit of 100)
		for ( $i = 0; $i < 150; $i++ ) {
			$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
			$response = rest_do_request( $request );

			$this->assertNotEquals( 429, $response->get_status(), "Whitelisted request $i should not be limited" );
		}
	}

	/**
	 * Test IP-based rate limiting for non-authenticated requests
	 */
	public function test_ip_based_rate_limiting() {
		// No user logged in
		wp_set_current_user( 0 );

		// Simulate IP
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

		// Make 10 requests
		for ( $i = 0; $i < 10; $i++ ) {
			$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
			$response = rest_do_request( $request );

			$this->assertNotEquals( 429, $response->get_status() );
		}

		// Verify status shows correct count
		$identifier = 'ip_' . md5( '192.168.1.100' );
		$status = $this->rate_limiter->get_status( $identifier );

		$this->assertEquals( 10, $status['requests_made'] );

		// Cleanup
		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Test IP whitelist
	 */
	public function test_ip_whitelist() {
		wp_set_current_user( 0 );

		$_SERVER['REMOTE_ADDR'] = '10.0.0.1';

		// Add IP to whitelist
		$this->rate_limiter->add_to_whitelist( '10.0.0.1' );

		// Make 150 requests
		for ( $i = 0; $i < 150; $i++ ) {
			$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
			$response = rest_do_request( $request );

			$this->assertNotEquals( 429, $response->get_status() );
		}

		// Cleanup
		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Test get_status method
	 */
	public function test_get_status() {
		wp_set_current_user( $this->user_id );

		// Make 25 requests
		for ( $i = 0; $i < 25; $i++ ) {
			rest_do_request( new WP_REST_Request( 'GET', '/myd-delivery/v1/products' ) );
		}

		$status = $this->rate_limiter->get_status();

		$this->assertEquals( 100, $status['limit'] );
		$this->assertEquals( 75, $status['remaining'] );
		$this->assertEquals( 25, $status['requests_made'] );
		$this->assertFalse( $status['whitelisted'] );
		$this->assertGreaterThan( time(), $status['reset'] );
	}

	/**
	 * Test reset_limit method
	 */
	public function test_reset_limit() {
		wp_set_current_user( $this->user_id );

		// Make 50 requests
		for ( $i = 0; $i < 50; $i++ ) {
			rest_do_request( new WP_REST_Request( 'GET', '/myd-delivery/v1/products' ) );
		}

		$status_before = $this->rate_limiter->get_status();
		$this->assertEquals( 50, $status_before['requests_made'] );

		// Reset the limit
		$identifier = 'user_' . $this->user_id;
		$this->rate_limiter->reset_limit( $identifier );

		$status_after = $this->rate_limiter->get_status();
		$this->assertEquals( 0, $status_after['requests_made'] );
	}

	/**
	 * Test add/remove from whitelist
	 */
	public function test_whitelist_management() {
		// Add to whitelist
		$result = $this->rate_limiter->add_to_whitelist( '192.168.1.1' );
		$this->assertTrue( $result );

		$whitelist = $this->rate_limiter->get_whitelist();
		$this->assertContains( '192.168.1.1', $whitelist );

		// Try adding again (should return false)
		$result = $this->rate_limiter->add_to_whitelist( '192.168.1.1' );
		$this->assertFalse( $result );

		// Remove from whitelist
		$result = $this->rate_limiter->remove_from_whitelist( '192.168.1.1' );
		$this->assertTrue( $result );

		$whitelist = $this->rate_limiter->get_whitelist();
		$this->assertNotContains( '192.168.1.1', $whitelist );

		// Try removing again (should return false)
		$result = $this->rate_limiter->remove_from_whitelist( '192.168.1.1' );
		$this->assertFalse( $result );
	}

	/**
	 * Test clear_all_limits method
	 */
	public function test_clear_all_limits() {
		// Create multiple users and make requests
		$user1 = $this->factory->user->create();
		$user2 = $this->factory->user->create();

		wp_set_current_user( $user1 );
		for ( $i = 0; $i < 10; $i++ ) {
			rest_do_request( new WP_REST_Request( 'GET', '/myd-delivery/v1/products' ) );
		}

		wp_set_current_user( $user2 );
		for ( $i = 0; $i < 20; $i++ ) {
			rest_do_request( new WP_REST_Request( 'GET', '/myd-delivery/v1/products' ) );
		}

		// Clear all limits
		$count = $this->rate_limiter->clear_all_limits();
		$this->assertGreaterThan( 0, $count );

		// Verify limits are reset
		$status1 = $this->rate_limiter->get_status( 'user_' . $user1 );
		$status2 = $this->rate_limiter->get_status( 'user_' . $user2 );

		$this->assertEquals( 0, $status1['requests_made'] );
		$this->assertEquals( 0, $status2['requests_made'] );
	}

	/**
	 * Test rate limit only applies to myd-delivery endpoints
	 */
	public function test_only_applies_to_myd_endpoints() {
		wp_set_current_user( $this->user_id );

		// Make request to WordPress core endpoint
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$response = rest_do_request( $request );

		// Should not have rate limit headers
		$headers = $response->get_headers();
		$this->assertArrayNotHasKey( 'X-RateLimit-Limit', $headers );
	}

	/**
	 * Test X-Forwarded-For header support
	 */
	public function test_x_forwarded_for_support() {
		wp_set_current_user( 0 );

		// Simulate load balancer/proxy
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.42, 192.168.1.1';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.1';

		// Should use first IP from X-Forwarded-For
		for ( $i = 0; $i < 5; $i++ ) {
			rest_do_request( new WP_REST_Request( 'GET', '/myd-delivery/v1/products' ) );
		}

		$identifier = 'ip_' . md5( '203.0.113.42' );
		$status = $this->rate_limiter->get_status( $identifier );

		$this->assertEquals( 5, $status['requests_made'] );

		// Cleanup
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Test filters for customizing limits
	 */
	public function test_filter_max_requests() {
		// Add filter to reduce limit to 10 for testing
		add_filter( 'myd_rate_limit_max_requests', function() {
			return 10;
		} );

		// Recreate rate limiter to apply filter
		$rate_limiter = new Rate_Limiter();

		wp_set_current_user( $this->user_id );

		// Make 10 requests
		for ( $i = 0; $i < 10; $i++ ) {
			$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
			$response = rest_do_request( $request );
			$this->assertNotEquals( 429, $response->get_status() );
		}

		// 11th should be blocked
		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
		$response = rest_do_request( $request );
		$this->assertEquals( 429, $response->get_status() );

		// Remove filter
		remove_all_filters( 'myd_rate_limit_max_requests' );
	}

	/**
	 * Test concurrent requests from different users
	 */
	public function test_concurrent_users() {
		$user1 = $this->factory->user->create();
		$user2 = $this->factory->user->create();

		// User 1 makes 50 requests
		wp_set_current_user( $user1 );
		for ( $i = 0; $i < 50; $i++ ) {
			rest_do_request( new WP_REST_Request( 'GET', '/myd-delivery/v1/products' ) );
		}

		$status1 = $this->rate_limiter->get_status( 'user_' . $user1 );
		$this->assertEquals( 50, $status1['requests_made'] );

		// User 2 should have independent limit
		wp_set_current_user( $user2 );
		$status2 = $this->rate_limiter->get_status( 'user_' . $user2 );
		$this->assertEquals( 0, $status2['requests_made'] );

		// User 2 can make full 100 requests
		for ( $i = 0; $i < 100; $i++ ) {
			$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/products' );
			$response = rest_do_request( $request );
			$this->assertNotEquals( 429, $response->get_status() );
		}
	}
}
