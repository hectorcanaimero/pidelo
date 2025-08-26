<?php

namespace MydPro\Includes;

use MydPro\Includes\Legacy\Legacy_Repeater;
use MydPro\Includes\Custom_Fields\Register_Custom_Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once MYD_PLUGIN_PATH . 'includes/legacy/class-legacy-repeater.php';

/**
 * Class to manage all reports
 *
 * @since 1.9.5
 */
class Myd_Reports {
	/**
	 * Order object - default last 7 days
	 *
	 * @since 1.9.5
	 */
	protected $orders = array();

	/**
	 * Count total orders
	 *
	 * @since 1.9.5
	 */
	protected $count_orders;

	/**
	 * Total orders
	 *
	 * @since 1.9.5
	 */
	protected $total_orders;

	/**
	 * Average sales per day
	 *
	 * @since 1.9.5
	 */
	protected $average_sales;

	/**
	 * Purchased items
	 *
	 * @since 1.9.5
	 */
	protected $purchased_items;

	/**
	 * Period
	 *
	 * @since 1.9.5
	 */
	protected $from;

	/**
	 * Period
	 *
	 * @since 1.9.5
	 */
	protected $to;

	/**
	 * Period in days
	 *
	 * @since 1.9.17
	 */
	protected $period_in_days;

	/**
	 * Orders by period
	 *
	 * @since 1.9.21
	 */
	protected $orders_by_period = array();

	/**
	 * Orders per day
	 *
	 * @since 1.9.21
	 */
	protected $orders_per_day = array();

	/**
	 * Contruct the class
	 *
	 * @since 1.9.5
	 * @param array $orders
	 * @param string $period
	 */
	public function __construct( $orders, $from, $to ) {
		$this->orders = $orders;
		$this->from = $from;
		$this->to = $to;
		$this->make_calcs();
		$this->count_orders = $this->set_count_orders();
		$this->period_in_days = $this->convert_period_to_days();
		$this->average_sales = $this->set_average_sales();
		$this->orders_by_period = $this->set_orders_by_period();
	}

	/**
	 * Make report calcs.
	 *
	 * @return void
	 */
	public function make_calcs() {
		$total_orders = 0;
		$purchased_items = array();
		foreach ( $this->orders as $key => $order ) {
			$order_status = get_post_meta( $order->ID, 'order_status', true );
			if ( $order_status === 'finished' ) {
				$current_order_total = get_post_meta( $order->ID, 'order_total', true );
				$current_order_total = str_replace( array( ',', '.' ), '', $current_order_total );
				$current_order_total = substr_replace( $current_order_total, '.', - Store_Data::get_store_data( 'number_decimals' ), 0 );
				$total_orders += (float) $current_order_total;

				/**
				 * TODO: check if is necessary migrate from old type of repeater data
				 */
				$order_items = get_post_meta( $order->ID, 'myd_order_items', true );
				$order_items_legacy = get_post_meta( $order->ID, 'order_items', true );
				$args = Register_Custom_Fields::get_registered_fields();
				$args = $args['myd_order_details']['fields']['myd_order_details'] ?? array();
				$update_db = Legacy_Repeater::need_update_db( $order_items_legacy, $order_items );
				if ( $update_db && ! empty( $args ) ) {
					$order_items = Legacy_Repeater::update_repeater_database( $order_items_legacy, $args, $order->ID );
				}

				$purchased_items =  array_merge( $purchased_items, $order_items );
				$post_date = date( 'Y-m-d',  strtotime( $order->post_date ) );
				$post_date = strtotime( $post_date );
				$this->orders_per_day[ $post_date ][] = $order->ID;
			}

			if ( $order_status !== 'finished' ) {
				unset( $this->orders[ $key ] );
			}
		}

		$this->total_orders = $total_orders;
		$this->purchased_items = $this->set_purchased_items( $purchased_items );
	}

