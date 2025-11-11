<?php
/**
 * Categories API Tests
 *
 * @package MydPro
 * @subpackage Tests
 * @since 2.3.9
 */

namespace MydPro\Tests\Api;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Test Categories API endpoints
 */
class Test_Categories_Api extends WP_UnitTestCase {

	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	protected $admin_id;

	/**
	 * Editor user ID
	 *
	 * @var int
	 */
	protected $editor_id;

	/**
	 * Test categories
	 *
	 * @var array
	 */
	protected $test_categories = array(
		'Pizzas',
		'Hamburguesas',
		'Bebidas',
	);

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Create test users
		$this->admin_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );

		$this->editor_id = $this->factory->user->create( array(
			'role' => 'editor',
		) );

		// Clear existing categories
		delete_option( 'fdm-list-menu-categories' );
	}

	/**
	 * Tear down test environment
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up
		delete_option( 'fdm-list-menu-categories' );
	}

	/**
	 * Test GET /categories - List all categories (empty)
	 */
	public function test_get_categories_empty() {
		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/categories' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'categories', $data );
		$this->assertArrayHasKey( 'total', $data );
		$this->assertEquals( 0, $data['total'] );
		$this->assertEmpty( $data['categories'] );
	}

	/**
	 * Test GET /categories - List all categories (with data)
	 */
	public function test_get_categories_with_data() {
		// Setup test data
		update_option( 'fdm-list-menu-categories', implode( ',', $this->test_categories ) );

		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/categories' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 3, $data['total'] );
		$this->assertCount( 3, $data['categories'] );

		// Verify first category structure
		$first_category = $data['categories'][0];
		$this->assertArrayHasKey( 'id', $first_category );
		$this->assertArrayHasKey( 'name', $first_category );
		$this->assertArrayHasKey( 'slug', $first_category );
		$this->assertArrayHasKey( 'order', $first_category );
		$this->assertArrayHasKey( 'product_count', $first_category );

		$this->assertEquals( 'Pizzas', $first_category['name'] );
		$this->assertEquals( 'pizzas', $first_category['slug'] );
	}

	/**
	 * Test POST /categories - Create category as admin
	 */
	public function test_create_category_as_admin() {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/categories' );
		$request->set_param( 'name', 'Postres' );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertEquals( 'Postres', $data['name'] );
		$this->assertEquals( 'postres', $data['slug'] );

		// Verify it was saved
		$saved_categories = get_option( 'fdm-list-menu-categories' );
		$this->assertStringContainsString( 'Postres', $saved_categories );
	}

	/**
	 * Test POST /categories - Create category without permission
	 */
	public function test_create_category_without_permission() {
		wp_set_current_user( $this->editor_id );

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/categories' );
		$request->set_param( 'name', 'Postres' );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'rest_forbidden', $data['code'] );
	}

	/**
	 * Test POST /categories - Create category without name
	 */
	public function test_create_category_without_name() {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/categories' );
		$request->set_param( 'name', '' );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'missing_name', $data['code'] );
	}

	/**
	 * Test POST /categories - Create duplicate category
	 */
	public function test_create_duplicate_category() {
		update_option( 'fdm-list-menu-categories', 'Pizzas' );
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/categories' );
		$request->set_param( 'name', 'Pizzas' );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'category_exists', $data['code'] );
	}

	/**
	 * Test PUT /categories/{id} - Update category
	 */
	public function test_update_category() {
		update_option( 'fdm-list-menu-categories', implode( ',', $this->test_categories ) );
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'PUT', '/myd-delivery/v1/categories/0' );
		$request->set_param( 'name', 'Pizzas Gourmet' );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'Pizzas Gourmet', $data['name'] );
		$this->assertEquals( 'pizzas-gourmet', $data['slug'] );

		// Verify it was updated
		$saved_categories = get_option( 'fdm-list-menu-categories' );
		$this->assertStringContainsString( 'Pizzas Gourmet', $saved_categories );
		$this->assertStringNotContainsString( 'Pizzas,', $saved_categories );
	}

	/**
	 * Test PUT /categories/{id} - Update non-existent category
	 */
	public function test_update_nonexistent_category() {
		update_option( 'fdm-list-menu-categories', 'Pizzas' );
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'PUT', '/myd-delivery/v1/categories/99' );
		$request->set_param( 'name', 'Test' );

		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'category_not_found', $data['code'] );
	}

	/**
	 * Test PUT /categories/{id} - Update without permission
	 */
	public function test_update_category_without_permission() {
		update_option( 'fdm-list-menu-categories', 'Pizzas' );
		wp_set_current_user( $this->editor_id );

		$request = new WP_REST_Request( 'PUT', '/myd-delivery/v1/categories/0' );
		$request->set_param( 'name', 'Pizzas Gourmet' );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test DELETE /categories/{id} - Delete category
	 */
	public function test_delete_category() {
		update_option( 'fdm-list-menu-categories', implode( ',', $this->test_categories ) );
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'DELETE', '/myd-delivery/v1/categories/1' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['deleted'] );
		$this->assertEquals( 'Hamburguesas', $data['name'] );

		// Verify it was deleted
		$saved_categories = get_option( 'fdm-list-menu-categories' );
		$this->assertStringNotContainsString( 'Hamburguesas', $saved_categories );

		// Verify remaining categories
		$categories_array = array_filter( array_map( 'trim', explode( ',', $saved_categories ) ) );
		$this->assertCount( 2, $categories_array );
	}

	/**
	 * Test DELETE /categories/{id} - Delete non-existent category
	 */
	public function test_delete_nonexistent_category() {
		update_option( 'fdm-list-menu-categories', 'Pizzas' );
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'DELETE', '/myd-delivery/v1/categories/99' );
		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'category_not_found', $data['code'] );
	}

	/**
	 * Test DELETE /categories/{id} - Delete without permission
	 */
	public function test_delete_category_without_permission() {
		update_option( 'fdm-list-menu-categories', 'Pizzas' );
		wp_set_current_user( $this->editor_id );

		$request = new WP_REST_Request( 'DELETE', '/myd-delivery/v1/categories/0' );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test PUT /categories/reorder - Reorder categories
	 */
	public function test_reorder_categories() {
		update_option( 'fdm-list-menu-categories', implode( ',', $this->test_categories ) );
		wp_set_current_user( $this->admin_id );

		// Reorder: move last to first
		$request = new WP_REST_Request( 'PUT', '/myd-delivery/v1/categories/reorder' );
		$request->set_param( 'categories', array( 2, 0, 1 ) ); // Bebidas, Pizzas, Hamburguesas

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );

		// Verify new order
		$saved_categories = get_option( 'fdm-list-menu-categories' );
		$categories_array = array_filter( array_map( 'trim', explode( ',', $saved_categories ) ) );

		$this->assertEquals( 'Bebidas', $categories_array[0] );
		$this->assertEquals( 'Pizzas', $categories_array[1] );
		$this->assertEquals( 'Hamburguesas', $categories_array[2] );
	}

	/**
	 * Test PUT /categories/reorder - Invalid order
	 */
	public function test_reorder_categories_invalid_order() {
		update_option( 'fdm-list-menu-categories', implode( ',', $this->test_categories ) );
		wp_set_current_user( $this->admin_id );

		// Try to reorder with missing indices
		$request = new WP_REST_Request( 'PUT', '/myd-delivery/v1/categories/reorder' );
		$request->set_param( 'categories', array( 0, 1 ) ); // Missing one category

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'invalid_order', $data['code'] );
	}

	/**
	 * Test PUT /categories/reorder - Without permission
	 */
	public function test_reorder_categories_without_permission() {
		update_option( 'fdm-list-menu-categories', implode( ',', $this->test_categories ) );
		wp_set_current_user( $this->editor_id );

		$request = new WP_REST_Request( 'PUT', '/myd-delivery/v1/categories/reorder' );
		$request->set_param( 'categories', array( 2, 0, 1 ) );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test category schema validation
	 */
	public function test_category_schema() {
		wp_set_current_user( $this->admin_id );

		// Test with valid data
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/categories' );
		$request->set_param( 'name', 'Ensaladas' );

		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Test with special characters (should be sanitized)
		$request = new WP_REST_Request( 'POST', '/myd-delivery/v1/categories' );
		$request->set_param( 'name', '<script>alert("test")</script>' );

		$response = rest_do_request( $request );
		$data = $response->get_data();

		// Name should be sanitized
		$this->assertStringNotContainsString( '<script>', $data['name'] );
	}

	/**
	 * Test public access to GET /categories
	 */
	public function test_get_categories_public_access() {
		update_option( 'fdm-list-menu-categories', 'Pizzas,Bebidas' );

		// No user logged in
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/categories' );
		$response = rest_do_request( $request );

		// Should still work for public
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 2, $data['total'] );
	}

	/**
	 * Test product count in categories
	 */
	public function test_category_product_count() {
		update_option( 'fdm-list-menu-categories', 'Pizzas' );

		// Create test products
		$product_id = $this->factory->post->create( array(
			'post_type' => 'mydelivery-produtos',
			'post_status' => 'publish',
		) );

		update_post_meta( $product_id, 'product_type', 'Pizzas' );

		$request = new WP_REST_Request( 'GET', '/myd-delivery/v1/categories' );
		$response = rest_do_request( $request );

		$data = $response->get_data();
		$this->assertEquals( 1, $data['categories'][0]['product_count'] );
	}
}
