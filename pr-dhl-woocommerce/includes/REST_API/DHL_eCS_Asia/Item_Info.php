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
	 * The array of order recipient information.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $recipient;

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
		$this->item 		= $this->get_default_item_info();

		$this->update_item( $args );
		$this->update_total_weight_dimensions( $args );
		$this->update_item_consignee_address( $args );
		$this->update_item_return_address( $args );
		$this->update_item_shipment_pieces( $args );
		$this->update_item_shipment_contents( $args );
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

		/*
		$this->order 		= $args[ 'order_details' ][ 'order_id' ];
		$this->shipment 	= Args_Parser::parse_args( $shipping_info, $this->get_shipment_info_schema() );
		$this->recipient 	= Args_Parser::parse_args( $recipient_info, $this->get_recipient_info_schema() );
		$this->contents 	= array();

		$this->contents = array();
		foreach ( $items_info as $item_info ) {
			$this->contents[] = Args_Parser::parse_args( $item_info, $this->get_content_item_info_schema() );
		}
		*/
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
			"deliveryConfirmationNo" 	=> "",
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
	public function update_item( $args ){

		$item 		= $this->item;
		$settings 	= $args[ 'dhl_settings' ];
		$order_id 	= $args[ 'order_details' ][ 'order_id' ];
		$order 		= wc_get_order( $order_id );
		$shipmentid = "DHL". date("YmdHis") . sprintf('%07d', $order_id );
		$item["shipmentID"] 			= $shipmentid;
		$item["returnMode"] 			= "01"; /***** ** * hardcoded */
		$item["deliveryConfirmationNo"] = null;
		$item["packageDesc"] 			= $args['order_details']['description'];
		$item["totalWeightUOM"] 		= $this->get_weight_uom();
		$item["dimensionUOM"] 			= $this->get_dimension_uom();
		$item["productCode"] 			= $args['order_details']['dhl_product'];
		$item["currency"] 				= get_woocommerce_currency();

		if( isset( $args['order_details']['duties'] ) ){
			$item["incoterm"] = $args['order_details']['duties'];
		}
		
		$this->item = array_merge( $this->item, $item );
	}

	/**
	 * Update consignee address data in the item
	 * 
	 * @since [*next-version*]
	 * 
	 * @param Array $args
	 * 
	 */
	public function update_item_consignee_address( $args ){

		$item 		= $this->item;
		$order_id 	= $args[ 'order_details' ][ 'order_id' ];
		$order 		= wc_get_order( $order_id );
		$user 		= get_user_by( 'ID', $order->get_customer_id() );
		$id_number 	= $user->user_login . '-' . $order->get_customer_id();

		$item["consigneeAddress"] = array(
			"name" 		=> $args['shipping_address']['first_name'] . " " . $args['shipping_address']['last_name'],
			"address1" 	=> $args['shipping_address']['address_1'],
			"address2" 	=> $args['shipping_address']['address_2'],
			"city" 		=> $args['shipping_address']['city'],
			"state" 	=> $args['shipping_address']['state'],
			"district" 	=> $args['shipping_address']['state'],
			"country" 	=> $args['shipping_address']['country'],
			"postCode" 	=> $args['shipping_address']['postcode'],
			"phone"		=> $order->get_billing_phone(),
			"email" 	=> $order->get_billing_email(),
			//"idNumber" 	=> $id_number, /** hardcoded */
			//"idType" 	=> "4" /** hardcoded */
		);

		$this->item = array_merge( $this->item, $item );

	}

	/**
	 * Update consignee address data in the item
	 * 
	 * @since [*next-version*]
	 * 
	 * @param Array $args
	 * 
	 */
	public function update_item_return_address( $args ){

		$item 		= $this->item;
		$settings 	= $args[ 'dhl_settings' ];

		if( empty( $settings['dhl_address_1'] ) || 
			empty( $settings['dhl_address_2'] ) || 
			empty( $settings['dhl_city'] ) || 
			empty( $settings['dhl_state'] ) || 
			empty( $settings['dhl_district'] ) || 
			empty( $settings['dhl_country'] ) || 
			empty( $settings['dhl_postcode'] ) )
		{
			return;
		}
		
		$item["returnAddress"] = array(
			"name" 		=> $settings['dhl_contact_name'],
			"address1" 	=> $settings['dhl_address_1'],
			"address2" 	=> $settings['dhl_address_2'],
			"city" 		=> $settings['dhl_city'],
			"state" 	=> $settings['dhl_state'],
			"district" 	=> $settings['dhl_district'],
			"country" 	=> $settings['dhl_country'],
			"postCode" 	=> $settings['dhl_postcode'],
		);

		if( !empty( $settings['dhl_phone'] ) ){
			$item["returnAddress"]['phone'] = $settings['dhl_phone'];
		}

		if( !empty( $settings['dhl_email'] ) ){
			$item["returnAddress"]['email'] = $settings['dhl_email'];
		}

		$this->item = array_merge( $this->item, $item );

	}

	/**
	 * Update shipment pieces data in the item
	 * 
	 * @since [*next-version*]
	 * 
	 * @param Array $args
	 * 
	 */
	public function update_item_shipment_pieces( $args ){

		$item 			= $this->item;
		$order_id 		= $args[ 'order_details' ][ 'order_id' ];
		$order 			= wc_get_order( $order_id );
		
		$total_weight 	= 0;
		$total_height 	= 0;
		$total_width 	= 0;
		$total_length 	= 0;

		foreach( $order->get_items() as $item_id => $item_line ){

			$product_id 	= $item_line->get_product_id();
			$product 		= wc_get_product( $product_id );

			$weight 		= absint( $product->get_weight() ) < 1? 1 : absint( $product->get_weight() );
			$weight_uom 	= $this->weightUom;
			$weight_gr		= $this->maybe_convert_to_grams( $weight, $weight_uom );

			$height 		= absint( $product->get_height() ) < 1? 1 : absint( $product->get_height() );
			$width 			= absint( $product->get_width() ) < 1? 1 : absint( $product->get_width() );
			$length 		= absint( $product->get_length() ) < 1? 1 : absint( $product->get_length() );

			$total_weight 	+= $weight;
			$total_height 	+= $height;
			$total_width 	+= $width;
			$total_length 	+= $length;
			
			/*
			$item["shipmentPieces"][] = array(
				"pieceID" 			=> $product_id,
				"announcedWeight" 	=> array(
					"weight" 	=> $weight_gr,
					"unit" 		=> $this->get_weight_uom()
				),
				"billingReference1"	=> $order_id . "-" . $product_id,
				"billingReference2" => $order_id . "-" . $product_id,
				"pieceDescription"	=> $item_line->get_name()
			);
			*/

		}

		$item["shipmentPieces"][] = array(
			"pieceID" 			=> $order_id,
			"announcedWeight" 	=> array(
				"weight" 	=> $total_weight,
				"unit" 		=> $this->get_weight_uom()
			),
			"billingReference1"	=> $order_id,
			"billingReference2" => $order_id,
			"pieceDescription"	=> "Order no. " . $order_id
		);

		$item["totalWeight"] 	= $total_weight;
		$item["height"] 		= $total_height;
		$item["length"] 		= $total_length;
		$item["width"] 			= $total_width;

		$this->item = array_merge( $this->item, $item );

	}

	/**
	 * Update total weight and dimensions data in the item
	 * 
	 * @since [*next-version*]
	 * 
	 * @param Array $args
	 * 
	 */
	public function update_total_weight_dimensions( $args ){

		$item 			= $this->item;
		$order_id 		= $args[ 'order_details' ][ 'order_id' ];
		$order 			= wc_get_order( $order_id );
		
		$total_weight 	= 0;
		$total_height 	= 0;
		$total_width 	= 0;
		$total_length 	= 0;

		foreach( $order->get_items() as $item_id => $item_line ){

			$product_id 	= $item_line->get_product_id();
			$product 		= wc_get_product( $product_id );

			$quantity 		= $item_line->get_quantity();

			$weight 		= absint( $product->get_weight() ) < 1? 1 : absint( $product->get_weight() );
			$weight_uom 	= $this->weightUom;
			$weight_gr		= $this->maybe_convert_to_grams( $weight, $weight_uom );

			$height 		= absint( $product->get_height() ) < 1? 1 : absint( $product->get_height() );
			$width 			= absint( $product->get_width() ) < 1? 1 : absint( $product->get_width() );
			$length 		= absint( $product->get_length() ) < 1? 1 : absint( $product->get_length() );

			$total_weight 	+= ( $weight * $quantity );
			$total_height 	+= ( $height * $quantity );
			$total_width 	+= ( $width * $quantity );
			$total_length 	+= ( $length * $quantity );
		
		}

		$item["totalWeight"] 	= $total_weight;
		$item["height"] 		= $total_height;
		$item["length"] 		= $total_length;
		$item["width"] 			= $total_width;

		$this->item = array_merge( $this->item, $item );
	}

	/**
	 * Update shipment contents data in the item
	 * 
	 * @since [*next-version*]
	 * 
	 * @param Array $args
	 * 
	 */
	public function update_item_shipment_contents( $args ){
		
		$settings 	= $args['dhl_settings'];
		
		$item 		= $this->item;
		$order_id 	= $args[ 'order_details' ][ 'order_id' ];
		$order 		= wc_get_order( $order_id );
		
		$total_val 	= 0;

		foreach( $order->get_items() as $item_id => $item_line ){

			$product_id 	= $item_line->get_product_id();
			$product 		= wc_get_product( $product_id );
			$product_sku 	= empty( $product->get_sku() )? "product_id-".$product_id : $product->get_sku();
			$weight 		= $product->get_weight();
			$weight_uom 	= $this->weightUom;
			$weight_gr		= $this->maybe_convert_to_grams( $weight, $weight_uom );
			$weight_gr 		= ( $weight_gr > 1 )? $weight_gr : 1;

			$country_origin    = get_post_meta( $product_id, '_dhl_manufacture_country', true );
			$hs_code           = get_post_meta( $product_id, '_dhl_hs_code', true );
			$content_indicator = get_post_meta( $product_id, '_dhl_dangerous_goods', true );

			if( empty( $country_origin ) ){
				$country_origin = $settings['dhl_country'];
			}

			$total_val 		+= $product->get_price();

			$shipment_contents = array(
				"skuNumber" 			=> $product_sku,
				"description"			=> $item_line->get_name(),
				"descriptionImport" 	=> $item_line->get_name(),
				"descriptionExport" 	=> $item_line->get_name(),
				"itemValue" 			=> round( $product->get_price(), 2),
				"itemQuantity" 			=> $item_line->get_quantity(),
				"grossWeight" 			=> $weight_gr,
				"netWeight" 			=> $weight_gr,
				"weightUOM" 			=> $this->get_weight_uom(),
				"countryOfOrigin" 		=> $country_origin
			);

			if( !empty( $hs_code ) ){
				$shipment_contents['hsCode'] = $hs_code;
			}

			if( !empty( $content_indicator ) ){
				$shipment_contents['contentIndicator'] = $content_indicator;
			}

			$item["shipmentContents"][] = $shipment_contents;

		}

		$item["totalValue"] 	= round( $total_val, 2);

		$this->item = array_merge( $this->item, $item );

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
