<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Currency Converter Class
 * Handles USD to VEF/VES conversion using DolarAPI Venezuela
 * 
 * @since 2.2.19
 */
class Currency_Converter {

	/**
	 * API URL for Venezuelan dollar rates
	 */
	// const API_URL = 'https://ve.dolarapi.com/v1/dolares';
	const API_URL = 'https://webhooks.guria.lat/webhook/a4b29525-f9a9-4374-a76f-c462046357b5';

	/**
	 * Transient key for caching the official rate
	 */
	const TRANSIENT_KEY = 'myd_dolar_oficial_rate';

	/**
	 * Cache duration in seconds (30 minutes)
	 */
	const CACHE_DURATION = 1800;

	/**
	 * Get the official USD to VEF rate from DolarAPI
	 *
	 * @since 2.2.19
	 * @return float|false The official rate or false on error
	 */
	public static function get_official_rate() {
		$rate = get_transient( self::TRANSIENT_KEY );

		if ( false === $rate ) {
			$data = self::fetch_data_from_api();
			
			if ( $data !== false && isset( $data['promedio'] ) ) {
				$rate = $data['promedio'];
				set_transient( self::TRANSIENT_KEY, $rate, self::CACHE_DURATION );
			} else {
				return false;
			}
		}

		return $rate;
	}

	/**
	 * Get the complete BCV data (rate, name, update date)
	 *
	 * @since 2.2.19
	 * @return array|false The complete BCV data or false on error
	 */
	public static function get_bcv_data() {
		$transient_key = 'myd_bcv_data';
		$data = get_transient( $transient_key );

		if ( false === $data ) {
			$data = self::fetch_data_from_api();
			
			if ( $data !== false ) {
				set_transient( $transient_key, $data, self::CACHE_DURATION );
			}
		}

		return $data;
	}

