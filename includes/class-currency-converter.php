<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Currency Converter Class
 * Handles currency conversions to VEF (Bolívares Venezolanos):
 * - USD -> VEF
 * - EUR -> VEF
 * Shows conversion based on selected currency in settings
 *
 * @since 2.2.19
 * @updated 2.2.20 - Added EUR -> VEF support
 */
class Currency_Converter {

	/**
	 * API URLs for currency rates
	 * Both endpoints return conversion rates to VEF (Venezuelan Bolívar)
	 */
	const API_URL_USD_VEF = 'https://webhooks.guria.lat/webhook/a4b29525-f9a9-4374-a76f-c462046357b5';
	const API_URL_EUR_VEF = 'https://webhooks.guria.lat/webhook/6ed6fb33-d736-43af-9038-7a7e2a2a1116';

	/**
	 * Transient keys for caching rates
	 */
	const TRANSIENT_KEY_USD_VEF = 'myd_usd_vef_rate';
	const TRANSIENT_KEY_EUR_VEF = 'myd_eur_vef_rate';

	/**
	 * Cache duration in seconds (30 minutes)
	 */
	const CACHE_DURATION = 1800;

	/**
	 * Get current store currency code
	 *
	 * @since 2.2.20
	 * @return string Currency code (USD, EUR, VEF, etc.)
	 */
	public static function get_store_currency() {
		return Myd_Currency::get_currency_code();
	}

	/**
	 * Check if currency conversion is enabled
	 *
	 * @since 2.2.20
	 * @return bool
	 */
	public static function is_conversion_enabled() {
		$enabled = get_option( 'myd-currency-conversion-enabled', false );
		return $enabled === '1' || $enabled === 1 || $enabled === true;
	}

	/**
	 * Check if manual USD to VEF rate is being used
	 *
	 * @since 2.3.1
	 * @return bool
	 */
	public static function is_manual_usd_rate_enabled() {
		$enabled = get_option( 'myd-currency-manual-rate-usd-vef-enabled' );
		$rate = get_option( 'myd-currency-manual-rate-usd-vef' );
		return ( $enabled === 'yes' && ! empty( $rate ) && is_numeric( $rate ) && floatval( $rate ) > 0 );
	}

	/**
	 * Check if manual EUR to VEF rate is being used
	 *
	 * @since 2.3.1
	 * @return bool
	 */
	public static function is_manual_eur_rate_enabled() {
		$enabled = get_option( 'myd-currency-manual-rate-eur-vef-enabled' );
		$rate = get_option( 'myd-currency-manual-rate-eur-vef' );
		return ( $enabled === 'yes' && ! empty( $rate ) && is_numeric( $rate ) && floatval( $rate ) > 0 );
	}

	/**
	 * Get the official USD to VEF rate
	 * Prioritizes manual rate if enabled, otherwise uses automatic rate from API
	 *
	 * @since 2.2.19
	 * @updated 2.3.1 - Added manual rate support
	 * @return float|false The USD to VEF rate or false on error
	 */
	public static function get_usd_vef_rate() {
		// Check if manual rate is enabled
		$manual_enabled = get_option( 'myd-currency-manual-rate-usd-vef-enabled' );
		if ( $manual_enabled === 'yes' ) {
			$manual_rate = get_option( 'myd-currency-manual-rate-usd-vef' );
			if ( ! empty( $manual_rate ) && is_numeric( $manual_rate ) && floatval( $manual_rate ) > 0 ) {
				return floatval( $manual_rate );
			}
		}

		// Fall back to automatic rate from API
		$rate = get_transient( self::TRANSIENT_KEY_USD_VEF );

		if ( false === $rate ) {
			$data = self::fetch_data_from_api( 'USD' );

			if ( $data !== false && isset( $data['promedio'] ) ) {
				$rate = $data['promedio'];
				set_transient( self::TRANSIENT_KEY_USD_VEF, $rate, self::CACHE_DURATION );
			} else {
				return false;
			}
		}

		return $rate;
	}

