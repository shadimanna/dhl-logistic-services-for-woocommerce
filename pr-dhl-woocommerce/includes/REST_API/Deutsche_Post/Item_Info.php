<?php

namespace PR\DHL\REST_API\Deutsche_Post;

use Exception;
use PR\DHL\Utils\Args_Parser;

/**
 * A class that represents a Deutsche Post item, which corresponds to a WooCommerce order.
 *
 * @since [*next-version*]
 */
class Item_Info {
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
	 * @param array  $args The arguments to parse.
	 * @param string $weightUom The units of measurement used for weights in the input args.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	public function __construct( $args, $weightUom = 'g' ) {
		$this->weightUom = $weightUom;
		$this->parse_args( $args, $weightUom );
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
		$settings       = $args['dhl_settings'];
		$recipient_info = $args['shipping_address'] + $settings;
		$shipping_info  = $args['order_details'] + $settings;
		$items_info     = $args['items'];

		$this->shipment  = Args_Parser::parse_args( $shipping_info, $this->get_shipment_info_schema() );
		$this->recipient = Args_Parser::parse_args( $recipient_info, $this->get_recipient_info_schema() );
		$this->contents  = array();

		$this->contents = array();
		foreach ( $items_info as $item_info ) {
			$this->contents[] = Args_Parser::parse_args( $item_info, $this->get_content_item_info_schema() );
		}
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing shipment info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_shipment_info_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'dhl_label_ref'     => array(
				'rename'   => 'label_ref',
				'sanitize' => function ( $label_ref ) use ( $self ) {

					return $self->string_length_sanitization( $label_ref, 28 );
				},
			),
			'dhl_label_ref_2'   => array(
				'rename'   => 'label_ref_2',
				'sanitize' => function ( $label_ref_2 ) use ( $self ) {

					return $self->string_length_sanitization( $label_ref_2, 28 );
				},
			),
			'dhl_product'       => array(
				'rename'   => 'product',
				'error'    => esc_html__( 'DHL "Product" is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $product ) use ( $self ) {

					$product_info   = explode( '-', $product );
					$product        = $product_info[0];

					return $product;
				},
			),
			'dhl_service_level' => array(
				'rename'   => 'service_level',
				'default'  => 'PRIORITY',
				'validate' => function ( $level ) {
					if ( $level !== 'STANDARD' && $level !== 'PRIORITY' && $level !== 'REGISTERED' ) {
						throw new Exception( esc_html__( 'Order "Service Level" is invalid', 'dhl-for-woocommerce' ) );
					}
				},
			),
			'weight'            => array(
				'error'    => esc_html__( 'Order "Weight" is empty!', 'dhl-for-woocommerce' ),
				'validate' => function ( $weight ) {
					if ( ! is_numeric( wc_format_decimal( $weight ) ) ) {
						throw new Exception( esc_html__( 'The order "Weight" must be a number', 'dhl-for-woocommerce' ) );
					}
				},
				'sanitize' => function ( $weight ) use ( $self ) {
					$weight = $self->maybe_convert_to_grams( $weight, $self->weightUom );

					return $weight;
				},
			),
			'currency'          => array(
				'error' => esc_html__( 'Shop "Currency" is empty!', 'dhl-for-woocommerce' ),
			),
			'total_value'       => array(
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
			'nature_type'       => array(
				'rename'  => 'nature_type',
				'default' => 'SALE_GOODS',
			),
			'packet_return'     => array(
				'default'  => false,
				'sanitize' => function ( $return ) use ( $self ) {

					return ( $return == 'yes' ) ? true : false;
				},
			),
			'importer_taxid'    => array(
				'default'  => '',
				'validate' => function ( $value ) use ( $self ) {

					if ( ! empty( $value ) ) {

						if ( ! ctype_alnum( $value ) ) {
							throw new Exception( esc_html__( 'The importer customs reference "value" must be an alphanumeric', 'dhl-for-woocommerce' ) );
						}

						if ( strlen( $value ) > 35 ) {
							throw new Exception( esc_html__( 'The importer customs reference "value" must be between 1 and 35 characters long', 'dhl-for-woocommerce' ) );
						}
					}
				},
			),
			'sender_taxid'      => array(
				'default'  => '',
				'validate' => function ( $value ) use ( $self ) {

					if ( ! empty( $value ) ) {

						if ( ! ctype_alnum( $value ) ) {
							throw new Exception( esc_html__( 'The sender customs reference "value" must be an alphanumeric', 'dhl-for-woocommerce' ) );
						}

						if ( strlen( $value ) > 35 ) {
							throw new Exception( esc_html__( 'The sender customs reference "value" must be between 1 and 35 characters long', 'dhl-for-woocommerce' ) );
						}
					}
				},
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order recipient info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_recipient_info_schema() {

		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'name'      => array(
				'default'  => '',
				'error'    => esc_html__( 'Recipient is empty!', 'dhl-for-woocommerce' ),
				'sanitize' => function ( $name ) use ( $self ) {

					return $self->string_length_sanitization( $name, 30 );
				},
			),
			'phone'     => array(
				'default'  => '',
				'sanitize' => function ( $phone ) use ( $self ) {

					return $self->string_length_sanitization( $phone, 15 );
				},
			),
			'email'     => array(
				'default' => '',
			),
			'address_1' => array(
				'error' => esc_html__( 'Shipping "Address 1" is empty!', 'dhl-for-woocommerce' ),
			),
			'address_2' => array(
				'default' => '',
			),
			'city'      => array(
				'error' => esc_html__( 'Shipping "City" is empty!', 'dhl-for-woocommerce' ),
			),
			'postcode'  => array(
				'default' => '',
			),
			'state'     => array(
				'default' => '',
			),
			'country'   => array(
				'error' => esc_html__( 'Shipping "Country" is empty!', 'dhl-for-woocommerce' ),
			),
		);
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order content item info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_content_item_info_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'hs_code'          => array(
				'default'  => '',
				'validate' => function ( $hs_code ) {
					$length = is_string( $hs_code ) ? strlen( $hs_code ) : 0;

					if ( empty( $length ) ) {
						return;
					}

					if ( $length < 4 || $length > 20 ) {
						throw new Exception(
							esc_html__( 'Item HS Code must be between 4 and 20 characters long', 'dhl-for-woocommerce' )
						);
					}
				},
			),
			'item_description' => array(
				'rename'   => 'description',
				'default'  => '',
				'sanitize' => function ( $description ) use ( $self ) {

					return $self->string_length_sanitization( $description, 33 );
				},
			),
			'item_export'      => array(
				'rename'   => 'description_export',
				'default'  => '',
				'sanitize' => function ( $description_export ) use ( $self ) {

					return $self->string_length_sanitization( $description_export, 33 );
				},
			),
			'product_id'       => array(
				'default' => '',
			),
			'sku'              => array(
				'default' => '',
			),
			'item_value'       => array(
				'rename'   => 'value',
				'default'  => 0,
				'sanitize' => function ( $value, $args ) use ( $self ) {

					$qty            = isset( $args['qty'] ) && is_numeric( $args['qty'] ) ? floatval( $args['qty'] ) : 1;
					$total_value    = floatval( $value ) * $qty;

					return (string) $self->float_round_sanitization( $total_value, 2 );
				},
			),
			'country_origin'   => array(
				'rename'  => 'origin',
				'default' => PR_DHL()->get_base_country(),
			),
			'qty'              => array(
				'default' => 1,
			),
			'item_weight'      => array(
				'rename'   => 'weight',
				'default'  => 1,
				'sanitize' => function ( $weight ) use ( $self ) {

					$weight = $self->maybe_convert_to_grams( $weight, $self->weightUom );

					return $weight;
				},
			),
		);
	}

	/**
	 * Converts a given weight into grams, if necessary.
	 *
	 * @since [*next-version*]
	 *
	 * @param float  $weight The weight amount.
	 * @param string $uom The unit of measurement of the $weight parameter..
	 *
	 * @return float The potentially converted weight.
	 */
	protected function maybe_convert_to_grams( $weight, $uom ) {
		$weight = floatval( wc_format_decimal( $weight ) );

		switch ( $uom ) {
			case 'kg':
				$weight = $weight * 1000;
				break;
			case 'lb':
				$weight = $weight / 2.2;
				break;
			case 'oz':
				$weight = $weight / 35.274;
				break;
		}

		return round( $weight );
	}

	protected function float_round_sanitization( $float, $numcomma ) {

		$float = round( floatval( $float ), $numcomma );

		return number_format( $float, 2, '.', '' );
	}

	protected function string_length_sanitization( $string, $max ) {

		$max = intval( $max );

		if ( strlen( $string ) <= $max ) {

			return $string;
		}

		return substr( $string, 0, ( $max - 1 ) );
	}
}
