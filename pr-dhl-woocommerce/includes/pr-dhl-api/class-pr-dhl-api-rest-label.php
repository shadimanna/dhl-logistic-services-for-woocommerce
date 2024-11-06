<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_REST_Label extends PR_DHL_API_REST implements PR_DHL_API_Label {

	private $dhl_label_format = 'PDF';
	private $dhl_label_size   = '4x6'; // must be lowercase 'x'
	private $dhl_label_page   = 'A4';
	private $dhl_label_layout = '1x1';
	// const PR_DHL_LABEL_SIZE =
	// const PR_DHL_PAGE_SIZE =
	// const PR_DHL_LAYOUT =
	const PR_DHL_AUTO_CLOSE = '1';

	private $args = array();

	public function __construct() {
		try {

			parent::__construct();
			// Set Endpoint
			$this->set_endpoint( '/shipping/v1/label' );

		} catch ( Exception $e ) {
			throw $e;
		}
	}

	public function get_dhl_label( $args ) {
		$this->set_arguments( $args );
		$this->set_query_string();

		$response_body = $this->post_request( $args['dhl_settings']['dhl_api_key'], $args['dhl_settings']['dhl_api_secret'] );

		// This will work on one order but NOT on bulk!
		$label_response = $response_body->shipments[0]->packages[0]->responseDetails;
		$package_id     = $label_response->labelDetails[0]->packageId;

		$label_tracking_info = $this->save_label_file( $package_id, $label_response->labelDetails[0]->format, $label_response->labelDetails[0]->labelData );

		$label_tracking_info['tracking_number'] = $package_id;
		$label_tracking_info['tracking_status'] = isset( $label_response->trackingNumberStatus ) ? $label_response->trackingNumberStatus : '';

		return $label_tracking_info;
	}

	public function delete_dhl_label( $args ) {
		$upload_path = wp_upload_dir();
		$label_path  = str_replace( $upload_path['url'], $upload_path['path'], $args['label_url'] );

		if ( file_exists( $label_path ) ) {
			if ( ! is_writable( $label_path ) ) {
				throw new Exception( esc_html__( 'DHL Label file is not writable!', 'dhl-for-woocommerce' ) );
			}
			wp_delete_file( $label_path );
		} else {
			throw new Exception( esc_html__( 'DHL Label could not be deleted!', 'dhl-for-woocommerce' ) );
		}
	}

	public function dhl_test_connection( $client_id, $client_secret ) {
		return $this->get_access_token( $client_id, $client_secret );
	}

	public function dhl_validate_field( $key, $value ) {
		$this->validate_field( $key, $value );
	}

	protected function validate_field( $key, $value ) {

		try {

			switch ( $key ) {
				case 'weight':
					$this->validate( $value );
					break;
				case 'hs_code':
					$this->validate( $value, 'string', 4, 20 );
					break;
				default:
					parent::validate_field( $key, $value );
					break;
			}
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	protected function save_label_file( $package_id, $format, $label_data ) {
		$label_name = 'dhl-label-' . $package_id . '.' . $format;
		$label_path = PR_DHL()->get_dhl_label_folder_dir() . $label_name;
		$label_url  = PR_DHL()->get_dhl_label_folder_url() . $label_name;

		if ( validate_file( $label_path ) > 0 ) {
			throw new Exception( esc_html__( 'Invalid file path!', 'dhl-for-woocommerce' ) );
		}

		$label_data_decoded = base64_decode( $label_data );
		$file_ret           = file_put_contents( $label_path, $label_data_decoded );
		// global $wp_filesystem;

		// // Initialize WP_Filesystem
		// if ( ! function_exists( 'WP_Filesystem' ) ) {
		// require_once ABSPATH . 'wp-admin/includes/file.php';
		// }

		// WP_Filesystem();

		// // Check if WP_Filesystem object is properly initialized
		// if ( empty( $wp_filesystem ) ) {
		// return false;
		// }

		// $file_ret = $wp_filesystem->put_contents( $label_path, $label_data_decoded, FS_CHMOD_FILE );

		if ( empty( $file_ret ) ) {
			throw new Exception( esc_html__( 'DHL Label file cannot be saved!', 'dhl-for-woocommerce' ) );
		}

		return array(
			'label_url'  => $label_url,
			'label_path' => $label_path,
		);
	}

	protected function set_arguments( $args ) {
		// Validate set args

		if ( empty( $args['dhl_settings']['dhl_api_key'] ) ) {
			throw new Exception( esc_html__( 'Please, provide the username in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['dhl_api_secret'] ) ) {
			throw new Exception( esc_html__( 'Please, provide the password for the username in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		// Validate order details
		if ( empty( $args['dhl_settings']['pickup'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a pickup account in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['distribution'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a distribution center in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( ! empty( $args['dhl_settings']['label_format'] ) ) {
			$this->dhl_label_format = $args['dhl_settings']['label_format'];
		}

		if ( ! empty( $args['dhl_settings']['label_size'] ) ) {
			$this->dhl_label_size = $args['dhl_settings']['label_size'];
		}

		if ( ! empty( $args['dhl_settings']['label_page'] ) ) {
			$this->dhl_label_page = $args['dhl_settings']['label_page'];

			if ( $this->dhl_label_page == 'A4' ) {
				$this->dhl_label_layout = '4x1';
			} else {
				$this->dhl_label_layout = '1x1';
			}
		}

		if ( empty( $args['order_details']['dhl_product'] ) ) {
			throw new Exception( esc_html__( 'DHL "Product" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['order_details']['order_id'] ) ) {
			throw new Exception( esc_html__( 'Shop "Order ID" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['order_details']['weightUom'] ) ) {
			throw new Exception( esc_html__( 'Shop "Weight Units of Measure" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['order_details']['weight'] ) ) {
			throw new Exception( esc_html__( 'Order "Weight" is empty!', 'dhl-for-woocommerce' ) );
		}

		// Validate weight
		try {
			$this->validate_field( 'weight', $args['order_details']['weight'] );
		} catch ( Exception $e ) {
			throw new Exception( 'Weight - ' . esc_html( $e->getMessage() ) );
		}

		// if ( empty( $args['order_details']['duties'] )) {
		// throw new Exception( esc_html__( 'DHL "Duties" is empty!', 'dhl-for-woocommerce' ) );
		// }

		if ( empty( $args['order_details']['currency'] ) ) {
			throw new Exception( esc_html__( 'Shop "Currency" is empty!', 'dhl-for-woocommerce' ) );
		}

		// Validate shipping address
		if ( empty( $args['shipping_address']['address_1'] ) ) {
			throw new Exception( esc_html__( 'Shipping "Address 1" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['shipping_address']['city'] ) ) {
			throw new Exception( esc_html__( 'Shipping "City" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['shipping_address']['country'] ) ) {
			throw new Exception( esc_html__( 'Shipping "Country" is empty!', 'dhl-for-woocommerce' ) );
		}

		// Add default values for required fields that might not be passed e.g. phone
		$default_args = array(
			'shipping_address' =>
										array(
											'name'      => '',
											'company'   => '',
											'address_2' => '',
											'email'     => '',
											// 'idNumber' => '',
											// 'idType' => '',
											'postcode'  => '',
											'state'     => '',
											'phone'     => ' ',
										),
			'order_details'    =>
				array(
					'cod_value'       => 0,
					'dangerous_goods' => '',
				),
		);

		$args['shipping_address'] = wp_parse_args( $args['shipping_address'], $default_args['shipping_address'] );
		$args['order_details']    = wp_parse_args( $args['order_details'], $default_args['order_details'] );

		$default_args_item = array(
			'item_description' => '',
			'sku'              => '',
			'item_value'       => 0,
			'country_origin'   => '',
			'hs_code'          => '',
			'qty'              => 1,
		);

		foreach ( $args['items'] as $key => $item ) {

			if ( ! empty( $item['hs_code'] ) ) {
				try {
					$this->validate_field( 'hs_code', $item['hs_code'] );
				} catch ( Exception $e ) {
					throw new Exception( 'HS Code - ' . esc_html( $e->getMessage() ) );
				}
			}

			$args['items'][ $key ] = wp_parse_args( $item, $default_args_item );
		}

		$this->args = $args;
	}

	protected function set_query_string() {
		$dhl_label_query_string =
			array(
				'format'    => $this->dhl_label_format,
				'labelSize' => $this->dhl_label_size,
				'pageSize'  => $this->dhl_label_page,
				'layout'    => $this->dhl_label_layout,
				'autoClose' => self::PR_DHL_AUTO_CLOSE,
			);

		$this->query_string = http_build_query( $dhl_label_query_string );
	}

	protected function set_message() {

		if ( ! empty( $this->args ) ) {

			$package_id = '';
			if ( ! empty( $this->args['order_details']['prefix'] ) ) {
				$package_id = $this->args['order_details']['prefix'];
			}
			$package_id .= $this->args['order_details']['order_id'] . time();
			// Package id must be max 30
			$package_id = substr( $package_id, 0, 30 );

			$package_desc = $package_id;
			if ( ! empty( $this->args['order_details']['description'] ) ) {
				$package_desc = mb_substr( $this->args['order_details']['description'], 0, 50, 'UTF-8' );
			}

			if ( strlen( $this->args['shipping_address']['address_1'] ) > 50 ) {
				$consignee_address_1 = mb_substr( $this->args['shipping_address']['address_1'], 0, 50, 'UTF-8' );

				$this->args['shipping_address']['address_2'] = mb_substr( $this->args['shipping_address']['address_1'], 50, 'UTF-8' ) . ' ' . $this->args['shipping_address']['address_2'];

			} else {
				$consignee_address_1 = $this->args['shipping_address']['address_1'];
			}

			$consignee_address_3 = '';
			if ( strlen( $this->args['shipping_address']['address_2'] ) > 50 ) {
				$consignee_address_2 = mb_substr( $this->args['shipping_address']['address_2'], 0, 50, 'UTF-8' );

				$consignee_address_3 = mb_substr( $this->args['shipping_address']['address_2'], 50, 50, 'UTF-8' );
			} else {
				$consignee_address_2 = $this->args['shipping_address']['address_2'];
			}

			$shipping_state = '';
			if ( $this->args['shipping_address']['state'] ) {

				// If China
				if ( $this->args['shipping_address']['country'] == 'CN' ) {

					// Remove everything after '/'
					$state_arr = explode( '/', $this->args['shipping_address']['state'] );

					if ( $state_arr ) {
						$this->args['shipping_address']['state'] = trim( $state_arr[0] );
					}
				}

				$shipping_state = mb_substr( $this->args['shipping_address']['state'], 0, 20, 'UTF-8' );
			}

			$cod_value = 0;
			if ( isset( $this->args['order_details']['is_cod'] ) && ( $this->args['order_details']['is_cod'] == 'yes' ) ) {

				$cod_value = round( floatval( $this->args['order_details']['total_value'] ), 2 );
			}

			$dhl_label_body =
				array(
					'shipments' =>
							array(
								array(
									'pickupAccount'      => $this->args['dhl_settings']['pickup'],
									'distributionCenter' => $this->args['dhl_settings']['distribution'],
									'consignmentNumber'  => $this->args['dhl_settings']['handover'],
									'packages'           =>
										array(
											array(
												'consigneeAddress' =>
													array(
														'name' => $this->args['shipping_address']['name'],
														'companyName' => $this->args['shipping_address']['company'],
														'address1' => $consignee_address_1,
														'address2' => $consignee_address_2,
														'address3' => $consignee_address_3,
														'city' => $this->args['shipping_address']['city'],
														'postalCode' => $this->args['shipping_address']['postcode'],
														'state' => $shipping_state,
														'country' => $this->args['shipping_address']['country'],
														'phone' => $this->args['shipping_address']['phone'],
													),
												'packageDetails' =>
													array(
														'codAmount' => $cod_value,
														'currency' => $this->args['order_details']['currency'],
														'dgCategory' => $this->args['order_details']['dangerous_goods'],
														'orderedProduct' => $this->args['order_details']['dhl_product'],
														'packageDesc' => $package_desc,
														'packageId' => $package_id,
														'weight' => round( floatval( $this->args['order_details']['weight'] ), 2 ),
														'weightUom' => strtoupper( $this->args['order_details']['weightUom'] ),
														'billingRef1' => mb_substr( $this->args['order_details']['order_note'], 0, 50, 'UTF-8' ),
														'billingRef2' => mb_substr( $this->args['order_details']['order_note'], 50, 25, 'UTF-8' ),
													),
												// 'customsDetails' => $customsDetails
											),
										),
								),
							),
				);

			// Add customs info
			if ( PR_DHL()->is_crossborder_shipment( $this->args['shipping_address'] ) ) {

				$customsDetails = array();
				foreach ( $this->args['items'] as $key => $item ) {

					$json_item = array(
						'itemDescription'   => mb_substr( $item['item_description'], 0, 200, 'UTF-8' ),
						'descriptionExport' => mb_substr( $item['item_export'], 0, 200, 'UTF-8' ),
						// 'descriptionImport' =>   substr( $item['item_description'], 0, 200 ),
						'countryOfOrigin'   => $item['country_origin'],
						'hsCode'            => $item['hs_code'],
						'packagedQuantity'  => intval( $item['qty'] ),
						'itemValue'         => round( floatval( $item['item_value'] ), 2 ),
						'skuNumber'         => $item['sku'],
					);

					array_push( $customsDetails, $json_item );
				}
				// Add customs info
				$dhl_label_body['shipments'][0]['packages'][0]['customsDetails'] = $customsDetails;

				// Add duties info
				$dhl_label_body['shipments'][0]['packages'][0]['packageDetails']['dutiesPaid'] = $this->args['order_details']['duties'];

				// Declared info
				$dhl_label_body['shipments'][0]['packages'][0]['packageDetails']['declaredValue'] = round( floatval( $this->args['order_details']['items_value'] ), 2 );

			}

			// Unset/remove any items that are empty strings or 0, even if required!
			$dhl_label_body = $this->walk_recursive_remove( $dhl_label_body );

			$this->body_request = wp_json_encode( $dhl_label_body, JSON_PRETTY_PRINT );
		}
	}

	// Unset/remove any items that are empty strings or 0
	private function walk_recursive_remove( array $array ) {
		foreach ( $array as $k => $v ) {
			if ( is_array( $v ) ) {
				$array[ $k ] = $this->walk_recursive_remove( $v );
			}

			if ( empty( $v ) ) {
				unset( $array[ $k ] );
			}
		}
		return $array;
	}
}
