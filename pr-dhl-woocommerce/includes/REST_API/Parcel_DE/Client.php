<?php

namespace PR\DHL\REST_API\Parcel_DE;

use Exception;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Response;

/**
 * The API client for DHL Paket.
 */
class Client extends API_Client {

	/**
	 * Creates multiple item on the remote API.
	 *
	 * @param  Item_Info[] $items_info set of Items.
	 *
	 * @return array.
	 * @throws Exception
	 * @since [*next-version*]
	 */
	public function create_items( array $items_info ) {
		// Prepare the request route and data
		$route = $this->request_order_route();
		$data  = $this->request_info_to_request_data( $items_info );

		$route    = $this->add_request_params( $route, $items_info[0] );
		$response = $this->post( $route, $data );

		// Return the response body on success
		if ( 200 === $response->status ) {
			return array( 'items' => $response->body->items );
		}

		if ( 207 === $response->status ) {
			$labels_data = array(
				'items' => array(),
			);

			foreach ( $response->body->items as $item ) {
				if ( 200 === $item->sstatus->statusCode ) {
					$labels_data['items'][] = $item;
				} else {
					$error_message           = $this->generate_error_message( $this->get_item_error_message( $item ) );
					$labels_data['errors'][] = array(
						'order_id' => '',
						'message'  => wp_kses_post(
							sprintf(
							// Translators: %s is replaced with the error message returned from the API.
								__( 'Error creating label: %s', 'dhl-for-woocommerce' ),
								$error_message
							)
						),
					);
				}
			}

			return $labels_data;
		}

		// Otherwise throw an exception using the response's error messages
		$message = $this->get_response_error_message( $response );

		throw new Exception(
			wp_kses_post(
				sprintf(
				// Translators: %s is replaced with the error message returned from the API.
					__( 'Error creating label: %s', 'dhl-for-woocommerce' ),
					$message
				)
			)
		);
	}

	/**
	 * Transforms an item info object into a request data array.
	 *
	 * @param  array<Item_Info> $items_info.
	 *
	 * @return array.
	 */
	public function request_info_to_request_data( array $items_info ) {
		$data = array(
			'profile'   => apply_filters( 'pr_shipping_dhl_paket_label_shipment_profile', 'STANDARD_GRUPPENPROFIL' ),
			'shipments' => array(),
		);

		foreach ( $items_info as $item_info ) {
			$shipment = array(
				'product'       => $item_info->shipment['product'],
				'refNo'         => apply_filters( 'pr_shipping_dhl_paket_label_ref_no_prefix', 'order_' ) . $item_info->shipment['refNo'],
				'billingNumber' => $item_info->shipment['billingNumber'],
				'costCenter'    => $item_info->shipment['costCenter'],
				'shipper'       => $this->get_shipper_address( $item_info ),
				'consignee'     => $this->get_consignee_address( $item_info ),
			);

			$services = $this->services_mappimng( $item_info );
			if ( ! empty( $services ) ) {
				$shipment['services'] = $services;
			}

			if ( $item_info->isCrossBorder ) {
				$shipment['customs'] = $this->get_customs( $item_info );
			}

			// Is Multiple package
			if ( isset( $item_info->args['order_details']['multi_packages_enabled'] ) && ( $item_info->args['order_details']['multi_packages_enabled'] == 'yes' ) ) {

				for ( $i = 0; $i < intval( $item_info->shipment['total_packages'] ); $i++ ) {
					$shipment['details'] = array(
						'weight' => array(
							'uom'   => 'kg' === $item_info->weightUom ? 'kg' : 'g', // its converted to grams in item_info.
							'value' => $item_info->args['order_details']['packages_weight'][ $i ],
						),
						'dim'    => array(
							'uom'    => $item_info->args['order_details']['dimUom'],
							'height' => $item_info->args['order_details']['packages_height'][ $i ],
							'length' => $item_info->args['order_details']['packages_length'][ $i ],
							'width'  => $item_info->args['order_details']['packages_width'][ $i ],
						),
					);

					$data['shipments'][] = $this->unset_empty_values( $shipment );
				}
			} else {
				$shipment['details'] = array(
					'weight' => array(
						'uom'   => 'kg' === $item_info->weightUom ? 'kg' : 'g', // its converted to grams in item_info.
						'value' => $item_info->shipment['weight'],
					),
				);

				$data['shipments'][] = $this->unset_empty_values( $shipment );
			}
		}

		return $data;
	}

