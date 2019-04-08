<?php

namespace PR\DHL\Deutsche_Post\Item;

use Exception;
use PR\DHL\Utils\Args_Parser;

/**
 * A class for item recipient information for Deutsche Post.
 *
 * @since [*next-version*]
 */
class Recipient_Info {
	/**
	 * The recipient's full name.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $name;
	/**
	 * The recipient's phone number, if any.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $phone;
	/**
	 * The recipient's email address, if any.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $email;
	/**
	 * The recipient's first address line.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $address_1;
	/**
	 * The recipient's second address line.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $address_2;
	/**
	 * The recipient's city.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $city;
	/**
	 * The recipient's post code.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $postcode;
	/**
	 * The recipient's state, if any.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $state;
	/**
	 * The recipient's country.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	public $country;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The item arguments.
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

		$this->name = $parsed['name'];
		$this->phone = $parsed['phone'];
		$this->email = $parsed['email'];
		$this->address_1 = $parsed['address_1'];
		$this->address_2 = $parsed['address_2'];
		$this->city = $parsed['city'];
		$this->postcode = $parsed['postcode'];
		$this->state = $parsed['state'];
		$this->country = $parsed['country'];
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
}
