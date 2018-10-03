<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_External_GeoLocation')) :

class DHLPWC_Model_API_External_GeoLocation extends DHLPWC_Model_API_External_Abstract
{

    public $latitude;
    public $longitude;

}

endif;