	/**
	 * Shipment selected services mapping.
	 *
	 * @param  Item_Info $request_info  .
	 *
	 * @return array.
	 */
	protected function services_mappimng( Item_Info $request_info ) {
		$services = array();
		foreach ( $request_info->services as $key => $service ) {
			/**
			 * GoGreenService.
			 * We should send a value to the API, even when not selected to allow the merchant to turn this option off.
			 */
			if ( 'goGreenPlus' === $key && ! empty( $service ) ) {
				$services[ $key ] = 'yes' === $service;
				continue;
			}

			// If checkbox not checked.
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
				case 'additionalInsurance':
					$services[ $key ] = array(
						'currency' => $request_info->shipment['currency'],
						'value'    => $request_info->shipment['value'],
					);
					break;
				case 'parcelOutletRouting':
					$services[ $key ] = $request_info->shipment['routing_email'];
					break;
				case 'cashOnDelivery':
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

				case 'dhlRetoure':
					$services[ $key ] = array(
						'refNo'         => apply_filters( 'pr_shipping_dhl_paket_label_ref_no_prefix', 'order_' ) . $request_info->shipment['refNo'],
						'billingNumber' => $request_info->args['dhl_settings']['account_num'] . $request_info->dhl_return_product . $request_info->args['dhl_settings']['participation_return'],
						'returnAddress' => $this->get_return_address( $request_info ),
					);

					if ( isset( $request_info->services['goGreenPlus'] ) ) {
						$services[ $key ]['goGreenPlus'] = 'yes' === $request_info->services['goGreenPlus'];
					}
					break;
			}
		}