	/**
	 * Set purchased items
	 *
	 * @since 1.9.21
	 */
	protected function set_purchased_items( $purchased_items ) {
		if ( empty( $purchased_items ) ) {
			return array();
		}

		$items = array_column( $purchased_items, 'product_name' );
		$new_items = array();

		foreach ( $items as $item ) {
			$name = preg_replace( '/.*\d\sx\s/', '', $item );
			$quantity = (int) preg_replace( '/\sx.*/', '', $item );
			$key_existent_item = array_search( $name, array_column( $new_items, 'name' ) );

			if ( $key_existent_item !== false ) {
				$new_items[ $key_existent_item ]['quantity'] += $quantity;
			} else {
				$new_items[] = array(
					'name' => $name,
					'quantity' => $quantity,
				);
			}
		}

		return $new_items;
	}

	/**
	 * Set count orders
	 *
	 * @since 1.9.5
	 */
	public function set_count_orders() {
		return count( $this->orders );
	}

	/**
	 * Set average sales
	 *
	 * @since 1.9.5
	 */
	public function set_average_sales() {
		if ( $this->period_in_days <= 0 ) {
			return $this->total_orders;
		}

		return (float) $this->total_orders / (int) $this->period_in_days;
	}

	/**
	 * Convert date (period) to number of the days.
	 *
	 * @return string
	 */
	public function convert_period_to_days() {
		$interval = date_diff( new \DateTime( $this->to ), new \DateTime( $this->from ) );
		return (int) $interval->format( '%a' );
	}

	/**
	 * Get count orders
	 *
	 * @since 1.9.5
	 */
	public function get_count_orders() {
		return $this->count_orders;
	}

	/**
	 * Get total orders
	 *
	 * @since 1.9.5
	 */
	public function get_total_orders() {
		return $this->total_orders;
	}

	/**
	 * Get average sales
	 *
	 * @since 1.9.5
	 */
	public function get_average_sales() {
		return $this->average_sales;
	}

	/**
	 * Get purchased items
	 *
	 * @since 1.9.5
	 */
	public function get_purchased_items() {
		return $this->purchased_items;
	}

	/**
	 * Get quantity of purchased items
	 *
	 * @since 1.9.21
	 */
	public function get_purchased_items_quantity() {
		return array_sum( array_column( $this->purchased_items, 'quantity' ) );
	}

	/**
	 * Get orders by period
	 *
	 * @since 1.9.21
	 */
	public function get_orders_by_period() {
		return $this->orders_by_period;
	}

	/**
	 * Get orders by dishes with total revenue
	 *
	 * @since 2.2.19
	 */
	public function get_orders_by_dishes() {
		$dishes_report = array();
		
		// Procesar directamente desde las órdenes (no usar purchased_items ya que no tiene totales)
		foreach ( $this->orders as $order ) {
			$order_status = get_post_meta( $order->ID, 'order_status', true );
			
			// Solo órdenes finalizadas
			if ( $order_status !== 'finished' ) {
				continue;
			}
			
			$order_items = get_post_meta( $order->ID, 'myd_order_items', true );
			if ( empty( $order_items ) ) {
				continue;
			}
			
			foreach ( $order_items as $item ) {
				$product_name = $item['product_name'] ?? '';
				$product_price = (float) str_replace(',', '.', $item['product_price'] ?? 0);
				$quantity = isset($item['quantity']) ? (int) $item['quantity'] : 1;
				
				// Extraer nombre limpio del producto
				$clean_name = preg_replace('/^\\d+\\s*x\\s*/', '', $product_name);
				
				if ( isset( $dishes_report[ $clean_name ] ) ) {
					$dishes_report[ $clean_name ]['quantity'] += $quantity;
					$dishes_report[ $clean_name ]['total'] += $product_price;
				} else {
					$dishes_report[ $clean_name ] = array(
						'name' => $clean_name,
						'quantity' => $quantity,
						'total' => $product_price
					);
				}
			}
		}
		
		// Ordenar por cantidad descendente
		uasort( $dishes_report, function($a, $b) {
			return $b['quantity'] - $a['quantity'];
		});
		
		return array_values( $dishes_report );
	}

