<?php

namespace PR\DHL\Deutsche_Post\Item;

use Exception;
use PR\DHL\Utils\Args_Parser;

/**
 * A class for item shipment information for Deutsche Post.
 *
 * @since [*next-version*]
 */
class Shipment_Info {
	/**
	 * The shipment service level.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $service_level;
	/**
	 * The shipment product.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $product;
	/**
	 * The shipment value.
	 *
	 * @since [*next-version*]
	 *
	 * @var float
	 */
	public $value;
	/**
	 * The currency of the shipment value.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $currency;
	/**
	 * The gross weight of the shipment.
	 *
	 * @since [*next-version*]
	 *
	 * @var float
	 */
	public $weight;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments.
	 *
	 * @throws Exception If some data in the $args failed validation.
	 */
	public function __construct( $args ) {
		$this->parse_args( $args );
	}

	/**
	 * Parses the arguments and sets the instance's properties.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments.
	 *
	 * @throws Exception If some data in the $args failed validation.
	 */
	protected function parse_args( $args ) {
		$parsed = Args_Parser::parse_args( $args, $this->get_args_scheme() );

		$this->product = $parsed['product'];
		$this->service_level = $parsed['service_level'];
		$this->value = $parsed['value'];
		$this->currency = $parsed['currency'];
		$this->weight = $parsed['weight'] * 1000.0;
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser}.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_args_scheme() {
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
}
