<?php

namespace PR\DHL\REST_API\DHL_eCS_Asia;

use Exception;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\Utils\Args_Parser;
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
		$response_body = json_decode( $response->body );
		
		if ( $response->status === 200 ) {

			if( $this->check_status_code( $response_body ) == 200 ){

				return $this->get_label_content( $response_body );

			}
		}

		throw new Exception(
			sprintf(
				__( 'Failed to create label: %s', 'pr-shipping-dhl' ),
				$this->generate_error_details( $response_body )
			)
		);
	}

	public function check_status_code( $label_response ){

		if( !isset( $label_response->labelResponse->bd->responseStatus->code ) ){
			throw new Exception( __( 'Response status is not exist!', 'pr-shipping-dhl' ) );
		}

		return $label_response->labelResponse->bd->responseStatus->code;
	}

	public function get_label_content( $label_response ){

		if( !isset( $label_response->labelResponse->bd->labels ) ){
			throw new Exception( __( 'Label info is not exist!', 'pr-shipping-dhl' ) );
		}

		$labels_info 		= $label_response->labelResponse->bd->labels;

		foreach( $labels_info as $info ){

			if( !isset( $info->content ) ){
				throw new Exception( __( 'Label content is not exist!', 'pr-shipping-dhl' ) );
			}elseif( !isset( $info->shipmentID ) ){
				throw new Exception( __( 'Shipment ID is not exist!', 'pr-shipping-dhl' ) );
			}else{

				return $info;

			}
		}

		return false;
	}

	public function generate_error_details( $label_response ){

		$error_details 	= '';

		if( isset( $label_response->labelResponse->bd->labels ) ) {

			$labels = $label_response->labelResponse->bd->labels;

			foreach( $labels as $label ){

				if( !isset( $label->responseStatus->messageDetails ) ){
					continue;
				}

				foreach( $label->responseStatus->messageDetails as $message_detail ){

					if( isset( $message_detail->messageDetail ) ){

						$error_details .= '<li>' . $message_detail->messageDetail . '</li>';

					}

				}

			}
		}

		$error_exception = '';
		$response_status = $label_response->labelResponse->bd->responseStatus;

		if( isset( $response_status->message) ){
			$error_exception .= $response_status->message  . '<br /> ';
		}

		if( isset( $response_status->messageDetails[0]->messageDetail ) ){

			$message_details = '';
			foreach( $response_status->messageDetails as $message_detail ){

				if( isset( $message_detail->messageDetail ) ){
					$message_details .= $message_detail->messageDetail . '<br />';
				}

			}

			if( !empty( $message_detail ) ){

				$error_exception .= 'Error Details: ' . $message_details;

			}

		}

		if( !empty( $error_details ) ){

			$error_exception .= '<ul class = "wc_dhl_error">' . $error_details . '</ul>';

		}else{

			$error_exception .= __( 'Error message detail is not exist!', 'pr-shipping-dhl' );

		}

		return $error_exception;
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
     * Get message version.
     *
     * @return string The version of the message.
     */
    protected function get_shipment_id( $prefix, $id ){
        $prefix = trim( $prefix );
        return $prefix . $id . time();
    }

	/**
	 * Transforms an item info object into a request data array.
	 *
	 * @param Item_Info $item_info The item info object to transform.
	 *
	 * @return array The request data for the given item info object.
	 */
	protected function item_info_to_request_data( Item_Info $item_info ) {
		$shipmentid 		= $this->get_shipment_id( $item_info->shipment['prefix'], $item_info->shipment['order_id'] );

		$shipment_item 		= array(
			//'returnAddress' 	=> $return_address,
//			'consigneeAddress' 	=> $this->get_consignee( $item_info ),
			'consigneeAddress' 	=> $item_info->consignee,
			'shipmentID' 		=> $shipmentid,
			'packageDesc' 		=> $item_info->shipment['description'],
			'totalWeight' 		=> $item_info->shipment['weight'],
			'totalWeightUOM' 	=> $item_info->shipment['weightUom'],
			'dimensionUOM' 		=> $item_info->shipment['dimensionUom'],
			'productCode' 		=> $item_info->shipment['product_code'],
			'codValue'          => $item_info->shipment['codValue'],
			'insuranceValue' 	=> $item_info->shipment['insuranceValue'],
			'totalValue'		=> $item_info->shipment['items_value'],
			'currency' 			=> $item_info->shipment['currency'],
			'remarks'           => $item_info->shipment['remarks'],
			'shipmentPieces' 	=> array(
				array(
					'pieceID' 			=> $item_info->shipment['order_id'],
					'announcedWeight' 	=> array(
						'weight' 	=> $item_info->shipment['weight'],
						'unit' 		=> $item_info->shipment['weightUom']
					),
					'billingReference1'	=> (string)$item_info->shipment['order_id'],
					'pieceDescription'	=> $item_info->shipment['description'],
                    'codAmount'         => $item_info->shipment['codValue'],
				)
			),
		);

        // Set cross-border fields
		if( $item_info->isCrossBorder ) {
            $contents 			= $item_info->contents;
            $shipment_contents 	= array();

            foreach( $contents as $content ){

                $shipment_content = array(
                    'skuNumber' 			=> $content['sku'],
                    'description'			=> $content['description'],
                    'descriptionExport'		=> $content['descriptionExport'],
                    'itemValue' 			=> $content['value'],
                    'itemQuantity' 			=> $content['qty'],
    //				'netWeight' 			=> $content['weight'],
    //				'weightUOM' 			=> $item_info->shipment['weightUom'],
                    'countryOfOrigin' 		=> $content['origin'],
                    'hsCode'                => $content['hs_code'],
                    'contentIndicator'      => $content['dangerous_goods']
                );

                $shipment_contents[] = $shipment_content;
            }

            $shipment_item['shipmentContents'] = $shipment_contents;

            if( !empty( $item_info->shipment['incoterm'] ) ) {
                $shipment_item['incoterm'] = $item_info->shipment['incoterm'];
            }
		}
		
		if( !empty( $item_info->shipment['obox_service'] ) ) {
			$shipment_item['valueAddedServices']['valueAddedService'][] =
                array(
                    'vasCode' => $item_info->shipment['obox_service']
                );
		}

		$request_data = array(
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
					'pickupAddress' 	=> $item_info->shipper,
					'shipmentItems' 	=> array( $shipment_item ),
					'label' 			=> array(
						'format' 	=> $item_info->body[ 'label_format' ],
						'layout' 	=> $item_info->body[ 'label_layout' ],
						'pageSize' 	=> $item_info->body[ 'label_pagesize' ]
					)

				)
			)
		);

        return Args_Parser::unset_empty_values( $request_data );
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