	/**
	 * Get orders by dishes for current week (Monday to Sunday)
	 *
	 * @since 2.2.19
	 */
	public function get_orders_by_dishes_weekly() {
		$dishes_report = array();
		
		// Calcular inicio y fin de la semana actual
		$today = new \DateTime();
		$week_start = clone $today;
		$week_start->modify('monday this week');
		$week_end = clone $today;
		$week_end->modify('sunday this week');
		
		foreach ( $this->orders as $order ) {
			$order_date = new \DateTime( $order->post_date );
			
			// Solo pedidos de esta semana
			$week_end_plus = clone $week_end;
			$week_end_plus->modify('+1 day'); // Incluir todo el domingo
			if ( $order_date < $week_start || $order_date >= $week_end_plus ) {
				continue;
			}
			
			$order_status = get_post_meta( $order->ID, 'order_status', true );
			
			// Solo pedidos finalizados (igual que el sistema existente)
			if ( $order_status !== 'finished' ) {
				continue;
			}
			
			$order_items = get_post_meta( $order->ID, 'myd_order_items', true );
			if ( empty( $order_items ) ) {
				continue;
			}
			
			foreach ( $order_items as $item ) {
				$product_name = $item['product_name'] ?? '';
				$product_price = (float) str_replace(',', '.', $item['product_price'] ?? 0);
				$quantity = isset($item['quantity']) ? (int) $item['quantity'] : 1;
				
				// Extraer nombre limpio del producto
				$clean_name = preg_replace('/^\d+\s*x\s*/', '', $product_name);
				
				if ( isset( $dishes_report[ $clean_name ] ) ) {
					$dishes_report[ $clean_name ]['quantity'] += $quantity;
					$dishes_report[ $clean_name ]['total'] += $product_price;
				} else {
					$dishes_report[ $clean_name ] = array(
						'name' => $clean_name,
						'quantity' => $quantity,
						'total' => $product_price
					);
				}
			}
		}
		
		// Ordenar por cantidad descendente
		uasort( $dishes_report, function($a, $b) {
			return $b['quantity'] - $a['quantity'];
		});
		
		return array_values( $dishes_report );
	}

	/**
	 * Get orders by delivery mode
	 *
	 * @since 2.2.19
	 */
	public function get_orders_by_delivery_mode() {
		$delivery_report = array();
		
		// Usar solo pedidos que ya están en $this->orders (ya filtrados por finished)
		foreach ( $this->orders as $order ) {
			$delivery_method = get_post_meta( $order->ID, 'order_ship_method', true );
			$order_total = get_post_meta( $order->ID, 'order_total', true );
			
			// Limpiar el total usando la misma lógica que el sistema existente
			$order_total = str_replace( array( ',', '.' ), '', $order_total );
			$order_total = substr_replace( $order_total, '.', - Store_Data::get_store_data( 'number_decimals' ), 0 );
			$order_total = (float) $order_total;
			
			// Mapear métodos de entrega
			$method_names = array(
				'delivery' => __( 'Delivery', 'myd-delivery-pro' ),
				'take-away' => __( 'Take Away', 'myd-delivery-pro' ),
				'order-in-store' => __( 'Order in Store', 'myd-delivery-pro' ),
			);
			
			$method_name = $method_names[ $delivery_method ] ?? $delivery_method;
			
			if ( isset( $delivery_report[ $method_name ] ) ) {
				$delivery_report[ $method_name ]['quantity']++;
				$delivery_report[ $method_name ]['total'] += $order_total;
			} else {
				$delivery_report[ $method_name ] = array(
					'mode' => $method_name,
					'quantity' => 1,
					'total' => $order_total
				);
			}
		}
		
		// Ordenar por cantidad descendente
		uasort( $delivery_report, function($a, $b) {
			return $b['quantity'] - $a['quantity'];
		});
		
		return array_values( $delivery_report );
	}

