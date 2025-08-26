<?php

namespace MydPro\Includes\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customer Repository Class
 * 
 * Handles data access for customer information aggregated from orders
 * 
 * @since 2.2.19
 */
class Customer_Repository {

	/**
	 * Get all unique customers with their statistics
	 * 
	 * @param array $args Query arguments
	 * @return array Array of customer objects
	 */
	public static function get_all_customers( $args = [] ) {
		global $wpdb;

		$defaults = [
			'search' => '',
			'limit' => 20,
			'offset' => 0,
			'orderby' => 'total_spent',
			'order' => 'DESC',
			'date_from' => '',
			'date_to' => ''
		];

		$args = wp_parse_args( $args, $defaults );

		// Base query - get raw data without total_spent calculation, only paid orders
		$sql = "
			SELECT 
				pm_phone.meta_value as phone,
				pm_name.meta_value as name,
				COUNT(DISTINCT p.ID) as total_orders,
				GROUP_CONCAT(pm_total.meta_value SEPARATOR '||') as order_totals,
				MAX(p.post_date) as last_order_date,
				MIN(p.post_date) as first_order_date
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_phone ON p.ID = pm_phone.post_id AND pm_phone.meta_key = 'customer_phone'
			INNER JOIN {$wpdb->postmeta} pm_name ON p.ID = pm_name.post_id AND pm_name.meta_key = 'order_customer_name'
			INNER JOIN {$wpdb->postmeta} pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = 'order_payment_status'
			LEFT JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = 'order_total'
			WHERE p.post_type = 'mydelivery-orders' 
			AND p.post_status = 'publish'
			AND pm_phone.meta_value != ''
			AND pm_name.meta_value != ''
			AND pm_payment.meta_value = 'paid'
		";

		// Add date filters
		if ( ! empty( $args['date_from'] ) ) {
			$sql .= $wpdb->prepare( " AND p.post_date >= %s", $args['date_from'] );
		}
		
		if ( ! empty( $args['date_to'] ) ) {
			$sql .= $wpdb->prepare( " AND p.post_date <= %s", $args['date_to'] );
		}

		// Add search filter
		if ( ! empty( $args['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$sql .= $wpdb->prepare( 
				" AND (pm_name.meta_value LIKE %s OR pm_phone.meta_value LIKE %s)", 
				$search_term, 
				$search_term 
			);
		}

		// Group by customer
		$sql .= " GROUP BY pm_phone.meta_value, pm_name.meta_value";

		// Order by - adjust for new column names
		$allowed_orderby = ['name', 'phone', 'total_orders', 'last_order_date'];
		$orderby = in_array( $args['orderby'], $allowed_orderby ) ? $args['orderby'] : 'last_order_date';
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		
		$sql .= " ORDER BY {$orderby} {$order}";
		
		// Note: total_spent ordering will be handled in PHP after calculation

		// Add limit and offset
		if ( $args['limit'] > 0 ) {
			$sql .= $wpdb->prepare( " LIMIT %d OFFSET %d", $args['limit'], $args['offset'] );
		}

		$results = $wpdb->get_results( $sql );

		if ( empty( $results ) ) {
			return [];
		}

		// Convert to customer objects with proper total_spent calculation
		$customers = [];
		foreach ( $results as $result ) {
			// Calculate total_spent using same logic as Reports class
			$total_spent = 0;
			if ( ! empty( $result->order_totals ) ) {
				$order_totals = explode( '||', $result->order_totals );
				foreach ( $order_totals as $order_total ) {
					if ( ! empty( $order_total ) ) {
						// Use same logic as Reports class (lines 120-122)
						$current_order_total = str_replace( array( ',', '.' ), '', $order_total );
						$current_order_total = substr_replace( $current_order_total, '.', - \MydPro\Includes\Store_Data::get_store_data( 'number_decimals' ), 0 );
						$total_spent += (float) $current_order_total;
					}
				}
			}
			
			$customers[] = [
				'phone' => $result->phone,
				'name' => $result->name,
				'total_orders' => (int) $result->total_orders,
				'total_spent' => $total_spent,
				'last_order_date' => $result->last_order_date,
				'first_order_date' => $result->first_order_date,
				'customer_since' => human_time_diff( strtotime( $result->first_order_date ), current_time( 'timestamp' ) )
			];
		}

		// Sort by total_spent if requested (since we calculate it in PHP)
		if ( $args['orderby'] === 'total_spent' ) {
			usort( $customers, function( $a, $b ) use ( $args ) {
				if ( strtoupper( $args['order'] ) === 'ASC' ) {
					return $a['total_spent'] <=> $b['total_spent'];
				} else {
					return $b['total_spent'] <=> $a['total_spent'];
				}
			});
		}
		
		return $customers;
	}

	/**
	 * Get total count of unique customers
	 * 
	 * @param array $args Query arguments for filtering
	 * @return int Total number of customers
	 */
	public static function get_customers_count( $args = [] ) {
		global $wpdb;

		$defaults = [
			'search' => '',
			'date_from' => '',
			'date_to' => ''
		];

		$args = wp_parse_args( $args, $defaults );

		$sql = "
			SELECT COUNT(DISTINCT CONCAT(pm_phone.meta_value, '-', pm_name.meta_value)) as total
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_phone ON p.ID = pm_phone.post_id AND pm_phone.meta_key = 'customer_phone'
			INNER JOIN {$wpdb->postmeta} pm_name ON p.ID = pm_name.post_id AND pm_name.meta_key = 'order_customer_name'
			INNER JOIN {$wpdb->postmeta} pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = 'order_payment_status'
			WHERE p.post_type = 'mydelivery-orders' 
			AND p.post_status = 'publish'
			AND pm_phone.meta_value != ''
			AND pm_name.meta_value != ''
			AND pm_payment.meta_value = 'paid'
		";

		// Add date filters
		if ( ! empty( $args['date_from'] ) ) {
			$sql .= $wpdb->prepare( " AND p.post_date >= %s", $args['date_from'] );
		}
		
		if ( ! empty( $args['date_to'] ) ) {
			$sql .= $wpdb->prepare( " AND p.post_date <= %s", $args['date_to'] );
		}

		// Add search filter
		if ( ! empty( $args['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$sql .= $wpdb->prepare( 
				" AND (pm_name.meta_value LIKE %s OR pm_phone.meta_value LIKE %s)", 
				$search_term, 
				$search_term 
			);
		}

		$result = $wpdb->get_var( $sql );
		return (int) $result;
	}

	/**
	 * Get customer orders by phone
	 * 
	 * @param string $phone Customer phone number
	 * @param array $args Query arguments
	 * @return array Array of order objects
	 */
	public static function get_customer_orders( $phone, $args = [] ) {
		$defaults = [
			'limit' => 20,
			'offset' => 0,
			'orderby' => 'date',
			'order' => 'DESC'
		];

		$args = wp_parse_args( $args, $defaults );

		$query_args = [
			'post_type' => 'mydelivery-orders',
			'post_status' => 'publish',
			'posts_per_page' => $args['limit'],
			'offset' => $args['offset'],
			'meta_query' => [
				[
					'key' => 'customer_phone',
					'value' => $phone,
					'compare' => '='
				]
			],
			'orderby' => $args['orderby'] === 'date' ? 'date' : 'meta_value_num',
			'order' => strtoupper( $args['order'] )
		];

		if ( $args['orderby'] !== 'date' ) {
			$query_args['meta_key'] = $args['orderby'];
		}

		$orders = get_posts( $query_args );

		if ( empty( $orders ) ) {
			return [];
		}

		// Format orders with metadata
		$formatted_orders = [];
		foreach ( $orders as $order ) {
			$order_data = [
				'ID' => $order->ID,
				'date' => $order->post_date,
				'status' => get_post_meta( $order->ID, 'order_status', true ),
				'total' => get_post_meta( $order->ID, 'order_total', true ),
				'payment_status' => get_post_meta( $order->ID, 'order_payment_status', true ),
				'delivery_method' => get_post_meta( $order->ID, 'order_shipping_method', true ),
				'address' => get_post_meta( $order->ID, 'order_address', true ),
				'customer_name' => get_post_meta( $order->ID, 'order_customer_name', true )
			];

			$formatted_orders[] = $order_data;
		}

		return $formatted_orders;
	}

	/**
	 * Get customer addresses by phone
	 * 
	 * @param string $phone Customer phone number
	 * @return array Array of unique addresses
	 */
	public static function get_customer_addresses( $phone ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			SELECT DISTINCT
				pm_address.meta_value as address,
				pm_number.meta_value as number,
				pm_comp.meta_value as complement,
				pm_neighborhood.meta_value as neighborhood,
				pm_zipcode.meta_value as zipcode,
				COUNT(p.ID) as usage_count
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_phone ON p.ID = pm_phone.post_id AND pm_phone.meta_key = 'customer_phone'
			LEFT JOIN {$wpdb->postmeta} pm_address ON p.ID = pm_address.post_id AND pm_address.meta_key = 'order_address'
			LEFT JOIN {$wpdb->postmeta} pm_number ON p.ID = pm_number.post_id AND pm_number.meta_key = 'order_address_number'
			LEFT JOIN {$wpdb->postmeta} pm_comp ON p.ID = pm_comp.post_id AND pm_comp.meta_key = 'order_address_comp'
			LEFT JOIN {$wpdb->postmeta} pm_neighborhood ON p.ID = pm_neighborhood.post_id AND pm_neighborhood.meta_key = 'order_neighborhood'
			LEFT JOIN {$wpdb->postmeta} pm_zipcode ON p.ID = pm_zipcode.post_id AND pm_zipcode.meta_key = 'order_zipcode'
			WHERE p.post_type = 'mydelivery-orders' 
			AND p.post_status = 'publish'
			AND pm_phone.meta_value = %s
			AND pm_address.meta_value != ''
			GROUP BY pm_address.meta_value, pm_number.meta_value, pm_comp.meta_value, pm_neighborhood.meta_value, pm_zipcode.meta_value
			ORDER BY usage_count DESC
		", $phone );

		$results = $wpdb->get_results( $sql );

		if ( empty( $results ) ) {
			return [];
		}

		$addresses = [];
		foreach ( $results as $result ) {
			$full_address = trim( $result->address );
			if ( ! empty( $result->number ) ) {
				$full_address .= ', ' . $result->number;
			}
			if ( ! empty( $result->complement ) ) {
				$full_address .= ' ' . $result->complement;
			}
			if ( ! empty( $result->neighborhood ) ) {
				$full_address .= ', ' . $result->neighborhood;
			}
			if ( ! empty( $result->zipcode ) ) {
				$full_address .= ' - ' . $result->zipcode;
			}

			$addresses[] = [
				'address' => $result->address,
				'number' => $result->number,
				'complement' => $result->complement,
				'neighborhood' => $result->neighborhood,
				'zipcode' => $result->zipcode,
				'full_address' => $full_address,
				'usage_count' => (int) $result->usage_count
			];
		}

		return $addresses;
	}

	/**
	 * Search customers by name or phone
	 * 
	 * @param string $search_term Search term
	 * @param int $limit Limit results
	 * @return array Array of customers
	 */
	public static function search_customers( $search_term, $limit = 10 ) {
		return self::get_all_customers([
			'search' => $search_term,
			'limit' => $limit,
			'offset' => 0,
			'orderby' => 'last_order_date',
			'order' => 'DESC'
		]);
	}

	/**
	 * Get customer statistics
	 * 
	 * @return array Statistics array
	 */
	public static function get_customers_statistics() {
		global $wpdb;

		// Query for customer statistics - only paid orders
		$sql = "
			SELECT 
				COUNT(DISTINCT CONCAT(pm_phone.meta_value, '-', pm_name.meta_value)) as total_customers
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_phone ON p.ID = pm_phone.post_id AND pm_phone.meta_key = 'customer_phone'
			INNER JOIN {$wpdb->postmeta} pm_name ON p.ID = pm_name.post_id AND pm_name.meta_key = 'order_customer_name'
			INNER JOIN {$wpdb->postmeta} pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = 'order_payment_status'
			WHERE p.post_type = 'mydelivery-orders' 
			AND p.post_status = 'publish'
			AND pm_phone.meta_value != ''
			AND pm_name.meta_value != ''
			AND pm_payment.meta_value = 'paid'
		";

		$total_customers = $wpdb->get_var( $sql );

		if ( $wpdb->last_error ) {
			error_log( 'Customer stats SQL error: ' . $wpdb->last_error );
		}

		// Si hay clientes, calcular estadísticas adicionales
		if ( $total_customers > 0 ) {
			// Obtener promedio de pedidos por cliente - solo pedidos pagados
			$avg_orders_sql = "
				SELECT AVG(order_count) as avg_orders
				FROM (
					SELECT COUNT(p.ID) as order_count
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm_phone ON p.ID = pm_phone.post_id AND pm_phone.meta_key = 'customer_phone'
					INNER JOIN {$wpdb->postmeta} pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = 'order_payment_status'
					WHERE p.post_type = 'mydelivery-orders' 
					AND p.post_status = 'publish'
					AND pm_phone.meta_value != ''
					AND pm_payment.meta_value = 'paid'
					GROUP BY pm_phone.meta_value
				) as order_counts
			";
			
			$avg_orders = $wpdb->get_var( $avg_orders_sql ) ?: 0;

			// Calcular tasa de retorno (clientes con más de 1 pedido pagado)
			$repeat_customers_sql = "
				SELECT COUNT(*) as repeat_customers
				FROM (
					SELECT pm_phone.meta_value
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm_phone ON p.ID = pm_phone.post_id AND pm_phone.meta_key = 'customer_phone'
					INNER JOIN {$wpdb->postmeta} pm_payment ON p.ID = pm_payment.post_id AND pm_payment.meta_key = 'order_payment_status'
					WHERE p.post_type = 'mydelivery-orders' 
					AND p.post_status = 'publish'
					AND pm_phone.meta_value != ''
					AND pm_payment.meta_value = 'paid'
					GROUP BY pm_phone.meta_value
					HAVING COUNT(p.ID) > 1
				) as repeat_stats
			";
			
			$repeat_customers = $wpdb->get_var( $repeat_customers_sql ) ?: 0;
			$repeat_rate = $total_customers > 0 ? ( $repeat_customers / $total_customers ) * 100 : 0;
			
			// Calculate average spent per customer using corrected logic (paid orders only)
			$avg_spent = 0;
			$customers_data = self::get_all_customers( ['limit' => 0] ); // Get all customers with paid orders
			if ( ! empty( $customers_data ) ) {
				$total_spent_all = array_sum( array_column( $customers_data, 'total_spent' ) );
				$avg_spent = $total_customers > 0 ? $total_spent_all / $total_customers : 0;
			}

			return [
				'total_customers' => (int) $total_customers,
				'avg_orders_per_customer' => round( (float) $avg_orders, 1 ),
				'avg_spent_per_customer' => round( $avg_spent, 2 ),
				'repeat_rate' => round( $repeat_rate, 1 )
			];
		}

		$result = null;

		if ( empty( $result ) ) {
			return [
				'total_customers' => 0,
				'avg_orders_per_customer' => 0,
				'avg_spent_per_customer' => 0,
				'highest_spent' => 0,
				'one_time_customers' => 0,
				'repeat_customers' => 0,
				'repeat_rate' => 0
			];
		}

		$repeat_rate = $result->total_customers > 0 ? 
			( $result->repeat_customers / $result->total_customers ) * 100 : 0;

		return [
			'total_customers' => (int) $result->total_customers,
			'avg_orders_per_customer' => round( (float) $result->avg_orders_per_customer, 2 ),
			'avg_spent_per_customer' => round( (float) $result->avg_spent_per_customer, 2 ),
			'highest_spent' => round( (float) $result->highest_spent, 2 ),
			'one_time_customers' => (int) $result->one_time_customers,
			'repeat_customers' => (int) $result->repeat_customers,
			'repeat_rate' => round( $repeat_rate, 1 )
		];
	}
}