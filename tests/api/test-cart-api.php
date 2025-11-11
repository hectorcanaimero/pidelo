<?php
/**
 * Cart API Tests
 *
 * @package MydPro
 * @subpackage Tests
 * @since 2.3.9
 */

namespace MydPro\Tests\Api;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Test Cart API endpoints
 */
class Test_Cart_Api extends WP_UnitTestCase {

	/**
	 * Test product IDs
	 *
	 * @var array
	 */
	protected $products = array();

	/**
	 * Test coupon ID
	 *
	 * @var int
	 */
	protected $coupon_id;

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test products
		$this->products['pizza'] = $this->factory->post->create( array(
			'post_type' => 'mydelivery-produtos',
			'post_status' => 'publish',
			'post_title' => 'Pizza Margherita',
		) );
		update_post_meta( $this->products['pizza'], 'product_price', '15.00' );
		update_post_meta( $this->products['pizza'], 'product_type', 'Pizzas' );

		$this->products['burger'] = $this->factory->post->create( array(
			'post_type' => 'mydelivery-produtos',
			'post_status' => 'publish',
			'post_title' => 'Hamburguesa Clásica',
		) );
		update_post_meta( $this->products['burger'], 'product_price', '12.50' );
		update_post_meta( $this->products['burger'], 'product_type', 'Hamburguesas' );

		$this->products['drink'] = $this->factory->post->create( array(
			'post_type' => 'mydelivery-produtos',
			'post_status' => 'publish',
			'post_title' => 'Coca Cola',
		) );
		update_post_meta( $this->products['drink'], 'product_price', '3.00' );
		update_post_meta( $this->products['drink'], 'product_type', 'Bebidas' );

		// Create test coupon (10% discount on total)
		$this->coupon_id = $this->factory->post->create( array(
			'post_type' => 'mydelivery-coupons',
			'post_status' => 'publish',
			'post_title' => 'DESCUENTO10',
		) );
		update_post_meta( $this->coupon_id, 'myd_coupon_type', 'discount-total' );
		update_post_meta( $this->coupon_id, 'myd_discount_format', 'percent' );
		update_post_meta( $this->coupon_id, 'myd_discount_value', '10' );