	/**
	 * Get orders by payment type
	 *
	 * @since 2.2.19
	 */
	public function get_orders_by_payment_type() {
		$payment_report = array();
		
		// Usar solo pedidos que ya están en $this->orders (ya filtrados por finished)
		foreach ( $this->orders as $order ) {
			$payment_type = get_post_meta( $order->ID, 'order_payment_type', true );
			$payment_method = get_post_meta( $order->ID, 'order_payment_method', true );
			$order_total = get_post_meta( $order->ID, 'order_total', true );
			
			// Limpiar el total usando la misma lógica que el sistema existente
			$order_total = str_replace( array( ',', '.' ), '', $order_total );
			$order_total = substr_replace( $order_total, '.', - Store_Data::get_store_data( 'number_decimals' ), 0 );
			$order_total = (float) $order_total;
			
			// Usar método de pago si está disponible, sino tipo de pago
			$payment_label = ! empty( $payment_method ) ? $payment_method : $payment_type;
			
			// Mapear tipos de pago
			$type_names = array(
				'upon-delivery' => __( 'Upon Delivery', 'myd-delivery-pro' ),
				'payment-integration' => __( 'Payment Integration', 'myd-delivery-pro' ),
			);
			
			if ( isset( $type_names[ $payment_label ] ) ) {
				$payment_label = $type_names[ $payment_label ];
			}
			
			if ( isset( $payment_report[ $payment_label ] ) ) {
				$payment_report[ $payment_label ]['quantity']++;
				$payment_report[ $payment_label ]['total'] += $order_total;
			} else {
				$payment_report[ $payment_label ] = array(
					'type' => $payment_label,
					'quantity' => 1,
					'total' => $order_total
				);
			}
		}
		
		// Ordenar por cantidad descendente
		uasort( $payment_report, function($a, $b) {
			return $b['quantity'] - $a['quantity'];
		});
		
		return array_values( $payment_report );
	}

	/**
	 * Get orders by payment type (only paid orders)
	 *
	 * @since 2.2.19
	 */
	public function get_orders_by_payment_type_paid() {
		$payment_report = array();
		
		// Obtener todas las órdenes del período, no solo finished
		$args = [
			'post_type' => 'mydelivery-orders',
			'no_found_rows' => true,
			'update_post_term_cache' => false,
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'date_query' => [
				[
					'after' => $this->from,
					'before' => $this->to,
					'inclusive' => true,
				]
			]
		];
		
		$all_orders = get_posts( $args );
		
		foreach ( $all_orders as $order ) {
			$payment_status = get_post_meta( $order->ID, 'order_payment_status', true );
			
			// Solo órdenes pagadas
			if ( $payment_status !== 'paid' ) {
				continue;
			}
			
			$payment_type = get_post_meta( $order->ID, 'order_payment_type', true );
			$payment_method = get_post_meta( $order->ID, 'order_payment_method', true );
			$order_total = get_post_meta( $order->ID, 'order_total', true );
			
			// Limpiar el total usando la misma lógica que el sistema existente
			if ( ! empty( $order_total ) ) {
				$order_total = str_replace( array( ',', '.' ), '', $order_total );
				$order_total = substr_replace( $order_total, '.', - Store_Data::get_store_data( 'number_decimals' ), 0 );
				$order_total = (float) $order_total;
			} else {
				$order_total = 0;
			}
			
			// Usar método de pago si está disponible, sino tipo de pago
			$payment_label = ! empty( $payment_method ) ? $payment_method : $payment_type;
			
			// Si no hay label, usar valor por defecto
			if ( empty( $payment_label ) ) {
				$payment_label = __( 'Unknown Payment Type', 'myd-delivery-pro' );
			}
			
			// Mapear tipos de pago
			$type_names = array(
				'upon-delivery' => __( 'Upon Delivery', 'myd-delivery-pro' ),
				'payment-integration' => __( 'Payment Integration', 'myd-delivery-pro' ),
			);
			
			if ( isset( $type_names[ $payment_label ] ) ) {
				$payment_label = $type_names[ $payment_label ];
			}
			
			if ( isset( $payment_report[ $payment_label ] ) ) {
				$payment_report[ $payment_label ]['quantity']++;
				$payment_report[ $payment_label ]['total'] += $order_total;
			} else {
				$payment_report[ $payment_label ] = array(
					'type' => $payment_label,
					'quantity' => 1,
					'total' => $order_total
				);
			}
		}
		
		// Ordenar por cantidad descendente
		uasort( $payment_report, function($a, $b) {
			return $b['quantity'] - $a['quantity'];
		});
		
		return array_values( $payment_report );
	}

