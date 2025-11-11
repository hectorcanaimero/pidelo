<?php
/**
 * Cart REST API endpoints
 *
 * @package MydPro
 * @subpackage Api
 * @since 2.3.9
 */

namespace MydPro\Includes\Api\Cart;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart API Class
 *
 * Handles shopping cart operations via REST API
 */
class Cart_Api {
	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_cart_routes' ] );
	}

	/**
	 * Register cart routes
	 */
	public function register_cart_routes() {
		// GET /cart - Get current cart
		\register_rest_route(
			'myd-delivery/v1',
			'/cart',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_cart' ],
					'permission_callback' => '__return_true', // Public - uses session
				),
			)
		);

		// POST /cart - Update entire cart
		\register_rest_route(
			'myd-delivery/v1',
			'/cart',
			array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'update_cart' ],
					'permission_callback' => '__return_true', // Public - uses session
					'args' => array(
						'items' => array(
							'description' => __( 'Cart items', 'myd-delivery-pro' ),
							'type' => 'array',
							'required' => true,
						),
						'coupon' => array(
							'description' => __( 'Coupon code', 'myd-delivery-pro' ),
							'type' => 'string',
						),
						'delivery_method' => array(
							'description' => __( 'Delivery method', 'myd-delivery-pro' ),
							'type' => 'string',
						),
					),
				),
			)
		);

		// DELETE /cart - Clear cart
		\register_rest_route(
			'myd-delivery/v1',
			'/cart',
			array(
				array(
					'methods'  => \WP_REST_Server::DELETABLE,
					'callback' => [ $this, 'clear_cart' ],
					'permission_callback' => '__return_true', // Public - uses session
				),
			)
		);

		// POST /cart/items - Add item to cart
		\register_rest_route(
			'myd-delivery/v1',
			'/cart/items',
			array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'add_item' ],
					'permission_callback' => '__return_true', // Public - uses session
					'args' => $this->get_item_schema(),
				),
			)
		);

		// PUT /cart/items/{product_id} - Update item quantity
		\register_rest_route(
			'myd-delivery/v1',
			'/cart/items/(?P<product_id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::EDITABLE,
					'callback' => [ $this, 'update_item' ],
					'permission_callback' => '__return_true', // Public - uses session
					'args' => array(
						'product_id' => array(
							'description' => __( 'Product ID', 'myd-delivery-pro' ),
							'type' => 'integer',
							'required' => true,
						),
						'quantity' => array(
							'description' => __( 'Item quantity', 'myd-delivery-pro' ),
							'type' => 'integer',
							'required' => true,
							'minimum' => 1,
						),
					),
				),
			)
		);

		// DELETE /cart/items/{product_id} - Remove item from cart
		\register_rest_route(
			'myd-delivery/v1',
			'/cart/items/(?P<product_id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::DELETABLE,
					'callback' => [ $this, 'remove_item' ],
					'permission_callback' => '__return_true', // Public - uses session
					'args' => array(
						'product_id' => array(
							'description' => __( 'Product ID', 'myd-delivery-pro' ),
							'type' => 'integer',
							'required' => true,
						),
					),
				),
			)
		);

		// POST /cart/calculate - Calculate cart totals
		\register_rest_route(
			'myd-delivery/v1',
			'/cart/calculate',
			array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'calculate_totals' ],
					'permission_callback' => '__return_true', // Public - uses session
					'args' => array(
						'items' => array(
							'description' => __( 'Cart items', 'myd-delivery-pro' ),
							'type' => 'array',
							'required' => true,
						),
						'coupon' => array(
							'description' => __( 'Coupon code', 'myd-delivery-pro' ),
							'type' => 'string',
						),
						'delivery_method' => array(
							'description' => __( 'Delivery method', 'myd-delivery-pro' ),
							'type' => 'string',
						),
						'delivery_price' => array(
							'description' => __( 'Delivery price', 'myd-delivery-pro' ),
							'type' => 'number',
						),
					),
				),
			)
		);
	}

	/**
	 * Get current cart
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_cart( $request ) {
		$cart = $this->get_session_cart();
		return rest_ensure_response( $cart );
	}

	/**
	 * Update entire cart
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_cart( $request ) {
		$items = $request['items'];
		$coupon = $request['coupon'] ?? '';
		$delivery_method = $request['delivery_method'] ?? '';

		// Validate and sanitize items
		$validated_items = array();

		foreach ( $items as $item ) {
			if ( ! isset( $item['product_id'] ) || ! isset( $item['quantity'] ) ) {
				continue;
			}

			$product_id = intval( $item['product_id'] );
			$quantity = intval( $item['quantity'] );

			if ( $quantity < 1 ) {
				continue;
			}

			// Get product details
			$product = get_post( $product_id );

			if ( ! $product || $product->post_type !== 'mydelivery-produtos' ) {
				continue;
			}

			$price = floatval( get_post_meta( $product_id, 'product_price', true ) );
			$extras = isset( $item['extras'] ) ? $item['extras'] : array();

			// Calculate extras price
			$extras_price = 0;
			if ( ! empty( $extras ) ) {
				foreach ( $extras as $extra ) {
					if ( isset( $extra['price'] ) ) {
						$extras_price += floatval( $extra['price'] );
					}
				}
			}

			$item_total = ( $price + $extras_price ) * $quantity;

			$validated_items[] = array(
				'product_id' => $product_id,
				'product_name' => $product->post_title,
				'quantity' => $quantity,
				'price' => $price,
				'extras' => $extras,
				'extras_price' => $extras_price,
				'total' => $item_total,
			);
		}

		// Calculate totals
		$totals = $this->calculate_cart_totals( $validated_items, $coupon, $delivery_method );

		// Save to session
		$cart = array(
			'items' => $validated_items,
			'coupon' => $coupon,
			'delivery_method' => $delivery_method,
			'subtotal' => $totals['subtotal'],
			'delivery_price' => $totals['delivery_price'],
			'discount' => $totals['discount'],
			'total' => $totals['total'],
		);

		$this->save_session_cart( $cart );

		return rest_ensure_response( $cart );
	}

	/**
	 * Clear cart
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function clear_cart( $request ) {
		$this->clear_session_cart();

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Cart cleared successfully', 'myd-delivery-pro' ),
		) );
	}

	/**
	 * Add item to cart
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function add_item( $request ) {
		$product_id = intval( $request['product_id'] );
		$quantity = intval( $request['quantity'] );
		$extras = $request['extras'] ?? array();

		// Get product details
		$product = get_post( $product_id );

		if ( ! $product || $product->post_type !== 'mydelivery-produtos' ) {
			return new \WP_Error( 'invalid_product', __( 'Product not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		// Get current cart
		$cart = $this->get_session_cart();
		$items = $cart['items'];

		// Check if item already exists
		$item_found = false;
		foreach ( $items as &$item ) {
			if ( $item['product_id'] === $product_id && $this->compare_extras( $item['extras'], $extras ) ) {
				$item['quantity'] += $quantity;
				$item_found = true;
				break;
			}
		}

		// Add new item if not found
		if ( ! $item_found ) {
			$price = floatval( get_post_meta( $product_id, 'product_price', true ) );

			$extras_price = 0;
			if ( ! empty( $extras ) ) {
				foreach ( $extras as $extra ) {
					if ( isset( $extra['price'] ) ) {
						$extras_price += floatval( $extra['price'] );
					}
				}
			}

			$item_total = ( $price + $extras_price ) * $quantity;

			$items[] = array(
				'product_id' => $product_id,
				'product_name' => $product->post_title,
				'quantity' => $quantity,
				'price' => $price,
				'extras' => $extras,
				'extras_price' => $extras_price,
				'total' => $item_total,
			);
		}

		// Update cart
		$cart['items'] = $items;
		$totals = $this->calculate_cart_totals( $items, $cart['coupon'], $cart['delivery_method'] );

		$cart['subtotal'] = $totals['subtotal'];
		$cart['delivery_price'] = $totals['delivery_price'];
		$cart['discount'] = $totals['discount'];
		$cart['total'] = $totals['total'];

		$this->save_session_cart( $cart );

		return rest_ensure_response( $cart );
	}

	/**
	 * Update item quantity
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$product_id = intval( $request['product_id'] );
		$quantity = intval( $request['quantity'] );

		// Get current cart
		$cart = $this->get_session_cart();
		$items = $cart['items'];

		// Find and update item
		$item_found = false;
		foreach ( $items as &$item ) {
			if ( $item['product_id'] === $product_id ) {
				$item['quantity'] = $quantity;
				$item['total'] = ( $item['price'] + $item['extras_price'] ) * $quantity;
				$item_found = true;
				break;
			}
		}

		if ( ! $item_found ) {
			return new \WP_Error( 'item_not_found', __( 'Item not found in cart', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		// Update cart
		$cart['items'] = $items;
		$totals = $this->calculate_cart_totals( $items, $cart['coupon'], $cart['delivery_method'] );

		$cart['subtotal'] = $totals['subtotal'];
		$cart['delivery_price'] = $totals['delivery_price'];
		$cart['discount'] = $totals['discount'];
		$cart['total'] = $totals['total'];

		$this->save_session_cart( $cart );

		return rest_ensure_response( $cart );
	}

	/**
	 * Remove item from cart
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function remove_item( $request ) {
		$product_id = intval( $request['product_id'] );

		// Get current cart
		$cart = $this->get_session_cart();
		$items = $cart['items'];

		// Find and remove item
		$item_found = false;
		foreach ( $items as $key => $item ) {
			if ( $item['product_id'] === $product_id ) {
				unset( $items[ $key ] );
				$item_found = true;
				break;
			}
		}

		if ( ! $item_found ) {
			return new \WP_Error( 'item_not_found', __( 'Item not found in cart', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		// Re-index array
		$items = array_values( $items );

		// Update cart
		$cart['items'] = $items;
		$totals = $this->calculate_cart_totals( $items, $cart['coupon'], $cart['delivery_method'] );

		$cart['subtotal'] = $totals['subtotal'];
		$cart['delivery_price'] = $totals['delivery_price'];
		$cart['discount'] = $totals['discount'];
		$cart['total'] = $totals['total'];

		$this->save_session_cart( $cart );

		return rest_ensure_response( $cart );
	}

	/**
	 * Calculate cart totals
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function calculate_totals( $request ) {
		$items = $request['items'];
		$coupon = $request['coupon'] ?? '';
		$delivery_method = $request['delivery_method'] ?? '';
		$delivery_price = $request['delivery_price'] ?? 0;

		$totals = $this->calculate_cart_totals( $items, $coupon, $delivery_method, $delivery_price );

		return rest_ensure_response( $totals );
	}

	/**
	 * Calculate cart totals helper
	 *
	 * @param array $items
	 * @param string $coupon
	 * @param string $delivery_method
	 * @param float $delivery_price
	 * @return array
	 */
	private function calculate_cart_totals( $items, $coupon = '', $delivery_method = '', $delivery_price = 0 ) {
		$subtotal = 0;

		foreach ( $items as $item ) {
			$subtotal += $item['total'];
		}

		$discount = 0;

		// Apply coupon discount if provided
		if ( ! empty( $coupon ) ) {
			$discount = $this->calculate_coupon_discount( $coupon, $subtotal, $delivery_price );
		}

		$total = $subtotal + $delivery_price - $discount;

		return array(
			'subtotal' => round( $subtotal, 2 ),
			'delivery_price' => round( $delivery_price, 2 ),
			'discount' => round( $discount, 2 ),
			'total' => round( $total, 2 ),
		);
	}

	/**
	 * Calculate coupon discount
	 *
	 * @param string $coupon_code
	 * @param float $subtotal
	 * @param float $delivery_price
	 * @return float
	 */
	private function calculate_coupon_discount( $coupon_code, $subtotal, $delivery_price ) {
		// Find coupon
		$coupons = get_posts( array(
			'post_type' => 'mydelivery-coupons',
			'title' => strtoupper( $coupon_code ),
			'post_status' => 'publish',
			'numberposts' => 1
		) );

		if ( empty( $coupons ) ) {
			return 0;
		}

		$coupon = $coupons[0];
		$coupon_type = get_post_meta( $coupon->ID, 'myd_coupon_type', true );
		$discount_format = get_post_meta( $coupon->ID, 'myd_discount_format', true );
		$discount_value = floatval( get_post_meta( $coupon->ID, 'myd_discount_value', true ) );

		$discount = 0;

		if ( $coupon_type === 'discount-total' ) {
			// Discount on total order
			if ( $discount_format === 'percent' ) {
				$discount = ( $subtotal * $discount_value ) / 100;
			} else {
				$discount = $discount_value;
			}
		} elseif ( $coupon_type === 'discount-delivery' ) {
			// Discount on delivery
			if ( $discount_format === 'percent' ) {
				$discount = ( $delivery_price * $discount_value ) / 100;
			} else {
				$discount = $discount_value;
			}
		}

		return $discount;
	}

	/**
	 * Get cart from session/cookie
	 *
	 * @return array
	 */
	private function get_session_cart() {
		// For now, return empty cart
		// TODO: Implement session/cookie storage
		$default_cart = array(
			'items' => array(),
			'coupon' => '',
			'delivery_method' => '',
			'subtotal' => 0,
			'delivery_price' => 0,
			'discount' => 0,
			'total' => 0,
		);

		// Try to get from transient (using IP as key for now)
		$cart_key = 'myd_cart_' . $this->get_client_ip();
		$cart = get_transient( $cart_key );

		return $cart ? $cart : $default_cart;
	}

	/**
	 * Save cart to session/cookie
	 *
	 * @param array $cart
	 * @return void
	 */
	private function save_session_cart( $cart ) {
		// Save to transient (24 hours)
		$cart_key = 'myd_cart_' . $this->get_client_ip();
		set_transient( $cart_key, $cart, DAY_IN_SECONDS );
	}

	/**
	 * Clear session cart
	 *
	 * @return void
	 */
	private function clear_session_cart() {
		$cart_key = 'myd_cart_' . $this->get_client_ip();
		delete_transient( $cart_key );
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
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return sanitize_text_field( $ip );
	}

	/**
	 * Compare extras arrays
	 *
	 * @param array $extras1
	 * @param array $extras2
	 * @return bool
	 */
	private function compare_extras( $extras1, $extras2 ) {
		return json_encode( $extras1 ) === json_encode( $extras2 );
	}

	/**
	 * Get item schema for validation
	 */
	public function get_item_schema() {
		return array(
			'product_id' => array(
				'description' => __( 'Product ID', 'myd-delivery-pro' ),
				'type' => 'integer',
				'required' => true,
			),
			'quantity' => array(
				'description' => __( 'Item quantity', 'myd-delivery-pro' ),
				'type' => 'integer',
				'required' => true,
				'minimum' => 1,
			),
			'extras' => array(
				'description' => __( 'Product extras', 'myd-delivery-pro' ),
				'type' => 'array',
			),
		);
	}
}

new Cart_Api();
