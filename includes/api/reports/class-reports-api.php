<?php

namespace MydPro\Includes\Api\Reports;

use MydPro\Includes\Myd_Reports;
use MydPro\Includes\Repositories\Customer_Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reports REST API endpoints
 */
class Reports_Api {
	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_reports_routes' ] );
	}

	/**
	 * Register reports routes
	 */
	public function register_reports_routes() {
		// GET /reports/sales - Sales reports
		\register_rest_route(
			'myd-delivery/v1',
			'/reports/sales',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_sales_reports' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_reports_params(),
				),
			)
		);

		// GET /reports/products - Products reports
		\register_rest_route(
			'myd-delivery/v1',
			'/reports/products',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_products_reports' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_reports_params(),
				),
			)
		);

		// GET /reports/customers - Customers reports
		\register_rest_route(
			'myd-delivery/v1',
			'/reports/customers',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_customers_reports' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_reports_params(),
				),
			)
		);

		// GET /reports/overview - General overview
		\register_rest_route(
			'myd-delivery/v1',
			'/reports/overview',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_overview_reports' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_reports_params(),
				),
			)
		);
	}

	/**
	 * Get sales reports
	 */
	public function get_sales_reports( $request ) {
		$date_from = $request->get_param( 'date_from' ) ?: date( 'Y-m-d', strtotime( '-7 days' ) );
		$date_to = $request->get_param( 'date_to' ) ?: date( 'Y-m-d' );

		// Get orders for the period
		$orders = $this->get_orders_by_period( $date_from, $date_to );
		$reports = new Myd_Reports( $orders, $date_from, $date_to );

		// Get additional sales metrics
		$sales_by_day = $this->get_sales_by_day( $date_from, $date_to );
		$sales_by_status = $this->get_sales_by_status( $date_from, $date_to );
		$sales_by_payment_method = $this->get_sales_by_payment_method( $date_from, $date_to );
		$sales_by_delivery_method = $this->get_sales_by_delivery_method( $date_from, $date_to );

		$response = array(
			'period' => array(
				'from' => $date_from,
				'to' => $date_to,
				'days' => $reports->get_period_in_days(),
			),
			'summary' => array(
				'total_sales' => $reports->get_total_orders(),
				'total_orders' => $reports->get_count_orders(),
				'average_per_day' => $reports->get_average_sales(),
				'average_order_value' => $reports->get_count_orders() > 0 ? $reports->get_total_orders() / $reports->get_count_orders() : 0,
			),
			'sales_by_day' => $sales_by_day,
			'sales_by_status' => $sales_by_status,
			'sales_by_payment_method' => $sales_by_payment_method,
			'sales_by_delivery_method' => $sales_by_delivery_method,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get products reports
	 */
	public function get_products_reports( $request ) {
		$date_from = $request->get_param( 'date_from' ) ?: date( 'Y-m-d', strtotime( '-7 days' ) );
		$date_to = $request->get_param( 'date_to' ) ?: date( 'Y-m-d' );

		// Get orders for the period
		$orders = $this->get_orders_by_period( $date_from, $date_to );
		$reports = new Myd_Reports( $orders, $date_from, $date_to );

		// Get top selling products
		$purchased_items = $reports->get_purchased_items();
		
		// Sort by quantity and get top products
		usort( $purchased_items, function( $a, $b ) {
			return $b['quantity'] - $a['quantity'];
		});

		$top_products = array_slice( $purchased_items, 0, 10 );

		// Get product categories performance
		$product_categories = $this->get_product_categories_performance( $date_from, $date_to );
		
		// Get product availability stats
		$product_stats = $this->get_product_availability_stats();

		$response = array(
			'period' => array(
				'from' => $date_from,
				'to' => $date_to,
			),
			'summary' => array(
				'total_products_sold' => array_sum( array_column( $purchased_items, 'quantity' ) ),
				'unique_products_sold' => count( $purchased_items ),
				'most_popular_product' => ! empty( $top_products ) ? $top_products[0] : null,
			),
			'top_products' => $top_products,
			'product_categories' => $product_categories,
			'product_stats' => $product_stats,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get customers reports
	 */
	public function get_customers_reports( $request ) {
		$date_from = $request->get_param( 'date_from' ) ?: date( 'Y-m-d', strtotime( '-7 days' ) );
		$date_to = $request->get_param( 'date_to' ) ?: date( 'Y-m-d' );

		// Get customer statistics
		$customers_args = array(
			'date_from' => $date_from,
			'date_to' => $date_to,
			'limit' => 0, // Get all customers for stats
		);

		$all_customers = Customer_Repository::get_all_customers( $customers_args );
		$total_customers = Customer_Repository::get_customers_count( $customers_args );

		// Get top customers by spending
		$top_customers_by_spending = array_slice( $all_customers, 0, 10 );

		// Get top customers by orders
		usort( $all_customers, function( $a, $b ) {
			return $b['total_orders'] - $a['total_orders'];
		});
		$top_customers_by_orders = array_slice( $all_customers, 0, 10 );

		// Calculate customer metrics
		$total_spent = array_sum( array_column( $all_customers, 'total_spent' ) );
		$total_orders = array_sum( array_column( $all_customers, 'total_orders' ) );
		$avg_customer_value = $total_customers > 0 ? $total_spent / $total_customers : 0;
		$avg_orders_per_customer = $total_customers > 0 ? $total_orders / $total_customers : 0;

		// Get new vs returning customers
		$customer_segmentation = $this->get_customer_segmentation( $date_from, $date_to );

		$response = array(
			'period' => array(
				'from' => $date_from,
				'to' => $date_to,
			),
			'summary' => array(
				'total_customers' => $total_customers,
				'total_spent' => $total_spent,
				'average_customer_value' => $avg_customer_value,
				'average_orders_per_customer' => $avg_orders_per_customer,
			),
			'top_customers_by_spending' => $top_customers_by_spending,
			'top_customers_by_orders' => $top_customers_by_orders,
			'customer_segmentation' => $customer_segmentation,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get overview reports
	 */
	public function get_overview_reports( $request ) {
		$date_from = $request->get_param( 'date_from' ) ?: date( 'Y-m-d', strtotime( '-7 days' ) );
		$date_to = $request->get_param( 'date_to' ) ?: date( 'Y-m-d' );

		// Get basic sales data
		$orders = $this->get_orders_by_period( $date_from, $date_to );
		$reports = new Myd_Reports( $orders, $date_from, $date_to );

		// Get customer count
		$total_customers = Customer_Repository::get_customers_count( array(
			'date_from' => $date_from,
			'date_to' => $date_to,
		) );

		// Get product stats
		$total_products = wp_count_posts( 'mydelivery-produtos' )->publish;
		$total_coupons = wp_count_posts( 'mydelivery-coupons' )->publish;

		// Get order status distribution
		$order_status_stats = $this->get_order_status_distribution();

		$response = array(
			'period' => array(
				'from' => $date_from,
				'to' => $date_to,
			),
			'summary' => array(
				'total_sales' => $reports->get_total_orders(),
				'total_orders' => $reports->get_count_orders(),
				'total_customers' => $total_customers,
				'total_products' => $total_products,
				'total_coupons' => $total_coupons,
				'average_order_value' => $reports->get_count_orders() > 0 ? $reports->get_total_orders() / $reports->get_count_orders() : 0,
			),
			'order_status_distribution' => $order_status_stats,
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Get orders by period
	 */
	private function get_orders_by_period( $date_from, $date_to ) {
		$args = array(
			'post_type' => 'mydelivery-orders',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'date_query' => array(
				array(
					'after' => $date_from,
					'before' => $date_to,
					'inclusive' => true,
				),
			),
		);

		return get_posts( $args );
	}

	/**
	 * Get sales by day
	 */
	private function get_sales_by_day( $date_from, $date_to ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT 
				DATE(p.post_date) as date,
				COUNT(p.ID) as orders,
				SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_sales
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = 'order_total'
			LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'order_status'
			WHERE p.post_type = 'mydelivery-orders' 
			AND p.post_status = 'publish'
			AND pm_status.meta_value = 'finished'
			AND DATE(p.post_date) BETWEEN %s AND %s
			GROUP BY DATE(p.post_date)
			ORDER BY DATE(p.post_date)
		", $date_from, $date_to );

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get sales by status
	 */
	private function get_sales_by_status( $date_from, $date_to ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT 
				pm_status.meta_value as status,
				COUNT(p.ID) as count,
				SUM(CASE WHEN pm_status.meta_value = 'finished' THEN CAST(pm_total.meta_value AS DECIMAL(10,2)) ELSE 0 END) as total_sales
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = 'order_total'
			LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'order_status'
			WHERE p.post_type = 'mydelivery-orders' 
			AND p.post_status = 'publish'
			AND DATE(p.post_date) BETWEEN %s AND %s
			GROUP BY pm_status.meta_value
			ORDER BY count DESC
		", $date_from, $date_to );

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get sales by payment method
	 */
	private function get_sales_by_payment_method( $date_from, $date_to ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT 
				pm_method.meta_value as payment_method,
				COUNT(p.ID) as count,
				SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_sales
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = 'order_total'
			LEFT JOIN {$wpdb->postmeta} pm_method ON p.ID = pm_method.post_id AND pm_method.meta_key = 'order_payment_method'
			LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'order_status'
			WHERE p.post_type = 'mydelivery-orders' 
			AND p.post_status = 'publish'
			AND pm_status.meta_value = 'finished'
			AND DATE(p.post_date) BETWEEN %s AND %s
			GROUP BY pm_method.meta_value
			ORDER BY count DESC
		", $date_from, $date_to );

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get sales by delivery method
	 */
	private function get_sales_by_delivery_method( $date_from, $date_to ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT 
				pm_ship.meta_value as delivery_method,
				COUNT(p.ID) as count,
				SUM(CAST(pm_total.meta_value AS DECIMAL(10,2))) as total_sales
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = 'order_total'
			LEFT JOIN {$wpdb->postmeta} pm_ship ON p.ID = pm_ship.post_id AND pm_ship.meta_key = 'order_ship_method'
			LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'order_status'
			WHERE p.post_type = 'mydelivery-orders' 
			AND p.post_status = 'publish'
			AND pm_status.meta_value = 'finished'
			AND DATE(p.post_date) BETWEEN %s AND %s
			GROUP BY pm_ship.meta_value
			ORDER BY count DESC
		", $date_from, $date_to );

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get product categories performance
	 */
	private function get_product_categories_performance( $date_from, $date_to ) {
		// This would require analyzing order items and matching with product categories
		// For now, return basic structure
		return array();
	}

	/**
	 * Get product availability stats
	 */
	private function get_product_availability_stats() {
		global $wpdb;

		$sql = "
			SELECT 
				pm.meta_value as availability,
				COUNT(p.ID) as count
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'product_available'
			WHERE p.post_type = 'mydelivery-produtos' 
			AND p.post_status = 'publish'
			GROUP BY pm.meta_value
		";

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get customer segmentation
	 */
	private function get_customer_segmentation( $date_from, $date_to ) {
		// New vs returning customers in period
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT 
				pm_phone.meta_value as phone,
				COUNT(p.ID) as orders_in_period,
				MIN(p.post_date) as first_order_in_period
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_phone ON p.ID = pm_phone.post_id AND pm_phone.meta_key = 'customer_phone'
			WHERE p.post_type = 'mydelivery-orders' 
			AND p.post_status = 'publish'
			AND DATE(p.post_date) BETWEEN %s AND %s
			GROUP BY pm_phone.meta_value
		", $date_from, $date_to );

		$customers_in_period = $wpdb->get_results( $sql );
		$new_customers = 0;
		$returning_customers = 0;

		foreach ( $customers_in_period as $customer ) {
			// Check if customer had orders before this period
			$previous_orders = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(p.ID)
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm_phone ON p.ID = pm_phone.post_id AND pm_phone.meta_key = 'customer_phone'
				WHERE p.post_type = 'mydelivery-orders' 
				AND p.post_status = 'publish'
				AND pm_phone.meta_value = %s
				AND DATE(p.post_date) < %s
			", $customer->phone, $date_from ) );

			if ( $previous_orders > 0 ) {
				$returning_customers++;
			} else {
				$new_customers++;
			}
		}

		return array(
			'new_customers' => $new_customers,
			'returning_customers' => $returning_customers,
			'total_customers' => count( $customers_in_period ),
		);
	}

	/**
	 * Get order status distribution
	 */
	private function get_order_status_distribution() {
		global $wpdb;

		$sql = "
			SELECT 
				pm.meta_value as status,
				COUNT(p.ID) as count
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'order_status'
			WHERE p.post_type = 'mydelivery-orders' 
			AND p.post_status = 'publish'
			GROUP BY pm.meta_value
			ORDER BY count DESC
		";

		return $wpdb->get_results( $sql );
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
	 * Get reports parameters
	 */
	public function get_reports_params() {
		return array(
			'date_from' => array(
				'description' => __( 'Start date for the report (YYYY-MM-DD)', 'myd-delivery-pro' ),
				'type' => 'string',
				'default' => date( 'Y-m-d', strtotime( '-7 days' ) ),
			),
			'date_to' => array(
				'description' => __( 'End date for the report (YYYY-MM-DD)', 'myd-delivery-pro' ),
				'type' => 'string',
				'default' => date( 'Y-m-d' ),
			),
		);
	}
}

new Reports_Api();