<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Product')) :

class DHLPWC_Model_API_Data_Product extends DHLPWC_Model_API_Data_Abstract
{

    public $key;
    public $label;
    public $code;
    public $menu_code;
    public $business_product;
    public $mono_collo_product;
    public $software_characteristic;
    public $return_product;

}

endif;
