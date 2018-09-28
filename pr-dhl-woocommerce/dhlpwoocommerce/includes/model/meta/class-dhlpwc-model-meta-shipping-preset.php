<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Meta_Shipping_Preset')) :

class DHLPWC_Model_Meta_Shipping_Preset extends DHLPWC_Model_Meta_Abstract
{

    public $frontend_id;
    public $setting_id;
    public $title;
    public $options;

}

endif;