		// Clear all transients
		$this->clear_cart_transients();
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->clear_cart_transients();
	}

	/**
	 * Clear all cart transients
	 */
	private function clear_cart_transients() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_myd_cart_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_myd_cart_%'" );
	}

	/**
	 * Test GET /cart - Empty cart
	 */
	public function test_get_cart_empty() {
		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/cart' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'subtotal', $data );
		$this->assertArrayHasKey( 'total', $data );
		$this->assertEmpty( $data['items'] );
		$this->assertEquals( 0, $data['subtotal'] );
		$this->assertEquals( 0, $data['total'] );
	}

	/**
	 * Test POST /cart/items - Add item to cart
	 */
	public function test_add_item_to_cart() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request->set_param( 'product_id', $this->products['pizza'] );
		$request->set_param( 'quantity', 2 );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 1, $data['items'] );

		$item = $data['items'][0];
		$this->assertEquals( $this->products['pizza'], $item['product_id'] );
		$this->assertEquals( 'Pizza Margherita', $item['product_name'] );
		$this->assertEquals( 2, $item['quantity'] );
		$this->assertEquals( 15.00, $item['price'] );
		$this->assertEquals( 30.00, $item['total'] );

		$this->assertEquals( 30.00, $data['subtotal'] );
		$this->assertEquals( 30.00, $data['total'] );
	}

	/**
	 * Test POST /cart/items - Add item with extras
	 */
	public function test_add_item_with_extras() {
		$extras = array(
			array(
				'name' => 'Extra queso',
				'price' => 2.00,
			),
			array(
				'name' => 'Champiñones',
				'price' => 1.50,
			),
		);

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request->set_param( 'product_id', $this->products['pizza'] );
		$request->set_param( 'quantity', 1 );
		$request->set_param( 'extras', $extras );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$item = $data['items'][0];

		$this->assertEquals( 3.50, $item['extras_price'] );
		$this->assertEquals( 18.50, $item['total'] ); // (15 + 3.50) * 1
	}

	/**
	 * Test POST /cart/items - Add invalid product
	 */
	public function test_add_invalid_product() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request->set_param( 'product_id', 99999 );
		$request->set_param( 'quantity', 1 );

		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'invalid_product', $data['code'] );
	}

	/**
	 * Test POST /cart/items - Add multiple items
	 */
	public function test_add_multiple_items() {
		// Add pizza
		$request1 = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request1->set_param( 'product_id', $this->products['pizza'] );
		$request1->set_param( 'quantity', 2 );
		rest_do_request( $request1 );

		// Add burger
		$request2 = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request2->set_param( 'product_id', $this->products['burger'] );
		$request2->set_param( 'quantity', 1 );
		$response = rest_do_request( $request2 );

		$data = $response->get_data();
		$this->assertCount( 2, $data['items'] );
		$this->assertEquals( 42.50, $data['subtotal'] ); // (15 * 2) + 12.50
	}

	/**
	 * Test PUT /cart/items/{id} - Update item quantity
	 */
	public function test_update_item_quantity() {
		// Add item first
		$add_request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$add_request->set_param( 'product_id', $this->products['pizza'] );
		$add_request->set_param( 'quantity', 2 );
		rest_do_request( $add_request );

		// Update quantity
		$update_request = new WP_REST_Request( 'PUT', '/myd-delivery/v1/cart/items/' . $this->products['pizza'] );
		$update_request->set_param( 'quantity', 5 );

		$response = rest_do_request( $update_request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$item = $data['items'][0];

		$this->assertEquals( 5, $item['quantity'] );
		$this->assertEquals( 75.00, $item['total'] ); // 15 * 5
		$this->assertEquals( 75.00, $data['subtotal'] );
	}

	/**
	 * Test PUT /cart/items/{id} - Update non-existent item
	 */
	public function test_update_nonexistent_item() {
		$request = new WP_REST_Request( 'PUT', '/myd-delivery/v1/cart/items/99999' );
		$request->set_param( 'quantity', 3 );

		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'item_not_found', $data['code'] );
	}

	/**
	 * Test DELETE /cart/items/{id} - Remove item
	 */
	public function test_remove_item() {
		// Add two items
		$request1 = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request1->set_param( 'product_id', $this->products['pizza'] );
		$request1->set_param( 'quantity', 2 );
		rest_do_request( $request1 );

		$request2 = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request2->set_param( 'product_id', $this->products['burger'] );
		$request2->set_param( 'quantity', 1 );
		rest_do_request( $request2 );

		// Remove pizza
		$delete_request = new WP_REST_Request( 'DELETE', '/myd-delivery/v1/cart/items/' . $this->products['pizza'] );
		$response = rest_do_request( $delete_request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 1, $data['items'] );
		$this->assertEquals( $this->products['burger'], $data['items'][0]['product_id'] );
		$this->assertEquals( 12.50, $data['subtotal'] );
	}

	/**
	 * Test DELETE /cart/items/{id} - Remove non-existent item
	 */
	public function test_remove_nonexistent_item() {
		$request = new WP_REST_Request( 'DELETE', '/myd-delivery/v1/cart/items/99999' );
		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'item_not_found', $data['code'] );
	}

	/**
	 * Test POST /cart - Update entire cart
	 */
	public function test_update_entire_cart() {
		$items = array(
			array(
				'product_id' => $this->products['pizza'],
				'quantity' => 2,
				'price' => 15.00,
				'extras' => array(),
				'extras_price' => 0,
				'total' => 30.00,
			),
			array(
				'product_id' => $this->products['burger'],
				'quantity' => 1,
				'price' => 12.50,
				'extras' => array(),
				'extras_price' => 0,
				'total' => 12.50,
			),
		);

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart' );
		$request->set_param( 'items', $items );
		$request->set_param( 'delivery_method', 'delivery' );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount( 2, $data['items'] );
		$this->assertEquals( 42.50, $data['subtotal'] );
		$this->assertEquals( 'delivery', $data['delivery_method'] );
	}

	/**
	 * Test DELETE /cart - Clear cart
	 */
	public function test_clear_cart() {
		// Add items first
		$add_request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$add_request->set_param( 'product_id', $this->products['pizza'] );
		$add_request->set_param( 'quantity', 2 );
		rest_do_request( $add_request );

		// Clear cart
		$clear_request = new WP_REST_Request( 'DELETE', '/myd-delivery/v1/cart' );
		$response = rest_do_request( $clear_request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );

		// Verify cart is empty
		$get_request = new WP_REST_Request( 'GET', '/myd-delivery/v1/cart' );
		$get_response = rest_do_request( $get_request );
		$get_data = $get_response->get_data();

		$this->assertEmpty( $get_data['items'] );
		$this->assertEquals( 0, $get_data['total'] );
	}

	/**
	 * Test POST /cart/calculate - Calculate totals
	 */
	public function test_calculate_totals() {
		$items = array(
			array(
				'product_id' => $this->products['pizza'],
				'quantity' => 2,
				'price' => 15.00,
				'extras' => array(),
				'extras_price' => 0,
				'total' => 30.00,
			),
		);

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/calculate' );
		$request->set_param( 'items', $items );
		$request->set_param( 'delivery_price', 5.00 );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 30.00, $data['subtotal'] );
		$this->assertEquals( 5.00, $data['delivery_price'] );
		$this->assertEquals( 0, $data['discount'] );
		$this->assertEquals( 35.00, $data['total'] );
	}

	/**
	 * Test POST /cart/calculate - With coupon discount (percentage)
	 */
	public function test_calculate_with_percentage_coupon() {
		$items = array(
			array(
				'product_id' => $this->products['pizza'],
				'quantity' => 2,
				'price' => 15.00,
				'extras' => array(),
				'extras_price' => 0,
				'total' => 30.00,
			),
		);

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/calculate' );
		$request->set_param( 'items', $items );
		$request->set_param( 'coupon', 'DESCUENTO10' );
		$request->set_param( 'delivery_price', 0 );

		$response = rest_do_request( $request );

		$data = $response->get_data();
		$this->assertEquals( 30.00, $data['subtotal'] );
		$this->assertEquals( 3.00, $data['discount'] ); // 10% of 30
		$this->assertEquals( 27.00, $data['total'] ); // 30 - 3
	}

	/**
	 * Test POST /cart/calculate - With fixed amount coupon
	 */
	public function test_calculate_with_fixed_coupon() {
		// Create fixed amount coupon
		$fixed_coupon = $this->factory->post->create( array(
			'post_type' => 'mydelivery-coupons',
			'post_status' => 'publish',
			'post_title' => 'FIJO5',
		) );
		update_post_meta( $fixed_coupon, 'myd_coupon_type', 'discount-total' );
		update_post_meta( $fixed_coupon, 'myd_discount_format', 'fixed' );
		update_post_meta( $fixed_coupon, 'myd_discount_value', '5' );

		$items = array(
			array(
				'product_id' => $this->products['pizza'],
				'quantity' => 1,
				'price' => 15.00,
				'extras' => array(),
				'extras_price' => 0,
				'total' => 15.00,
			),
		);

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/calculate' );
		$request->set_param( 'items', $items );
		$request->set_param( 'coupon', 'FIJO5' );
		$request->set_param( 'delivery_price', 0 );

		$response = rest_do_request( $request );

		$data = $response->get_data();
		$this->assertEquals( 15.00, $data['subtotal'] );
		$this->assertEquals( 5.00, $data['discount'] );
		$this->assertEquals( 10.00, $data['total'] );
	}

	/**
	 * Test POST /cart/calculate - With delivery discount coupon
	 */
	public function test_calculate_with_delivery_coupon() {
		// Create delivery discount coupon
		$delivery_coupon = $this->factory->post->create( array(
			'post_type' => 'mydelivery-coupons',
			'post_status' => 'publish',
			'post_title' => 'ENVIOGRATIS',
		) );
		update_post_meta( $delivery_coupon, 'myd_coupon_type', 'discount-delivery' );
		update_post_meta( $delivery_coupon, 'myd_discount_format', 'percent' );
		update_post_meta( $delivery_coupon, 'myd_discount_value', '100' );

		$items = array(
			array(
				'product_id' => $this->products['pizza'],
				'quantity' => 1,
				'price' => 15.00,
				'extras' => array(),
				'extras_price' => 0,
				'total' => 15.00,
			),
		);

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/calculate' );
		$request->set_param( 'items', $items );
		$request->set_param( 'coupon', 'ENVIOGRATIS' );
		$request->set_param( 'delivery_price', 5.00 );

		$response = rest_do_request( $request );

		$data = $response->get_data();
		$this->assertEquals( 15.00, $data['subtotal'] );
		$this->assertEquals( 5.00, $data['delivery_price'] );
		$this->assertEquals( 5.00, $data['discount'] ); // 100% of delivery
		$this->assertEquals( 15.00, $data['total'] ); // 15 + 5 - 5
	}

	/**
	 * Test POST /cart/calculate - With invalid coupon
	 */
	public function test_calculate_with_invalid_coupon() {
		$items = array(
			array(
				'product_id' => $this->products['pizza'],
				'quantity' => 1,
				'price' => 15.00,
				'extras' => array(),
				'extras_price' => 0,
				'total' => 15.00,
			),
		);

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/calculate' );
		$request->set_param( 'items', $items );
		$request->set_param( 'coupon', 'INVALID' );
		$request->set_param( 'delivery_price', 0 );

		$response = rest_do_request( $request );

		$data = $response->get_data();
		$this->assertEquals( 0, $data['discount'] ); // Invalid coupon = no discount
		$this->assertEquals( 15.00, $data['total'] );
	}

	/**
	 * Test cart persistence in session (transient)
	 */
	public function test_cart_persistence() {
		// Add item
		$request1 = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request1->set_param( 'product_id', $this->products['pizza'] );
		$request1->set_param( 'quantity', 2 );
		rest_do_request( $request1 );

		// Get cart (should retrieve from transient)
		$request2 = new WP_REST_Request( 'GET', '/myd-delivery/v1/cart' );
		$response = rest_do_request( $request2 );

		$data = $response->get_data();
		$this->assertCount( 1, $data['items'] );
		$this->assertEquals( $this->products['pizza'], $data['items'][0]['product_id'] );
		$this->assertEquals( 2, $data['items'][0]['quantity'] );
	}

	/**
	 * Test cart with multiple same items (should merge quantities)
	 */
	public function test_add_same_item_twice() {
		// Add pizza first time
		$request1 = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request1->set_param( 'product_id', $this->products['pizza'] );
		$request1->set_param( 'quantity', 2 );
		$request1->set_param( 'extras', array() );
		rest_do_request( $request1 );

		// Add pizza second time (same extras)
		$request2 = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request2->set_param( 'product_id', $this->products['pizza'] );
		$request2->set_param( 'quantity', 3 );
		$request2->set_param( 'extras', array() );
		$response = rest_do_request( $request2 );

		$data = $response->get_data();
		$this->assertCount( 1, $data['items'] ); // Should still be 1 item
		$this->assertEquals( 5, $data['items'][0]['quantity'] ); // 2 + 3 = 5
		$this->assertEquals( 75.00, $data['items'][0]['total'] ); // 15 * 5
	}

	/**
	 * Test cart with same product but different extras (should be separate items)
	 */
	public function test_add_same_product_different_extras() {
		// Add pizza with extra cheese
		$request1 = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request1->set_param( 'product_id', $this->products['pizza'] );
		$request1->set_param( 'quantity', 1 );
		$request1->set_param( 'extras', array(
			array( 'name' => 'Extra queso', 'price' => 2.00 ),
		) );
		rest_do_request( $request1 );

		// Add pizza without extras
		$request2 = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request2->set_param( 'product_id', $this->products['pizza'] );
		$request2->set_param( 'quantity', 1 );
		$request2->set_param( 'extras', array() );
		$response = rest_do_request( $request2 );

		$data = $response->get_data();
		$this->assertCount( 2, $data['items'] ); // Should be 2 separate items
	}

	/**
	 * Test public access to cart endpoints
	 */
	public function test_public_access_to_cart() {
		// No user logged in
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/cart' );
		$response = rest_do_request( $request );

		// Should work without authentication
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test cart schema validation
	 */
	public function test_cart_item_schema() {
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/items' );
		$request->set_param( 'product_id', $this->products['pizza'] );
		$request->set_param( 'quantity', 1 );

		$response = rest_do_request( $request );
		$data = $response->get_data();
		$item = $data['items'][0];

		// Verify all required fields are present
		$this->assertArrayHasKey( 'product_id', $item );
		$this->assertArrayHasKey( 'product_name', $item );
		$this->assertArrayHasKey( 'quantity', $item );
		$this->assertArrayHasKey( 'price', $item );
		$this->assertArrayHasKey( 'extras', $item );
		$this->assertArrayHasKey( 'extras_price', $item );
		$this->assertArrayHasKey( 'total', $item );
	}

	/**
	 * Test rounding precision in calculations
	 */
	public function test_calculation_precision() {
		$items = array(
			array(
				'product_id' => $this->products['pizza'],
				'quantity' => 3,
				'price' => 15.99,
				'extras' => array(),
				'extras_price' => 0,
				'total' => 47.97,
			),
		);

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/cart/calculate' );
		$request->set_param( 'items', $items );
		$request->set_param( 'delivery_price', 3.33 );

		$response = rest_do_request( $request );
		$data = $response->get_data();

		// Check that values are properly rounded to 2 decimals
		$this->assertEquals( 47.97, $data['subtotal'] );
		$this->assertEquals( 3.33, $data['delivery_price'] );
		$this->assertEquals( 51.30, $data['total'] );
	}
}
