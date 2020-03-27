<?php

namespace PR\DHL\REST_API\DHL_eCS_Asia;

use Exception;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use stdClass;

/**
 * The API client for DHL eCS.
 *
 * @since [*next-version*]
 */
class Client extends API_Client {

	/**
	 * The pickup address data.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	protected $pickup_address;

	/**
	 * The shipper address data.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	protected $shipper_address;

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 *
	 * @param string $contact_name The contact name to use for creating orders.
	 */
	public function __construct( $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
		parent::__construct( $base_url, $driver, $auth );

	}

	/**
	 * Create shipping label
	 *
	 * @since [*next-version*]
	 *
	 * @param int $order_id The order id.
	 *
	 */
	public function create_label( Item_Info $item_info ){

		$route 	= $this->shipping_label_route();

		$data = $this->item_info_to_request_data( $item_info );
		
		$response = $this->post($route, $data);
		
		if ( $response->status === 200 ) {
			
			return $response->body;

		}

		throw new Exception(
			sprintf(
				__( 'Failed to create order: %s', 'pr-shipping-dhl' ),
				implode( ', ', $response->body->messages )
			)
		);
	}

	/**
	 * Transforms an item info object into a request data array.
	 *
	 * @param Item_Info $item_info The item info object to transform.
	 *
	 * @return array The request data for the given item info object.
	 */
	protected function item_info_to_request_data( Item_Info $item_info ) {

		$token 	= $this->auth->load_token();

		$pickup_address = $item_info->pickup;

		if( empty( $pickup_address['phone'] ) ){
			unset( $pickup_address['phone'] );
		}

		if( empty( $pickup_address['email'] ) ){
			unset( $pickup_address['email'] );
		}

		$shipper_address 	= $pickup_address;

		$contents 			= $item_info->contents;
		$shipment_contents 	= array();

		foreach( $contents as $content ){

			$shipment_content = array(
				"skuNumber" 			=> $content['sku'],
				"description"			=> $content['description'],
				"descriptionImport" 	=> $content['description'],
				"descriptionExport" 	=> $content['description'],
				"itemValue" 			=> round( $content['value'], 2 ),
				"itemQuantity" 			=> $content['qty'],
				"grossWeight" 			=> $content['weight'],
				"netWeight" 			=> $content['weight'],
				"weightUOM" 			=> $item_info->shipment['weightUom'],
				"countryOfOrigin" 		=> $content['origin']
			);

			if( !empty( $content['hs_code'] ) ){
				$shipment_content['hsCode'] = $content['hs_code'];
			}

			if( !empty( $content['dangerous_goods'] ) ){
				$shipment_content['contentIndicator'] = $content['dangerous_goods'];
			}

			$shipment_contents[] = $shipment_content;

		}

		$return_address 	= $pickup_address;
		$consignee 			= $item_info->consignee;

		if( $consignee['district'] == '' ){
			$consignee['district'] = $consignee['state'];
		}

		$shipmentid 		= "DHL". date("YmdHis") . sprintf('%07d', $item_info->order );

		$shipment_item 		= array(
			'returnAddress' 	=> $return_address,
			'consigneeAddress' 	=> $consignee,
			'shipmentID' 		=> $shipmentid,
			'returnMode' 		=> $item_info->shipment['return_mode'],
			'packageDesc' 		=> $item_info->shipment['description'],
			'totalWeight' 		=> $item_info->shipment['weight'],
			'totalWeightUOM' 	=> $item_info->shipment['weightUom'],
			'dimensionUOM' 		=> $item_info->shipment['dimensionUom'],
			"height" 			=> $item_info->shipment['height'],
			"length" 			=> $item_info->shipment['length'],
			"width" 			=> $item_info->shipment['width'],
			'productCode' 		=> $item_info->shipment['product_code'],
			'totalValue'		=> $item_info->shipment['total_value'],
			'currency' 			=> $item_info->shipment['currency'],
			"isMult"			=> $item_info->shipment['is_mult'],
			"deliveryOption"	=> $item_info->shipment['delivery_option'],
			'shipmentPieces' 	=> array(
				array(
					"pieceID" 			=> $item_info->order,
					"announcedWeight" 	=> array(
						"weight" 	=> $item_info->shipment['weight'],
						"unit" 		=> $item_info->shipment['weightUom']
					),
					"billingReference1"	=> $item_info->order,
					"billingReference2" => $item_info->order,
					"pieceDescription"	=> "Order no. " . $item_info->order
				)
			),
			'shipmentContents' 			=> $shipment_contents
		);

		if( !empty( $item_info->shipment['incoterm'] ) ){

			$shipment_item["incoterm"] = $item_info->shipment['incoterm'];

		}

		return array(
			'labelRequest' 	=> array(
				'hdr' 	=> array(
					'messageType' 		=> $item_info->header[ 'message_type' ],
					'messageDateTime' 	=> $item_info->header[ 'message_date_time' ],
					'messageVersion' 	=> $item_info->header[ 'message_version' ],
					'accessToken'		=> $token->token,
					'messageLanguage' 	=> $item_info->header[ 'message_language' ]
				),
				'bd' 	=> array(
					'pickupAccountId' 	=> $item_info->body[ 'pickup_id' ],
					'soldToAccountId'	=> $item_info->body[ 'soldto_id' ],
					'pickupAddress' 	=> $pickup_address,
					'shipperAddress' 	=> $shipper_address,
					'shipmentItems' 	=> array( $shipment_item ),
					'label' 			=> array(
						'format' 	=> $item_info->body[ 'label_format' ],
						'layout' 	=> $item_info->body[ 'label_layout' ],
						'pageSize' 	=> $item_info->body[ 'label_pagesize' ]
					)
					
				)
			)
		);
	}

	/**
	 * Prepares an API route with the customer namespace and EKP.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $route The route to prepare.
	 *
	 * @return string
	 */
	protected function shipping_label_route() {
		return 'rest/v2/Label';
	}

}
