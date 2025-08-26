<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customer Class
 * 
 * Represents a customer with their orders and statistics
 * 
 * @since 2.2.19
 */
class Customer {

	/**
	 * Customer phone number (unique identifier)
	 * 
	 * @var string
	 */
	public $phone;

	/**
	 * Customer name
	 * 
	 * @var string
	 */
	public $name;

	/**
	 * Total number of orders
	 * 
	 * @var int
	 */
	public $total_orders;

	/**
	 * Total amount spent
	 * 
	 * @var float
	 */
	public $total_spent;

	/**
	 * Last order date
	 * 
	 * @var string
	 */
	public $last_order_date;

	/**
	 * First order date
	 * 
	 * @var string
	 */
	public $first_order_date;

	/**
	 * Customer since (human readable)
	 * 
	 * @var string
	 */
	public $customer_since;

	/**
	 * Customer orders
	 * 
	 * @var array
	 */
	private $orders = null;

	/**
	 * Customer addresses
	 * 
	 * @var array
	 */
	private $addresses = null;

	/**
	 * Constructor
	 * 
	 * @param array $data Customer data
	 */
	public function __construct( $data = [] ) {
		$this->phone = $data['phone'] ?? '';
		$this->name = $data['name'] ?? '';
		$this->total_orders = $data['total_orders'] ?? 0;
		$this->total_spent = $data['total_spent'] ?? 0.0;
		$this->last_order_date = $data['last_order_date'] ?? '';
		$this->first_order_date = $data['first_order_date'] ?? '';
		$this->customer_since = $data['customer_since'] ?? '';
	}

	/**
	 * Get customer orders
	 * 
	 * @param array $args Query arguments
	 * @return array Customer orders
	 */
	public function get_orders( $args = [] ) {
		if ( $this->orders === null || ! empty( $args ) ) {
			$this->orders = \MydPro\Includes\Repositories\Customer_Repository::get_customer_orders( $this->phone, $args );
		}

		return $this->orders;
	}

	/**
	 * Get customer addresses
	 * 
	 * @return array Customer addresses
	 */
	public function get_addresses() {
		if ( $this->addresses === null ) {
			$this->addresses = \MydPro\Includes\Repositories\Customer_Repository::get_customer_addresses( $this->phone );
		}

		return $this->addresses;
	}

	/**
	 * Get formatted total spent
	 * 
	 * @return string Formatted total spent
	 */
	public function get_formatted_total_spent() {
		$currency_symbol = \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' );
		
		return $currency_symbol . \MydPro\Includes\Myd_Store_Formatting::format_price( $this->total_spent );
	}

	/**
	 * Get average order value
	 * 
	 * @return float Average order value
	 */
	public function get_average_order_value() {
		if ( $this->total_orders === 0 ) {
			return 0.0;
		}

		return $this->total_spent / $this->total_orders;
	}

	/**
	 * Get formatted average order value
	 * 
	 * @return string Formatted average order value
	 */
	public function get_formatted_average_order_value() {
		$currency_symbol = \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' );
		
		$avg = $this->get_average_order_value();
		return $currency_symbol . \MydPro\Includes\Myd_Store_Formatting::format_price( $avg );
	}

	/**
	 * Get most used address
	 * 
	 * @return array|null Most used address or null
	 */
	public function get_most_used_address() {
		$addresses = $this->get_addresses();
		
		if ( empty( $addresses ) ) {
			return null;
		}

		// Addresses are already sorted by usage_count DESC
		return $addresses[0];
	}

	/**
	 * Get customer type based on order frequency
	 * 
	 * @return string Customer type
	 */
	public function get_customer_type() {
		if ( $this->total_orders === 1 ) {
			return __( 'Nuevo Cliente', 'myd-delivery-pro' );
		} elseif ( $this->total_orders >= 2 && $this->total_orders <= 5 ) {
			return __( 'Cliente Regular', 'myd-delivery-pro' );
		} elseif ( $this->total_orders >= 6 && $this->total_orders <= 15 ) {
			return __( 'Cliente Frecuente', 'myd-delivery-pro' );
		} else {
			return __( 'Cliente VIP', 'myd-delivery-pro' );
		}
	}

