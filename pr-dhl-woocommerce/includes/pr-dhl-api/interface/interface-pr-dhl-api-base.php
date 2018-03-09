<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

interface PR_DHL_API_Base {

	public function dhl_test_connection( $client_id, $client_secret );

	public function dhl_validate_field( $key, $value );
}
