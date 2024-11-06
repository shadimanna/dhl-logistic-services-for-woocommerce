<?php

namespace PR\DHL\REST_API\Paket;

use Exception;
use PR\DHL\Utils\Args_Parser;

/**
 * A class that represents a Pickup Request item, which corresponds to a DHL Shipment for a WooCommerce Order.
 *
 * @since [*next-version*]
 */
class Pickup_Request_Info {

	/**
	 * The array of customer details.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $customer_details = array();

	/**
	 * The array of pickup contact.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $pickup_contact = array();

	/**
	 * The array of pickup addtions.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $pickup_address = array();

	/**
	 * The array of pickup information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $pickup_details = array();

	/**
	 * The array of all business hours for Pickup Location.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $business_hours;

	/**
	 * The array of all shipments.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $shipments;

	/**
	 * The units of measurement used for weights in the input args.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	protected $weightUom;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param array  $args The arguments to parse.
	 * @param string $weightUom The units of measurement used for weights in the input args.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	public function __construct( $args, $uom ) {
		// $this->parse_args( $args );
		$this->weightUom = $uom;

		$this->parse_args( $args, $uom );
	}

	/**
	 * Parses the arguments and sets the instance's properties.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	protected function parse_args( $args ) {

		$settings = $args['dhl_settings'];
		// $shipping_info = $args[ 'order_details' ] + $settings;

		$pickup_info = array(
			'dhl_pickup_type' => $args['dhl_pickup_type'],
			'dhl_pickup_date' => $args['dhl_pickup_date'],
		);

		$this->customer_details = Args_Parser::parse_args( $args, $this->get_customer_details_schema() );
		$this->pickup_contact   = Args_Parser::parse_args( $settings, $this->get_pickup_contact_schema() );
		$this->pickup_address   = Args_Parser::parse_args( $settings, $this->get_pickup_address_schema() );
		$this->pickup_details   = Args_Parser::parse_args( $pickup_info, $this->get_pickup_info_schema() );

		$this->shipments = array();
		foreach ( $args['dhl_pickup_shipments'] as $shipment_info ) {

			$pickup_shipment = Args_Parser::parse_args( $shipment_info, $this->get_shipment_info_schema() );

			// Empty tracking number?
			if ( isset( $pickup_shipment['shipmentNumber'] ) && empty( $pickup_shipment['shipmentNumber'] ) ) {
				unset( $pickup_shipment['shipmentNumber'] );
			}
			$this->shipments[] = $pickup_shipment;
		}

		$this->business_hours = array();
		if ( isset( $args['dhl_pickup_business_hours'] ) && $args['dhl_pickup_business_hours'] ) {
			foreach ( $args['dhl_pickup_business_hours'] as $time_slot ) {
				if ( isset( $time_slot['start'] ) && $time_slot['start'] && isset( $time_slot['end'] ) && $time_slot['end'] ) {
					$time_slot              = array(
						'timeFrom'  => $time_slot['start'],
						'timeUntil' => $time_slot['end'],
					);
					$this->business_hours[] = Args_Parser::parse_args( $time_slot, $this->get_pickup_business_hours_schema() );
				}
			}
		}
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing pickup location schema
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_customer_details_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'dhl_pickup_billing_number' => array(
				'rename'   => 'billingNumber',
				// 'error'  => esc_html__( '"Account Number" in settings is empty.', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $account ) use ( $self ) {

					if ( empty( $account ) ) {
						throw new Exception(
							esc_html__( 'Check your settings "Account Number" and "Participation Number".', 'dhl-for-woocommerce' )
						);
					}

					return $account;
				},
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for base item info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_pickup_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'dhl_pickup_date' => array(
				'default' => 0,
			),
			'dhl_pickup_type' => array(
				'default' => 'asap',
			),
			// 'weight'     => array(
			// 'error'    => esc_html__( 'Order "Weight" is empty!', 'dhl-for-woocommerce' ),
			// 'validate' => function( $weight ) use ($self) {
			// if ( ! is_numeric( $weight ) || $weight <= 0 ) {
			// throw new Exception( esc_html__( 'The order "Weight" must be a positive number', 'dhl-for-woocommerce' ) );
			// }
			// },
			// 'sanitize' => function ( $weight ) use ($self) {
			//
			// $weight = $self->maybe_convert_to_grams( $weight, $self->weightUom );
			//
			// return $weight;
			// }
			// ),
			// 'weightUom'  => array(
			// 'sanitize' => function ( $uom ) use ($self) {
			//
			// return ( $uom != 'G' )? 'G' : $uom;
			// }
			// ),
			// 'dimensionUom'     => array(
			// 'default' => 'CM'
			// )
		);
	}



	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing pickup location schema
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_pickup_contact_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'shipper_name'  => array(
				'rename'   => 'name',
				// 'error'  => esc_html__( '"Account Name" in settings is empty.', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {

					// if (empty($name)) {
					// throw new Exception(
					// esc_html__( '"Account Name" in settings is empty.', 'dhl-for-woocommerce' )
					// );
					// }

					return $self->string_length_sanitization( $name, 30 );
				},
			),
			'shipper_phone' => array(
				'rename'  => 'phone',
				'default' => '',
			),
			'shipper_email' => array(
				'rename'  => 'email',
				'default' => '',
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing pickup location schema
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_pickup_address_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'shipper_address'       => array(
				'rename'   => 'addressStreet',
				'error'    => esc_html__( 'Shipper "Address 1" is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {

					if ( empty( $name ) ) {
						throw new Exception(
							esc_html__( 'Shipper "Address 1" is empty!', 'dhl-for-woocommerce' )
						);
					}

					return $self->string_length_sanitization( $name, 50 );
				},
			),
			'shipper_address_no'    => array(
				'rename'  => 'addressHouse',
				'default' => '',
			),
			'shipper_address_city'  => array(
				'rename' => 'city',
				'error'  => esc_html__( 'Shipper "City" is empty!', 'dhl-for-woocommerce' ),
			),
			'shipper_address_zip'   => array(
				'rename' => 'postalCode',
				'error'  => esc_html__( 'Shipper "Postcode" is empty!', 'dhl-for-woocommerce' ),
			),
			'shipper_address_state' => array(
				'rename'  => 'state',
				'default' => '',
			),
			'shipper_country'       => array(
				'rename' => 'country',
				'error'  => esc_html__( 'Shipper "Country" is empty!', 'dhl-for-woocommerce' ),
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for base item info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_shipment_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'transportation_type' => array(
				'rename'  => 'transportationType',
				'default' => 'PAKET',
			),
			'tracking_number'     => array(
				'rename'  => 'shipmentNo',
				'default' => '',
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for base item info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_pickup_business_hours_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'timeFrom'  => array(
				'default' => 0,
			),
			'timeUntil' => array(
				'default' => 0,
			),
		);
	}



	/**
	 * Converts a given weight into grams, if necessary.
	 *
	 * @since [*next-version*]
	 *
	 * @param float  $weight The weight amount.
	 * @param string $uom The unit of measurement of the $weight parameter..
	 *
	 * @return float The potentially converted weight.
	 */
	protected function maybe_convert_to_grams( $weight, $uom ) {
		$weight = floatval( $weight );

		switch ( $uom ) {
			case 'kg':
				$weight = $weight * 1000;
				break;
			case 'lb':
				$weight = $weight / 2.2;
				break;
			case 'oz':
				$weight = $weight / 35.274;
				break;
		}

		return round( $weight );
	}

	protected function float_round_sanitization( $float, $numcomma ) {

		$float = floatval( $float );

		return round( $float, $numcomma );
	}

	protected function string_length_sanitization( $string, $max ) {

		$max = intval( $max );

		if ( strlen( $string ) <= $max ) {

			return $string;
		}

		return substr( $string, 0, ( $max - 1 ) );
	}
}