	/**
	 * Fetch data from DolarAPI
	 *
	 * @since 2.2.19
	 * @return array|false The official BCV data or false on error
	 */
	private static function fetch_data_from_api() {
		$response = wp_remote_get( 
			self::API_URL,
			array(
				'timeout' => 10,
				'headers' => array(
					'User-Agent' => 'MyD-Delivery-Pro/' . MYD_CURRENT_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'MYD Currency Converter: API request failed - ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			error_log( 'MYD Currency Converter: API returned status ' . $response_code );
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			error_log( 'MYD Currency Converter: Invalid JSON response' );
			return false;
		}

		foreach ( $data as $currency ) {
			if ( isset( $currency['fuente'] ) && $currency['fuente'] === 'oficial' ) {
				if ( isset( $currency['promedio'] ) && is_numeric( $currency['promedio'] ) ) {
					return array(
						'nombre' => isset( $currency['nombre'] ) ? $currency['nombre'] : 'BCV',
						'promedio' => floatval( $currency['promedio'] ),
						'fechaActualizacion' => isset( $currency['fechaActualizacion'] ) ? $currency['fechaActualizacion'] : null
					);
				}
			}
		}

		error_log( 'MYD Currency Converter: Official rate not found in API response' );
		return false;
	}

	/**
	 * Convert USD amount to VEF using official rate
	 *
	 * @since 2.2.19
	 * @param float $usd_amount The amount in USD to convert
	 * @return float|false The converted amount in VEF or false on error
	 */
	public static function convert_usd_to_vef( $usd_amount ) {
		if ( ! is_numeric( $usd_amount ) || $usd_amount <= 0 ) {
			return false;
		}

		$rate = self::get_official_rate();
		if ( $rate === false || $rate <= 0 ) {
			return false;
		}

		return floatval( $usd_amount ) * $rate;
	}

	/**
	 * Format VEF amount using Venezuelan format (. for thousands, , for decimals)
	 *
	 * @since 2.2.19
	 * @param float $amount The amount to format
	 * @param int $decimals Number of decimal places
	 * @return string Formatted amount
	 */
	public static function format_vef_amount( $amount, $decimals = 2 ) {
		return number_format( $amount, $decimals, ',', '.' );
	}

	/**
	 * Check if currency conversion is enabled in settings
	 *
	 * @since 2.2.19
	 * @return bool True if enabled, false otherwise
	 */
	public static function is_conversion_enabled() {
		$enabled = get_option( 'myd-currency-conversion-enabled', false );
		return $enabled === '1' || $enabled === 1 || $enabled === true;
	}

	/**
	 * Get conversion display HTML for a USD price
	 *
	 * @since 2.2.19
	 * @param float $usd_price The price in USD
	 * @param bool $show_both Whether to show both currencies or just VEF
	 * @return string HTML markup for the conversion display
	 */
	public static function get_conversion_display( $usd_price, $show_both = true ) {
		if ( ! self::is_conversion_enabled() ) {
			return '';
		}

		$vef_amount = self::convert_usd_to_vef( $usd_price );
		if ( $vef_amount === false ) {
			return '';
		}

		$currency_symbol = Store_Data::get_store_data( 'currency_simbol' );
		$formatted_vef = self::format_vef_amount( $vef_amount );

		$html = '<div class="myd-currency-conversion">';
		
		if ( $show_both ) {
			$html .= '<span class="myd-usd-price">' . esc_html( $currency_symbol ) . number_format( $usd_price, 2 ) . ' USD</span>';
			$html .= '<span class="myd-currency-separator"> ≈ </span>';
		}
		
		$html .= '<span class="myd-vef-price">Bs. ' . esc_html( $formatted_vef ) . '</span>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Clear the cached rate (useful for manual refresh)
	 *
	 * @since 2.2.19
	 * @return bool True on success, false on failure
	 */
	public static function clear_rate_cache() {
		return delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Get rate cache info for debugging
	 *
	 * @since 2.2.19
	 * @return array Cache information
	 */
	public static function get_cache_info() {
		$rate = get_transient( self::TRANSIENT_KEY );
		$timeout = get_option( '_transient_timeout_' . self::TRANSIENT_KEY );
		
		return array(
			'rate' => $rate,
			'cached' => $rate !== false,
			'expires_at' => $timeout ? date( 'Y-m-d H:i:s', $timeout ) : null,
			'expires_in_minutes' => $timeout ? round( ( $timeout - time() ) / 60 ) : null,
		);
	}

	/**
	 * Shortcode para mostrar información del BCV
	 *
	 * @since 2.2.19
	 * @param array $atts Atributos del shortcode
	 * @return string HTML del shortcode
	 */
	public static function bcv_rate_shortcode( $atts = array() ) {
		$atts = shortcode_atts( array(
			'show_name' => 'true',
			'show_rate' => 'true', 
			'show_date' => 'true',
			'date_format' => 'd/m/Y H:i',
			'class' => 'myd-bcv-info'
		), $atts, 'myd_bcv_rate' );

		$data = self::get_bcv_data();
		
		if ( $data === false ) {
			return '<div class="' . esc_attr( $atts['class'] ) . ' myd-bcv-error">' . 
				   esc_html__( 'Error al obtener datos del BCV', 'myd-delivery-pro' ) . 
				   '</div>';
		}

		$html = '<div class="' . esc_attr( $atts['class'] ) . '">';

		if ( $atts['show_name'] === 'true' && isset( $data['nombre'] ) ) {
			$html .= '<div class="myd-bcv-name">' . esc_html( $data['nombre'] ) . '</div>';
		}

		if ( $atts['show_rate'] === 'true' && isset( $data['promedio'] ) ) {
			$formatted_rate = self::format_vef_amount( $data['promedio'] );
			$html .= '<div class="myd-bcv-rate">Bs. ' . esc_html( $formatted_rate ) . ' / USD</div>';
		}

		if ( $atts['show_date'] === 'true' && isset( $data['fechaActualizacion'] ) && $data['fechaActualizacion'] ) {
			$date = date_create( $data['fechaActualizacion'] );
			if ( $date !== false ) {
				$formatted_date = date_format( $date, $atts['date_format'] );
				$html .= '<div class="myd-bcv-date">' . 
						 esc_html__( 'Actualizado:', 'myd-delivery-pro' ) . ' ' . 
						 esc_html( $formatted_date ) . 
						 '</div>';
			}
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Registrar el shortcode
	 *
	 * @since 2.2.19
	 */
	public static function register_shortcode() {
		add_shortcode( 'myd_bcv_rate', array( __CLASS__, 'bcv_rate_shortcode' ) );
	}
}