	/**
	 * Get delivery mode statistics for charts
	 *
	 * @since 2.2.19
	 */
	public function get_delivery_mode_stats() {
		$delivery_stats = array();
		$total_orders = 0;
		
		// Usar solo pedidos que ya están en $this->orders (ya filtrados por finished)
		foreach ( $this->orders as $order ) {
			$delivery_method = get_post_meta( $order->ID, 'order_ship_method', true );
			
			// Mapear métodos de entrega
			$method_names = array(
				'delivery' => __( 'Delivery', 'myd-delivery-pro' ),
				'take-away' => __( 'Take Away', 'myd-delivery-pro' ),
				'order-in-store' => __( 'Order in Store', 'myd-delivery-pro' ),
			);
			
			$method_name = $method_names[ $delivery_method ] ?? $delivery_method;
			
			if ( isset( $delivery_stats[ $method_name ] ) ) {
				$delivery_stats[ $method_name ]++;
			} else {
				$delivery_stats[ $method_name ] = 1;
			}
			$total_orders++;
		}
		
		// Convertir a formato para gráficos con porcentajes
		$chart_data = array();
		foreach ( $delivery_stats as $mode => $count ) {
			$percentage = $total_orders > 0 ? round(($count / $total_orders) * 100, 1) : 0;
			$chart_data[] = array(
				'mode' => $mode,
				'count' => $count,
				'percentage' => $percentage
			);
		}
		
		return $chart_data;
	}

	/**
	 * Get average ticket by delivery mode
	 *
	 * @since 2.2.19
	 */
	public function get_delivery_mode_average_ticket() {
		$delivery_data = array();
		
		// Usar solo pedidos que ya están en $this->orders (ya filtrados por finished)
		foreach ( $this->orders as $order ) {
			$delivery_method = get_post_meta( $order->ID, 'order_ship_method', true );
			$order_total = get_post_meta( $order->ID, 'order_total', true );
			
			// Limpiar el total usando la misma lógica que el sistema existente
			$order_total = str_replace( array( ',', '.' ), '', $order_total );
			$order_total = substr_replace( $order_total, '.', - Store_Data::get_store_data( 'number_decimals' ), 0 );
			$order_total = (float) $order_total;
			
			// Mapear métodos de entrega
			$method_names = array(
				'delivery' => __( 'Delivery', 'myd-delivery-pro' ),
				'take-away' => __( 'Take Away', 'myd-delivery-pro' ),
				'order-in-store' => __( 'Order in Store', 'myd-delivery-pro' ),
			);
			
			$method_name = $method_names[ $delivery_method ] ?? $delivery_method;
			
			if ( isset( $delivery_data[ $method_name ] ) ) {
				$delivery_data[ $method_name ]['total'] += $order_total;
				$delivery_data[ $method_name ]['count']++;
			} else {
				$delivery_data[ $method_name ] = array(
					'total' => $order_total,
					'count' => 1
				);
			}
		}
		
		// Calcular promedios
		$average_data = array();
		foreach ( $delivery_data as $mode => $data ) {
			$average_data[] = array(
				'mode' => $mode,
				'average' => $data['count'] > 0 ? round($data['total'] / $data['count'], 2) : 0,
				'total' => $data['total'],
				'count' => $data['count']
			);
		}
		
		// Ordenar por promedio descendente
		uasort( $average_data, function($a, $b) {
			return $b['average'] <=> $a['average'];
		});
		
		return array_values( $average_data );
	}

