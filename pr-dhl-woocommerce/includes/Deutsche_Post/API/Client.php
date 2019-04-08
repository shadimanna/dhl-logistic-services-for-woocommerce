<?php

namespace PR\DHL\Deutsche_Post\API;

use Exception;
use PR\DHL\Deutsche_Post\Deutsche_Post_Label;
use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Request;

class Client extends API_Client {
	public function generate_label( Deutsche_Post_Label $label ) {
		$args = $label->get_args();
		$order = $args['order_details'];
		$shipping = $args['shipping_address'];

		$contents_data = array();

		// Add customs info
		if ( PR_DHL()->is_crossborder_shipment( $shipping['country'] ) ) {
			foreach ( $args['items'] as $item ) {
				$contents_data[] = array(
					'contentPieceDescription' => $item['description'],
					'contentPieceValue'       => $item['value'],
					'contentPieceNetweight'   => $item['weight'],
					'contentPieceAmount'      => $item['qty'],
					'contentPieceOrigin'      => $item['origin'],
				);
			}
		}

		$data = array(
			'serviceLevel'        => 'PRIORITY',
			'product'             => $order['dhl_product'],
			'recipient'           => $shipping['name'],
			'recipientPhone'      => $shipping['phone'],
			'recipientEmail'      => $shipping['email'],
			'addressLine1'        => $shipping['address_1'],
			'addressLine2'        => $shipping['address_2'],
			'city'                => $shipping['city'],
			'postalCode'          => $shipping['postcode'],
			'state'               => $shipping['state'],
			'destinationCountry'  => $shipping['country'],
			'shipmentAmount'      => 85,
			'shipmentCurrency'    => $order['currency'],
			'shipmentGrossWeight' => $order['weight'] * 1000.0,
			'contents'            => $contents_data,
		);

		$body = json_encode( $data );

		$this->send_request( Request::TYPE_POST, '/customers/' );

		$request = new Request( Request::TYPE_POST, $this->prepare_url( '/items/' ), array(), $body );

		return $request;
	}

	public function create_item( $customer_ekp, $item_data ) {
		$response = $this->send_request(
			Request::TYPE_POST,
			$this->customer_route( $customer_ekp, 'items' ),
			$item_data
		);

		if ( $response->status !== 200 ) {
			$message = ! empty( $response->body->messages )
				? implode( ', ', $response->body->messages )
				: '';

			throw new Exception(
				sprintf( __( 'API error: %s', 'pr-shipping-dhl' ), $message )
			);
		}

		return $response->body;
	}

	public function get_items( $customer_ekp ) {
		return $this->send_request( Request::TYPE_GET, $this->customer_route( $customer_ekp, 'items' ) );
	}

	protected function customer_route( $customer_ekp, $route ) {
		return sprintf( 'customers/%s/%s', $customer_ekp, $route );
	}
}