		return $services;
	}

	/**
	 * Prepare shipment items.
	 *
	 * @param  Item_Info $request_info.
	 *
	 * @return array.
	 */
	protected function prepare_items( Item_Info $request_info ) {
		$items = array();
		foreach ( $request_info->items as $item ) {
			$items[] = array(
				'itemDescription'  => $item['itemDescription'],
				'countryOfOrigin'  => $item['countryOfOrigin'],
				'hsCode'           => $item['hsCode'],
				'packagedQuantity' => $item['packagedQuantity'],
				'itemValue'        => array(
					'currency' => $item['itemValue']['currency'],
					'value'    => $item['itemValue']['amount'],
				),
				'itemWeight'       => array(
					'uom'   => 'kg' === $item['itemWeight']['uom'] ? 'kg' : 'g', // its converted to grams in item_info.
					'value' => $item['itemWeight']['value'],
				),
			);
		}

		return $items;
	}

	/**
	 * For international shipments, Get necessary information for customs about the exported goods.
	 *
	 * @param  Item_Info $request_info.
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

		$additional_fee = floatval( $request_info->args['order_details']['additional_fee'] );
		$shipping_fee   = floatval( $request_info->args['order_details']['shipping_fee'] );

		$customs = array(
			'invoiceNo'         => $request_info->args['order_details']['invoice_num'],
			'exportType'        => apply_filters( 'pr_shipping_dhl_paket_label_shipment_export_type', 'COMMERCIAL_GOODS' ),
			'exportDescription' => substr( $item_description, 0, 80 ),
			'items'             => $this->prepare_items( $request_info ),
			'postalCharges'     => array(
				'currency' => $request_info->args['order_details']['currency'],
				'value'    => $additional_fee + $shipping_fee,
			),
		);

		if ( ! empty( $request_info->shipment['mrn'] ) ) {
			$customs['MRN'] = $request_info->shipment['mrn'];
		}

		return $customs;
	}

	/**
	 * Consignee address information.
	 * Either a doorstep address (contact address) including contact information or a droppoint address.
	 * One of packstation (parcel locker), or post office (postfiliale/retail shop).
	 *
	 * @param  Item_Info $request_info.
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
			'name2',
			'addressStreet',
			'addressHouse',
			'additionalAddressInformation1',
			'postalCode',
			'city',
			'state',
			'country',
			'phone',
			'email',
		);

		return $this->get_address( $address_fields, $request_info->contactAddress );
	}

	/**
	 * Shipper address information.
	 *
	 * @param  Item_Info $request_info.
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
			'country',
			'shipperRef',
		);

		return $this->get_address( $address_fields, $request_info->shipper );
	}

	/**
	 * Return address information.
	 *
	 * @param  Item_Info $request_info.
	 *
	 * @return array.
	 */
	protected function get_return_address( Item_Info $request_info ) {
		$address_fields = array(
			'name1',
			'phone',
			'email',
			'addressStreet',
			'addressHouse',
			'postalCode',
			'city',
			'state',
			'country',
		);

		return $this->get_address( $address_fields, $request_info->returnAddress );
	}

	/**
	 * Get required address.
	 *
	 * @param  array $address_fields
	 * @param  array $address
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
	 * @param  array $array.
	 *
	 * @return array.
	 */
	protected function unset_empty_values( array $array ) {
		foreach ( $array as $k => $v ) {
			if ( is_array( $v ) ) {
				$array[ $k ] = $this->unset_empty_values( $v );
			}

			if ( empty( $v ) && ! is_numeric( $v ) ) {
				// Don't unset GoGreenPlus value if its false.
				if ( 'goGreenPlus' === $k ) {
					continue;
				}

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
	 * Add params to the API request.
	 *
	 * @return string.
	 */
	protected function add_request_params( $route, $item_info ) {
		// Print only if codeable.
		$route .= '?mustEncode=' . $item_info->shipment['mustEncode'];

		// Print only if codeable.
		$route .= '&printFormat=' . $item_info->shipment['printFormat'];

		return $route;
	}

	/**
	 * Get delete shipment rout, used for label deletion.
	 *
	 * @return string.
	 */
	protected function delete_shipment_route( $shipment_number ) {
		$profile = apply_filters( 'pr_shipping_dhl_paket_label_shipment_profile', 'STANDARD_GRUPPENPROFIL' );
		return 'v2/orders?profile=' . $profile . '&shipment=' . $shipment_number;
	}

	/**
	 * Get response error messages.
	 *
	 * @param  Response $response  .
	 *
	 * @return string.
	 */
	protected function get_response_error_message( Response $response ) {
		$multiple_errors_list = array();

		if ( ! is_array( $response->body->items ) ) {
			return $response->body->detail;
		}

		foreach ( $response->body->items as $item ) {
			$errors_list = $this->get_item_error_message( $item );
			foreach ( $errors_list as $key => $list ) {
				if ( ! isset( $multiple_errors_list[ $key ] ) ) {
					$multiple_errors_list[ $key ] = array();
				}

				if ( is_array( $list ) ) {
					$multiple_errors_list[ $key ] += $list;
				} else {
					$multiple_errors_list[ $key ][] = $list;
				}
			}
		}

		return $this->generate_error_message( $multiple_errors_list );
	}

	/**
	 * Get item erros.
	 *
	 * @param $item  .
	 *
	 * @return array.
	 */
	protected function get_item_error_message( $item ) {
		if ( isset( $item->message ) ) {
			return array( 'Error' => $item->message );
		}

		$multiple_errors_list = array();

		if ( isset( $item->sstatus ) && isset( $item->shipmentNo ) ) {
			$multiple_errors_list[ $item->sstatus->title ][] = $item->shipmentNo . $item->sstatus->detail;
		}

		foreach ( $item->validationMessages as $message ) {
			if ( ! isset( $multiple_errors_list[ $message->validationState ] ) ) {
				$multiple_errors_list[ $message->validationState ] = array();
			}

			$property = isset( $message->property ) ? '( ' . $message->property . ' ) : ' : '';
			$multiple_errors_list[ $message->validationState ][] = $property . $message->validationMessage;
		}

		return $multiple_errors_list;
	}

	protected function generate_error_message( $multiple_errors_list ) {
		if ( isset( $multiple_errors_list['Error'] ) ) {
			$errors = $multiple_errors_list['Error'];
			unset( $multiple_errors_list['Error'] );

			$multiple_errors_list = array( 'Error' => $errors ) + $multiple_errors_list;
		}

		$error_message = '<br>';

		if ( ! empty( $multiple_errors_list ) ) {
			foreach ( $multiple_errors_list as $key => $errors ) {
				$error_message .= '<strong class="wc_dhl_error">' . $key . ' : </strong>';
				$error_message .= '<ul class="wc_dhl_error">';
				foreach ( $errors as $error ) {
					$error_message .= '<li>' . esc_html( $error ) . '</li>';
				}
				$error_message .= '</ul>';
			}
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
			wp_kses_post(
				sprintf(
					// Translators: %s is replaced with the error message returned from the API.
					__( 'Error deleting label: %s', 'dhl-for-woocommerce' ),
					$message
				)
			)
		);
	}
}
