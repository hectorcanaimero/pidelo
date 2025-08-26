<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupon Class
 */
class Coupon {
	/**
	 * Id
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * Allow Types
	 *
	 * @var array
	 */
	public array $allow_types = array(
		'discount-total',
		'discount-delivery',
	);

	/**
	 * Type
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Code
	 *
	 * @var string
	 */
	public string $code;

	/**
	 * Discount format ($ or %)
	 */
	protected array $allow_discount_formats = array(
		'amount',
		'percent',
	);

	/**
	 * Discount format ($ or %)
	 */
	public string $discount_format;

	/**
	 * Amount of discount
	 */
	public int $amount;

	/**
	 * Description
	 *
	 * @var string
	 */
	public string $description;

	/**
	 * Construct
	 */
	public function __construct( int $id = 0 ) {
		if ( empty( $id ) || $id === 0 ) {
			return; // TODO: handle error
		}

		$this->id = $id;
		$this->type = get_post_meta( $this->id, 'myd_coupon_type', true );
		$this->code = get_the_title( $id );
		$this->discount_format = get_post_meta( $this->id, 'myd_discount_format', true );
		$this->amount = get_post_meta( $this->id, 'myd_discount_value', true );
		$this->description = get_post_meta( $this->id, 'myd_coupon_description', true );
	}
}
