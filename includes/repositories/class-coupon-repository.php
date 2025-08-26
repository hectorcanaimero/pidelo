<?php

namespace MydPro\Includes\Repositories;

use MydPro\Includes\Coupon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupon Class
 */
class Coupon_Repository {
	/**
	 * Get coupon by name
	 *
	 * @param string $name
	 * @return Coupon|null
	 */
	public static function get_by_name( string $name = '' ) : ?Coupon {
		if ( empty( $name ) ) {
			return null; // handle error
		}

		$args = array(
			'post_type' => 'mydelivery-coupons',
			'fields' => 'ids',
			'title' => $name,
			'no_found_rows' => true,
		);

		$coupons = new \WP_Query( $args );
		$coupons = $coupons->posts;

		if ( empty( $coupons ) ) {
			return null;
		}

		return new Coupon( $coupons[0] );
	}
}
