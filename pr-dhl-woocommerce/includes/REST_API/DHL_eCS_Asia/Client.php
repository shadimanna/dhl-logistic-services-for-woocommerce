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
	 * The DHL Pickup Account ID.
	 *
	 */
	protected $pickup_id;

	/**
	 * The DHL SoldTo Account ID
	 *
	 */
	protected $soldto_id;

	/**
	 * The language of the message
	 *
	 */
	protected $language = 'en';

	/**
	 * The version of the message
	 *
	 */
	protected $version = '1.4';

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 *
	 * @param string $contact_name The contact name to use for creating orders.
	 */
	public function __construct( $pickup_id, $soldto_id, $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
		parent::__construct( $base_url, $driver, $auth );

		$this->pickup_id = $pickup_id;
		$this->soldto_id = $soldto_id;
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
				__( 'Failed to create label: %s', 'pr-shipping-dhl' ),
				implode( ', ', $response->body->messages )
			)
		);
	}

	/**
	 * Get message type.
	 *
	 * @param string $type The type of the message.
	 * 
	 * @return string The type of the message.
	 */
	protected function get_type( $type = 'create' ){

		if( $type == 'delete' ) {
			return 'DELETESHIPMENT';
		}elseif( $type == 'create' ) {
			return 'LABEL';
		}
	}

	/**
	 * Get date time.
	 *
	 * @return string The date and time of the message.
	 */
	protected function get_datetime(){
		return date( 'c', time() );
	}

	/**
	 * Get message language.
	 *
	 * @return string The language of the message.
	 */
	protected function get_language(){
		return $this->language;
	}

	/**
	 * Get message version.
	 *
	 * @return string The version of the message.
	 */
	protected function get_version(){
		return $this->version;
	}

	/**
	 * Transforms an item info object into a request data array.
	 *
	 * @param Item_Info $item_info The item info object to transform.
	 *
	 * @return array The request data for the given item info object.
	 */
	protected function item_info_to_request_data( Item_Info $item_info ) {

		$shipper_address = $item_info->shipper;

		if( empty( $shipper_address['phone'] ) ){
			unset( $shipper_address['phone'] );
		}

		if( empty( $shipper_address['email'] ) ){
			unset( $shipper_address['email'] );
		}

		//$pickup_address 	= $shipper_address;

		$contents 			= $item_info->contents;
		$shipment_contents 	= array();

		foreach( $contents as $content ){

			$shipment_content = array(
				'skuNumber' 			=> $content['sku'],
				'description'			=> $content['description'],
				'descriptionExport'		=> $content['description'],
				'itemValue' 			=> $content['value'],
				'itemQuantity' 			=> $content['qty'],
//				'netWeight' 			=> $content['weight'],
//				'weightUOM' 			=> $item_info->shipment['weightUom'],
				'countryOfOrigin' 		=> $content['origin']
			);

			if( !empty( $content['hs_code'] ) ){
				$shipment_content['hsCode'] = $content['hs_code'];
			}

			if( !empty( $content['dangerous_goods'] ) ){
				$shipment_content['contentIndicator'] = $content['dangerous_goods'];
			}

			$shipment_contents[] = $shipment_content;

		}

		$shipmentid 		= $item_info->shipment['prefix'] . sprintf('%07d', $item_info->shipment['order_id'] );

		$shipment_item 		= array(
			//'returnAddress' 	=> $return_address,
			'consigneeAddress' 	=> $this->get_consignee( $item_info ),
			'shipmentID' 		=> $shipmentid,
			'packageDesc' 		=> $item_info->shipment['description'],
			'totalWeight' 		=> $item_info->shipment['weight'],
			'totalWeightUOM' 	=> $item_info->shipment['weightUom'],
			'dimensionUOM' 		=> $item_info->shipment['dimensionUom'],
			'productCode' 		=> $item_info->shipment['product_code'],
			'totalValue'		=> $item_info->shipment['items_value'],
			'currency' 			=> $item_info->shipment['currency'],
			'shipmentPieces' 	=> array(
				array(
					'pieceID' 			=> $item_info->shipment['order_id'],
					'announcedWeight' 	=> array(
						'weight' 	=> $item_info->shipment['weight'],
						'unit' 		=> $item_info->shipment['weightUom']
					),
					'billingReference1'	=> $item_info->shipment['order_id'],
					'pieceDescription'	=> $item_info->shipment['description']
				)
			),
			'shipmentContents' 			=> $shipment_contents
		);

		if( !empty( $item_info->shipment['incoterm'] ) ){

			$shipment_item['incoterm'] = $item_info->shipment['incoterm'];

		}

		return array(
			'labelRequest' 	=> array(
				'hdr' 	=> array(
					'messageType' 		=> $this->get_type(),
					'messageDateTime' 	=> $this->get_datetime(),
					'messageVersion' 	=> $this->get_version(),
					//'accessToken'		=> $token->token,
					'messageLanguage' 	=> $this->get_language()
				),
				'bd' 	=> array(
					'pickupAccountId' 	=> $this->pickup_id,
					'soldToAccountId'	=> $this->soldto_id,
					//'pickupAddress' 	=> $pickup_address,
//					'shipperAddress' 	=> $shipper_address,
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

	protected function get_consignee( Item_Info $item_info ) {
        $consignee 			= $item_info->consignee;

        if( empty( $consignee['district'] ) && ! empty( $consignee['state'] )) {
            $consignee['district'] = $consignee['state'];
        }

        foreach ( $consignee as $consignee_key => $consignee_val ) {
            // If the field is empty do not pass it
            if( empty( $consignee_val ) ){
                unset( $consignee[ $consignee_key ] );
            }
        }

        return $consignee;
    }
	/**
	 * Deletes an item from the remote API.
	 *
	 * @since [*next-version*]
	 *
	 * @param int $shipment_id The ID of the shipment to delete.
	 *
	 * @return stdClass The response.
	 *
	 * @throws Exception
	 */
	public function delete_label( $shipment_id ) {

		$route 	= $this->delete_label_route();

		$data 		= array(
			'deleteShipmentReq' 	=> array(
				'hdr' 	=> array(
					'messageType' 		=> $this->get_type( 'delete' ),
					'messageDateTime' 	=> $this->get_datetime(),
					'messageVersion' 	=> $this->get_version(),
					'messageLanguage' 	=> $this->get_language()
				),
				'bd' 	=> array(
					'pickupAccountId' 	=> $this->pickup_id,
					'soldToAccountId'	=> $this->soldto_id,
					'shipmentItems' 	=> array(
						array(
							'shipmentID' 		=> $shipment_id,
						)
					 ),
				)
			)
		);

		$response 	= $this->post($route, $data);
		
		if ( $response->status === 200 ) {
			
			return $response->body;

		}

		throw new Exception(
			sprintf(
				__( 'Failed to delete label: %s', 'pr-shipping-dhl' ),
				implode( ', ', $response->body->messages )
			)
		);
	}

	/**
	 * Prepares an API route with the customer namespace and EKP.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 */
	protected function shipping_label_route() {
		return 'rest/v2/Label';
	}

	/**
	 * Prepares an API route for deleting label.
	 *
	 * @since [*next-version*]
	 *
	 * @return string
	 */
	protected function delete_label_route() {
		return $this->shipping_label_route(). '/Delete';
	}

}
