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
	 * The array of header information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $header;

	/**
	 * The array of body information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $body;

	/**
	 * The array of shipment information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $shipment;

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
	public $recipient;

	/**
	 * The array of consignee information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $consignee;

	/**
	 * The array of shipper information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $shipper;

	/**
	 * The array of content item information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $contents;

	/**
	 * The array of delete information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $delete_info;

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
	 * @param array $args The arguments to parse.
	 * @param string $weightUom The units of measurement used for weights in the input args.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	public function __construct( $args, $uom ) {
		//$this->parse_args( $args );
		$this->weightUom 	= $uom;
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
		$settings = $args[ 'dhl_settings' ];
		$recipient_info = $args[ 'shipping_address' ] + $settings;
		$shipping_info = $args[ 'order_details' ] + $settings;
		$items_info = $args['items'];
		
		$this->header 			= Args_Parser::parse_args( $shipping_info, $this->get_header_info_schema() );
		$this->body 			= Args_Parser::parse_args( $shipping_info, $this->get_body_info_schema() );
		$this->shipment 		= Args_Parser::parse_args( $shipping_info, $this->get_shipment_info_schema() );
		$this->consignee 		= Args_Parser::parse_args( $recipient_info, $this->get_recipient_info_schema() );
//		$this->shipper 			= Args_Parser::parse_args( $settings, $this->get_shipper_info_schema() );
		$this->contents 		= array();

		foreach ( $items_info as $item_info ) {
			$this->contents[] = Args_Parser::parse_args( $item_info, $this->get_content_item_info_schema() );
		}

		if( isset( $args[ 'label_tracking' ] ) ){
			$tracking_info 			= $args[ 'label_tracking' ] + $settings;
			$this->delete_info 		= Args_Parser::parse_args( $tracking_info, $this->get_delete_info_schema() );	
		}
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for header info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_header_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;
		
		return array(
			'message_type' => array(
				'default' => 'LABEL'
			),
			'message_date_time' => array(
				'default' => date( 'c', time() )
			),
			'message_version' => array(
				'default' => '1.4'
			),
			'access_token' => array(
				'default' => ''
			),
			'message_language' => array(
				'default' => 'en'
			)
		);
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
			'pickup_id' => array(
				'error' => __( 'Pickup Account ID is empty!', 'pr-shipping-dhl' ),
			),
			'soldto_id' => array(
				'error' => __( 'Soldto Account ID is empty!', 'pr-shipping-dhl' ),
			),
			'label_format' => array(
				'default' => 'PDF'
			),
			'label_layout' => array(
				'default' => '1x1'
			),
			'label_pagesize' => array(
				'default' => '400x600'
			)
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
			'order_id'      => array(
				'error'  => __( 'Shipment "Order ID" is empty!', 'pr-shipping-dhl' ),
			),
			'prefix' 		=> array(
				'default' => 'DHL'
			),
			'return_mode'   => array(
				'default' => '01'
			),
			'description' 	=> array(
				'error'  => __( 'Shipment "Description" is empty!', 'pr-shipping-dhl' ),
			),
			'weight'     => array(
				'default' => '1',
				'sanitize' => function ( $weight ) use ($self) {

					$weight = $self->maybe_convert_to_grams( $weight, $self->weightUom );
					$weight = ( $weight > 1 )? $weight : 1;
					return $weight;
				}
			),
			'weightUom'  => array(
				'default' => 'G',
				'sanitize' => function ( $uom ) use ($self) {

					return ( $uom != 'G' )? 'G' : $uom;
				}
			),
			'dimensionUom'     => array(
				'default' => 'CM'
			),
			'dhl_product' => array(
				'rename' 	=> 'product_code',
				'default' 	=> 'PDO'
			),
			'duties' => array(
				'rename' 	=> 'incoterm',
				'default' 	=> ''
			),
			'total_value' => array(
				'error'  => __( 'Shipment "Value" is empty!', 'pr-shipping-dhl' ),
				'validate' => function( $value ) {
					if ( ! is_numeric( $value ) ) {
						throw new Exception( __( 'The order "value" must be a number', 'pr-shipping-dhl' ) );
					}
				},
				'sanitize' => function( $value ) use ($self) {

					return $self->float_round_sanitization( $value, 2 );
				}
			),
			'currency' => array(
				'error' => __( 'Shop "Currency" is empty!', 'pr-shipping-dhl' ),
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
				'error'  => __( 'Recipient is empty!', 'pr-shipping-dhl' ),
				'sanitize' => function( $name ) use ($self) {

					return $self->string_length_sanitization( $name, 30 );
				}
			),
			'phone'     => array(
				'default' => '',
				'sanitize' => function( $phone ) use ($self) {

					return $self->string_length_sanitization( $phone, 15 );
				}
			),
			'email'     => array(
				'error' => __( 'Shipping "Email" is empty!', 'pr-shipping-dhl' ),
			),
			'address_1' => array(
				'rename' => 'address1',
				'error' => __( 'Shipping "Address 1" is empty!', 'pr-shipping-dhl' ),
			),
			'address_2' => array(
				'rename' => 'address2',
				'default' => '',
			),
			'city'      => array(
				'error' => __( 'Shipping "City" is empty!', 'pr-shipping-dhl' ),
			),
			'postcode'  => array(
				'rename' => 'postCode',
				'error' => __( 'Shipping "Postcode" is empty!', 'pr-shipping-dhl' ),
			),
			'district' => array(
				'default' => ''
			),
			'state'     => array(
				'error' => __( 'Shipping "State" is empty!', 'pr-shipping-dhl' ),
			),
			'country'   => array(
				'error' => __( 'Shipping "Country" is empty!', 'pr-shipping-dhl' ),
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
			'dhl_contact_name'      => array(
				'rename' => 'name',
				'error'  => __( '"Account Name" in settings is empty.', 'pr-shipping-dhl' ),
				'sanitize' => function( $name ) use ($self) {

					return $self->string_length_sanitization( $name, 30 );
				}
			),
			'dhl_phone'     => array(
				'rename' => 'phone',
				'default' => '',
			),
			'dhl_email'     => array(
				'rename' => 'email',
				'default' => '',
			),
			'dhl_address_1' => array(
				'rename' => 'address1',
				'error' => __( 'Base "Address 1" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_address_2' => array(
				'rename' => 'address2',
				'default' => '',
			),
			'dhl_city'      => array(
				'rename' => 'city',
				'error' => __( 'Base "City" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_district'     => array(
				'rename' => 'district',
				'default' => '',
			),
			'dhl_postcode'  => array(
				'rename' => 'postCode',
				'error' => __( 'Base "Postcode" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_state'     => array(
				'rename' => 'state',
				'error'   => __( 'Base "State" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_country'   => array(
				'rename' => 'country',
				'error' => __( 'Base "Country" is empty!', 'pr-shipping-dhl' ),
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
	protected function get_content_item_info_schema()
	{
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'hs_code'     => array(
				'default'  => '',
				'validate' => function( $hs_code ) {
					$length = is_string( $hs_code ) ? strlen( $hs_code ) : 0;

					if (empty($length)) {
						return;
					}

					if ( $length < 4 || $length > 20 ) {
						throw new Exception(
							__( 'Item HS Code must be between 0 and 20 characters long', 'pr-shipping-dhl' )
						);
					}
				},
			),
			'item_description' => array(
				'rename' => 'description',
				'default' => '',
				'sanitize' => function( $description ) use ($self) {

					return $self->string_length_sanitization( $description, 33 );
				}
			),
			'product_id'  => array(
				'default' => '',
			),
			'sku'         => array(
				'default' => '',
			),
			'item_value'       => array(
				'rename' => 'value',
				'default' => 0,
				'sanitize' => function( $value ) use ($self) {

					return (string) $self->float_round_sanitization( $value, 2 );
				}
			),
			'origin'      => array(
				'default' => PR_DHL()->get_base_country(),
			),
			'qty'         => array(
				'default' => 1,
			),
			'item_weight'      => array(
				'rename' => 'weight',
				'default' => 1,
				'sanitize' => function ( $weight ) use ($self) {

					$weight = $self->maybe_convert_to_grams( $weight, $self->weightUom );
					$weight = ( $weight > 1 )? $weight : 1;
					return $weight;
				}
			),
			'dangerous_goods' => array(
				'default' => ''
			)
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order content item info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_delete_info_schema()
	{
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'message_type' 		=> array(
				'default' => 'DELETESHIPMENT',
			),
			'shipment_id'     	=> array(
				'default'  => '',
			)
		);
	}

	public function get_weight_uom(){
		return 'G';
	}

	public function get_dimension_uom(){
		return 'CM';
	}

	/**
	 * Converts a given weight into grams, if necessary.
	 *
	 * @since [*next-version*]
	 *
	 * @param float $weight The weight amount.
	 * @param string $uom The unit of measurement of the $weight parameter..
	 *
	 * @return float The potentially converted weight.
	 */
	protected function maybe_convert_to_grams( $weight, $uom ) {
		$weight = floatval( $weight );

		switch ( $uom ) {
			case 'kg':
				return $weight * 1000;

			case 'lb':
				return $weight / 2.2;

			case 'oz':
				return $weight / 35.274;
		}

		return $weight;
	}

	protected function absolute_float_sanitization( $float ){

		$abs = absint( $float ) < 1 ? 1 : floatval( $float);
		return $this->float_round_sanitization( $abs, 2 );

	}

	protected function float_round_sanitization( $float, $numcomma ) {

		$float = floatval( $float );

		return round( $float, $numcomma);
	}

	protected function string_length_sanitization( $string, $max ) {

		$max = intval( $max );

		if( strlen( $string ) <= $max ){

			return $string;
		}

		return substr( $string, 0, ( $max-1 ));
	}

}
