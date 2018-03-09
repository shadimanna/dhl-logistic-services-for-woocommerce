<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

interface PR_DHL_API_Rate {

	public function get_dhl_rates( $args );
}
