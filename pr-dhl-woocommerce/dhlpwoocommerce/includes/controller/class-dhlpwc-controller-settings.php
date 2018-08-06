<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Settings')) :

class DHLPWC_Controller_Settings
{

    public function __construct()
    {
        add_filter('woocommerce_shipping_methods', array($this, 'add_shipping_method'), 10, 1);
    }

    public function add_shipping_method($methods)
    {
        $methods['dhlpwc'] = 'DHLPWC_Model_WooCommerce_Settings_Shipping_Method';
        return $methods;
    }

}

endif;
