<?php
/**
 * Rate Limiter for REST API
 *
 * Implements rate limiting to prevent API abuse.
 * Limits: 100 requests per minute per user/IP
 *
 * @package MydPro
 * @subpackage Api
 * @since 2.4.0
 */

namespace MydPro\Includes\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rate Limiter Class
 *
 * Handles rate limiting for API endpoints using WordPress transients
 */
class Rate_Limiter {

	/**
	 * Maximum requests per window
	 *
	 * @var int
	 */
	private $max_requests = 100;

	/**
	 * Time window in seconds (1 minute)
	 *
	 * @var int
	 */
	private $time_window = 60;

	/**
	 * Whitelisted IPs (no rate limiting)
	 *
	 * @var array
	 */
	private $whitelist = array();

	/**
	 * Construct the class
	 */
	public function __construct() {
		// Load whitelist from options
		$this->whitelist = get_option( 'myd_rate_limit_whitelist', array() );

		// Allow filtering the limits
		$this->max_requests = apply_filters( 'myd_rate_limit_max_requests', $this->max_requests );
		$this->time_window = apply_filters( 'myd_rate_limit_time_window', $this->time_window );
		$this->whitelist = apply_filters( 'myd_rate_limit_whitelist', $this->whitelist );

		// Hook into REST API
		add_filter( 'rest_pre_dispatch', array( $this, 'check_rate_limit' ), 10, 3 );
		add_filter( 'rest_post_dispatch', array( $this, 'add_rate_limit_headers' ), 10, 3 );
	}

