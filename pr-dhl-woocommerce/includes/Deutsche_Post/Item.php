<?php

namespace PR\DHL\Deutsche_Post;

use Exception;
use PR\DHL\Deutsche_Post\Item\Content_Piece_Info;
use PR\DHL\Deutsche_Post\Item\Recipient_Info;
use PR\DHL\Deutsche_Post\Item\Shipment_Info;
use PR\DHL\Utils\Args_Parser;

/**
 * A class that represents a Deutsche Post item, which corresponds to a WooCommerce order.
 *
 * @since [*next-version*]
 */
class Item {
	/**
	 * The shipment information.
	 *
	 * @since [*next-version*]
	 *
	 * @var Shipment_Info
	 */
	protected $shipment;
	/**
	 * The recipient information.
	 *
	 * @since [*next-version*]
	 *
	 * @var Recipient_Info
	 */
	protected $recipient;
	/**
	 * The content pieces.
	 *
	 * These correspond to WooCommerce products.
	 *
	 * @since [*next-version*]
	 *
	 * @var Content_Piece_Info[]
	 */
	protected $contents;

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
		$parsed = Args_Parser::parse_args( $args, $this->get_args_scheme() );

		$this->recipient = new Recipient_Info( $parsed['recipient'] );
		$this->shipment = new Shipment_Info( $parsed['shipment'] );

		$this->contents = array();
		foreach ( $parsed['content_pieces'] as $content_piece_args ) {
			$this->contents[] = new Content_Piece_Info( $content_piece_args );
		}
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
			'dhl_settings'     => array(
				'rename' => 'settings',
			),
			'shipping_address' => array(
				'rename' => 'recipient',
			),
			'order_details'    => array(
				'rename' => 'shipment',
			),
			'items'            => array(
				'rename' => 'content_pieces',
			),
		);
	}
}
