<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_GeoLocation')) :

class DHLPWC_Model_API_Data_GeoLocation extends DHLPWC_Model_API_Data_Abstract
{

    public $latitude;
    public $longitude;

}

endif;
