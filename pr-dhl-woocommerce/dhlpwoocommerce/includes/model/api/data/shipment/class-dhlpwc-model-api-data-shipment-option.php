<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Shipment_Option')) :

class DHLPWC_Model_API_Data_Shipment_Option extends DHLPWC_Model_API_Data_Abstract
{

    protected $ignore_null_map = array(
        'input',
    );

    public $key;
    public $input;

}

endif;
