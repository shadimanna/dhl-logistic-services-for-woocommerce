<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Checkout')) :

class DHLPWC_Controller_Checkout
{

    public function __construct()
    {
        add_filter('woocommerce_validate_postcode', array($this, 'validate_postcode'), 10, 3);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'add_option_meta'), 10, 2);

        add_action('wp_loaded', array($this, 'set_parcelshop_hooks'));
    }

    public function set_parcelshop_hooks()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CHECKOUT_PARCELSHOP)) {
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
            case 'CH' :
                $service = DHLPWC_Model_Service_Postcode::instance();
                $valid = $service->validate($postcode, $country);
                break;
        }
        return $valid;
    }

    public function add_option_meta($order_id, $data)
    {
        $service = DHLPWC_Model_Service_Shipping_Preset::instance();
        $presets = $service->get_presets();

        foreach($presets as $preset_data) {
            $preset = new DHLPWC_Model_Meta_Shipping_Preset($preset_data);

            if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-' . $preset->frontend_id, $data['shipping_method'])) {
                $meta_service = new DHLPWC_Model_Service_Order_Meta_Option();

                // Save preset data
                $delivery_times_service = DHLPWC_Model_Service_Delivery_Times::instance();
                if ($delivery_times_service->check_checkout_delivery_time_selected($preset)) {
                    $delivery_times_service->save_order_time_selection($order_id);
                }

                foreach($preset->options as $option) {
                    if ($option === DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS) {
                        $sync = WC()->session->get('dhlpwc_parcelshop_selection_sync');
                        if ($sync) {
                            list($parcelshop_id, $country_code, $postal_code_memory) = $sync;
                        } else {
                            list($parcelshop_id, $country_code, $postal_code_memory) = array(null, null, null);
                        }
                        unset($country_code, $postal_code_memory);

                        $meta_service->save_option_preference($order_id, $option, $parcelshop_id);
                    } else {
                        $meta_service->save_option_preference($order_id, $option);
                    }
                }
            }
        }
    }

    public function validate_parcelshop_selection($data, $errors)
    {
        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-parcelshop', $data['shipping_method'])) {
            $sync = WC()->session->get('dhlpwc_parcelshop_selection_sync');
            if ($sync) {
                list($parcelshop_id, $country_code, $postal_code_memory) = $sync;
            } else {
                list($parcelshop_id, $country_code, $postal_code_memory) = array(null, null, null);
            }
            unset($postal_code_memory);

            if (empty($parcelshop_id) || empty($country_code)) {
                $errors->add('dhlpwc_parcelshop_selection_sync', __('Choose a DHL ServicePoint', 'dhlpwc'));
            }
            $shipping_country = WC()->customer->get_shipping_country();
            if ($country_code != $shipping_country) {
                $errors->add('dhlpwc_parcelshop_selection_sync_country', __('The DHL ServicePoint country cannot be different than the shipping address country.', 'dhlpwc'));
            }
        }
    }

}

endif;