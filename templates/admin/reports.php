<?php

use MydPro\Includes\Store_Data;
use MydPro\Includes\Myd_Store_Orders;
use MydPro\Includes\Myd_Reports;
use MydPro\Includes\Myd_Store_Formatting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TODO: add user permission verification as we did on verion 1.9.42.
 */

/**
 * Store data and attrs
 */
$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );
$url = esc_url( home_url( '/wp-admin/admin.php?page=myd-delivery-reports' ) );
$today = current_time( 'Y-m-d' );
$latest_7_days = date( 'Y-m-d', strtotime( "$today -7 days" ) );
$latest_30_days = date( 'Y-m-d', strtotime( "$today -30 days" ) );

/**
 * Define period
 */
$to = isset( $_GET['to'] ) ? sanitize_text_field( $_GET['to'] ) : $today;
$from = isset( $_GET['from'] ) ? sanitize_text_field( $_GET['from'] ) : $today;
$filter_type = isset( $_GET['filter_type'] ) ? sanitize_text_field( $_GET['filter_type'] ) : '';

/**
 * Query orders by period
 */
$args = [
	'post_type' => 'mydelivery-orders',
	'no_found_rows' => true,
	'update_post_term_cache' => false,
	'posts_per_page' => -1,
	'post_status' => 'publish',
	'date_query' => [
		[
			'after' => $from,
			'before' => $to,
			'inclusive' => true,
		]
	]
];
$orders = new Myd_Store_Orders( $args );
$orders = $orders->get_orders();

/**
 * Reports
 */
$report = new Myd_Reports( $orders, $from, $to );

