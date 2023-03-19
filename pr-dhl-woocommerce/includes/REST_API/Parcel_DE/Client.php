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
	 * @param  Item_Info  $item_info  The information of the item to be created.
	 *
	 * @throws Exception
	 * @since [*next-version*]
	 *
	 */
	public function create_item( Item_Info $request_info ) {
		// Prepare the request route and data
		$route = $this->request_order_route();
		$data  = $this->request_info_to_request_data( $request_info );

		$response = $this->post( $route, $data );

		// Return the response body on success
		if ( 200 === $response->status ) {
			return $response->body;
		}

		// Otherwise throw an exception using the response's error messages
		$message = ! empty( $response->body->items[0]->message )
			? strval( $response->body->items[0]->message )
			: ( ! empty( $response->body->status->detail ) ? strval( $response->body->status->detail ) : $response->body->status->detail );

		throw new Exception(
			sprintf( __( 'API error: %s', 'dhl-for-woocommerce' ), $message )
		);
	}

	public function request_info_to_request_data( Item_Info $request_info ) {
		$shipment = array(
			'product'       => $request_info->shipment['product'],
			'refNo'         => apply_filters( 'pr_shipping_dhl_paket_label_ref_no',
				'order_' . $request_info->shipment['refNo'] ),
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
				'name1'         => $request_info->contactAddress['name1'],
				'addressStreet' => $request_info->contactAddress['addressStreet'],
				'addressHouse'  => $request_info->contactAddress['addressHouse'],
				'postalCode'    => $request_info->contactAddress['postalCode'],
				'city'          => $request_info->contactAddress['city'],
				'state'         => $request_info->contactAddress['state'],
				'country'       => $request_info->contactAddress['country'],
				'phone'         => $request_info->contactAddress['phone'],
				'email'         => $request_info->contactAddress['email']
			),
			'details'       => array(
				'weight' => array(
					'uom'   => $request_info->weightUom,
					'value' => $request_info->shipment['weight'],
				)
			),
		);

		$services = $this->services_mappimng( $request_info );
		if ( ! empty( $services ) ) {
			$shipment['services'] = $services;
		}

		if ( $request_info->isCrossBorder ) {
			// Items description
			$item_description = '';
			foreach ( $request_info->items as $item ) {
				$item_description .= ! empty( $item_description ) ? ', ' : '';
				$item_description .= $item['itemDescription'];
			}

			$shipment['customs'] = array(
				'invoiceNo' => $request_info->args['order_details']['invoice_num'],
				'exportType'        => apply_filters( 'pr_shipping_dhl_paket_label_shipment_export_type', 'COMMERCIAL_GOODS' ),
				'exportDescription' => substr( $item_description, 0, 255 ),
				'items'             => $this->prepare_items( $request_info ),
				'postalCharges'     => array(
					'currency' => $request_info->args['order_details']['currency'],
					'value' => $request_info->args['order_details']['total_value'],
				),
			);
		}

		return array(
			'profile'   => apply_filters( 'pr_shipping_dhl_paket_label_shipment_profile', 'STANDARD_GRUPPENPROFIL' ),
			'shipments' => array(
				$shipment
			)
		);
	}

	/**
	 * Shipment selected services mapping.
	 *
	 * @param  Item_Info  $request_info
	 *
	 * @return array
	 */
	protected function services_mappimng( Item_Info $request_info ) {
		$services = array();
		foreach ( $request_info->services as $key => $service ) {
			// If checkbox not checked
			if ( empty( $request_info->services[ $key ] ) || ( $request_info->services[ $key ] == 'no' ) ) {
				continue;
			}

			if ( 'yes' === $request_info->services[ $key ] ) {
				$services[ $key ] = true;
			} else {
				$services[ $key ] = $request_info->services[ $key ];
			}

			switch ( $key ) {
				case 'shippingConfirmation':
					$services[ $key ] = array(
						'email' => $request_info->contactAddress['email'],
					);
					break;
				case 'identCheck':
					$ident_check          = array(
						'firstName'   => $request_info->args['shipping_address']['first_name'] ?? '',
						'lastName'    => $request_info->args['shipping_address']['last_name'] ?? '',
						'dateOfBirth' => $request_info->args['order_details']['identcheck_dob'] ?? '',
						'minimumAge'  => $request_info->args['order_details']['identcheck_age'] ?? '',
					);
					$services[ $key ] = $ident_check;
					break;
				case 'additionalInsurance' :
					$services[ $key ] = array(
						'currency' => $request_info->shipment['currency'],
						'value'    => $request_info->shipment['value'],
					);
					break;
				case 'parcelOutletRouting' :
					$services[ $key ] = $request_info->shipment['routing_email'];
					break;
				case 'cashOnDelivery' :
					if ( ! empty( $request_info->shipment['cod_value'] ) ) {
						$bank_data_map = array(
							'bank_holder' => 'accountHolder',
							'bank_name'   => 'bankName',
							'bank_iban'   => 'iban',
							'bank_bic'    => 'bic'
						);

						$bank_data = array();
						foreach ( $bank_data_map as $bank_data_key => $bank_data_value ) {
							if ( isset( $request_info->args['dhl_settings'][ $bank_data_key ] ) ) {
								$bank_data[ $bank_data_value ] = $request_info->args['dhl_settings'][ $bank_data_key ];
							}
						}

						$services['cashOnDelivery'] = array(
							'amount'        => array(
								'currency' => $request_info->shipment['currency'],
								'value'    => $request_info->shipment['cod_value'],
							),
							'bankAccount'   => $bank_data,
							'transferNote1' => $request_info->args['dhl_settings']['bank_ref'],
							'transferNote2' => $request_info->args['dhl_settings']['bank_ref_2'],
						);

					}
					break;
			}

		}

		return $services;
	}

	/**
	 * Prepare shipment items.
	 *
	 * @param  Item_Info  $request_info
	 *
	 * @return array
	 */
	protected function prepare_items( Item_Info $request_info ) {
		$items = array();
		foreach ( $request_info->items as $item ) {
			$items[] = array(
				'itemDescription' => $item['itemDescription'],
				'countryOfOrigin' => $item['countryOfOrigin'],
				'hsCode'          => $item['hsCode'],
				'itemValue'       => array(
					'currency' => $item['itemValue']['currency'],
					'value'    => $item['itemValue']['amount']
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