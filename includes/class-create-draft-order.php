<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Myd Create Order
 *
 */
class Create_Draft_Order {
	/**
	 * Request data
	 *
	 */
	protected array $request_data;

	/**
	 * Id
	 *
	 */
	public int $id;

	/**
	 * Type
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Subtotal
	 *
	 * @var float
	 */
	public float $subtotal;

	/**
	 * Total
	 *
	 * @var float
	 */
	public float $total;

	/**
	 * Cart
	 *
	 * @var Cart
	 */
	public Cart $cart;

	/**
	 * Payment
	 *
	 * @var array
	 */
	public array $payment;

	/**
	 * Customer
	 *
	 * @var array
	 */
	public array $customer;

	/**
	 * Shipping
	 *
	 * @var array
	 */
	public array $shipping;

	/**
	 * Coupon
	 *
	 * @var ?Coupon
	 */
	public ?Coupon $coupon;

	/**
	 * Construct class.
	 */
	public function __construct( array $request_data ) {
		$this->request_data = $request_data;
		$this->subtotal = $request_data['subtotal'] ?? 0;
		$this->total = $request_data['total'] ?? 0;
		$this->payment = $request_data['payment'] ?? array();
	}

	/**
	 * Set type
	 *
	 * @param string $type
	 * @return void
	 */
	public function set_type( string $type ) : void {
		$this->type = $type;
	}

	/**
	 * Set cart
	 *
	 * @param Cart $cart
	 * @return void
	 */
	public function set_cart( Cart $cart ) : void {
		$this->cart = $cart;
	}

	/**
	 * Set customer
	 *
	 * @param [type] $customer
	 * @return void
	 */
	public function set_customer( $customer ) : void {
		$this->customer = $customer;
	}

	/**
	 * Set shipping
	 *
	 * @param [type] $shipping
	 * @return void
	 */
	public function set_shipping( $shipping ) : void {
		$this->shipping = $shipping;
	}

	/**
	 * Set coupon
	 *
	 * @param Coupon|null $coupon
	 * @return void
	 */
	public function set_coupon( ?Coupon $coupon ) : void {
		$this->coupon = $coupon;
	}
	/**
	 * Create Order
	 *
	 * @return void
	 */
	public function create() : void {
		$data = array(
			'post_title' => '#',
			'post_status' => 'draft',
			'post_type' => 'mydelivery-orders',
		);

		$this->id = wp_insert_post( $data );
		wp_update_post(
			array(
				'ID' => $this->id,
				'post_title' => $this->id,
			)
		);
	}

	/**
	 * Get formated extras - legacy and temp. function to be removed when this class is formated
	 *
	 * @param array $extras
	 * @return string
	 */
	private function get_formated_extras( array $extras ) : string {
		if ( empty( $extras['groups'] ) ) {
			return '';
		}

		$formated_extras = array();
		foreach ( $extras['groups'] as $group ) {
			$formated_extras[] = $group['group'] . ':' . PHP_EOL . implode( PHP_EOL, array_column( $group['items'], 'name' ) ) . PHP_EOL;
		}

		return implode( PHP_EOL, $formated_extras );
	}

