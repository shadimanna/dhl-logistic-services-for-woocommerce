<?php

namespace PR\DHL\REST_API\Parcel_DE;

use Exception;
use PR\DHL\Utils\Args_Parser;

/**
 * A class that represents a Deutsche Post item, which corresponds to a WooCommerce order.
 *
 * @since [*next-version*]
 */
class Item_Info {
	/**
	 * @var array.
	 */
	public $args;

	/**
	 * Shipment details.
	 *
	 * @var array.
	 */
	public $shipment;

	/**
	 * Shipper information, including contact information, address. Alternatively, a predefined shipper reference can be used.
	 *
	 * @var array.
	 */
	public $shipper;

	/**
	 * Consignee address information.
	 *
	 * @var array.
	 */
	public $contactAddress;

	/**
	 * Consignee address information.
	 *
	 * @var array.
	 */
	public $returnAddress;

	/**
	 * Consignee Pack Station / Locker address information.
	 *
	 * @var array.
	 */
	public $packStationAddress;

	/**
	 * Consignee PostOffice Locker address information.
	 *
	 * Only usable for German post offices or retail outlets (Paketshops), international postOffices or retail outlets cannot be addressed directly.
	 * If your customer wishes for international delivery to a droppoint, please use DHL Parcel International (V53WPAK) with the delivery type "Closest Droppoint".
	 *
	 * @var array.
	 */
	public $postOfficeAddress;

	/**
	 * Shipment items.
	 *
	 * @var array[].
	 */
	public $items;

	/**
	 * For international shipments, this array contains information necessary for customs about the exported goods.
	 *
	 * @var array.
	 */
	public $services;

	/**
	 * The units of measurement used for weights in the input args.
	 *
	 * @var string.
	 */
	public $weightUom;

	/**
	 * The units of measurement used for weights in the input args.
	 *
	 * @var string.
	 */
	public $dimUom;

	/**
	 * Is the shipment cross-border or domestic.
	 *
	 * @var boolean.
	 */
	public $isCrossBorder;

	/**
	 * is packstation ( Locker ).
	 *
	 * @var boolean.
	 */
	public $pos_ps = false;

	/**
	 * is parcelshop ( PostOffice ).
	 *
	 * @var boolean.
	 */
	public $pos_rs = false;

	/**
	 * is post office.
	 *
	 * @var boolean.
	 */
	public $pos_po = false;

	/**
	 * is post office.
	 *
	 * @var boolean.
	 */
	public $dhl_return_product = '07';

