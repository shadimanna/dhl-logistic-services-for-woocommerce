<?php

namespace PR\DHL\Deutsche_Post\API;

use Exception;
use PR\DHL\Deutsche_Post\Item_Info;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;
use PR\DHL\REST_API\Request;
use stdClass;

/**
 * The API client for Deutsche Post.
 *
 * @since [*next-version*]
 */
class Client extends API_Client {
	/**
	 * The customer EKP.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	protected $ekp;

	/**
	 * {@inheritdoc}
	 *
	 * @since [*next-version*]
	 *
	 * @param string $ekp The customer EKP.
	 */
	public function __construct( $ekp, $base_url, API_Driver_Interface $driver, API_Auth_Interface $auth = null ) {
		parent::__construct( $base_url, $driver, $auth );

		$this->ekp = $ekp;
	}

	/**
	 * Creates an item on the remote API.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The information of the item to be created.
	 *
	 * @return stdClass The item information as returned by the remote API.
	 *
	 * @throws Exception
	 */
	public function create_item( Item_Info $item_info ) {
		$response = $this->post(
			$this->customer_route( 'items' ),
			array(
				'serviceLevel'        => $item_info->shipment->service_level,
				'product'             => $item_info->shipment->product,
				'recipient'           => $item_info->recipient->name,
				'recipientPhone'      => $item_info->recipient->phone,
				'recipientEmail'      => $item_info->recipient->email,
				'addressLine1'        => $item_info->recipient->address_1,
				'addressLine2'        => $item_info->recipient->address_2,
				'city'                => $item_info->recipient->city,
				'postalCode'          => $item_info->recipient->postcode,
				'state'               => $item_info->recipient->state,
				'destinationCountry'  => $item_info->recipient->country,
				'shipmentAmount'      => $item_info->shipment->value,
				'shipmentCurrency'    => $item_info->shipment->currency,
				'shipmentGrossWeight' => $item_info->shipment->weight * 1000.0,
				'contents'            => $item_info->contents,
			)
		);

		if ( $response->status !== 200 ) {
			return $response->body;
		}

		$message = ! empty( $response->body->messages )
			? implode( ', ', $response->body->messages )
			: '';

		throw new Exception(
			sprintf( __( 'API error: %s', 'pr-shipping-dhl' ), $message )
		);
	}

	/**
	 * Retrieves items from the remote API.
	 *
	 * @since [*next-version*]
	 *
	 * @return array The list of items.
	 *
	 * @throws Exception
	 */
	public function get_items() {
		$response = $this->send_request( Request::TYPE_GET, $this->customer_route( 'items' ) );

		if ( $response->status === 200 ) {
			return (array) $response->body;
		}

		throw new Exception(
			sprintf(
				__( 'Failed to get items from the API: %s', 'pr-shipping-dhl' )
			),
			implode( ', ', $response->body->messages )
		);
	}

	/**
	 * Prepares an API route with the customer namespace and EKP.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $route The route to prepare.
	 *
	 * @return string
	 */
	protected function customer_route( $route ) {
		return sprintf( 'customers/%s/%s', $this->ekp, $route );
	}
}
