<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Checkout')) :

class DHLPWC_Controller_Checkout
{

    public function __construct()
    {
        add_filter('woocommerce_validate_postcode', array($this, 'validate_postcode'), 10, 3);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'add_option_meta'), 10, 2);

        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPTION_PARCELSHOP)) {
            add_action('woocommerce_after_checkout_validation', array($this, 'validate_parcelshop_selection'), 10, 2);
        }
    }

    /**
     * Add The Netherlands, Belgium and Luxembourg to the postcode check (missing in default WooCommerce)
     *
     * @param $valid
     * @param $postcode
     * @param $country
     * @return bool
     */
    public function validate_postcode($valid, $postcode, $country)
    {
        switch ($country) {
            case 'NL' :
            case 'BE' :
            case 'LU' :
                $service = DHLPWC_Model_Service_Postcode::instance();
                $valid = $service->validate($postcode, $country);
                break;
        }
        return $valid;
    }

    public function add_option_meta($order_id, $data)
    {

        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-home', $data['shipping_method'])) {
            $service = new DHLPWC_Model_Service_Order_Meta_Option();
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR);
        }

        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-home-no-neighbour', $data['shipping_method'])) {
            $service = new DHLPWC_Model_Service_Order_Meta_Option();
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR);
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_NBB);
        }

        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-home-evening', $data['shipping_method'])) {
            $service = new DHLPWC_Model_Service_Order_Meta_Option();
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR);
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_EVE);
        }

        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-parcelshop', $data['shipping_method'])) {
            list($parcelshop_id, $country) = WC()->session->get('dhlpwc_parcelshop_passive_sync');
            $service = new DHLPWC_Model_Service_Order_Meta_Option();
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS, $parcelshop_id);
        }

    }

    public function validate_parcelshop_selection($data, $errors)
    {
        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-parcelshop', $data['shipping_method'])) {
            list($parcelshop_id, $country) = WC()->session->get('dhlpwc_parcelshop_passive_sync');
            if (empty($parcelshop_id) || empty($country)) {
                $errors->add('dhlpwc_parcelshop_passive_sync', __('Choose a DHL ServicePoint', 'dhlpwc'));
            }
            $shipping_country = WC()->customer->get_shipping_country();
            if ($country != $shipping_country) {
                $errors->add('dhlpwc_parcelshop_passive_sync_country', __('The DHL ServicePoint country cannot be different than the shipping address country.', 'dhlpwc'));
            }
        }
    }

}

endif;