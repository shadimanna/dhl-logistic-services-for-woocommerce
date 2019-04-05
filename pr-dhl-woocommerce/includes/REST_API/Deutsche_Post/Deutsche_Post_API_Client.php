<?php

namespace PR\DHL\REST_API\Deutsche_Post;

use PR\DHL\REST_API\API_Client;
use PR\DHL\REST_API\Request;

class Deutsche_Post_API_Client extends API_Client {

	public function create_item( $customer_ekp ) {
		$data = array(
			'id'                  => 1,
			'barcode'             => 'BC123456789',
			'product'             => 'GPP',
			'serviceLevel'        => 'PRIORITY',
			'custRef'             => 'REF-2361890-AB',
			'recipient'           => 'Miguel Muscat',
			'recipientPhone'      => '+35679965506',
			'recipientEmail'      => 'miguel.muscat@rebelcode.com',
			'addressLine1'        => '22 Block 3 Flat 4',
			'addressLine2'        => 'Vittorjo Cassar Street',
			'city'                => 'Marsascala',
			'postalCode'          => 'MSK 3703',
			'state'               => 'None',
			'destinationCountry'  => 'MT',
			'shipmentAmount'      => 85,
			'shipmentCurrency'    => 'EUR',
			'shipmentGrossWeight' => 1500,
			'returnItemWanted'    => false,
			'shipmentNaturetype'  => 'GIFT',
			'contents'            => array(
				array(
					'contentPieceDescription' => 'Trousers',
					'contentPieceValue'       => 85,
					'contentPieceNetweight'   => 1200,
					'contentPieceOrigin'      => 'DE',
					'contentPieceAmount'      => 2,
				),
			),
		);

		return $this->send_request( Request::TYPE_POST, '/customers/' . $customer_ekp . '/items', $data );
	}

	public function get_items( $customer_ekp ) {
		return $this->send_request( Request::TYPE_GET, $this->customer_route( $customer_ekp, 'items' ) );
	}

	protected function customer_route( $customer_ekp, $route ) {
		return sprintf( 'customers/%s/%s', $customer_ekp, $route );
	}
}