	/**
	 * Get the official EUR to VEF rate
	 * Prioritizes manual rate if enabled, otherwise uses automatic rate from API
	 *
	 * @since 2.2.20
	 * @updated 2.3.1 - Added manual rate support
	 * @return float|false The EUR to VEF rate or false on error
	 */
	public static function get_eur_vef_rate() {
		// Check if manual rate is enabled
		$manual_enabled = get_option( 'myd-currency-manual-rate-eur-vef-enabled' );
		if ( $manual_enabled === 'yes' ) {
			$manual_rate = get_option( 'myd-currency-manual-rate-eur-vef' );
			if ( ! empty( $manual_rate ) && is_numeric( $manual_rate ) && floatval( $manual_rate ) > 0 ) {
				return floatval( $manual_rate );
			}
		}

		// Fall back to automatic rate from API
		$rate = get_transient( self::TRANSIENT_KEY_EUR_VEF );

		if ( false === $rate ) {
			$data = self::fetch_data_from_api( 'EUR' );

			if ( $data !== false && isset( $data['promedio'] ) ) {
				$rate = $data['promedio'];
				set_transient( self::TRANSIENT_KEY_EUR_VEF, $rate, self::CACHE_DURATION );
			} else {
				return false;
			}
		}

		return $rate;
	}

	/**
	 * Alias for backwards compatibility
	 * @deprecated Use get_usd_vef_rate() instead
	 */
	public static function get_official_rate() {
		return self::get_usd_vef_rate();
	}

	/**
	 * Alias for backwards compatibility
	 * @deprecated Use get_eur_vef_rate() instead
	 */
	public static function get_eur_rate() {
		return self::get_eur_vef_rate();
	}

	/**
	 * Get the complete USD->VEF data (rate, name, update date)
	 *
	 * @since 2.2.19
	 * @return array|false The complete USD->VEF data or false on error
	 */
	public static function get_usd_vef_data() {
		$transient_key = 'myd_usd_vef_data';
		$data = get_transient( $transient_key );

		if ( false === $data ) {
			$data = self::fetch_data_from_api( 'USD' );

			if ( $data !== false ) {
				set_transient( $transient_key, $data, self::CACHE_DURATION );
			}
		}

		return $data;
	}

	/**
	 * Get the complete EUR->VEF data (rate, name, update date)
	 *
	 * @since 2.2.20
	 * @return array|false The complete EUR->VEF data or false on error
	 */
	public static function get_eur_vef_data() {
		$transient_key = 'myd_eur_vef_data';
		$data = get_transient( $transient_key );

		if ( false === $data ) {
			$data = self::fetch_data_from_api( 'EUR' );

			if ( $data !== false ) {
				set_transient( $transient_key, $data, self::CACHE_DURATION );
			}
		}

		return $data;
	}

	/**
	 * Alias for backwards compatibility
	 * @deprecated Use get_usd_vef_data() instead
	 */
	public static function get_bcv_data() {
		return self::get_usd_vef_data();
	}

	/**
	 * Alias for backwards compatibility
	 * @deprecated Use get_eur_vef_data() instead
	 */
	public static function get_eur_data() {
		return self::get_eur_vef_data();
	}