	/**
	 * Update order
	 *
	 * @return void
	 */
	public function save() : void {
		$this->calculate_total();

		$order_items = array();
		foreach ( $this->cart->items as $item ) {
			$order_items[] = array(
				'product_name' => '' . $item['quantity'] . ' x ' . \esc_html( \get_the_title( $item['id'] ) ),
				'product_extras' => ! empty( $item['extras'] ) ? $this->get_formated_extras( $item['extras'] ) : '',
				'product_price' => Myd_Store_Formatting::format_price( $item['total'] ?? 0 ),
				'product_note' => $item['note'],
				// TODO: create a function to custom fields show the data based on array keys. !IMPORTANT
				'id' => $item['id'] ?? 0,
				'name' => \esc_html( \get_post_meta( $item['id'], 'product_name', true ) ),
				'quantity' => $item['quantity'] ?? 0,
				'extras' => $item['extras'] ?? array(),
				'price' => $item['price'] ?? 0,
				'total' => $item['total'] ?? 0,
				'note' => $item['note'] ?? '',
			);
		}

		\update_post_meta( $this->id, 'myd_order_items', $order_items );
		$this->add_order_note( __( 'Order status changed to: started', 'myd-delivery-pro' ), );

		\update_post_meta( $this->id, 'order_status', 'started' );
		\update_post_meta( $this->id, 'order_date', current_time( 'd-m-Y H:i' ) );
		\update_post_meta( $this->id, 'order_customer_name', sanitize_text_field( $this->customer['name'] ?? '' ) );
		\update_post_meta( $this->id, 'customer_phone', sanitize_text_field( $this->customer['phone'] ?? '' ) );
		\update_post_meta( $this->id, 'order_address', sanitize_text_field( $this->customer['address']['street'] ?? '' ) );
		\update_post_meta( $this->id, 'order_address_number', sanitize_text_field( $this->customer['address']['number'] ?? '' ) );
		\update_post_meta( $this->id, 'order_address_comp', sanitize_text_field( $this->customer['address']['complement'] ?? '' ) );
		\update_post_meta( $this->id, 'order_neighborhood', sanitize_text_field( $this->customer['address']['neighborhood'] ?? '' ) );
		\update_post_meta( $this->id, 'order_zipcode', sanitize_text_field( $this->customer['address']['zipcode'] ?? '' ) );
		\update_post_meta( $this->id, 'order_ship_method', sanitize_text_field( $this->type ?? '' ) );
		\update_post_meta( $this->id, 'order_delivery_price', sanitize_text_field( Myd_Store_Formatting::format_price( $this->shipping['price'] ?? '' ) ) );
		\update_post_meta( $this->id, 'order_coupon', sanitize_text_field( $this->coupon->code ?? '' ) );
		\update_post_meta( $this->id, 'order_table', sanitize_text_field( $this->shipping['table'] ?? '' ) );
		\update_post_meta( $this->id, 'order_payment_status', 'waiting' );
		// \update_post_meta( $this->id, 'order_coupon_discount', sanitize_text_field( $this->coupon->code ?? '' ) );
		\update_post_meta( $this->id, 'order_subtotal', sanitize_text_field( Myd_Store_Formatting::format_price( $this->subtotal ) ) );
		\update_post_meta( $this->id, 'order_total', sanitize_text_field( Myd_Store_Formatting::format_price( $this->total ) ) );
	}

	/**
	 * Calculate order total
	 *
	 * @return void
	 */
	public function calculate_total() : void {
		$this->subtotal = $this->cart->total;
		$this->total = $this->cart->total + $this->shipping['price'];
		if ( $this->coupon ) {
			$this->calculate_discount();
		}
	}

	/**
	 * Calculate discount
	 *
	 * @return void
	 */
	private function calculate_discount() : void {
		if ( $this->coupon->type === 'discount-total' ) {
			if ( $this->coupon->discount_format === 'amount' ) {
				$this->total = $this->total - $this->coupon->amount;
			}

			if ( $this->coupon->discount_format === 'percent' ) {
				$desconto = ( $this->coupon->amount * $this->total ) / 100;
				$this->total = $this->total - $desconto;
			}
		}

		if ( $this->coupon->type === 'discount-delivery' ) {
			if ( $this->coupon->discount_format === 'amount' ) {
				$this->total = $this->cart->total + ( $this->shipping['price'] - $this->coupon->amount );
			}

			if ( $this->coupon->discount_format === 'percent' ) {
				$desconto = ( $this->coupon->amount * $this->shipping['price'] ) / 100;
				$this->total = $this->cart->total + ( $this->shipping['price'] - $desconto );
			}
		}
	}

	/**
	 * Add order note
	 */
	public function add_order_note( string $note, string $type = 'success' ) : void {
		$order_note = \get_post_meta( $this->id, 'order_notes', true );
		$order_note = is_array( $order_note ) ? $order_note : array();
		$order_note[] = array(
			'type' => \esc_html( $type ),
			'note' => \esc_html( $note ),
			'date' => wp_date( get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ) ),
		);

		\update_post_meta( $this->id, 'order_notes', $order_note );
	}

	/**
	 * Undocumented function
	 *
	 */
	public function get_total_summary_template() {
		ob_start();
		require_once MYD_PLUGIN_PATH . 'templates/cart/cart-pricing-summary.php';
		return ob_get_clean();
	}
}
