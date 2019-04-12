<?php

namespace PR\DHL\Deutsche_Post\Item;

use Exception;
use PR\DHL\Utils\Args_Parser;

/**
 * A class for an item's content pieces for Deutsche Post.
 *
 * @since [*next-version*]
 */
class Content_Piece_Info {
	/**
	 * The content piece's HS code.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $hs_code;
	/**
	 * The content piece's SKU.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $sku;
	/**
	 * The description of this content piece.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $description;
	/**
	 * The value of a single content piece of this type.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $value;
	/**
	 * The pieces for this content type.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $qty;
	/**
	 * The overall net weight for all pieces of this content type.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
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

		$this->hs_code = $parsed['hs_code'];
		$this->sku = $parsed['sku'];
		$this->description = $parsed['description'];
		$this->value = $parsed['value'];
		$this->qty = $parsed['qty'];
		$this->weight = $parsed['weight'];
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
			'description' => array(
				'default' => '',
			),
			'sku'         => array(
				'default' => '',
			),
			'value'       => array(
				'default' => 0,
			),
			'origin'      => array(
				'default' => '',
			),
			'qty'         => array(
				'default' => 1,
			),
			'weight'      => array(
				'default' => 0,
			),
		);
	}
}
