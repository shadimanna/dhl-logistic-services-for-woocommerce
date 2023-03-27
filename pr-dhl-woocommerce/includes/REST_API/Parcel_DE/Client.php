<?php

namespace PR\DHL\REST_API\Parcel_DE;

use Exception;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Response;

/**
 * The API client for DHL Paket.
 *
 * @since [*next-version*]
 */
class Client extends API_Client {

	/**
	 * Creates an item on the remote API.
	 *
	 * @param  Item_Info  $request_info.
	 *
	 * @return \stdClass.
	 * @throws Exception.
	 */
	public function create_item( Item_Info $request_info ) {
		// Prepare the request route and data
		$route = $this->request_order_route();
		$data  = $this->request_info_to_request_data( array( $request_info ) );

		$response = $this->post( $route, $data );

		// Return the response body on success
		if ( 200 === $response->status ) {
			return $response->body;
		}

		// Otherwise throw an exception using the response's error messages
		$message = $this->get_response_error_message( $response );

		throw new Exception(
			sprintf( __( 'API errors: %s', 'dhl-for-woocommerce' ), $message )
		);
	}

	/**
	 * Creates multiple item on the remote API.
	 *
	 * @param  Item_Info[]  $items_info set of Items.
	 *
	 * @return \stdClass|string
	 * @throws Exception
	 * @since [*next-version*]
	 */
	public function create_items( array $items_info ) {
		// Prepare the request route and data
		$route = $this->request_order_route();
		$data  = $this->request_info_to_request_data( $items_info );

		$response = $this->post( $route, $data );

		// Return the response body on success
		if ( 200 === $response->status || 207 === $response->status ) {
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

	/**
	 * Transforms an item info object into a request data array.
	 *
	 * @param  Item_Info  $request_info.
	 *
	 * @return array.
	 */
	public function request_info_to_request_data( Item_Info $request_info ) {
		$shipment = array(
			'product'       => $request_info->shipment['product'],
			'refNo'         => apply_filters( 'pr_shipping_dhl_paket_label_ref_no_prefix', 'order_' ) . $request_info->shipment['refNo'],
			'billingNumber' => $request_info->shipment['billingNumber'],
			'costCenter'    => $request_info->shipment['costCenter'],
			'shipper'       => $this->get_shipper_address( $request_info ),
			'consignee'     => $this->get_consignee_address( $request_info ),
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
			$shipment['customs'] = $this->get_customs( $request_info );
		}

		return array(
			'profile'   => apply_filters( 'pr_shipping_dhl_paket_label_shipment_profile', 'STANDARD_GRUPPENPROFIL' ),
			'shipments' => array(
				$this->unset_empty_values( $shipment )
			)
		);
	}

	/**
	 * Shipment selected services mapping.
	 *
	 * @param  Item_Info  $request_info  .
	 *
	 * @return array.
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
					$ident_check      = array(
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
							'bank_bic'    => 'bic',
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
	 * @param  Item_Info  $request_info.
	 *
	 * @return array.
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
					'value' => $item['itemWeight']['value'],
				)
			);
		}

		return $items;
	}

	/**
	 * For international shipments, Get necessary information for customs about the exported goods.
	 *
	 * @param  Item_Info  $request_info.
	 *
	 * @return array.
	 */
	protected function get_customs( Item_Info $request_info ) {
		// Items description
		$item_description = '';
		foreach ( $request_info->items as $item ) {
			$item_description .= ! empty( $item_description ) ? ', ' : '';
			$item_description .= $item['itemDescription'];
		}

		return array(
			'invoiceNo'         => $request_info->args['order_details']['invoice_num'],
			'exportType'        => apply_filters( 'pr_shipping_dhl_paket_label_shipment_export_type', 'COMMERCIAL_GOODS' ),
			'exportDescription' => substr( $item_description, 0, 255 ),
			'items'             => $this->prepare_items( $request_info ),
			'postalCharges'     => array(
				'currency' => $request_info->args['order_details']['currency'],
				'value'    => $request_info->args['order_details']['total_value'],
			),
		);
	}

	/**
	 * Consignee address information.
	 * Either a doorstep address (contact address) including contact information or a droppoint address.
	 * One of packstation (parcel locker), or post office (postfiliale/retail shop).
	 *
	 * @param  Item_Info  $request_info.
	 *
	 * @return array.
	 */
	protected function get_consignee_address( Item_Info $request_info ) {
		if ( $request_info->pos_rs || $request_info->pos_po ) {
			$address_fields = array( 'name', 'postNumber', 'retailID', 'postalCode', 'city', 'country' );

			return $this->get_address( $address_fields, $request_info->postOfficeAddress );
		}

		if ( $request_info->pos_ps ) {
			$address_fields = array( 'name', 'postNumber', 'lockerID', 'postalCode', 'city', 'country' );

			return $this->get_address( $address_fields, $request_info->packStationAddress );
		}


		// Normal shipping address.
		$address_fields = array(
			'name1',
			'addressStreet',
			'addressHouse',
			'postalCode',
			'city',
			'state',
			'country',
			'phone',
			'email'
		);

		return $this->get_address( $address_fields, $request_info->contactAddress );
	}

	/**
	 * Shipper address information.
	 *
	 * @param  Item_Info  $request_info.
	 *
	 * @return array.
	 */
	protected function get_shipper_address( Item_Info $request_info ) {
		$address_fields = array(
			'name1',
			'phone',
			'email',
			'addressStreet',
			'addressHouse',
			'postalCode',
			'city',
			'state',
			'country'
		);

		return $this->get_address( $address_fields, $request_info->shipper );
	}

	/**
	 * Get required address.
	 *
	 * @param  array  $address_fields
	 * @param  array  $address
	 *
	 * @return array
	 */
	protected function get_address( array $address_fields, array $address ) {
		$modified_address = array();

		// Set only nonempty fields.
		foreach ( $address_fields as $address_field ) {
			if ( isset( $address[ $address_field ] ) && '' !== $address[ $address_field ] ) {
				$modified_address[ $address_field ] = $address[ $address_field ];
			}
		}

		return $modified_address;
	}

	/**
	 * Unset/remove any items that are empty strings.
	 *
	 * @param  array  $array.
	 *
	 * @return array.
	 */
	protected function unset_empty_values( array $array ) {
		foreach ( $array as $k => $v ) {
			if ( is_array( $v ) ) {
				$array[ $k ] = $this->unset_empty_values( $v );
			}

			if ( empty( $v ) ) {
				unset( $array[ $k ] );
			}
		}

		return $array;
	}

	/**
	 * Get order rout, used for label creation.
	 *
	 * @return string.
	 */
	protected function request_order_route() {
		return 'v2/orders';
	}

	/**
	 * Get delete shipment rout, used for label deletion.
	 *
	 * @return string.
	 */
	protected function delete_shipment_route( $shipment_number ) {
		$profile = apply_filters( 'pr_shipping_dhl_paket_label_shipment_profile', 'STANDARD_GRUPPENPROFIL' );
		return 'v2/orders?profile='. $profile .'&shipment=' . $shipment_number;
	}

	/**
	 * Get response error messages.
	 *
	 * @param  Response  $response.
	 *
	 * @return string.
	 */
	protected function get_response_error_message( Response $response ) {
		if ( empty( $response->body->items[0]->validationMessages ) ) {
			return $response->body->status->detail ?? $response->body->detail;
		}

		$error_message = '';
		foreach ( $response->body->items[0]->validationMessages as $message ) {
			$error_message .= '<br><br><strong>' . $message->validationState . '</strong>' . ': ' . $message->validationMessage;
		}

		return $error_message;
	}

	/**
	 * Deletes an item from the remote API.
	 *
	 * @param int $shipment_number The Shipment number of the item to delete.
	 *
	 * @return \stdClass The response.
	 *
	 * @throws Exception.
	 */
	public function delete_item( $shipment_number ) {
		// Compute the route to the API endpoint
		$route = $this->delete_shipment_route( $shipment_number );

		// Send the DELETE request
		$response = $this->delete( $route );

		// Return the response body on success
		if ( $response->status === 200 ) {
			return $response->body;
		}

		// Otherwise throw an exception using the response's error messages
		$message = $this->get_response_error_message( $response );

		throw new Exception(
			sprintf( __( 'API errors: %s', 'dhl-for-woocommerce' ), $message )
		);
	}
}