	/**
	 * Constructor.
	 *
	 * @param array  $args The arguments to parse.
	 * @param string $weightUom The units of measurement used for weights in the input args.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	public function __construct( $args, $weightUom = 'kg' ) {
		$this->weightUom     = $weightUom;
		$this->isCrossBorder = PR_DHL()->is_crossborder_shipment( $args['shipping_address'] );

		$this->args = $args;

		$this->pos_ps = PR_DHL()->is_packstation( $args['shipping_address']['address_1'] );
		$this->pos_rs = PR_DHL()->is_parcelshop( $args['shipping_address']['address_1'] );
		$this->pos_po = PR_DHL()->is_post_office( $args['shipping_address']['address_1'] );

		$this->set_address_2();
		$this->parse_args();
	}

	/**
	 * Parses the arguments and sets the instance's properties.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	protected function parse_args() {
		$settings       = $this->args['dhl_settings'];
		$recipient_info = $this->args['shipping_address'] + $settings;
		$shipping_info  = $this->args['order_details'] + $settings;
		$items_info     = $this->args['items'];

		$this->shipment       = Args_Parser::parse_args( $shipping_info, $this->get_shipment_info_schema() );
		$this->shipper        = Args_Parser::parse_args( $shipping_info, $this->get_shipper_info_schema() );
		$this->contactAddress = Args_Parser::parse_args( $recipient_info, $this->get_contact_address_schema() );
		$this->services       = Args_Parser::parse_args( $shipping_info, $this->get_services_schema() );
		$this->returnAddress  = Args_Parser::parse_args( $shipping_info, $this->get_return_address_schema() );

		if ( $this->pos_ps ) {
			$this->packStationAddress = Args_Parser::parse_args( $recipient_info, $this->get_packstation_address_schema() );
		}

		if ( $this->pos_ps || $this->pos_rs ) {
			$this->postOfficeAddress = Args_Parser::parse_args( $recipient_info, $this->get_post_office_address_schema() );
		}

		$this->items = array();
		foreach ( $items_info as $item_info ) {
			$this->items[] = Args_Parser::parse_args( $item_info, $this->get_content_item_info_schema() );
		}
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing shipment info.
	 *
	 * @return array.
	 */
	protected function get_shipment_info_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'dhl_product'            => array(
				'rename'   => 'product',
				'error'    => esc_html__( 'DHL "Product" is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $product ) use ( $self ) {

					$product_info = explode( '-', $product );
					$product      = $product_info[0];

					return $product;
				},
			),
			'order_id'               => array(
				'rename'   => 'refNo',
				'default'  => '',
				'sanitize' => function ( $label_ref ) use ( $self ) {
					return $self->string_length_sanitization( $label_ref, 50 );
				},
			),
			'account_num'            => array(
				'rename'   => 'billingNumber',
				'sanitize' => function ( $account ) use ( $self ) {

					if ( empty( $account ) ) {
						throw new Exception( esc_html__( 'Check your settings "Account Number" and "Participation Number".', 'dhl-for-woocommerce' ) );
					}

					// create account number
					$product_number = preg_match( '!\d+!', $self->args['order_details']['dhl_product'], $matches );

					if ( $product_number ) {
						return $self->args['dhl_settings']['account_num'] . $matches[0] . $self->args['dhl_settings']['participation'];
					} else {
						throw new Exception( esc_html__( 'Could not create account number - no product number.', 'dhl-for-woocommerce' ) );
					}
				},
			),
			'cost_center'            => array(
				'rename'  => 'costCenter',
				'default' => '',
			),
			'weight'                 => array(
				'error'    => esc_html__( 'Order "Weight" is empty!', 'dhl-for-woocommerce' ),
				'validate' => function ( $weight ) {
					if ( ! is_numeric( wc_format_decimal( $weight ) ) ) {
						throw new Exception( esc_html__( 'The order "Weight" must be a number', 'dhl-for-woocommerce' ) );
					}
				},
				'sanitize' => function ( $weight ) use ( $self ) {
					return $self->maybe_convert_weight( $weight, $self->weightUom );
				},
			),
			'currency'               => array(
				'error' => esc_html__( 'Shop "Currency" is empty!', 'dhl-for-woocommerce' ),
			),
			'total_value'            => array(
				'rename'   => 'value',
				'error'    => esc_html__( 'Shipment "Value" is empty!', 'dhl-for-woocommerce' ),
				'validate' => function ( $value ) {
					if ( ! is_numeric( $value ) ) {
						throw new Exception( esc_html__( 'The order "value" must be a number', 'dhl-for-woocommerce' ) );
					}
				},
				'sanitize' => function ( $value ) use ( $self ) {

					return $self->float_round_sanitization( $value, 2 );
				},
			),
			'cod_value'              => array(
				'default'  => '',
				'sanitize' => function ( $value ) use ( $self ) {
					return $self->float_round_sanitization( $value, 2 );
				},
			),
			'routing_email'          => array(
				'default' => '',
			),
			'multi_packages_enabled' => array(
				'default' => '',
			),
			'total_packages'         => array(
				'default'  => '',
				'validate' => function ( $value ) use ( $self ) {
					if ( isset( $self->args['order_details']['multi_packages_enabled'] ) && ( $self->args['order_details']['multi_packages_enabled'] == 'yes' ) ) {
						for ( $i = 0; $i < intval( $value ); $i++ ) {

							if ( empty( $self->args['order_details']['packages_number'][ $i ] ) ) {
								throw new Exception(
									esc_html__(
										'A package number is empty. Ensure all package details are filled in.',
										'dhl-for-woocommerce'
									)
								);
							}

							if ( empty( $self->args['order_details']['packages_weight'][ $i ] ) ) {
								throw new Exception(
									esc_html__(
										'A package weight is empty. Ensure all package details are filled in.',
										'dhl-for-woocommerce'
									)
								);
							}

							if ( empty( $self->args['order_details']['packages_length'][ $i ] ) ) {
								throw new Exception(
									esc_html__(
										'A package length is empty. Ensure all package details are filled in.',
										'dhl-for-woocommerce'
									)
								);
							}

							if ( empty( $self->args['order_details']['packages_width'][ $i ] ) ) {
								throw new Exception(
									esc_html__(
										'A package width is empty. Ensure all package details are filled in.',
										'dhl-for-woocommerce'
									)
								);
							}

							if ( empty( $self->args['order_details']['packages_height'][ $i ] ) ) {
								throw new Exception(
									esc_html__(
										'A package height is empty. Ensure all package details are filled in.',
										'dhl-for-woocommerce'
									)
								);
							}
						}
					}
				},
				'sanitize' => function ( $value ) use ( $self ) {
					if ( ! isset( $self->args['order_details']['multi_packages_enabled'] ) || 'yes' !== $self->args['order_details']['multi_packages_enabled'] ) {
						return $value;
					}

					for ( $i = 0; $i < intval( $value ); $i++ ) {
						$self->args['order_details']['packages_weight'][ $i ] = $self->maybe_convert_weight( $self->args['order_details']['packages_weight'][ $i ], $self->weightUom );
					}

					return $value;
				},
			),
			'is_codeable'            => array(
				'rename'   => 'mustEncode',
				'default'  => 'false',
				'sanitize' => function ( $value ) use ( $self ) {
					if ( 'yes' === $value ) {
						return 'true';
					}

					return 'false';
				},
			),
			'label_format'           => array(
				'rename' => 'printFormat',
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing shipper info.
	 *
	 * @return array.
	 */
	protected function get_shipper_info_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'shipper_name'          => array(
				'rename'   => 'name1',
				'sanitize' => function ( $name ) use ( $self ) {
					if ( empty( $name ) ) {
						throw new Exception(
							esc_html__( '"Account Name" in settings is empty.', 'dhl-for-woocommerce' )
						);
					}

					return $self->string_length_sanitization( $name, 50 );
				},
			),
			'shipper_phone'         => array(
				'rename' => 'phone',
			),
			'shipper_email'         => array(
				'rename' => 'email',
			),
			'shipper_address'       => array(
				'rename'   => 'addressStreet',
				'error'    => esc_html__( 'Shipper "Address 1" is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {

					if ( empty( $name ) ) {
						throw new Exception(
							esc_html__( 'Shipper "Address 1" is empty!', 'dhl-for-woocommerce' )
						);
					}

					return $self->string_length_sanitization( $name, 50 );
				},
			),
			'shipper_address_no'    => array(
				'rename' => 'addressHouse',
			),
			'shipper_address_zip'   => array(
				'rename' => 'postalCode',
				'error'  => esc_html__( 'Shipper "Postcode" is empty!', 'dhl-for-woocommerce' ),
			),
			'shipper_address_city'  => array(
				'rename'   => 'city',
				'error'    => esc_html__( 'Shipper "City" is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $value ) use ( $self ) {
					return $self->string_length_sanitization( $value, 40 );
				},
			),
			'shipper_address_state' => array(
				'rename'   => 'state',
				'sanitize' => function ( $value ) use ( $self ) {
					return $self->string_length_sanitization( $value, 20 );
				},
			),
			'shipper_country'       => array(
				'rename'   => 'country',
				'sanitize' => function ( $countryCode ) use ( $self ) {
					if ( empty( $countryCode ) ) {
						throw new Exception(
							esc_html__( 'Shipper "Country" is empty!', 'dhl-for-woocommerce' )
						);
					}

					return $self->country_code_to_alpha3( $countryCode );
				},
			),
			'shipper_reference'     => array(
				'rename'   => 'shipperRef',
				'sanitize' => function ( $value ) use ( $self ) {
					if ( 'yes' === $self->args['dhl_settings']['add_logo'] ) {
						return $value;
					}

					return '';
				},
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing shipper info.
	 *
	 * @return array.
	 */
	protected function get_return_address_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'return_name'          => array(
				'rename'   => 'name1',
				'sanitize' => function ( $name ) use ( $self ) {
					return $self->string_length_sanitization( $name, 50 );
				},
				'default'  => '',
			),
			'return_phone'         => array(
				'rename'  => 'phone',
				'default' => '',
			),
			'return_email'         => array(
				'rename'  => 'email',
				'default' => '',
			),
			'return_address'       => array(
				'rename'   => 'addressStreet',
				'sanitize' => function ( $name ) use ( $self ) {
					return $self->string_length_sanitization( $name, 50 );
				},
				'default'  => '',
			),
			'return_address_no'    => array(
				'rename'  => 'addressHouse',
				'default' => '',
			),
			'return_address_zip'   => array(
				'rename'  => 'postalCode',
				'default' => '',
			),
			'return_address_city'  => array(
				'rename'  => 'city',
				'default' => '',
			),
			'return_address_state' => array(
				'rename'   => 'state',
				'default'  => '',
				'sanitize' => function ( $value ) use ( $self ) {
					return $self->string_length_sanitization( $value, 20 );
				},
			),
			'shipper_country'      => array(
				'rename'   => 'country',
				'default'  => '',
				'sanitize' => function ( $countryCode ) use ( $self ) {
					return $self->country_code_to_alpha3( $countryCode );
				},
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing consignee info.
	 *
	 * @return array.
	 */
	protected function get_contact_address_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'name'               => array(
				'rename'   => 'name1',
				'error'    => esc_html__( 'Recipient name is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {

					return $self->string_length_sanitization( $name, 50 );
				},
			),
			'company'            => array(
				'rename' => 'name2',
			),
			'address_1'          => array(
				'rename' => 'addressStreet',
				'error'  => esc_html__( 'Shipping "Address 1" is empty!', 'dhl-for-woocommerce' ),
			),
			'address_2'          => array(
				'rename'   => 'addressHouse',
				'sanitize' => function ( $value ) use ( $self ) {
					return strlen( $value ) <= 10 ? $value : '';
				},
			),
			'address_additional' => array(
				'rename'  => 'additionalAddressInformation1',
				'default' => '',
			),
			'postcode'           => array(
				'rename' => 'postalCode',
				'error'  => esc_html__( 'Shipping "Postcode" is empty!', 'dhl-for-woocommerce' ),
			),
			'city'               => array(
				'error' => esc_html__( 'Shipping "City" is empty!', 'dhl-for-woocommerce' ),
			),
			'state'              => array(
				'sanitize' => function ( $value ) use ( $self ) {
					return $self->string_length_sanitization( $value, 20 );
				},
			),
			'country'            => array(
				'sanitize' => function ( $countryCode ) use ( $self ) {
					if ( empty( $countryCode ) ) {
						throw new Exception(
							esc_html__( 'Shipping "Country" is empty!', 'dhl-for-woocommerce' )
						);
					}

					return $self->country_code_to_alpha3( $countryCode );
				},
			),
			'phone'              => array(
				'sanitize' => function ( $phone ) use ( $self ) {

					return $self->string_length_sanitization( $phone, 20 );
				},
			),
			'email'              => array(),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing consignee info.
	 * for Locker, known as Packstation.
	 *
	 * @return array.
	 */
	protected function get_packstation_address_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'name'        => array(
				'rename'   => 'name',
				'error'    => esc_html__( 'Packstation name is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {
					return $self->string_length_sanitization( $name, 50 );
				},
			),
			'dhl_postnum' => array(
				'rename' => 'postNumber',
				'error'  => esc_html__( 'Post Number is missing, it is mandatory for "Packstation" delivery.', 'dhl-for-woocommerce' ),
			),
			'address_1'   => array(
				'rename'   => 'lockerID',
				'error'    => esc_html__( 'Locker ID is missing, it is mandatory for "Packstation" delivery.', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {
					return filter_var( $this->args['shipping_address']['address_1'], FILTER_SANITIZE_NUMBER_INT );
				},
			),
			'postcode'    => array(
				'rename' => 'postalCode',
				'error'  => esc_html__( 'Shipping "Postcode" is empty!', 'dhl-for-woocommerce' ),
			),
			'city'        => array(
				'error' => esc_html__( 'Shipping "City" is empty!', 'dhl-for-woocommerce' ),
			),
			'country'     => array(
				'error'    => esc_html__( 'Shipping "Country" is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $countryCode ) use ( $self ) {
					return $self->country_code_to_alpha3( $countryCode );
				},
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing consignee info.
	 * for Post Office.
	 *
	 * @return array.
	 */
	protected function get_post_office_address_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'name'        => array(
				'rename'   => 'name',
				'error'    => esc_html__( 'Name is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {
					return $self->string_length_sanitization( $name, 50 );
				},
			),
			'dhl_postnum' => array(
				'default' => '',
				'rename'  => 'postNumber',
			),
			'address_1'   => array(
				'rename'   => 'retailID',
				'error'    => esc_html__( 'Locker ID is missing, it is mandatory for "Packstation" delivery.', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {
					return filter_var( $this->args['shipping_address']['address_1'], FILTER_SANITIZE_NUMBER_INT );
				},
			),
			'postcode'    => array(
				'rename' => 'postalCode',
				'error'  => esc_html__( 'Shipping "Postcode" is empty!', 'dhl-for-woocommerce' ),
			),
			'city'        => array(
				'error' => esc_html__( 'Shipping "City" is empty!', 'dhl-for-woocommerce' ),
			),
			'country'     => array(
				'error'    => esc_html__( 'Shipping "Country" is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $countryCode ) use ( $self ) {
					return $self->country_code_to_alpha3( $countryCode );
				},
			),
			'email'       => array(),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order content item info.
	 *
	 * @return array.
	 */
	protected function get_content_item_info_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'item_description' => array(
				'rename'   => 'itemDescription',
				'default'  => '',
				'sanitize' => function ( $description ) use ( $self ) {

					return $self->string_length_sanitization( $description, 33 );
				},
			),
			'country_origin'   => array(
				'rename'   => 'countryOfOrigin',
				'default'  => PR_DHL()->get_base_country(),
				'sanitize' => function ( $countryCode ) use ( $self ) {

					return $self->country_code_to_alpha3( $countryCode );
				},
			),
			'hs_code'          => array(
				'rename'   => 'hsCode',
				'default'  => '',
				'validate' => function ( $hs_code ) {
					$length = is_string( $hs_code ) ? strlen( $hs_code ) : 0;

					if ( empty( $length ) ) {
						return;
					}

					if ( $length < 4 || $length > 11 ) {
						throw new Exception(
							esc_html__( 'Item HS Code must be between 4 and 11 characters long', 'dhl-for-woocommerce' )
						);
					}
				},
			),
			'qty'              => array(
				'rename'  => 'packagedQuantity',
				'default' => 1,
			),
			'item_value'       => array(
				'rename'   => 'itemValue',
				'default'  => array(
					'currency' => PR_DHL()->get_currency_symbol(),
					'amount'   => 0,
				),
				'sanitize' => function ( $value, $args ) use ( $self ) {
					return array(
						'currency' => $self->args['order_details']['currency'],
						'amount'   => (string) $self->float_round_sanitization( $value, 2 ),
					);
				},
			),
			'item_weight'      => array(
				'rename'   => 'itemWeight',
				'sanitize' => function ( $weight ) use ( $self ) {
					return array(
						'uom'   => $self->weightUom,
						'value' => $self->maybe_convert_weight( $weight, $self->weightUom ),
					);
				},
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing shipment services.
	 *
	 * @return array.
	 */
	protected function get_services_schema() {
		$self = $this;

		return array(
			'preferred_neighbor'     => array(
				'default' => '',
				'rename'  => 'preferredNeighbour',
			),
			'preferred_location'     => array(
				'default' => '',
				'rename'  => 'preferredLocation',
			),
			'email_notification'     => array(
				'default' => '',
				'rename'  => 'shippingConfirmation',
			),
			'age_visual'             => array(
				'default' => '',
				'rename'  => 'visualCheckOfAge',
			),
			'named_person'           => array(
				'default' => '',
				'rename'  => 'namedPersonOnly',
			),
			'identcheck'             => array(
				'default' => '',
				'rename'  => 'identCheck',
			),
			'preferred_day'          => array(
				'default' => '',
				'rename'  => 'preferredDay',
			),
			'no_neighbor'            => array(
				'default' => '',
				'rename'  => 'noNeighbourDelivery',
			),
			'additional_insurance'   => array(
				'default' => '',
				'rename'  => 'additionalInsurance',
			),
			'bulky_goods'            => array(
				'default' => '',
				'rename'  => 'bulkyGoods',
			),
			'cdp_delivery'           => array(
				'default' => '',
				'rename'  => 'closestDropPoint',
			),
			'premium'                => array(
				'default' => '',
				'rename'  => 'premium',
			),
			'routing'                => array(
				'default' => '',
				'rename'  => 'parcelOutletRouting',
			),
			'PDDP'                   => array(
				'default' => '',
				'rename'  => 'postalDeliveryDutyPaid',
			),
			'endorsement'            => array(
				'default'  => '',
				'sanitize' => function ( $value, $args ) use ( $self ) {
					switch ( $value ) {
						case 'ABANDONMENT':
							$value = 'ABANDON';
							break;
						default:
							$value = 'RETURN';
							break;
					}

					return $value;
				},
			),
			'return_address_enabled' => array(
				'default' => '',
				'rename'  => 'dhlRetoure',
			),
			'signature_service'      => array(
				'default' => '',
				'rename'  => 'signedForByRecipient',
			),
		);
	}

	/**
	 * Converts a given weight into grams, if necessary.
	 *
	 * @param float  $weight The weight amount.
	 * @param string $uom The unit of measurement of the $weight parameter..
	 *
	 * @return float The potentially converted weight.
	 */
	protected function maybe_convert_weight( $weight, $uom ) {
		$weight = floatval( wc_format_decimal( $weight ) );

		if ( 'kg' === $uom ) {
			return round( $weight, 3 );
		}

		if ( 'g' === $uom ) {
			return (int) ceil( $weight );
		}

		switch ( $uom ) {
			case 'lbs':
			case 'lb':
				$weight = $weight * 453.592;
				break;
			case 'oz':
				$weight = $weight * 28.3495;
				break;
			default:
				break;
		}

		return intval( $weight );
	}

	/**
	 * Round float number.
	 *
	 * @param $float.
	 * @param $numcomma.
	 *
	 * @return string.
	 */
	protected function float_round_sanitization( $float, $numcomma ) {
		$float = round( floatval( $float ), $numcomma );

		return number_format( $float, 2, '.', '' );
	}

	/**
	 * String length sanitization.
	 *
	 * @param $string.
	 * @param $max.
	 *
	 * @return string.
	 */
	protected function string_length_sanitization( $string, $max ) {
		$max = intval( $max );

		if ( strlen( $string ) <= $max ) {
			return $string;
		}

		return substr( $string, 0, ( $max - 1 ) );
	}

	/**
	 * Convert country code to alpha3.
	 *
	 * @param $countryCode.
	 *
	 * @return string.
	 */
	protected function country_code_to_alpha3( $countryCode ) {
		$countries = array(
			'AF' => 'AFG', // Afghanistan
			'AX' => 'ALA', // &#197;land Islands
			'AL' => 'ALB', // Albania
			'DZ' => 'DZA', // Algeria
			'AS' => 'ASM', // American Samoa
			'AD' => 'AND', // Andorra
			'AO' => 'AGO', // Angola
			'AI' => 'AIA', // Anguilla
			'AQ' => 'ATA', // Antarctica
			'AG' => 'ATG', // Antigua and Barbuda
			'AR' => 'ARG', // Argentina
			'AM' => 'ARM', // Armenia
			'AW' => 'ABW', // Aruba
			'AU' => 'AUS', // Australia
			'AT' => 'AUT', // Austria
			'AZ' => 'AZE', // Azerbaijan
			'BS' => 'BHS', // Bahamas
			'BH' => 'BHR', // Bahrain
			'BD' => 'BGD', // Bangladesh
			'BB' => 'BRB', // Barbados
			'BY' => 'BLR', // Belarus
			'BE' => 'BEL', // Belgium
			'BZ' => 'BLZ', // Belize
			'BJ' => 'BEN', // Benin
			'BM' => 'BMU', // Bermuda
			'BT' => 'BTN', // Bhutan
			'BO' => 'BOL', // Bolivia
			'BQ' => 'BES', // Bonaire, Saint Estatius and Saba
			'BA' => 'BIH', // Bosnia and Herzegovina
			'BW' => 'BWA', // Botswana
			'BV' => 'BVT', // Bouvet Islands
			'BR' => 'BRA', // Brazil
			'IO' => 'IOT', // British Indian Ocean Territory
			'BN' => 'BRN', // Brunei
			'BG' => 'BGR', // Bulgaria
			'BF' => 'BFA', // Burkina Faso
			'BI' => 'BDI', // Burundi
			'KH' => 'KHM', // Cambodia
			'CM' => 'CMR', // Cameroon
			'CA' => 'CAN', // Canada
			'CV' => 'CPV', // Cape Verde
			'KY' => 'CYM', // Cayman Islands
			'CF' => 'CAF', // Central African Republic
			'TD' => 'TCD', // Chad
			'CL' => 'CHL', // Chile
			'CN' => 'CHN', // China
			'CX' => 'CXR', // Christmas Island
			'CC' => 'CCK', // Cocos (Keeling) Islands
			'CO' => 'COL', // Colombia
			'KM' => 'COM', // Comoros
			'CG' => 'COG', // Congo
			'CD' => 'COD', // Congo, Democratic Republic of the
			'CK' => 'COK', // Cook Islands
			'CR' => 'CRI', // Costa Rica
			'CI' => 'CIV', // Côte d\'Ivoire
			'HR' => 'HRV', // Croatia
			'CU' => 'CUB', // Cuba
			'CW' => 'CUW', // Curaçao
			'CY' => 'CYP', // Cyprus
			'CZ' => 'CZE', // Czech Republic
			'DK' => 'DNK', // Denmark
			'DJ' => 'DJI', // Djibouti
			'DM' => 'DMA', // Dominica
			'DO' => 'DOM', // Dominican Republic
			'EC' => 'ECU', // Ecuador
			'EG' => 'EGY', // Egypt
			'SV' => 'SLV', // El Salvador
			'GQ' => 'GNQ', // Equatorial Guinea
			'ER' => 'ERI', // Eritrea
			'EE' => 'EST', // Estonia
			'ET' => 'ETH', // Ethiopia
			'FK' => 'FLK', // Falkland Islands
			'FO' => 'FRO', // Faroe Islands
			'FJ' => 'FIJ', // Fiji
			'FI' => 'FIN', // Finland
			'FR' => 'FRA', // France
			'GF' => 'GUF', // French Guiana
			'PF' => 'PYF', // French Polynesia
			'TF' => 'ATF', // French Southern Territories
			'GA' => 'GAB', // Gabon
			'GM' => 'GMB', // Gambia
			'GE' => 'GEO', // Georgia
			'DE' => 'DEU', // Germany
			'GH' => 'GHA', // Ghana
			'GI' => 'GIB', // Gibraltar
			'GR' => 'GRC', // Greece
			'GL' => 'GRL', // Greenland
			'GD' => 'GRD', // Grenada
			'GP' => 'GLP', // Guadeloupe
			'GU' => 'GUM', // Guam
			'GT' => 'GTM', // Guatemala
			'GG' => 'GGY', // Guernsey
			'GN' => 'GIN', // Guinea
			'GW' => 'GNB', // Guinea-Bissau
			'GY' => 'GUY', // Guyana
			'HT' => 'HTI', // Haiti
			'HM' => 'HMD', // Heard Island and McDonald Islands
			'VA' => 'VAT', // Holy See (Vatican City State)
			'HN' => 'HND', // Honduras
			'HK' => 'HKG', // Hong Kong
			'HU' => 'HUN', // Hungary
			'IS' => 'ISL', // Iceland
			'IN' => 'IND', // India
			'ID' => 'IDN', // Indonesia
			'IR' => 'IRN', // Iran
			'IQ' => 'IRQ', // Iraq
			'IE' => 'IRL', // Republic of Ireland
			'IM' => 'IMN', // Isle of Man
			'IL' => 'ISR', // Israel
			'IT' => 'ITA', // Italy
			'JM' => 'JAM', // Jamaica
			'JP' => 'JPN', // Japan
			'JE' => 'JEY', // Jersey
			'JO' => 'JOR', // Jordan
			'KZ' => 'KAZ', // Kazakhstan
			'KE' => 'KEN', // Kenya
			'KI' => 'KIR', // Kiribati
			'KP' => 'PRK', // Korea, Democratic People\'s Republic of
			'KR' => 'KOR', // Korea, Republic of (South)
			'KW' => 'KWT', // Kuwait
			'KG' => 'KGZ', // Kyrgyzstan
			'LA' => 'LAO', // Laos
			'LV' => 'LVA', // Latvia
			'LB' => 'LBN', // Lebanon
			'LS' => 'LSO', // Lesotho
			'LR' => 'LBR', // Liberia
			'LY' => 'LBY', // Libya
			'LI' => 'LIE', // Liechtenstein
			'LT' => 'LTU', // Lithuania
			'LU' => 'LUX', // Luxembourg
			'MO' => 'MAC', // Macao S.A.R., China
			'MK' => 'MKD', // Macedonia
			'MG' => 'MDG', // Madagascar
			'MW' => 'MWI', // Malawi
			'MY' => 'MYS', // Malaysia
			'MV' => 'MDV', // Maldives
			'ML' => 'MLI', // Mali
			'MT' => 'MLT', // Malta
			'MH' => 'MHL', // Marshall Islands
			'MQ' => 'MTQ', // Martinique
			'MR' => 'MRT', // Mauritania
			'MU' => 'MUS', // Mauritius
			'YT' => 'MYT', // Mayotte
			'MX' => 'MEX', // Mexico
			'FM' => 'FSM', // Micronesia
			'MD' => 'MDA', // Moldova
			'MC' => 'MCO', // Monaco
			'MN' => 'MNG', // Mongolia
			'ME' => 'MNE', // Montenegro
			'MS' => 'MSR', // Montserrat
			'MA' => 'MAR', // Morocco
			'MZ' => 'MOZ', // Mozambique
			'MM' => 'MMR', // Myanmar
			'NA' => 'NAM', // Namibia
			'NR' => 'NRU', // Nauru
			'NP' => 'NPL', // Nepal
			'NL' => 'NLD', // Netherlands
			'AN' => 'ANT', // Netherlands Antilles
			'NC' => 'NCL', // New Caledonia
			'NZ' => 'NZL', // New Zealand
			'NI' => 'NIC', // Nicaragua
			'NE' => 'NER', // Niger
			'NG' => 'NGA', // Nigeria
			'NU' => 'NIU', // Niue
			'NF' => 'NFK', // Norfolk Island
			'MP' => 'MNP', // Northern Mariana Islands
			'NO' => 'NOR', // Norway
			'OM' => 'OMN', // Oman
			'PK' => 'PAK', // Pakistan
			'PW' => 'PLW', // Palau
			'PS' => 'PSE', // Palestinian Territory
			'PA' => 'PAN', // Panama
			'PG' => 'PNG', // Papua New Guinea
			'PY' => 'PRY', // Paraguay
			'PE' => 'PER', // Peru
			'PH' => 'PHL', // Philippines
			'PN' => 'PCN', // Pitcairn
			'PL' => 'POL', // Poland
			'PT' => 'PRT', // Portugal
			'PR' => 'PRI', // Puerto Rico
			'QA' => 'QAT', // Qatar
			'RE' => 'REU', // Reunion
			'RO' => 'ROU', // Romania
			'RU' => 'RUS', // Russia
			'RW' => 'RWA', // Rwanda
			'BL' => 'BLM', // Saint Barth&eacute;lemy
			'SH' => 'SHN', // Saint Helena
			'KN' => 'KNA', // Saint Kitts and Nevis
			'LC' => 'LCA', // Saint Lucia
			'MF' => 'MAF', // Saint Martin (French part)
			'SX' => 'SXM', // Sint Maarten / Saint Matin (Dutch part)
			'PM' => 'SPM', // Saint Pierre and Miquelon
			'VC' => 'VCT', // Saint Vincent and the Grenadines
			'WS' => 'WSM', // Samoa
			'SM' => 'SMR', // San Marino
			'ST' => 'STP', // S&atilde;o Tom&eacute; and Pr&iacute;ncipe
			'SA' => 'SAU', // Saudi Arabia
			'SN' => 'SEN', // Senegal
			'RS' => 'SRB', // Serbia
			'SC' => 'SYC', // Seychelles
			'SL' => 'SLE', // Sierra Leone
			'SG' => 'SGP', // Singapore
			'SK' => 'SVK', // Slovakia
			'SI' => 'SVN', // Slovenia
			'SB' => 'SLB', // Solomon Islands
			'SO' => 'SOM', // Somalia
			'ZA' => 'ZAF', // South Africa
			'GS' => 'SGS', // South Georgia/Sandwich Islands
			'SS' => 'SSD', // South Sudan
			'ES' => 'ESP', // Spain
			'LK' => 'LKA', // Sri Lanka
			'SD' => 'SDN', // Sudan
			'SR' => 'SUR', // Suriname
			'SJ' => 'SJM', // Svalbard and Jan Mayen
			'SZ' => 'SWZ', // Swaziland
			'SE' => 'SWE', // Sweden
			'CH' => 'CHE', // Switzerland
			'SY' => 'SYR', // Syria
			'TW' => 'TWN', // Taiwan
			'TJ' => 'TJK', // Tajikistan
			'TZ' => 'TZA', // Tanzania
			'TH' => 'THA', // Thailand
			'TL' => 'TLS', // Timor-Leste
			'TG' => 'TGO', // Togo
			'TK' => 'TKL', // Tokelau
			'TO' => 'TON', // Tonga
			'TT' => 'TTO', // Trinidad and Tobago
			'TN' => 'TUN', // Tunisia
			'TR' => 'TUR', // Turkey
			'TM' => 'TKM', // Turkmenistan
			'TC' => 'TCA', // Turks and Caicos Islands
			'TV' => 'TUV', // Tuvalu
			'UG' => 'UGA', // Uganda
			'UA' => 'UKR', // Ukraine
			'AE' => 'ARE', // United Arab Emirates
			'GB' => 'GBR', // United Kingdom
			'US' => 'USA', // United States
			'UM' => 'UMI', // United States Minor Outlying Islands
			'UY' => 'URY', // Uruguay
			'UZ' => 'UZB', // Uzbekistan
			'VU' => 'VUT', // Vanuatu
			'VE' => 'VEN', // Venezuela
			'VN' => 'VNM', // Vietnam
			'VG' => 'VGB', // Virgin Islands, British
			'VI' => 'VIR', // Virgin Island, U.S.
			'WF' => 'WLF', // Wallis and Futuna
			'EH' => 'ESH', // Western Sahara
			'YE' => 'YEM', // Yemen
			'ZM' => 'ZMB', // Zambia
			'ZW' => 'ZWE', // Zimbabwe

		);

		return $countries[ $countryCode ] ?? $countryCode;
	}

	/**
	 * Set Address 2 ( HouseNumber ).
	 *
	 * @return void.
	 * @throws Exception.
	 */
	protected function set_address_2() {
		if ( $this->pos_ps || $this->pos_rs || $this->pos_po ) {
			return;
		}

		if ( ! empty( $this->args['shipping_address']['address_2'] ) ) {

			// If address_2 greated than 10 chars, try to pass with additional address (does not show for DE)
			if ( strlen( $this->args['shipping_address']['address_2'] ) > 10 ) {
				$this->args['shipping_address']['address_additional'] = $this->args['shipping_address']['address_2'];
				$this->args['shipping_address']['address_2']          = '';
			}

			return;
		}

		$set_key = false;
		// Break address into pieces by spaces
		$address_exploded = explode( ' ', $this->args['shipping_address']['address_1'] );

		// If no spaces found
		if ( count( $address_exploded ) == 1 ) {
			// Break address into pieces by '.'
			$address_exploded = explode( '.', $this->args['shipping_address']['address_1'] );

			// If no address number and in Germany, return error
			if ( 1 === count( $address_exploded ) && 'DE' === $this->args['shipping_address']['country'] ) {
				throw new Exception( esc_html__( 'Shipping street number is missing!', 'dhl-for-woocommerce' ) );
			}
		}

		// If greater than 1, means there are two parts to the address...otherwise Address 2 is empty which is possible in some countries outside of Germany
		if ( count( $address_exploded ) > 1 ) {
			// Loop through address and set number value only...
			// ...last found number will be 'address_2'
			foreach ( $address_exploded as $address_key => $address_value ) {
				if ( is_numeric( $address_value ) ) {
					// Set last index as street number
					$set_key = $address_key;
				}
			}

			// If no number was found, then take last part of address no matter what it is
			if ( false === $set_key ) {
				$set_key = $address_key;
			}

			// The number is the first part of address 1
			if ( 0 === $set_key ) {
				$this->args['shipping_address']['address_1'] = implode( ' ', array_slice( $address_exploded, 1 ) );
				$this->args['shipping_address']['address_2'] = implode( ' ', array_slice( $address_exploded, 0, 1 ) );
			} else {
				$this->args['shipping_address']['address_1'] = implode(
					' ',
					array_slice( $address_exploded, 0, $set_key )
				);
				$this->args['shipping_address']['address_2'] = implode(
					' ',
					array_slice( $address_exploded, $set_key )
				);
			}
		}
	}
}
