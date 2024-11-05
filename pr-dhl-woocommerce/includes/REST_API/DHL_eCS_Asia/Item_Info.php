<?php

namespace PR\DHL\REST_API\DHL_eCS_Asia;

use Exception;
use PR\DHL\Utils\Args_Parser;

/**
 * A class that represents a Deutsche Post item, which corresponds to a WooCommerce order.
 *
 * @since [*next-version*]
 */
class Item_Info {

	/**
	 * The order id
	 *
	 * @since [*next-version*]
	 *
	 * @var int
	 */
	public $order_id;

	/**
	 * The array of body information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $body = array();

	/**
	 * The array of shipment information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $shipment = array();

	/**
	 * The array of shipment pieces information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $shipment_pieces;

	/**
	 * The array of order recipient information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $recipient = array();

	/**
	 * The array of consignee information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $consignee = array();

	/**
	 * The array of shipper information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $shipper = array();

	/**
	 * The array of shipper address and tax information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $shipper_address_w_tax = array();

	/**
	 * The array of content item information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $contents = array();

	/**
	 * The units of measurement used for weights in the input args.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	protected $weightUom;

	/**
	 * Is the shipment cross-border or domestic
	 *
	 * @since [*next-version*]
	 *
	 * @var boolean
	 */
	public $isCrossBorder;

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
	public function __construct( $args, $uom, $isCrossBorder ) {
		// $this->parse_args( $args );
		$this->weightUom     = $uom;
		$this->isCrossBorder = $isCrossBorder;

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

		$settings       = $args['dhl_settings'];
		$recipient_info = $args['shipping_address'] + $settings;
		$shipping_info  = $args['order_details'] + $settings;
		$items_info     = $args['items'];

		$this->body      = Args_Parser::parse_args( $shipping_info, $this->get_body_info_schema() );
		$this->shipment  = Args_Parser::parse_args( $shipping_info, $this->get_shipment_info_schema() );
		$this->consignee = Args_Parser::parse_args( $recipient_info, $this->get_recipient_info_schema() );

		if ( $args['order_details']['dhl_product'] == 'SDP' ) {
			$this->shipper = Args_Parser::parse_args( $settings, $this->get_shipper_info_schema() );
		}

		if ( $this->isCrossBorder ) {
			$this->shipper_address_w_tax = Args_Parser::parse_args( $settings, $this->get_shipper_address_w_tax_info_schema() );
		}

		$this->contents = array();
		foreach ( $items_info as $item_info ) {
			$this->contents[] = Args_Parser::parse_args( $item_info, $this->get_content_item_info_schema() );
		}
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for header info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_body_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'label_format'   => array(
				'default' => '',
			),
			'label_layout'   => array(
				'default' => '',
			),
			'label_pagesize' => array(
				'default' => '400x600',
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
			'order_id'        => array(
				'error' => esc_html__( 'Shipment "Order ID" is empty!', 'dhl-for-woocommerce' ),
			),
			'prefix'          => array(
				'default' => 'DHL',
			),
			'description'     => array(
				'default'  => '',
				'validate' => function ( $value ) {

					if ( empty( $value ) && $this->isCrossBorder ) {
						throw new Exception( esc_html__( 'Shipment "Description" is empty!', 'dhl-for-woocommerce' ) );
					}
				},
			),
			'weight'          => array(
				'error'    => esc_html__( 'Order "Weight" is empty!', 'dhl-for-woocommerce' ),
				'validate' => function ( $weight ) use ( $self ) {
					if ( ! is_numeric( $weight ) || $weight <= 0 ) {
						throw new Exception( esc_html__( 'The order "Weight" must be a positive number', 'dhl-for-woocommerce' ) );
					}
				},
				'sanitize' => function ( $weight ) use ( $self ) {

					$weight = $self->maybe_convert_to_grams( $weight, $self->weightUom );

					return $weight;
				},
			),
			'weightUom'       => array(
				'sanitize' => function ( $uom ) use ( $self ) {

					return ( $uom != 'G' ) ? 'G' : $uom;
				},
			),
			'dimensionUom'    => array(
				'default' => 'CM',
			),
			'dhl_product'     => array(
				'rename' => 'product_code',
				'error'  => esc_html__( '"DHL Product" is empty!', 'dhl-for-woocommerce' ),
			),
			'duties'          => array(
				'rename'   => 'incoterm',
				'default'  => '',
				'validate' => function ( $value ) {

					if ( empty( $value ) && $this->isCrossBorder ) {
						throw new Exception( esc_html__( 'Shipment "Duties" is empty!', 'dhl-for-woocommerce' ) );
					}
				},
			),
			'items_value'     => array(
				'error'    => esc_html__( 'Shipment "Value" is empty!', 'dhl-for-woocommerce' ),
				'validate' => function ( $value ) {
					if ( ! is_numeric( $value ) ) {
						throw new Exception( esc_html__( 'The order "value" must be a number', 'dhl-for-woocommerce' ) );
					}
				},
				'sanitize' => function ( $value ) use ( $self ) {

					return $self->float_round_sanitization( $value, 2 );
				},
			),
			'currency'        => array(
				'error' => esc_html__( 'Shop "Currency" is empty!', 'dhl-for-woocommerce' ),
			),
			'cod_value'       => array(
				'default'  => 0,
				'rename'   => 'codValue',
				'sanitize' => function ( $value, $args ) use ( $self ) {
					if ( isset( $args['is_cod'] ) && $args['is_cod'] == 'yes' ) {
						$value = $self->float_round_sanitization( $value, 2 );
					} else {
						$value = 0;
					}
					return $value;
				},
			),
			'order_note'      => array(
				'default' => '',
				'rename'  => 'remarks',
			),
			'insurance_value' => array(
				'default'  => 0,
				'rename'   => 'insuranceValue',
				'validate' => function ( $value, $args ) {
					if ( isset( $args['additional_insurance'] ) && $args['additional_insurance'] == 'yes' && empty( $value ) ) {
						throw new Exception( esc_html__( 'The "Insurance Value" cannot be empty', 'dhl-for-woocommerce' ) );
					}
				},
				'sanitize' => function ( $value, $args ) use ( $self ) {
					if ( isset( $args['additional_insurance'] ) && $args['additional_insurance'] == 'yes' ) {

						$value = $self->float_round_sanitization( $value, 2 );

					} else {
						$value = 0;
					}
					return $value;
				},
			),
			'obox_service'    => array(
				'default'  => '',
				'sanitize' => function ( $value ) use ( $self ) {

					if ( isset( $value ) && $value == 'yes' ) {
						$value = 'OBOX';
					} else {
						$value = '';
					}

					return $value;
				},
			),
			'dangerous_goods' => array(
				'default' => '',
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order recipient info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_recipient_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'name'      => array(
				'error'    => esc_html__( 'Recipient is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {

					return $self->string_length_sanitization( $name, 30 );
				},
			),
			'phone'     => array(
				'default'  => '',
				'sanitize' => function ( $phone ) use ( $self ) {

					return $self->string_length_sanitization( $phone, 15 );
				},
			),
			'email'     => array(
				'default' => '',
			),
			'address_1' => array(
				'rename' => 'address1',
				'error'  => esc_html__( 'Shipping "Address 1" is empty!', 'dhl-for-woocommerce' ),
			),
			'address_2' => array(
				'rename'  => 'address2',
				'default' => '',
			),
			'city'      => array(
				'validate' => function ( $value ) {

					if ( empty( $value ) && $this->isCrossBorder ) {
						throw new Exception( esc_html__( 'Shipping "City" is empty!', 'dhl-for-woocommerce' ) );
					}
				},
			),
			'postcode'  => array(
				'rename' => 'postCode',
				'error'  => esc_html__( 'Shipping "Postcode" is empty!', 'dhl-for-woocommerce' ),
			),
			'district'  => array(
				'default' => '',
			),
			'state'     => array(
				'default' => '',
			),
			'country'   => array(
				'error' => esc_html__( 'Shipping "Country" is empty!', 'dhl-for-woocommerce' ),
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order pickup shipment info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_shipper_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'dhl_contact_name' => array(
				'rename'   => 'name',
				'error'    => esc_html__( '"Account Name" in settings is empty.', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {

					if ( empty( $name ) ) {
						throw new Exception(
							esc_html__( '"Account Name" in settings is empty.', 'dhl-for-woocommerce' )
						);
					}

					return $self->string_length_sanitization( $name, 30 );
				},
			),
			'dhl_phone'        => array(
				'rename'  => 'phone',
				'default' => '',
			),
			'dhl_email'        => array(
				'rename'  => 'email',
				'default' => '',
			),
			'dhl_address_1'    => array(
				'rename'   => 'address1',
				'error'    => esc_html__( 'Base "Address 1" is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {

					if ( empty( $name ) ) {
						throw new Exception(
							esc_html__( 'Base "Address 1" is empty!', 'dhl-for-woocommerce' )
						);
					}

					return $self->string_length_sanitization( $name, 50 );
				},
			),
			'dhl_address_2'    => array(
				'rename'  => 'address2',
				'default' => '',
			),
			'dhl_city'         => array(
				'rename' => 'city',
				'error'  => esc_html__( 'Base "City" is empty!', 'dhl-for-woocommerce' ),
			),
			'dhl_district'     => array(
				'rename'  => 'district',
				'default' => '',
			),
			'dhl_postcode'     => array(
				'rename' => 'postCode',
				'error'  => esc_html__( 'Base "Postcode" is empty!', 'dhl-for-woocommerce' ),
			),
			'dhl_state'        => array(
				'rename'  => 'state',
				'default' => '',
			),
			'dhl_country'      => array(
				'rename' => 'country',
				'error'  => esc_html__( 'Base "Country" is empty!', 'dhl-for-woocommerce' ),
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order shipper tax info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_shipper_address_w_tax_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'dhl_contact_name' => array(
				'rename'   => 'name',
				'error'    => esc_html__( '"Account Name" in settings is empty.', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {

					if ( empty( $name ) ) {
						throw new Exception(
							esc_html__( '"Account Name" in settings is empty.', 'dhl-for-woocommerce' )
						);
					}

					return $self->string_length_sanitization( $name, 30 );
				},
			),
			'dhl_phone'        => array(
				'rename'  => 'phone',
				'default' => '',
			),
			'dhl_email'        => array(
				'rename'  => 'email',
				'default' => '',
			),
			'dhl_address_1'    => array(
				'rename'   => 'address1',
				'error'    => esc_html__( 'Base "Address 1" is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {

					if ( empty( $name ) ) {
						throw new Exception(
							esc_html__( 'Base "Address 1" is empty!', 'dhl-for-woocommerce' )
						);
					}

					return $self->string_length_sanitization( $name, 50 );
				},
			),
			'dhl_address_2'    => array(
				'rename'  => 'address2',
				'default' => '',
			),
			'dhl_city'         => array(
				'rename' => 'city',
				'error'  => esc_html__( 'Base "City" is empty!', 'dhl-for-woocommerce' ),
			),
			'dhl_district'     => array(
				'rename'  => 'district',
				'default' => '',
			),
			'dhl_postcode'     => array(
				'rename' => 'postCode',
				'error'  => esc_html__( 'Base "Postcode" is empty!', 'dhl-for-woocommerce' ),
			),
			'dhl_state'        => array(
				'rename'  => 'state',
				'default' => '',
			),
			'dhl_country'      => array(
				'rename' => 'country',
				'error'  => esc_html__( 'Base "Country" is empty!', 'dhl-for-woocommerce' ),
			),
			'dh_tax_id_type'   => array(
				'rename'   => 'fiscalIdType',
				'error'    => esc_html__( 'You must select a "Shipper Tax ID Type", or select "-- No Shipper Tax ID --" to continue without a Shipper Tax ID.', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $value, $args ) use ( $self ) {
					if ( $value == 'none' ) {
						return '';
					} else {
						return $self->string_length_sanitization( $value, 50 );
					}
				},
			),
			'dh_tax_id'        => array(
				'default'  => '',
				'rename'   => 'fiscalId',
				'validate' => function ( $value, $args ) {
					if ( isset( $args['dh_tax_id_type'] ) && $args['dh_tax_id_type'] != 'none' && $args['dh_tax_id_type'] != 4 && empty( $value ) ) {
						throw new Exception(
							esc_html__( 'You must provide a "Shipper Tax ID", or to continue without a Shipper Tax ID you must select "-- No Shipper Tax ID --" for "Shipper Tax ID Type"."', 'dhl-for-woocommerce' )
						);
					}
				},
				'sanitize' => function ( $value, $args ) use ( $self ) {
					return $self->string_length_sanitization( $value, 50 );
				},
			),

		);
	}



	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order content item info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_content_item_info_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'hs_code'          => array(
				'default'  => '',
				'validate' => function ( $hs_code ) {
					$length = is_string( $hs_code ) ? strlen( $hs_code ) : 0;

					if ( empty( $length ) ) {
						return;
					}

					if ( $length < 6 || $length > 20 ) {
						throw new Exception(
							esc_html__( 'Item HS Code must be between 6 and 20 characters long', 'dhl-for-woocommerce' )
						);
					}
				},
			),
			'item_description' => array(
				'rename'   => 'description',
				'default'  => '',
				'sanitize' => function ( $description ) use ( $self ) {

					return $self->string_length_sanitization( $description, 50 );
				},
			),
			'item_export'      => array(
				'rename'   => 'descriptionExport',
				'default'  => '',
				'sanitize' => function ( $description ) use ( $self ) {

					return $self->string_length_sanitization( $description, 50 );
				},
			),
			'product_id'       => array(
				'error' => esc_html__( 'Item "Product ID" is empty!', 'dhl-for-woocommerce' ),
			),
			'sku'              => array(
				'error' => esc_html__( 'Item "Product SKU" is empty!', 'dhl-for-woocommerce' ),
			),
			'item_value'       => array(
				'rename'   => 'value',
				'default'  => 0,
				'sanitize' => function ( $value ) use ( $self ) {

					return $self->float_round_sanitization( $value, 2 );
				},
			),
			'origin'           => array(
				'default' => PR_DHL()->get_base_country(),
			),
			'qty'              => array(
				'validate' => function ( $qty ) {

					if ( ! is_numeric( $qty ) || $qty < 1 ) {

						throw new Exception(
							esc_html__( 'Item quantity must be more than 1', 'dhl-for-woocommerce' )
						);

					}
				},
			),
			'item_weight'      => array(
				'rename'   => 'weight',
				'sanitize' => function ( $weight ) use ( $self ) {

					$weight = $self->maybe_convert_to_grams( $weight, $self->weightUom );
					$weight = ( $weight > 1 ) ? $weight : 1;
					return $weight;
				},
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