	/**
	 * Get weekly trend for delivery modes
	 *
	 * @since 2.2.19
	 */
	public function get_delivery_mode_weekly_trend() {
		$weekly_data = array();
		$days_of_week = array(
			'Monday' => __( 'Monday', 'myd-delivery-pro' ),
			'Tuesday' => __( 'Tuesday', 'myd-delivery-pro' ),
			'Wednesday' => __( 'Wednesday', 'myd-delivery-pro' ),
			'Thursday' => __( 'Thursday', 'myd-delivery-pro' ),
			'Friday' => __( 'Friday', 'myd-delivery-pro' ),
			'Saturday' => __( 'Saturday', 'myd-delivery-pro' ),
			'Sunday' => __( 'Sunday', 'myd-delivery-pro' )
		);
		
		// Inicializar estructura de datos
		foreach ( $days_of_week as $day_en => $day_es ) {
			$weekly_data[ $day_es ] = array(
				'day' => $day_es,
				'delivery' => 0,
				'take_away' => 0,
				'order_in_store' => 0,
				'total' => 0
			);
		}
		
		// Usar solo pedidos que ya están en $this->orders (ya filtrados por finished)
		foreach ( $this->orders as $order ) {
			$delivery_method = get_post_meta( $order->ID, 'order_ship_method', true );
			$order_date = new \DateTime( $order->post_date );
			$day_of_week = $order_date->format('l'); // Monday, Tuesday, etc.
			$day_translated = $days_of_week[ $day_of_week ] ?? $day_of_week;
			
			if ( isset( $weekly_data[ $day_translated ] ) ) {
				$weekly_data[ $day_translated ]['total']++;
				
				switch ( $delivery_method ) {
					case 'delivery':
						$weekly_data[ $day_translated ]['delivery']++;
						break;
					case 'take-away':
						$weekly_data[ $day_translated ]['take_away']++;
						break;
					case 'order-in-store':
						$weekly_data[ $day_translated ]['order_in_store']++;
						break;
				}
			}
		}
		
		return array_values( $weekly_data );
	}

	/**
	 * Set orders by period
	 *
	 * @since 1.9.21
	 */
	protected function set_orders_by_period() {
		if ( $this->period_in_days === 0 ) {
			$this->orders_by_period[] = array(
				'period' => date( 'M j', strtotime( $this->to ) ),
				'total' => $this->total_orders,
				'number_orders' => $this->count_orders,
			);

			return $this->orders_by_period;
		}

		for ( $limit = 1; $limit <= $this->period_in_days; $limit++ ) {
			$period = ! isset( $period ) ? $this->to : date( 'Y-m-d', strtotime( '-1 day', strtotime( $period ) ) );
			$date_timestamp = strtotime( $period );
			$orders = $this->orders_per_day[ $date_timestamp ] ?? array();
			$total = 0;

			foreach ( $orders as $order ) {
				$order_total = get_post_meta( $order, 'order_total', true );
				if ( ! empty( $order_total ) ) {
					$order_total = str_replace( array( ',', '.' ), '', $order_total );
					$order_total = substr_replace( $order_total, '.', - Store_Data::get_store_data( 'number_decimals' ), 0 );
					$total += (float) $order_total;
				}
			}

			$this->orders_by_period[] = array(
				'period' => date( 'M j', strtotime( $period ) ),
				'total' => $total,
				'number_orders' => count( $orders ),
				'orders' => $orders,
			);
		}

		if( $this->period_in_days >= 14 ) {
			$new_period = array();
			$divider = round( $this->period_in_days / 7 );
			$chunk_orders = array_chunk( $this->orders_by_period, $divider, true );

			foreach ( $chunk_orders as $period ) {
				$firs_key = array_key_first( $period );
				$last_key = array_key_last( $period );
				$period_name = date( 'M j', strtotime( $period[ $last_key ]['period'] ) ) . ' - ' . date( 'M j', strtotime( $period[ $firs_key ]['period'] ) );
				$new_period[] = array(
					'period' => $period_name,
					'total' => array_sum( array_column( $period, 'total' ) ),
					'number_orders' => array_sum( array_column( $period, 'number_orders' ) ),
					'orders' => array_merge( ...array_column( $period, 'orders' ) ),
				);
			}

			$this->orders_by_period = $new_period;
		}

		return $this->orders_by_period;
	}
}
