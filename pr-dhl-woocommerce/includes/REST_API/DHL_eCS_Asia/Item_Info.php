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
	 * The array of order recipient information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $item;

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
	 * The array of return information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $return;

	/**
	 * The array of content item information sub-arrays.
	 *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $contents;

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
		$this->item 		= $this->get_default_item_info();

		$this->update_item();
		$this->update_item_consignee_address();
		$this->update_item_return_address();
		$this->update_item_shipment_contents();
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

		$this->order 			= $args[ 'order_details' ][ 'order_id' ];
		$this->shipment 		= Args_Parser::parse_args( $shipping_info, $this->get_base_info_schema() );
		$this->consignee 		= Args_Parser::parse_args( $recipient_info, $this->get_recipient_info_schema() );
		$this->return 			= Args_Parser::parse_args( $settings, $this->get_return_info_schema() );
		$this->contents 		= array();

		$this->contents = array();
		foreach ( $items_info as $item_info ) {
			$this->contents[] = Args_Parser::parse_args( $item_info, $this->get_content_item_info_schema() );
		}
	}

	/**
	 * Get Default value for the label info 
	 * 
	 * @return array
	 */
	protected function get_default_item_info() {

		return array(
			"consigneeAddress" 			=> array(),
			"shipmentID" 				=> "",
			"deliveryConfirmationNo" 	=> null,
			"packageDesc" 				=> "",
			"totalWeight" 				=> 0.0,
			"totalWeightUOM" 			=> "G",
			"dimensionUOM" 				=> "cm",
			"height" 					=> 0.0,
			"length" 					=> 0.0,
			"width" 					=> 0.0,
			"productCode" 				=> "PDO",
			"totalValue" 				=> "",
			"currency" 					=> "",
			"isMult"					=> "FALSE",
			"deliveryOption"			=> "P",
			"shipmentPieces" 			=> array(),
		);
	}

	/**
	 * Update item data
	 * 
	 * @since [*next-version*]
	 * 
	 * @param Array $args
	 * 
	 */
	public function update_item(){

		$item 				= $this->item;
		$shipment   		= $this->shipment;
		
		$shipmentid = "DHL". date("YmdHis") . sprintf('%07d', $this->order );
		$item["shipmentID"] 			= $shipmentid;
		$item["returnMode"] 			= $shipment['return_mode'];
		$item["packageDesc"] 			= $shipment['description'];
		$item["totalWeight"] 			= $shipment['weight'];
		$item["totalWeightUOM"] 		= $shipment['weightUom'];
		$item["dimensionUOM"] 			= $shipment['dimensionUom'];
		$item["productCode"] 			= $shipment['product_code'];
		$item['totalValue']				= $shipment['total_value'];
		$item["currency"] 				= $shipment['currency'];

		if( !empty( $shipment['incoterm'] ) ){
			$item["incoterm"] = $shipment['incoterm'];
		}

		$item["shipmentPieces"][] = array(
			"pieceID" 			=> $this->order,
			"announcedWeight" 	=> array(
				"weight" 	=> $shipment['weight'],
				"unit" 		=> $shipment['weightUom']
			),
			"billingReference1"	=> $this->order,
			"billingReference2" => $this->order,
			"pieceDescription"	=> "Order no. " . $this->order
		);
		
		$this->item = array_merge( $this->item, $item );
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for base item info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_base_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'order_id'      => array(
				'default' => '',
			),
			'return_mode'   => array(
				'default' => '01'
			),
			'description' 	=> array(
				'default' => ''
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
				'default' => "CM"
			),
			'dhl_product' => array(
				'rename' 	=> 'product_code',
				'default' 	=> 'PDO'
			),
			'duties' => array(
				'rename' 	=> 'incoterm',
				'default' 	=> ''
			),
			'height'     => array(
				'default' => 1,
				'sanitize' => function( $value ) use ( $self ) {
					return (string) $self->absolute_float_sanitization( $value, 2 );
				}
			),
			'length'     => array(
				'default' => 1,
				'sanitize' => function( $value ) use ( $self ) {
					return (string) $self->absolute_float_sanitization( $value, 2 );
				}
			),
			'width'     => array(
				'default' => 1,
				'sanitize' => function( $value ) use ( $self ) {
					return (string) $self->absolute_float_sanitization( $value, 2 );
				}
			),
			'total_value' => array(
				'default' => 1,
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
			'is_mult' => array(
				'default' => 'FALSE'
			),
			'delivery_option' => array(
				'default' => 'P'
			)
		);
	}

	/**
	 * Update consignee address data in the item
	 * 
	 * @since [*next-version*]
	 * 
	 * @param Array $args
	 * 
	 */
	public function update_item_consignee_address(){

		$item 		= $this->item;
		$consignee  = $this->consignee;

		$item["consigneeAddress"] = array(
			"name" 		=> $consignee['name'],
			"address1" 	=> $consignee['address_1'],
			"address2" 	=> $consignee['address_2'],
			"city" 		=> $consignee['city'],
			"state" 	=> $consignee['state'],
			"district" 	=> $consignee['state'],
			"country" 	=> $consignee['country'],
			"postCode" 	=> $consignee['postcode'],
			"phone"		=> $consignee['phone'],
			"email" 	=> $consignee['email'],
			//"idNumber" 	=> $id_number, /** hardcoded */
			//"idType" 	=> "4" /** hardcoded */
		);

		$this->item = array_merge( $this->item, $item );

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
				'default' => '',
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
				'default' => '',
				'error' => __( 'Shipping "Email" is empty!', 'pr-shipping-dhl' ),
			),
			'address_1' => array(
				'error' => __( 'Shipping "Address 1" is empty!', 'pr-shipping-dhl' ),
			),
			'address_2' => array(
				'default' => '',
			),
			'city'      => array(
				'error' => __( 'Shipping "City" is empty!', 'pr-shipping-dhl' ),
			),
			'postcode'  => array(
				'default' => '',
				'error' => __( 'Shipping "Postcode" is empty!', 'pr-shipping-dhl' ),
			),
			'state'     => array(
				'default' => '',
			),
			'country'   => array(
				'error' => __( 'Shipping "Country" is empty!', 'pr-shipping-dhl' ),
			),
		);
	}

	/**
	 * Update consignee address data in the item
	 * 
	 * @since [*next-version*]
	 * 
	 * @param Array $args
	 * 
	 */
	public function update_item_return_address(){

		$item 			= $this->item;
		$return_info 	= $this->return;
		
		$item["returnAddress"] = array(
			"name" 		=> $return_info['dhl_contact_name'],
			"address1" 	=> $return_info['dhl_address_1'],
			"address2" 	=> $return_info['dhl_address_2'],
			"city" 		=> $return_info['dhl_city'],
			"state" 	=> $return_info['dhl_state'],
			"district" 	=> $return_info['dhl_district'],
			"country" 	=> $return_info['dhl_country'],
			"postCode" 	=> $return_info['dhl_postcode'],
		);

		if( !empty( $return_info['dhl_phone'] ) ){
			$item["returnAddress"]['phone'] = $return_info['dhl_phone'];
		}

		if( !empty( $return_info['dhl_email'] ) ){
			$item["returnAddress"]['email'] = $return_info['dhl_email'];
		}

		$this->item = array_merge( $this->item, $item );

	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order return shipment info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_return_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'dhl_contact_name'      => array(
				'default' => '',
				'error'  => __( 'Base "Contact Name" is empty!', 'pr-shipping-dhl' ),
				'sanitize' => function( $name ) use ($self) {

					return $self->string_length_sanitization( $name, 30 );
				}
			),
			'dhl_phone'     => array(
				'default' => '',
				'sanitize' => function( $phone ) use ($self) {

					return $self->string_length_sanitization( $phone, 15 );
				}
			),
			'dhl_email'     => array(
				'default' => '',
				'error'  => __( 'Base "Email" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_address_1' => array(
				'error' => __( 'Base "Address 1" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_address_2' => array(
				'default' => '',
			),
			'dhl_city'      => array(
				'error' => __( 'Base "City" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_district'     => array(
				'default' => '',
				'error' => __( 'Base "District" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_postcode'  => array(
				'default' => '',
				'error' => __( 'Base "Postcode" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_state'     => array(
				'default' => '',
				'error'   => __( 'Base "State" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_country'   => array(
				'error' => __( 'Base "Country" is empty!', 'pr-shipping-dhl' ),
			),
		);
	}

	/**
	 * Update shipment contents data in the item
	 * 
	 * @since [*next-version*]
	 * 
	 * @param Array $args
	 * 
	 */
	public function update_item_shipment_contents(){
		
		$contents 	= $this->contents;
		$item 		= $this->item;

		foreach( $contents as $content ){

			$shipment_contents = array(
				"skuNumber" 			=> $content['sku'],
				"description"			=> $content['description'],
				"descriptionImport" 	=> $content['description'],
				"descriptionExport" 	=> $content['description'],
				"itemValue" 			=> round( $content['value'], 2 ),
				"itemQuantity" 			=> $content['qty'],
				"grossWeight" 			=> $content['weight'],
				"netWeight" 			=> $content['weight'],
				"weightUOM" 			=> $this->shipment['weightUom'],
				"countryOfOrigin" 		=> $content['origin']
			);

			if( !empty( $content['hs_code'] ) ){
				$shipment_contents['hsCode'] = $content['hs_code'];
			}

			if( !empty( $content['dangerous_goods'] ) ){
				$shipment_contents['contentIndicator'] = $content['dangerous_goods'];
			}

			$item["shipmentContents"][] = $shipment_contents;

		}

		$this->item = array_merge( $this->item, $item );

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
			'item_height' 	=> array(
				'default'  => 1,
				'sanitize' => function( $value ) use ( $self ) {
					return (string) $self->absolute_float_sanitization( $value, 2 );
				}
			),
			'item_width' 	=> array(
				'default'  => 1,
				'sanitize' => function( $value ) use ( $self ) {
					return (string) $self->absolute_float_sanitization( $value, 2 );
				}
			),
			'item_length' 	=> array(
				'default'  => 1,
				'sanitize' => function( $value ) use ( $self ) {
					return (string) $self->absolute_float_sanitization( $value, 2 );
				}
			),
			'dangerous_goods' => array(
				'default' => ''
			)
		);
	}

	public function get_weight_uom(){
		return "G";
	}

	public function get_dimension_uom(){
		return "CM";
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