	/**
	 * Fetch data from API
	 *
	 * @since 2.2.19
	 * @updated 2.2.20 - Added EUR currency type
	 * @param string $currency_type 'USD' or 'EUR' (both convert to VEF)
	 * @return array|false The currency data or false on error
	 */
	private static function fetch_data_from_api( $currency_type = 'USD' ) {
		$api_url = $currency_type === 'EUR' ? self::API_URL_EUR_VEF : self::API_URL_USD_VEF;

		$response = wp_remote_get(
			$api_url,
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
	 * @param float $usd_amount The amount in USD to convert to VEF
	 * @return float|false The converted amount in VEF or false on error
	 */
	public static function convert_usd_to_vef( $usd_amount ) {
		if ( ! is_numeric( $usd_amount ) || $usd_amount <= 0 ) {
			return false;
		}

		$rate = self::get_usd_vef_rate();
		if ( $rate === false || $rate <= 0 ) {
			return false;
		}

		return floatval( $usd_amount ) * $rate;
	}

	/**
	 * Convert EUR amount to VEF using official rate
	 *
	 * @since 2.2.20
	 * @param float $eur_amount The amount in EUR to convert to VEF
	 * @return float|false The converted amount in VEF or false on error
	 */
	public static function convert_eur_to_vef( $eur_amount ) {
		if ( ! is_numeric( $eur_amount ) || $eur_amount <= 0 ) {
			return false;
		}

		$rate = self::get_eur_vef_rate();
		if ( $rate === false || $rate <= 0 ) {
			return false;
		}

		return floatval( $eur_amount ) * $rate;
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
	 * Get converted amount based on store currency
	 * Auto-detects which conversion to apply:
	 * - If store currency is EUR: convert to VEF (Bolívares)
	 * - If store currency is USD: convert to VEF (Bolívares)
	 * - If store currency is VEF: no conversion needed
	 *
	 * @since 2.2.20
	 * @param float $amount Amount in store currency
	 * @return array|false Array with converted amount and currency code, or false on error
	 */
	public static function get_conversion( $amount ) {
		// Convert to float for proper comparison
		$amount = floatval( $amount );

		if ( ! is_numeric( $amount ) || $amount <= 0 ) {
			return false;
		}

		if ( ! self::is_conversion_enabled() ) {
			return false;
		}

		$store_currency = self::get_store_currency();

		switch ( $store_currency ) {
			case 'EUR':
				// EUR -> VEF conversion
				$vef_amount = self::convert_eur_to_vef( $amount );
				if ( $vef_amount !== false ) {
					return array(
						'amount' => $vef_amount,
						'currency_code' => 'VEF',
						'currency_symbol' => 'Bs',
						'currency_name' => 'Bolívares',
					);
				}
				break;

			case 'USD':
				// USD -> VEF conversion
				$vef_amount = self::convert_usd_to_vef( $amount );
				if ( $vef_amount !== false ) {
					return array(
						'amount' => $vef_amount,
						'currency_code' => 'VEF',
						'currency_symbol' => 'Bs',
						'currency_name' => 'Bolívares',
					);
				}
				break;

			case 'VEF':
			default:
				// No conversion for VEF or other currencies
				return false;
		}

		return false;
	}

	/**
	 * Get conversion display HTML for any price based on store currency
	 * Automatically shows the appropriate conversion:
	 * - EUR store: shows VEF (Bolívares) equivalent
	 * - USD store: shows VEF (Bolívares) equivalent
	 * - VEF store: no conversion shown
	 *
	 * @since 2.2.20
	 * @param float $price The price in store currency
	 * @param bool $show_both Whether to show both currencies or just converted
	 * @return string HTML markup for the conversion display
	 */
	public static function get_conversion_display( $price, $show_both = true ) {
		$conversion = self::get_conversion( $price );

		if ( $conversion === false ) {
			return '';
		}

		$store_currency = self::get_store_currency();
		$currency_symbol = Store_Data::get_store_data( 'currency_simbol' );

		$html = '<div class="myd-currency-conversion">';

		if ( $show_both ) {
			$html .= '<span class="myd-original-price">';
			$html .= esc_html( $currency_symbol ) . ' ' . number_format( $price, 2, ',', '.' );
			$html .= ' <small>' . esc_html( $store_currency ) . '</small>';
			$html .= '</span>';
			$html .= '<span class="myd-currency-separator"> ≈ </span>';
		}

		$html .= '<span class="myd-converted-price myd-vef-price">';
		$html .= esc_html( $conversion['currency_symbol'] ) . ' ';
		$html .= number_format( $conversion['amount'], 2, ',', '.' );
		$html .= ' <small>' . esc_html( $conversion['currency_code'] ) . '</small>';
		$html .= '</span>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Clear all cached rates (useful for manual refresh)
	 *
	 * @since 2.2.19
	 * @updated 2.2.20 - Clear both USD->VEF and EUR->VEF caches
	 * @return bool True on success, false on failure
	 */
	public static function clear_rate_cache() {
		$usd_cleared = delete_transient( self::TRANSIENT_KEY_USD_VEF );
		$eur_cleared = delete_transient( self::TRANSIENT_KEY_EUR_VEF );
		delete_transient( 'myd_usd_vef_data' );
		delete_transient( 'myd_eur_vef_data' );
		// Legacy transients
		delete_transient( 'myd_dolar_oficial_rate' );
		delete_transient( 'myd_bcv_data' );
		return $usd_cleared && $eur_cleared;
	}

	/**
	 * Get rate cache info for debugging
	 *
	 * @since 2.2.19
	 * @updated 2.2.20 - Updated for USD->VEF and EUR->VEF
	 * @updated 2.3.1 - Added manual rate information
	 * @return array Cache information for both conversion rates
	 */
	public static function get_cache_info() {
		$usd_rate = get_transient( self::TRANSIENT_KEY_USD_VEF );
		$usd_timeout = get_option( '_transient_timeout_' . self::TRANSIENT_KEY_USD_VEF );
		$usd_manual_enabled = self::is_manual_usd_rate_enabled();
		$usd_manual_rate = get_option( 'myd-currency-manual-rate-usd-vef' );

		$eur_rate = get_transient( self::TRANSIENT_KEY_EUR_VEF );
		$eur_timeout = get_option( '_transient_timeout_' . self::TRANSIENT_KEY_EUR_VEF );
		$eur_manual_enabled = self::is_manual_eur_rate_enabled();
		$eur_manual_rate = get_option( 'myd-currency-manual-rate-eur-vef' );

		return array(
			'usd_to_vef' => array(
				'rate' => $usd_rate,
				'cached' => $usd_rate !== false,
				'expires_at' => $usd_timeout ? date( 'Y-m-d H:i:s', $usd_timeout ) : null,
				'expires_in_minutes' => $usd_timeout ? round( ( $usd_timeout - time() ) / 60 ) : null,
				'manual_rate_enabled' => $usd_manual_enabled,
				'manual_rate' => $usd_manual_enabled ? floatval( $usd_manual_rate ) : null,
				'using_manual_rate' => $usd_manual_enabled,
			),
			'eur_to_vef' => array(
				'rate' => $eur_rate,
				'cached' => $eur_rate !== false,
				'expires_at' => $eur_timeout ? date( 'Y-m-d H:i:s', $eur_timeout ) : null,
				'expires_in_minutes' => $eur_timeout ? round( ( $eur_timeout - time() ) / 60 ) : null,
				'manual_rate_enabled' => $eur_manual_enabled,
				'manual_rate' => $eur_manual_enabled ? floatval( $eur_manual_rate ) : null,
				'using_manual_rate' => $eur_manual_enabled,
			),
		);
	}

	/**
	 * Shortcode para mostrar información de conversión según moneda activa
	 * Muestra USD->VEF o EUR->VEF automáticamente
	 *
	 * @since 2.2.19
	 * @updated 2.2.20 - Auto-detect currency (USD or EUR)
	 * @updated 2.3.1 - Show manual rate indicator
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

		// Detectar moneda activa
		$store_currency = self::get_store_currency();
		$data = false;
		$currency_label = 'USD';
		$is_manual = false;
		$manual_rate = 0;

		// Obtener datos según la moneda configurada
		if ( $store_currency === 'EUR' ) {
			$is_manual = self::is_manual_eur_rate_enabled();
			if ( $is_manual ) {
				$manual_rate = get_option( 'myd-currency-manual-rate-eur-vef' );
				$data = array(
					'nombre' => 'Tasa Manual',
					'promedio' => floatval( $manual_rate ),
					'fechaActualizacion' => null
				);
			} else {
				$data = self::get_eur_vef_data();
			}
			$currency_label = 'EUR';
		} elseif ( $store_currency === 'USD' ) {
			$is_manual = self::is_manual_usd_rate_enabled();
			if ( $is_manual ) {
				$manual_rate = get_option( 'myd-currency-manual-rate-usd-vef' );
				$data = array(
					'nombre' => 'Tasa Manual',
					'promedio' => floatval( $manual_rate ),
					'fechaActualizacion' => null
				);
			} else {
				$data = self::get_usd_vef_data();
			}
			$currency_label = 'USD';
		}

		if ( $data === false ) {
			return '<div class="' . esc_attr( $atts['class'] ) . ' myd-bcv-error">' .
				   esc_html__( 'Error al obtener datos de conversión', 'myd-delivery-pro' ) .
				   '</div>';
		}

		$html = '<div class="' . esc_attr( $atts['class'] ) . '">';

		if ( $atts['show_name'] === 'true' && isset( $data['nombre'] ) ) {
			$html .= '<div class="myd-bcv-name">' . esc_html( $data['nombre'] ) . '</div>';
		}

		if ( $atts['show_rate'] === 'true' && isset( $data['promedio'] ) ) {
			$formatted_rate = self::format_vef_amount( $data['promedio'] );
			$html .= '<div class="myd-bcv-rate">Bs. ' . esc_html( $formatted_rate ) . ' / ' . esc_html( $currency_label );
			if ( $is_manual ) {
				$html .= ' <small style="color: #2271b1;">(Manual)</small>';
			}
			$html .= '</div>';
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
	 * Shortcode para mostrar información del EUR
	 *
	 * @since 2.2.20
	 * @param array $atts Atributos del shortcode
	 * @return string HTML del shortcode
	 */
	public static function eur_rate_shortcode( $atts = array() ) {
		$atts = shortcode_atts( array(
			'show_name' => 'true',
			'show_rate' => 'true',
			'show_date' => 'true',
			'date_format' => 'd/m/Y H:i',
			'class' => 'myd-eur-info'
		), $atts, 'myd_eur_rate' );

		$data = self::get_eur_data();

		if ( $data === false ) {
			return '<div class="' . esc_attr( $atts['class'] ) . ' myd-eur-error">' .
				   esc_html__( 'Error al obtener datos del EUR', 'myd-delivery-pro' ) .
				   '</div>';
		}

		$html = '<div class="' . esc_attr( $atts['class'] ) . '">';

		if ( $atts['show_name'] === 'true' && isset( $data['nombre'] ) ) {
			$html .= '<div class="myd-eur-name">' . esc_html( $data['nombre'] ) . '</div>';
		}

		if ( $atts['show_rate'] === 'true' && isset( $data['promedio'] ) ) {
			$formatted_rate = number_format( $data['promedio'], 2, ',', '.' );
			$html .= '<div class="myd-eur-rate">€ ' . esc_html( $formatted_rate ) . '</div>';
		}

		if ( $atts['show_date'] === 'true' && isset( $data['fechaActualizacion'] ) && $data['fechaActualizacion'] ) {
			$date = date_create( $data['fechaActualizacion'] );
			if ( $date !== false ) {
				$formatted_date = date_format( $date, $atts['date_format'] );
				$html .= '<div class="myd-eur-date">' .
						 esc_html__( 'Actualizado:', 'myd-delivery-pro' ) . ' ' .
						 esc_html( $formatted_date ) .
						 '</div>';
			}
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Registrar los shortcodes
	 *
	 * @since 2.2.19
	 * @updated 2.2.20 - Added EUR shortcode
	 */
	public static function register_shortcode() {
		add_shortcode( 'myd_bcv_rate', array( __CLASS__, 'bcv_rate_shortcode' ) );
		add_shortcode( 'myd_eur_rate', array( __CLASS__, 'eur_rate_shortcode' ) );
	}
}