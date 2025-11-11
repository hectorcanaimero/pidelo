<?php
/**
 * Categories REST API endpoints
 *
 * @package MydPro
 * @subpackage Api
 * @since 2.3.9
 */

namespace MydPro\Includes\Api\Categories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Categories API Class
 *
 * Handles CRUD operations for product categories
 */
class Categories_Api {
	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_category_routes' ] );
	}

	/**
	 * Register category routes
	 */
	public function register_category_routes() {
		// GET /categories - List all categories
		\register_rest_route(
			'myd-delivery/v1',
			'/categories',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_categories' ],
					'permission_callback' => '__return_true', // Public endpoint
				),
			)
		);

		// POST /categories - Create category
		\register_rest_route(
			'myd-delivery/v1',
			'/categories',
			array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'create_category' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_category_schema(),
				),
			)
		);

		// PUT /categories/reorder - Reorder categories
		\register_rest_route(
			'myd-delivery/v1',
			'/categories/reorder',
			array(
				array(
					'methods'  => \WP_REST_Server::EDITABLE,
					'callback' => [ $this, 'reorder_categories' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'categories' => array(
							'description' => __( 'Array of category IDs in desired order', 'myd-delivery-pro' ),
							'type' => 'array',
							'required' => true,
						),
					),
				),
			)
		);

		// PUT /categories/{id} - Update category
		\register_rest_route(
			'myd-delivery/v1',
			'/categories/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::EDITABLE,
					'callback' => [ $this, 'update_category' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_category_schema(),
				),
			)
		);

		// DELETE /categories/{id} - Delete category
		\register_rest_route(
			'myd-delivery/v1',
			'/categories/(?P<id>\d+)',
			array(
				array(
					'methods'  => \WP_REST_Server::DELETABLE,
					'callback' => [ $this, 'delete_category' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'id' => array(
							'description' => __( 'Category ID', 'myd-delivery-pro' ),
							'type' => 'integer',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Get all categories
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_categories( $request ) {
		// Get categories from settings
		$categories_raw = get_option( 'fdm-list-menu-categories', '' );

		if ( empty( $categories_raw ) ) {
			return rest_ensure_response( array(
				'categories' => array(),
				'total' => 0,
			) );
		}

		// Parse categories (format: "Category1,Category2,Category3")
		$category_names = array_filter( array_map( 'trim', explode( ',', $categories_raw ) ) );

		$categories = array();
		$index = 0;

		foreach ( $category_names as $name ) {
			// Count products in this category
			$product_count = $this->get_products_count_by_category( $name );

			$categories[] = array(
				'id' => $index,
				'name' => $name,
				'slug' => sanitize_title( $name ),
				'order' => $index,
				'product_count' => $product_count,
			);

			$index++;
		}

		$response = array(
			'categories' => $categories,
			'total' => count( $categories ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Create new category
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_category( $request ) {
		$name = sanitize_text_field( $request['name'] );

		if ( empty( $name ) ) {
			return new \WP_Error( 'missing_name', __( 'Category name is required', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Get existing categories
		$categories_raw = get_option( 'fdm-list-menu-categories', '' );
		$category_names = array_filter( array_map( 'trim', explode( ',', $categories_raw ) ) );

		// Check if category already exists
		if ( in_array( $name, $category_names ) ) {
			return new \WP_Error( 'category_exists', __( 'Category already exists', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Add new category
		$category_names[] = $name;
		$categories_string = implode( ',', $category_names );

		update_option( 'fdm-list-menu-categories', $categories_string );

		// Return created category
		$category = array(
			'id' => count( $category_names ) - 1,
			'name' => $name,
			'slug' => sanitize_title( $name ),
			'order' => count( $category_names ) - 1,
			'product_count' => 0,
		);

		return rest_ensure_response( $category );
	}

	/**
	 * Update category
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_category( $request ) {
		$id = $request['id'];
		$new_name = sanitize_text_field( $request['name'] );

		if ( empty( $new_name ) ) {
			return new \WP_Error( 'missing_name', __( 'Category name is required', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Get existing categories
		$categories_raw = get_option( 'fdm-list-menu-categories', '' );
		$category_names = array_filter( array_map( 'trim', explode( ',', $categories_raw ) ) );

		if ( ! isset( $category_names[ $id ] ) ) {
			return new \WP_Error( 'category_not_found', __( 'Category not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$old_name = $category_names[ $id ];

		// Update category name
		$category_names[ $id ] = $new_name;
		$categories_string = implode( ',', $category_names );

		update_option( 'fdm-list-menu-categories', $categories_string );

		// Update all products with this category
		$this->update_products_category( $old_name, $new_name );

		// Return updated category
		$category = array(
			'id' => $id,
			'name' => $new_name,
			'slug' => sanitize_title( $new_name ),
			'order' => $id,
			'product_count' => $this->get_products_count_by_category( $new_name ),
		);

		return rest_ensure_response( $category );
	}

	/**
	 * Delete category
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_category( $request ) {
		$id = $request['id'];

		// Get existing categories
		$categories_raw = get_option( 'fdm-list-menu-categories', '' );
		$category_names = array_filter( array_map( 'trim', explode( ',', $categories_raw ) ) );

		if ( ! isset( $category_names[ $id ] ) ) {
			return new \WP_Error( 'category_not_found', __( 'Category not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$deleted_name = $category_names[ $id ];

		// Remove category
		unset( $category_names[ $id ] );
		$category_names = array_values( $category_names ); // Re-index array
		$categories_string = implode( ',', $category_names );

		update_option( 'fdm-list-menu-categories', $categories_string );

		return rest_ensure_response( array(
			'deleted' => true,
			'id' => $id,
			'name' => $deleted_name,
			'message' => __( 'Category deleted successfully', 'myd-delivery-pro' ),
		) );
	}

	/**
	 * Reorder categories
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function reorder_categories( $request ) {
		$new_order = $request['categories'];

		if ( ! is_array( $new_order ) ) {
			return new \WP_Error( 'invalid_data', __( 'Categories must be an array', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Get existing categories
		$categories_raw = get_option( 'fdm-list-menu-categories', '' );
		$category_names = array_filter( array_map( 'trim', explode( ',', $categories_raw ) ) );

		// Reorder based on provided indices
		$reordered = array();
		foreach ( $new_order as $id ) {
			if ( isset( $category_names[ $id ] ) ) {
				$reordered[] = $category_names[ $id ];
			}
		}

		if ( count( $reordered ) !== count( $category_names ) ) {
			return new \WP_Error( 'invalid_order', __( 'Invalid category order', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}

		// Save reordered categories
		$categories_string = implode( ',', $reordered );
		update_option( 'fdm-list-menu-categories', $categories_string );

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Categories reordered successfully', 'myd-delivery-pro' ),
		) );
	}

	/**
	 * Get count of products in a category
	 *
	 * @param string $category
	 * @return int
	 */
	private function get_products_count_by_category( $category ) {
		$args = array(
			'post_type' => 'mydelivery-produtos',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'product_type',
					'value' => $category,
					'compare' => '=',
				),
			),
			'fields' => 'ids',
		);

		$query = new \WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Update products category name
	 *
	 * @param string $old_name
	 * @param string $new_name
	 * @return void
	 */
	private function update_products_category( $old_name, $new_name ) {
		$args = array(
			'post_type' => 'mydelivery-produtos',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'product_type',
					'value' => $old_name,
					'compare' => '=',
				),
			),
			'fields' => 'ids',
			'posts_per_page' => -1,
		);

		$products = get_posts( $args );

		foreach ( $products as $product_id ) {
			update_post_meta( $product_id, 'product_type', $new_name );
		}
	}

	/**
	 * Check admin permissions
	 */
	public function check_admin_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'You do not have permission to access this.', 'myd-delivery-pro' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Get category schema for validation
	 */
	public function get_category_schema() {
		return array(
			'name' => array(
				'description' => __( 'Category name', 'myd-delivery-pro' ),
				'type' => 'string',
				'required' => true,
			),
		);
	}
}

new Categories_Api();
