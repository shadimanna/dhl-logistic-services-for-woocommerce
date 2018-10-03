<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_External_Terminal')) :

class DHLPWC_Model_API_External_Terminal extends DHLPWC_Model_API_External_Abstract
{

    protected $class_map = array(
        'address'     => 'DHLPWC_Model_API_External_Address',
        'geo_location' => 'DHLPWC_Model_API_External_GeoLocation',
    );

    public $id;
    public $name;
    /** @var DHLPWC_Model_API_External_Address $address */
    public $address;
    /** @var DHLPWC_Model_API_External_GeoLocation $geo_location */
    public $geo_location;

}

endif;
