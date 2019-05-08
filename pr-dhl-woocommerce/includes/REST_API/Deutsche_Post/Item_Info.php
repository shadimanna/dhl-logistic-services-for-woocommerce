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
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	public function __construct( $args ) {
		$this->parse_args( $args );
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
		$settings = $args[ 'dhl_settings' ];
		$recipient_info = $args[ 'shipping_address' ] + $settings;
		$shipping_info = $args[ 'order_details' ] + $settings;
		$items_info = $args['items'];

		$this->shipment = Args_Parser::parse_args( $shipping_info, $this->get_shipment_info_schema() );
		$this->recipient = Args_Parser::parse_args( $recipient_info, $this->get_recipient_info_schema() );
		$this->contents = array();

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
		return array(
			'dhl_product'       => array(
				'rename' => 'product',
				'error'  => __( 'DHL "Product" is empty!', 'pr-shipping-dhl' ),
			),
			'dhl_service_level' => array(
				'rename'   => 'service_level',
				'default'  => 'STANDARD',
				'validate' => function( $level ) {
					if ( $level !== 'STANDARD' && $level !== 'PRIORITY' && $level !== 'REGISTERED' ) {
						throw new Exception( __( 'Order "Service Level" is invalid', 'pr-shipping-dhl' ) );
					}
				},
			),
			'weight'            => array(
				'error'    => __( 'Order "Weight" is empty!', 'pr-shipping-dhl' ),
				'validate' => function( $weight ) {
					if ( ! is_numeric( $weight ) ) {
						throw new Exception( __( 'The order "Weight" must be a number', 'pr-shipping-dhl' ) );
					}
				},
			),
			'currency'          => array(
				'error' => __( 'Shop "Currency" is empty!', 'pr-shipping-dhl' ),
			),
			'total_value'       => array(
				'rename' => 'value',
				'error'  => __( 'Shipment "Value" is empty!', 'pr-shipping-dhl' ),
				'validate' => function( $value ) {
					if ( ! is_numeric( $value ) ) {
						throw new Exception( __( 'The order "value" must be a number', 'pr-shipping-dhl' ) );
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
		return array(
			'name'      => array(
				'default' => '',
			),
			'phone'     => array(
				'default' => '',
			),
			'email'     => array(
				'default' => '',
			),
			'address_1' => array(
				'error' => __( 'Shipping "Address 1" is empty!', 'pr-shipping-dhl' ),
			),
			'address_2' => array(
				'default' => '',
			),
			'city'      => array(
				'error' => __( 'Shipping "City" is empty!', 'pr-shipping-dhl' ),
			),
			'postcode'  => array(
				'default' => '',
			),
			'state'     => array(
				'default' => '',
			),
			'country'   => array(
				'error' => __( 'Shipping "Country" is empty!', 'pr-shipping-dhl' ),
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
	protected function get_content_item_info_schema()
	{
		return array(
			'hs_code'     => array(
				'default'  => '',
				'validate' => function( $hs_code ) {
					$length = is_string( $hs_code ) ? strlen( $hs_code ) : 0;

					if (empty($length)) {
						return;
					}

					if ( $length < 4 || $length > 20 ) {
						throw new Exception(
							__( 'Item HS Code must be between 0 and 20 characters long', 'pr-shipping-dhl' )
						);
					}
				},
			),
			'item_description' => array(
				'rename' => 'description',
				'default' => '',
			),
			'sku'         => array(
				'default' => '',
			),
			'item_value'       => array(
				'rename' => 'value',
				'default' => 0,
			),
			'origin'      => array(
				'default' => PR_DHL()->get_base_country(),
			),
			'qty'         => array(
				'default' => 1,
			),
			'item_weight'      => array(
				'rename' => 'weight',
				'default' => 1,
			),
		);
	}
}
