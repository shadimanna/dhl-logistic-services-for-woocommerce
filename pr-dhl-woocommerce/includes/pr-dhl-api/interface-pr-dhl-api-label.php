<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

interface PR_DHL_API_Label {

	public function get_dhl_label( $args );

	public function delete_dhl_label( $args );

	public function dhl_test_connection( $client_id, $client_secret );

	public function dhl_validate_field( $key, $value );
}