	/**
	 * Get customer type class for styling
	 * 
	 * @return string CSS class name
	 */
	public function get_customer_type_class() {
		if ( $this->total_orders === 1 ) {
			return 'customer-new';
		} elseif ( $this->total_orders >= 2 && $this->total_orders <= 5 ) {
			return 'customer-regular';
		} elseif ( $this->total_orders >= 6 && $this->total_orders <= 15 ) {
			return 'customer-frequent';
		} else {
			return 'customer-vip';
		}
	}

	/**
	 * Get days since last order
	 * 
	 * @return int Days since last order
	 */
	public function get_days_since_last_order() {
		if ( empty( $this->last_order_date ) ) {
			return 0;
		}

		$last_order_timestamp = strtotime( $this->last_order_date );
		$current_timestamp = current_time( 'timestamp' );
		
		return round( ( $current_timestamp - $last_order_timestamp ) / DAY_IN_SECONDS );
	}

	/**
	 * Check if customer is at risk (haven't ordered in a while)
	 * 
	 * @param int $days_threshold Days threshold to consider at risk
	 * @return bool True if at risk
	 */
	public function is_at_risk( $days_threshold = 30 ) {
		if ( $this->total_orders === 1 ) {
			return false; // New customers are not at risk
		}

		return $this->get_days_since_last_order() > $days_threshold;
	}

	/**
	 * Get customer status
	 * 
	 * @return array Status array with label and class
	 */
	public function get_status() {
		$days_since_last = $this->get_days_since_last_order();

		if ( $days_since_last <= 7 ) {
			return [
				'label' => __( 'Activo', 'myd-delivery-pro' ),
				'class' => 'status-active'
			];
		} elseif ( $days_since_last <= 30 ) {
			return [
				'label' => __( 'Regular', 'myd-delivery-pro' ),
				'class' => 'status-regular'
			];
		} elseif ( $days_since_last <= 90 ) {
			return [
				'label' => __( 'Inactivo', 'myd-delivery-pro' ),
				'class' => 'status-inactive'
			];
		} else {
			return [
				'label' => __( 'En Riesgo', 'myd-delivery-pro' ),
				'class' => 'status-at-risk'
			];
		}
	}

	/**
	 * Get recent orders
	 * 
	 * @param int $limit Number of recent orders to get
	 * @return array Recent orders
	 */
	public function get_recent_orders( $limit = 5 ) {
		return $this->get_orders([
			'limit' => $limit,
			'orderby' => 'date',
			'order' => 'DESC'
		]);
	}

	/**
	 * Get order status distribution
	 * 
	 * @return array Order status distribution
	 */
	public function get_order_status_distribution() {
		$orders = $this->get_orders();
		$distribution = [];

		foreach ( $orders as $order ) {
			$status = $order['status'] ?? 'unknown';
			$distribution[$status] = ( $distribution[$status] ?? 0 ) + 1;
		}

		return $distribution;
	}

	/**
	 * Convert customer to array
	 * 
	 * @return array Customer data as array
	 */
	public function to_array() {
		return [
			'phone' => $this->phone,
			'name' => $this->name,
			'total_orders' => $this->total_orders,
			'total_spent' => $this->total_spent,
			'last_order_date' => $this->last_order_date,
			'first_order_date' => $this->first_order_date,
			'customer_since' => $this->customer_since,
			'formatted_total_spent' => $this->get_formatted_total_spent(),
			'average_order_value' => $this->get_average_order_value(),
			'formatted_average_order_value' => $this->get_formatted_average_order_value(),
			'customer_type' => $this->get_customer_type(),
			'customer_type_class' => $this->get_customer_type_class(),
			'days_since_last_order' => $this->get_days_since_last_order(),
			'is_at_risk' => $this->is_at_risk(),
			'status' => $this->get_status()
		];
	}

	/**
	 * Create customer from phone number
	 * 
	 * @param string $phone Phone number
	 * @return Customer|null Customer object or null if not found
	 */
	public static function find_by_phone( $phone ) {
		$customers = \MydPro\Includes\Repositories\Customer_Repository::get_all_customers([
			'search' => $phone,
			'limit' => 1
		]);

		if ( empty( $customers ) ) {
			return null;
		}

		return new self( $customers[0] );
	}

	/**
	 * Create multiple customer objects from repository data
	 * 
	 * @param array $customers_data Array of customer data from repository
	 * @return array Array of Customer objects
	 */
	public static function create_from_data( $customers_data ) {
		$customers = [];

		foreach ( $customers_data as $customer_data ) {
			$customers[] = new self( $customer_data );
		}

		return $customers;
	}
}