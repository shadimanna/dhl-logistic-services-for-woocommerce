<?php

namespace PR\DHL\REST_API\Parcel_DE;

use Exception;
use PR\DHL\REST_API\API_Client;

/**
 * The API client for DHL Paket.
 *
 * @since [*next-version*]
 */
class Client extends API_Client {

	/**
	 * Creates an item on the remote API.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The information of the item to be created.
	 *
	 * @throws Exception
	 */
	public function create_item( Item_Info $request_info ) {
		// Prepare the request route and data
		$route = $this->request_order_route();
		$data = $this->request_info_to_request_data( $request_info );

		$response = $this->post( $route, $data );

		// Return the response body on success
		if ( 200 === $response->status ) {
			return $response->body;
		}

		// Otherwise throw an exception using the response's error messages
		$message = ! empty( $response->body->messages )
			? implode( ', ', $response->body->messages )
			: strval( $response->body );

		throw new Exception(
			sprintf( __( 'API error: %s', 'dhl-for-woocommerce' ), $message )
		);
	}

	public function request_info_to_request_data( Item_Info $request_info ) {
		$request_data = array(
			'profile'   => '',
			'shipments' => array(
				'product'       => $request_info->shipment['product'],
				'refNo'         => $request_info->shipment['refNo'],
				'billingNumber' => $request_info->shipment['billingNumber'],
				'costCenter'    => $request_info->shipment['costCenter'],
				'shipper'       => array(
					'name1'         => $request_info->shipper['name1'],
					'phone'         => $request_info->shipper['phone'],
					'email'         => $request_info->shipper['email'],
					'addressStreet' => $request_info->shipper['addressStreet'],
					'addressHouse'  => $request_info->shipper['addressHouse'],
					'postalCode'    => $request_info->shipper['postalCode'],
					'city'          => $request_info->shipper['city'],
					'state'         => $request_info->shipper['state'],
					'country'       => $request_info->shipper['country']
				),
				'consignee'     => array(
					'ContactAddress' => array(
						'name1'         => $request_info->contactAddress['name1'],
						'addressStreet' => $request_info->contactAddress['addressStreet'],
						'addressHouse'  => $request_info->contactAddress['addressHouse'],
						'postalCode'    => $request_info->contactAddress['postalCode'],
						'city'          => $request_info->contactAddress['city'],
						'state'         => $request_info->contactAddress['state'],
						'country'       => $request_info->contactAddress['country'],
						'phone'         => $request_info->contactAddress['phone'],
						'email'         => $request_info->contactAddress['email']
					)
				),
				'details'       => array(
					'weight' => array(
						'uom'   => $request_info->shipment['uom'],
						'value' => $request_info->shipment['weight'],
					)
				),
				'services'      => $this->services_mappimng( $request_info )
			)
		);

		if ( $request_info->isCrossBorder ) {
			// Items description
			$item_description = '';
			foreach ( $request_info->items as $item ) {
				$item_description .= ! empty( $item_description ) ? ', ' : '';
				$item_description .= $item['itemDescription'];
			}

			$request_data['customs'] = array(
				'exportType'        => apply_filters( 'pr_shipping_dhl_paket_label_shipment_export_type', 'COMMERCIAL_GOODS' ),
				'exportDescription' => substr( $item_description, 0, 255 ),
				'items'             => $this->prepare_items( $request_info )
			);
		}

		return $request_data;
	}

	protected function services_mappimng( Item_Info $request_info ) {
		$services_map = array(
			'preferredNeighbour',
			'preferredLocation',
			'shippingConfirmation',
			'visualCheckOfAge',
			'namedPersonOnly',
			'identCheck',
			'preferredDay',
			'noNeighbourDelivery',
			'additionalInsurance',
			'bulkyGoods',
			'cashOnDelivery',
			'premium',
			'parcelOutletRouting',
			'postalDeliveryDutyPaid'
		);

		$services = array();
		foreach ( $services_map as $service ) {
			if ( 'no' == $request_info->services[ $service ] ) {
				continue;
			}

			switch ( $service ) {
				case 'shippingConfirmation':
					$services[ $service ]['email'] = $request_info->contactAddress['email'];
					break;
				case 'identCheck':
					// need to recheck
					$services[ $service ]['firstName'] = $request_info->contactAddress['name'];
					break;
				case 'additionalInsurance' :
					$services[ $service ]['currency'] = $request_info->shipment['currency'];
					$services[ $service ]['value']    = $request_info->shipment['value'];
					break;
				case 'parcelOutletRouting' :
					$services[ $service ] = $request_info->shipment['routing_email'];
					break;
				default :
					$services[ $service ] = $request_info->services[ $service ];
					break;
			}

			if ( ! empty( $request_info->shipment['cod_value'] ) ) {
				$services['cashOnDelivery'] = array(
					'amount' => array(
						'currency'    => $request_info->shipment['currency'],
						'value'       => $request_info->shipment['cod_value'],
						'bankAccount' => array()
					)
				);

				// Need to recheck
				/*$bank_data_map = array(
					'bank_holder' => 'accountOwner',
					'bank_name' => 'bankName',
					'bank_iban' => 'iban',
					'bank_ref' => 'note1',
					'bank_ref_2' => 'note2',
					'bank_bic' => 'bic'
				);

				foreach ($bank_data_map as $key => $value) {
					if( isset( $this->args['dhl_settings'][ $key ] ) ) {
						$bank_data[ $value ] = $this->args['dhl_settings'][ $key ];
					}
				}*/
			}
		}

		return $services;
	}

	protected function prepare_items( Item_Info $request_info ) {
		$items = array();
		foreach ( $request_info->items as $item ) {
			$items[] = array(
				'itemDescription' => $item['itemDescription'],
				'countryOfOrigin' => $item['countryOfOrigin'],
				'hsCode'          => $item['hsCode'],
				'itemValue'       => array(
					'currency' => $item['itemValue']['currency'],
					'value'    => $item['itemValue']['value']
				),
				'itemWeight'      => array(
					'uom'   => $item['itemWeight']['uom'],
					'value' => $item['itemWeight']['value']
				)
			);
		}

		return $items;
	}

	/**
	 * Prepares an API route.
	 *
	 * @return string
	 * @since [*next-version*]
	 *
	 */
	protected function request_order_route() {
		return 'v2/orders';
	}
}