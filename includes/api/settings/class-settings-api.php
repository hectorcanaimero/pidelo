<?php

namespace MydPro\Includes\Api\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings REST API endpoints
 */
class Settings_Api {
	/**
	 * Available settings and their default values
	 */
	private $available_settings = array(
		// Company settings
		'fdm-business-name' => '',
		'fdm-business-country' => 'United States',
		'fdm-business-address' => '',
		'fdm-business-phone' => '',
		'fdm-business-email' => '',
		'myd-business-mail' => '',
		'myd-business-whatsapp' => '',

		// Payment settings
		'myd-currency' => '$',
		'fdm-number-decimal' => '2',
		'fdm-decimal-separator' => '.',
		'fdm-thousands-separator' => ',',
		'fdm-payment-in-cash' => 'yes',
		'fdm-payment-type' => '',
		'myd-payment-receipt-required' => 'no',

		// Delivery settings
		'fdm-estimate-time-delivery' => '30-45 minutes',
		'fdm-mask-phone' => '',
		'fdm-delivery-methods' => '',
		'fdm-minimum-order' => '0',
		'myd-delivery-time' => array(),
		'myd-delivery-mode' => '',
		'myd-delivery-mode-options' => array(),
		'myd-free-delivery-enabled' => 'no',
		'myd-free-delivery-amount' => '0',

		// Store settings
		'fdm-list-menu-categories' => '',
		'fdm-store-hours' => '',
		'fdm-store-timezone' => '',
		'myd-delivery-force-open-close-store' => '',

		// Appearance settings
		'fdm-principal-color' => '#ea1d2b',
		'fdm-secondary-color' => '#ffffff',
		'fdm-text-color' => '#333333',
		'fdm-background-color' => '#ffffff',
		'myd-price-color' => '',
		'fdm-print-size' => '',
		'fdm-print-font-size' => '',
		'myd-products-list-columns' => 'myd-product-list--2columns',
		'myd-products-list-boxshadow' => 'myd-product-item--boxshadow',

		// Operation mode settings
		'myd-operation-mode-delivery' => 'delivery',
		'myd-operation-mode-take-away' => '',
		'myd-operation-mode-in-store' => '',
		'myd-skip-payment-in-store' => 'no',

		// Form field settings
		'myd-form-hide-zipcode' => '',
		'myd-form-hide-address-number' => '',

		// Order options
		'myd-option-minimum-price' => '',
		'myd-option-redirect-whatsapp' => '',

		// Currency conversion settings
		'myd-currency-conversion-enabled' => '0',
		'myd-currency-manual-rate-usd-vef-enabled' => 'no',
		'myd-currency-manual-rate-usd-vef' => '',
		'myd-currency-manual-rate-eur-vef-enabled' => 'no',
		'myd-currency-manual-rate-eur-vef' => '',

		// Google Maps / Shipping distance settings
		'fdm-google-maps-api-key' => '',
		'myd-shipping-distance-google-api-key' => '',
		'myd-shipping-distance-address-latitude' => '',
		'myd-shipping-distance-address-longitude' => '',
		'myd-shipping-distance-formated-address' => '',

		// Order templates
		'myd-template-order-custom-message-list-products' => '',
		'myd-template-order-custom-message-delivery' => '',
		'myd-template-order-custom-message-take-away' => '',
		'myd-template-order-custom-message-digital-menu' => '',

		// Advanced settings
		'fdm-page-order-track' => '',
		'fdm-whatsapp-number' => '',
		'fdm-notification-sound' => 'yes',
		'myd-notification-audio-enabled' => 'yes',
		'myd-notification-audio-volume' => '0.8',
		'myd-notification-repeat-count' => '3',

		// Integration settings
		'fdm-payment-gateway' => '',
		'fdm-email-notifications' => 'yes',
		'fdm-sms-notifications' => 'no',

		// Evolution API (WhatsApp) settings
		'myd-evolution-api-enabled' => 'no',
		'myd-evolution-phone-country-code' => '55',
		'myd-evolution-auto-send-events' => array(),
		'myd-evolution-template-order-created' => '',
		'myd-evolution-template-order-confirmed' => '',
		'myd-evolution-template-order-in-process' => '',
		'myd-evolution-template-order-ready' => '',
		'myd-evolution-template-order-in-delivery' => '',
		'myd-evolution-template-order-done' => '',
		'myd-evolution-template-order-finished' => '',

		// License (sensitive - consider excluding from public API)
		'fdm-license' => '',
	);

	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_settings_routes' ] );
	}

	/**
	 * Register settings routes
	 */
	public function register_settings_routes() {
		// GET /settings - Get all settings
		\register_rest_route(
			'myd-delivery/v1',
			'/settings',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'group' => array(
							'description' => __( 'Filter settings by group', 'myd-delivery-pro' ),
							'type' => 'string',
							'enum' => array( 'company', 'payment', 'delivery', 'store', 'appearance', 'advanced', 'integration' ),
						),
					),
				),
			)
		);

		// PUT /settings - Update settings
		\register_rest_route(
			'myd-delivery/v1',
			'/settings',
			array(
				array(
					'methods'  => \WP_REST_Server::EDITABLE,
					'callback' => [ $this, 'update_settings' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => $this->get_settings_schema(),
				),
			)
		);

		// GET /settings/{key} - Get specific setting
		\register_rest_route(
			'myd-delivery/v1',
			'/settings/(?P<key>[a-zA-Z0-9\-_]+)',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_setting' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'key' => array(
							'description' => __( 'Setting key', 'myd-delivery-pro' ),
							'type' => 'string',
							'required' => true,
						),
					),
				),
			)
		);

		// PUT /settings/{key} - Update specific setting
		\register_rest_route(
			'myd-delivery/v1',
			'/settings/(?P<key>[a-zA-Z0-9\-_]+)',
			array(
				array(
					'methods'  => \WP_REST_Server::EDITABLE,
					'callback' => [ $this, 'update_setting' ],
					'permission_callback' => [ $this, 'check_admin_permissions' ],
					'args' => array(
						'key' => array(
							'description' => __( 'Setting key', 'myd-delivery-pro' ),
							'type' => 'string',
							'required' => true,
						),
						'value' => array(
							'description' => __( 'Setting value', 'myd-delivery-pro' ),
							'type' => array( 'string', 'number', 'boolean' ),
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Get all settings
	 */
	public function get_settings( $request ) {
		$group = $request->get_param( 'group' );
		$settings = array();

		foreach ( $this->available_settings as $key => $default ) {
			// Filter by group if specified
			if ( $group && ! $this->setting_belongs_to_group( $key, $group ) ) {
				continue;
			}

			$value = get_option( $key, $default );
			$settings[ $key ] = $this->format_setting_value( $value );
		}

		$response = array(
			'settings' => $settings,
			'groups' => $this->get_settings_groups(),
		);

		if ( $group ) {
			$response['group'] = $group;
			$response['group_info'] = $this->get_group_info( $group );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Get specific setting
	 */
	public function get_setting( $request ) {
		$key = $request['key'];

		if ( ! array_key_exists( $key, $this->available_settings ) ) {
			return new \WP_Error( 'setting_not_found', __( 'Setting not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$value = get_option( $key, $this->available_settings[ $key ] );

		$response = array(
			'key' => $key,
			'value' => $this->format_setting_value( $value ),
			'default' => $this->available_settings[ $key ],
			'group' => $this->get_setting_group( $key ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Update all settings
	 */
	public function update_settings( $request ) {
		$updated_settings = array();
		$errors = array();

		foreach ( $this->available_settings as $key => $default ) {
			if ( ! isset( $request[ $key ] ) ) {
				continue;
			}

			$value = $request[ $key ];
			$sanitized_value = $this->sanitize_setting_value( $key, $value );

			if ( is_wp_error( $sanitized_value ) ) {
				$errors[ $key ] = $sanitized_value->get_error_message();
				continue;
			}

			// update_option returns false if value is unchanged, which is not an error
			update_option( $key, $sanitized_value );
			$updated_settings[ $key ] = $sanitized_value;
		}

		if ( ! empty( $errors ) ) {
			return new \WP_Error( 'validation_failed', __( 'Some settings could not be updated', 'myd-delivery-pro' ), array(
				'status' => 400,
				'errors' => $errors
			) );
		}

		$response = array(
			'updated' => $updated_settings,
			'message' => __( 'Settings updated successfully', 'myd-delivery-pro' ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Update specific setting
	 */
	public function update_setting( $request ) {
		$key = $request['key'];
		$value = $request['value'];

		if ( ! array_key_exists( $key, $this->available_settings ) ) {
			return new \WP_Error( 'setting_not_found', __( 'Setting not found', 'myd-delivery-pro' ), array( 'status' => 404 ) );
		}

		$sanitized_value = $this->sanitize_setting_value( $key, $value );

		if ( is_wp_error( $sanitized_value ) ) {
			return $sanitized_value;
		}

		// update_option always updates the value, returns false only if unchanged
		update_option( $key, $sanitized_value );

		// Verify the value was actually saved
		$saved_value = get_option( $key );
		if ( $saved_value !== $sanitized_value ) {
			return new \WP_Error( 'update_failed', __( 'Failed to update setting', 'myd-delivery-pro' ), array( 'status' => 500 ) );
		}

		$response = array(
			'key' => $key,
			'value' => $this->format_setting_value( $sanitized_value ),
			'message' => __( 'Setting updated successfully', 'myd-delivery-pro' ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * Check if setting belongs to group
	 */
	private function setting_belongs_to_group( $key, $group ) {
		$group_mappings = array(
			'company' => array(
				'fdm-business-name',
				'fdm-business-country',
				'fdm-business-address',
				'fdm-business-phone',
				'fdm-business-email',
				'myd-business-mail',
				'myd-business-whatsapp'
			),
			'payment' => array(
				'myd-currency',
				'fdm-number-decimal',
				'fdm-decimal-separator',
				'fdm-thousands-separator',
				'fdm-payment-in-cash',
				'fdm-payment-type',
				'fdm-payment-gateway',
				'myd-payment-receipt-required',
				'myd-currency-conversion-enabled',
				'myd-currency-manual-rate-usd-vef-enabled',
				'myd-currency-manual-rate-usd-vef',
				'myd-currency-manual-rate-eur-vef-enabled',
				'myd-currency-manual-rate-eur-vef'
			),
			'delivery' => array(
				'fdm-estimate-time-delivery',
				'fdm-mask-phone',
				'fdm-delivery-methods',
				'fdm-minimum-order',
				'myd-delivery-time',
				'myd-delivery-mode',
				'myd-delivery-mode-options',
				'myd-option-minimum-price',
				'myd-option-redirect-whatsapp',
				'myd-operation-mode-delivery',
				'myd-operation-mode-take-away',
				'myd-operation-mode-in-store',
				'myd-skip-payment-in-store'
			),
			'store' => array(
				'fdm-list-menu-categories',
				'fdm-store-hours',
				'fdm-store-timezone',
				'myd-delivery-force-open-close-store'
			),
			'appearance' => array(
				'fdm-principal-color',
				'fdm-secondary-color',
				'fdm-text-color',
				'fdm-background-color',
				'myd-price-color',
				'fdm-print-size',
				'fdm-print-font-size',
				'myd-products-list-columns',
				'myd-products-list-boxshadow',
				'myd-form-hide-zipcode',
				'myd-form-hide-address-number'
			),
			'advanced' => array(
				'fdm-page-order-track',
				'fdm-google-maps-api-key',
				'myd-shipping-distance-google-api-key',
				'myd-shipping-distance-address-latitude',
				'myd-shipping-distance-address-longitude',
				'myd-shipping-distance-formated-address',
				'fdm-whatsapp-number',
				'fdm-notification-sound',
				'myd-notification-audio-enabled',
				'myd-notification-audio-volume',
				'myd-notification-repeat-count',
				'myd-template-order-custom-message-list-products',
				'myd-template-order-custom-message-delivery',
				'myd-template-order-custom-message-take-away',
				'myd-template-order-custom-message-digital-menu',
				'fdm-license'
			),
			'integration' => array(
				'fdm-email-notifications',
				'fdm-sms-notifications',
				'myd-evolution-api-enabled',
				'myd-evolution-phone-country-code',
				'myd-evolution-auto-send-events',
				'myd-evolution-template-order-created',
				'myd-evolution-template-order-confirmed',
				'myd-evolution-template-order-in-process',
				'myd-evolution-template-order-ready',
				'myd-evolution-template-order-in-delivery',
				'myd-evolution-template-order-done',
				'myd-evolution-template-order-finished'
			),
		);

		return isset( $group_mappings[ $group ] ) && in_array( $key, $group_mappings[ $group ] );
	}

	/**
	 * Get setting group
	 */
	private function get_setting_group( $key ) {
		$groups = array( 'company', 'payment', 'delivery', 'store', 'appearance', 'advanced', 'integration' );
		
		foreach ( $groups as $group ) {
			if ( $this->setting_belongs_to_group( $key, $group ) ) {
				return $group;
			}
		}

		return 'general';
	}

	/**
	 * Get settings groups
	 */
	private function get_settings_groups() {
		return array(
			'company' => __( 'Company Information', 'myd-delivery-pro' ),
			'payment' => __( 'Payment Settings', 'myd-delivery-pro' ),
			'delivery' => __( 'Delivery Settings', 'myd-delivery-pro' ),
			'store' => __( 'Store Settings', 'myd-delivery-pro' ),
			'appearance' => __( 'Appearance Settings', 'myd-delivery-pro' ),
			'advanced' => __( 'Advanced Settings', 'myd-delivery-pro' ),
			'integration' => __( 'Integration Settings', 'myd-delivery-pro' ),
		);
	}

	/**
	 * Get group info
	 */
	private function get_group_info( $group ) {
		$info = array(
			'company' => __( 'Business information and contact details', 'myd-delivery-pro' ),
			'payment' => __( 'Currency, payment methods and financial settings', 'myd-delivery-pro' ),
			'delivery' => __( 'Delivery options, timing and restrictions', 'myd-delivery-pro' ),
			'store' => __( 'Menu categories, hours and store configuration', 'myd-delivery-pro' ),
			'appearance' => __( 'Colors, themes and visual customization', 'myd-delivery-pro' ),
			'advanced' => __( 'API keys, tracking pages and technical settings', 'myd-delivery-pro' ),
			'integration' => __( 'Third-party services and notification settings', 'myd-delivery-pro' ),
		);

		return $info[ $group ] ?? '';
	}

	/**
	 * Format setting value for API response
	 */
	private function format_setting_value( $value ) {
		// Convert string representations of booleans
		if ( $value === 'yes' || $value === 'true' ) {
			return true;
		}
		if ( $value === 'no' || $value === 'false' ) {
			return false;
		}

		// Convert numeric strings to numbers where appropriate
		if ( is_numeric( $value ) ) {
			// Check if it contains a decimal point to determine float vs int
			if ( strpos( $value, '.' ) !== false ) {
				return floatval( $value );
			}
			return intval( $value );
		}

		return $value;
	}

	/**
	 * Sanitize setting value
	 */
	private function sanitize_setting_value( $key, $value ) {
		// Handle array values
		if ( in_array( $key, array( 'myd-delivery-time', 'myd-delivery-mode-options', 'myd-evolution-auto-send-events' ) ) ) {
			if ( ! is_array( $value ) ) {
				return new \WP_Error( 'invalid_type', __( 'Value must be an array', 'myd-delivery-pro' ), array( 'status' => 400 ) );
			}
			return array_map( 'sanitize_text_field', $value );
		}

		// Handle boolean values - accept both boolean and string representations
		if ( in_array( $key, array(
			'fdm-payment-in-cash',
			'fdm-notification-sound',
			'fdm-email-notifications',
			'fdm-sms-notifications',
			'myd-payment-receipt-required',
			'myd-skip-payment-in-store',
			'myd-currency-conversion-enabled',
			'myd-currency-manual-rate-usd-vef-enabled',
			'myd-currency-manual-rate-eur-vef-enabled',
			'myd-notification-audio-enabled',
			'myd-evolution-api-enabled',
			'myd-option-redirect-whatsapp',
			'myd-form-hide-zipcode',
			'myd-form-hide-address-number'
		) ) ) {
			// Handle various boolean representations
			if ( is_bool( $value ) ) {
				return $value ? 'yes' : 'no';
			}
			if ( $value === 'yes' || $value === 'true' || $value === '1' || $value === 1 ) {
				return 'yes';
			}
			return 'no';
		}

		// Handle numeric values
		if ( in_array( $key, array(
			'fdm-number-decimal',
			'fdm-minimum-order',
			'myd-notification-audio-volume',
			'myd-notification-repeat-count',
			'myd-option-minimum-price',
			'myd-currency-manual-rate-usd-vef',
			'myd-currency-manual-rate-eur-vef',
			'myd-shipping-distance-address-latitude',
			'myd-shipping-distance-address-longitude'
		) ) ) {
			$numeric_value = floatval( $value );
			if ( $numeric_value < 0 && ! in_array( $key, array( 'myd-shipping-distance-address-latitude', 'myd-shipping-distance-address-longitude' ) ) ) {
				return new \WP_Error( 'invalid_value', __( 'Value must be positive', 'myd-delivery-pro' ), array( 'status' => 400 ) );
			}
			// Return int for specific fields
			if ( in_array( $key, array( 'fdm-number-decimal', 'myd-notification-repeat-count' ) ) ) {
				return intval( $numeric_value );
			}
			return $numeric_value;
		}

		// Handle color values
		if ( strpos( $key, 'color' ) !== false ) {
			$value = sanitize_text_field( $value );
			if ( ! empty( $value ) && ! preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value ) ) {
				return new \WP_Error( 'invalid_color', __( 'Invalid color format. Use hex format like #FF0000', 'myd-delivery-pro' ), array( 'status' => 400 ) );
			}
			return $value;
		}

		// Handle email values
		if ( strpos( $key, 'email' ) !== false || strpos( $key, 'mail' ) !== false ) {
			if ( ! empty( $value ) ) {
				$sanitized_email = sanitize_email( $value );
				if ( ! is_email( $sanitized_email ) ) {
					return new \WP_Error( 'invalid_email', __( 'Invalid email format', 'myd-delivery-pro' ), array( 'status' => 400 ) );
				}
				return $sanitized_email;
			}
			return '';
		}

		// Handle phone numbers - preserve + and - characters
		if ( strpos( $key, 'phone' ) !== false || strpos( $key, 'whatsapp' ) !== false ) {
			return sanitize_text_field( $value );
		}

		// Handle template/textarea values - preserve newlines
		if ( strpos( $key, 'template' ) !== false || strpos( $key, 'message' ) !== false ) {
			return sanitize_textarea_field( $value );
		}

		// Handle license key - no sanitization to preserve exact value
		if ( $key === 'fdm-license' ) {
			return $value;
		}

		// Default sanitization
		return sanitize_text_field( $value );
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
	 * Get settings schema for validation
	 */
	public function get_settings_schema() {
		$schema = array();

		foreach ( $this->available_settings as $key => $default ) {
			$type = 'string';

			// Determine type based on key name or default value
			if ( in_array( $key, array(
				'fdm-payment-in-cash',
				'fdm-notification-sound',
				'fdm-email-notifications',
				'fdm-sms-notifications',
				'myd-payment-receipt-required',
				'myd-skip-payment-in-store',
				'myd-currency-conversion-enabled',
				'myd-currency-manual-rate-usd-vef-enabled',
				'myd-currency-manual-rate-eur-vef-enabled',
				'myd-notification-audio-enabled',
				'myd-evolution-api-enabled',
				'myd-option-redirect-whatsapp',
				'myd-form-hide-zipcode',
				'myd-form-hide-address-number'
			) ) ) {
				$type = 'boolean';
			} elseif ( in_array( $key, array(
				'fdm-number-decimal',
				'fdm-minimum-order',
				'myd-notification-audio-volume',
				'myd-notification-repeat-count',
				'myd-option-minimum-price',
				'myd-currency-manual-rate-usd-vef',
				'myd-currency-manual-rate-eur-vef',
				'myd-shipping-distance-address-latitude',
				'myd-shipping-distance-address-longitude'
			) ) ) {
				$type = 'number';
			} elseif ( in_array( $key, array(
				'myd-delivery-time',
				'myd-delivery-mode-options',
				'myd-evolution-auto-send-events'
			) ) ) {
				$type = 'array';
			}

			$schema[ $key ] = array(
				'description' => sprintf( __( 'Setting: %s', 'myd-delivery-pro' ), $key ),
				'type' => $type,
			);
		}

		return $schema;
	}
}