	/**
	 * Check if request should be rate limited
	 *
	 * @param mixed            $result  Response to replace the requested version with.
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @return mixed
	 */
	public function check_rate_limit( $result, $server, $request ) {
		// Only apply to our API endpoints
		if ( strpos( $request->get_route(), '/myd-delivery/v1' ) !== 0 ) {
			return $result;
		}

		// Get identifier (user ID or IP)
		$identifier = $this->get_identifier();

		// Check if whitelisted
		if ( $this->is_whitelisted( $identifier ) ) {
			return $result;
		}

		// Get current request count
		$count_data = $this->get_request_count( $identifier );

		// Check if limit exceeded
		if ( $count_data['requests'] >= $this->max_requests ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					__( 'Rate limit exceeded. Maximum %d requests per minute allowed.', 'myd-delivery-pro' ),
					$this->max_requests
				),
				array(
					'status' => 429,
					'headers' => array(
						'X-RateLimit-Limit' => $this->max_requests,
						'X-RateLimit-Remaining' => 0,
						'X-RateLimit-Reset' => $count_data['reset_time'],
						'Retry-After' => $count_data['reset_time'] - time(),
					),
				)
			);
		}

		// Increment request count
		$this->increment_request_count( $identifier, $count_data );

		return $result;
	}

	/**
	 * Add rate limit headers to response
	 *
	 * @param \WP_HTTP_Response $response Result to send to the client.
	 * @param \WP_REST_Server   $server   Server instance.
	 * @param \WP_REST_Request  $request  Request used to generate the response.
	 * @return \WP_HTTP_Response
	 */
	public function add_rate_limit_headers( $response, $server, $request ) {
		// Only apply to our API endpoints
		if ( strpos( $request->get_route(), '/myd-delivery/v1' ) !== 0 ) {
			return $response;
		}

		// Get identifier
		$identifier = $this->get_identifier();

		// Skip if whitelisted
		if ( $this->is_whitelisted( $identifier ) ) {
			return $response;
		}

		// Get current count
		$count_data = $this->get_request_count( $identifier );

		// Calculate remaining requests
		$remaining = max( 0, $this->max_requests - $count_data['requests'] );

		// Add headers
		$response->header( 'X-RateLimit-Limit', $this->max_requests );
		$response->header( 'X-RateLimit-Remaining', $remaining );
		$response->header( 'X-RateLimit-Reset', $count_data['reset_time'] );

		// Add Retry-After header if limit exceeded
		if ( $remaining === 0 ) {
			$retry_after = $count_data['reset_time'] - time();
			$response->header( 'Retry-After', $retry_after );
		}

		return $response;
	}

	/**
	 * Get unique identifier for rate limiting
	 *
	 * Uses user ID if authenticated, otherwise IP address
	 *
	 * @return string
	 */
	private function get_identifier() {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			return 'user_' . $user_id;
		}

		// Use IP address
		$ip = $this->get_client_ip();
		return 'ip_' . md5( $ip );
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Handle proxy/load balancer
			$ip_list = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip = trim( $ip_list[0] );
		} elseif ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$ip = $_SERVER['HTTP_X_REAL_IP'];
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return sanitize_text_field( $ip );
	}

	/**
	 * Check if identifier is whitelisted
	 *
	 * @param string $identifier
	 * @return bool
	 */
	private function is_whitelisted( $identifier ) {
		// Check if user ID is whitelisted
		if ( strpos( $identifier, 'user_' ) === 0 ) {
			$user_id = str_replace( 'user_', '', $identifier );
			return in_array( 'user:' . $user_id, $this->whitelist, true );
		}

		// Check if IP is whitelisted
		$ip = $this->get_client_ip();
		return in_array( $ip, $this->whitelist, true );
	}

	/**
	 * Get request count for identifier
	 *
	 * @param string $identifier
	 * @return array
	 */
	private function get_request_count( $identifier ) {
		$transient_key = 'myd_rate_limit_' . $identifier;
		$count_data = get_transient( $transient_key );

		if ( false === $count_data ) {
			// Initialize new window
			$reset_time = time() + $this->time_window;

			$count_data = array(
				'requests' => 0,
				'reset_time' => $reset_time,
				'start_time' => time(),
			);
		}

		return $count_data;
	}

	/**
	 * Increment request count
	 *
	 * @param string $identifier
	 * @param array  $count_data
	 * @return void
	 */
	private function increment_request_count( $identifier, $count_data ) {
		$count_data['requests']++;

		$transient_key = 'myd_rate_limit_' . $identifier;

		// Calculate remaining time in window
		$remaining_time = $count_data['reset_time'] - time();

		// Set transient with remaining window time
		set_transient( $transient_key, $count_data, $remaining_time );
	}

	/**
	 * Reset rate limit for identifier
	 *
	 * Useful for testing or manual reset
	 *
	 * @param string $identifier
	 * @return bool
	 */
	public function reset_limit( $identifier ) {
		$transient_key = 'myd_rate_limit_' . $identifier;
		return delete_transient( $transient_key );
	}

	/**
	 * Get current rate limit status for identifier
	 *
	 * @param string $identifier Optional. If not provided, uses current user/IP
	 * @return array
	 */
	public function get_status( $identifier = null ) {
		if ( null === $identifier ) {
			$identifier = $this->get_identifier();
		}

		$count_data = $this->get_request_count( $identifier );
		$remaining = max( 0, $this->max_requests - $count_data['requests'] );

		return array(
			'limit' => $this->max_requests,
			'remaining' => $remaining,
			'reset' => $count_data['reset_time'],
			'reset_in_seconds' => max( 0, $count_data['reset_time'] - time() ),
			'requests_made' => $count_data['requests'],
			'whitelisted' => $this->is_whitelisted( $identifier ),
		);
	}

	/**
	 * Add IP to whitelist
	 *
	 * @param string $ip IP address
	 * @return bool
	 */
	public function add_to_whitelist( $ip ) {
		$whitelist = get_option( 'myd_rate_limit_whitelist', array() );

		if ( ! in_array( $ip, $whitelist, true ) ) {
			$whitelist[] = $ip;
			update_option( 'myd_rate_limit_whitelist', $whitelist );
			$this->whitelist = $whitelist;
			return true;
		}

		return false;
	}

	/**
	 * Remove IP from whitelist
	 *
	 * @param string $ip IP address or user:ID
	 * @return bool
	 */
	public function remove_from_whitelist( $ip ) {
		$whitelist = get_option( 'myd_rate_limit_whitelist', array() );
		$key = array_search( $ip, $whitelist, true );

		if ( false !== $key ) {
			unset( $whitelist[ $key ] );
			$whitelist = array_values( $whitelist ); // Re-index
			update_option( 'myd_rate_limit_whitelist', $whitelist );
			$this->whitelist = $whitelist;
			return true;
		}

		return false;
	}

	/**
	 * Get whitelist
	 *
	 * @return array
	 */
	public function get_whitelist() {
		return $this->whitelist;
	}

	/**
	 * Clear all rate limit data
	 *
	 * Useful for testing or emergency reset
	 *
	 * @return int Number of transients deleted
	 */
	public function clear_all_limits() {
		global $wpdb;

		$count = $wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_myd_rate_limit_%'
			OR option_name LIKE '_transient_timeout_myd_rate_limit_%'"
		);

		return $count;
	}
}

// Initialize rate limiter
new Rate_Limiter();