wp_enqueue_script( 'myd-chart-js' );

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Reports', 'myd-delivery-pro' ); ?></h1>

	<section class="myd-custom-content-page">
		<div class="myd-admin-filter">
			<a class="myd-admin-filter__item <?php echo esc_attr( $filter_type === 'today' ? 'myd-admin-filter--active' : '' ); ?>" href="<?php echo esc_attr( $url . '&filter_type=today&from=' . $today ); ?>"><?php esc_html_e( 'Today', 'myd-delivery-pro' ); ?></a>

			<a class="myd-admin-filter__item <?php echo esc_attr( $filter_type === '7' ? 'myd-admin-filter--active' : '' ); ?>" href="<?php echo esc_attr( $url . '&filter_type=7&from=' . $latest_7_days ); ?>"><?php esc_html_e( 'Latest 7 days', 'myd-delivery-pro' ); ?></a>

			<a class="myd-admin-filter__item <?php echo esc_attr( $filter_type === '30' ? 'myd-admin-filter--active' : '' ); ?>" href="<?php echo esc_attr( $url . '&filter_type=30&from=' . $latest_30_days ); ?>"><?php esc_html_e( 'Latest 30 days', 'myd-delivery-pro' ); ?></a>

			<div class="myd-admin-filter__range">
				<span>From:</span>
				<input type="date" name="report-range-from" id="report-range-from" max="<?php echo esc_attr( $today ); ?>" value="<?php echo esc_attr( $filter_type === 'range' ? esc_attr( $from ) : '' ); ?>">

				<span>To:</span>
				<input type="date" name="report-range-to" id="report-range-to" max="<?php echo esc_attr( $today ); ?>" value="<?php echo esc_attr( $filter_type === 'range' ? esc_attr( $to ) : '' ); ?>">

				<a class="button-primary" id="report-range-submit" data-from="<?php echo esc_attr( $filter_type === 'range' ? esc_attr( $from ) : '' ); ?>" data-to="<?php echo esc_attr( $filter_type === 'range' ? esc_attr( $to ) : '' ); ?>" data-url="<?php echo esc_attr( $url . '&filter_type=range&from={from}&to={to}'); ?>" href="#"><?php esc_html_e( 'Filter', 'myd-delivery-pro' ); ?></a>
			</div>

		</div>
		<div class="myd-admin-cards myd-card-4columns">
			<div class="myd-admin-cards__item myd-cards--price">
				<span class="myd-admin-cards__amount"><?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( Myd_Store_Formatting::format_price( $report->get_total_orders() ) ); ?></span>
				<p class="myd-admin-cards__description"><?php esc_html_e( 'Total sales in this period', 'myd-delivery-pro' ); ?></p>
			</div>

			<div class="myd-admin-cards__item myd-cards--orders">
				<span class="myd-admin-cards__amount"><?php echo esc_html( $report->get_count_orders() ); ?></span>
				<p class="myd-admin-cards__description"><?php esc_html_e( 'Total orders in this period', 'myd-delivery-pro' ); ?></p>
			</div>

			<div class="myd-admin-cards__item myd-cards--purchased">
				<span class="myd-admin-cards__amount"><?php echo esc_html( $report->get_purchased_items_quantity() ); ?></span>
				<p class="myd-admin-cards__description"><?php esc_html_e( 'Quantity of products sold', 'myd-delivery-pro' ); ?></p>
			</div>

			<div class="myd-admin-cards__item myd-cards--average">
				<span class="myd-admin-cards__amount"><?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( Myd_Store_Formatting::format_price( $report->get_average_sales() ) ); ?></span>
				<p class="myd-admin-cards__description"><?php esc_html_e( 'Average amount sales per day', 'myd-delivery-pro' ); ?></p>
			</div>
		</div>

		<div class="myd-reports-charts">
			<div class="myd-reports__chart-wrapper myd-chart-30">
				<h3 style="text-align:center;"><?php echo __( 'Top 3 products', 'myd-delivery-pro' ); ?></h3>
				<canvas id="myd-reports-charts-1" class="myd-reports__charts"></canvas>

				<?php
				$purchased_items = $report->get_purchased_items();
				$labels = array();
				$colors = array();
				$data = array();
				$vailable_colors = array(
					'rgb(255, 99, 132)',
					'rgb(54, 162, 235)',
					'rgb(255, 205, 86)',
				);

				$chart_purchased_items = array();

				if ( empty( $purchased_items ) ) {
					$chart_purchased_items[] = array(
						'label' => 'No product',
						'color' => 'rgb(255, 99, 132)',
						'qty' => 1,
					);
				} else {
					foreach ( $purchased_items as $key => $item ) {
						if ( $key <= 2 ) {
							$chart_purchased_items[] = array(
								'label' => $item['name'] ?? 'Product',
								'color' => $vailable_colors[ $key ] ?? 'rgb(255, 99, 132)',
								'qty' => $item['quantity'] ?? 1,
							);
						}
					}
				}
				?>

				<script>
					window.addEventListener('DOMContentLoaded', (e) => {
						const chartWrapper = document.getElementById('myd-reports-charts-1');

						new Chart(chartWrapper, {
							type: 'doughnut',
							data: {
							labels: <?php echo wp_json_encode( array_column( $chart_purchased_items, 'label' ) ); ?>,
							datasets: [{
								data: <?php echo wp_json_encode( array_column( $chart_purchased_items, 'qty' ) ); ?>,
								backgroundColor: <?php echo wp_json_encode( array_column( $chart_purchased_items, 'color' ) ); ?>,
								hoverOffset: 4
							}]
							},
						});
					});
				</script>
			</div>

			<div class="myd-reports__chart-wrapper myd-chart-70">
				<canvas id="myd-reports-charts" class="myd-reports__charts"></canvas>

				<?php
				$orders = $report->get_orders_by_period();
				$orders = array_reverse( $orders, true );
				$labels = array();
				$data = array();

				$chart_orders = array();

				if ( empty( $orders ) ) {
					$chart_orders[] = array(
						'label' => 'No orders',
						'qty' => 0,
					);
				} else {
					foreach ( $orders as $key => $item ) {
						$chart_orders[] = array(
							'label' => $item['period'] ?? 'Product',
							'qty' => $item['number_orders'] ?? 1,
						);
					}
				}
				?>

				<script>
					window.addEventListener('DOMContentLoaded', (e) => {
						const chartWrapper = document.getElementById('myd-reports-charts');

						new Chart(chartWrapper, {
							type: 'bar',
							data: {
							labels: <?php echo wp_json_encode( array_column( $chart_orders, 'label' ) ); ?>,
							datasets: [{
								label: 'Orders in period',
								data: <?php echo wp_json_encode( array_column( $chart_orders, 'qty' ) ); ?>,
								borderWidth: 1,
								backgroundColor: 'rgb(54, 162, 235)'
							}]
							},
							options: {
							scales: {
								y: {
								beginAtZero: true
								}
							}
							}
						});
					});
				</script>
			</div>

			<div class="myd-reports__chart-wrapper">
				<canvas id="myd-reports-charts-2" class="myd-reports__charts"></canvas>

				<?php
				$orders = $report->get_orders_by_period();
				$orders = array_reverse( $orders, true );
				$labels = array();
				$data = array();

				$chart_orders = array();

				if ( empty( $orders ) ) {
					$chart_orders[] = array(
						'label' => 'No orders',
						'qty' => 0,
					);
				} else {
					foreach ( $orders as $key => $item ) {
						$chart_orders[] = array(
							'label' => $item['period'] ?? 'Date',
							'qty' => $item['total'] ?? 0,
						);
					}
				}
				?>

				<script>
					window.addEventListener('DOMContentLoaded', (e) => {
						const chartWrapper = document.getElementById('myd-reports-charts-2');

						new Chart(chartWrapper, {
							type: 'bar',
							data: {
							labels: <?php echo wp_json_encode( array_column( $chart_orders, 'label' ) ); ?>,
							datasets: [{
								label: 'Amount of sales in period',
								data: <?php echo wp_json_encode( array_column( $chart_orders, 'qty' ) ); ?>,
								borderWidth: 1,
								backgroundColor: 'rgb(54, 162, 235)'
							}]
							},
							options: {
							scales: {
								y: {
								beginAtZero: true
								}
							}
							}
						});
					});
				</script>
			</div>
		</div>
	</section>
</div>
