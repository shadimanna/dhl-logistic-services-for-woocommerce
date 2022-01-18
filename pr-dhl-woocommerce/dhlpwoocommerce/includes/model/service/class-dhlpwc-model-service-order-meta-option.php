<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Order_Meta_Option')) :

class DHLPWC_Model_Service_Order_Meta_Option extends DHLPWC_Model_Core_Singleton_Abstract
{

    const ORDER_OPTION_PREFERENCES = '_dhlpwc_order_option_preferences';
    const ORDER_CONNECTORS_DATA = '_dhlpwc_order_connectors_data';

    public function save_option_preference($order_id, $option, $input = null)
    {
        $meta_object = new DHLPWC_Model_Meta_Order_Option_Preference(array(
            'key' => $option,
            'input' => $input,
        ));
        return DHLPWC_Model_Logic_Order_Meta::instance()->add_to_stack(
            self::ORDER_OPTION_PREFERENCES, $order_id, $option, $meta_object
        );
    }

    public function get_option_preference($order_id, $option)
    {
        return DHLPWC_Model_Logic_Order_Meta::instance()->get_from_stack(
            self::ORDER_OPTION_PREFERENCES, $order_id, $option
        );
    }

    public function remove_option_preference($order_id, $option)
    {
        return DHLPWC_Model_Logic_Order_Meta::instance()->remove_from_stack(
            self::ORDER_OPTION_PREFERENCES, $order_id, $option
        );
    }

    public function get_all($order_id)
    {
        return DHLPWC_Model_Logic_Order_Meta::instance()->get_stack(
            self::ORDER_OPTION_PREFERENCES, $order_id
        );
    }

    public function get_keys($order_id)
    {
        $options_data = $this->get_all($order_id);
        $options = array();
        foreach($options_data as $option_data) {
            $option = new DHLPWC_Model_Meta_Order_Option_Preference($option_data);
            $options[] = $option->key;
        }
        return $options;
    }

    public function filter_priority_options($options, $reverse = false)
    {
        $priority_options = array();
        foreach($options as $option) {
            $is_priority = in_array($option, array(
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS,
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_BP,
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_H
            ));

            if ($is_priority != $reverse) {
                $priority_options[] = $option;
            }
        }
        return $priority_options;
    }

    public function check_exclusion($option_key, $order_id, $options, $to_business)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $allowed_shipping_options = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CAPABILITY_ORDER_OPTIONS, array(
            'order_id'    => $order_id,
            'options'     => $options,
            'to_business' => $to_business,
        ));

        $exclusions = $this->get_exclusions($allowed_shipping_options, $options);

        if (!array_key_exists($option_key, $allowed_shipping_options)
            || in_array($option_key, $exclusions)) {
            return false;
        }

        return true;
    }

    public function default_signature($order_id, $options, $to_business)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $send_signature_checked = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEFAULT_SEND_SIGNATURE);
        if (!$send_signature_checked) {
            return false;
        }

        $allowed_shipping_options = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CAPABILITY_ORDER_OPTIONS, array(
            'order_id'    => $order_id,
            'options'     => $options,
            'to_business' => $to_business,
        ));

        $exclusions = $this->get_exclusions($allowed_shipping_options, $options);

        // Disable automatic checking of send signature if there are no parceltypes for it
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_HANDT, $allowed_shipping_options)
            || in_array(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_HANDT, $exclusions)) {
            return false;
        }

        return true;
    }

    public function default_age_check($order_id, $options, $to_business)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $send_age_check_checked = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEFAULT_AGE_CHECK);
        if (!$send_age_check_checked) {
            return false;
        }

        $allowed_shipping_options = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CAPABILITY_ORDER_OPTIONS, array(
            'order_id'    => $order_id,
            'options'     => $options,
            'to_business' => $to_business,
        ));

        $exclusions = $this->get_exclusions($allowed_shipping_options, $options);

        // Disable automatic checking of age check if there are no parceltypes for it
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_AGE_CHECK, $allowed_shipping_options)
            || in_array(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_AGE_CHECK, $exclusions)) {
            return false;
        }

        return true;
    }

    public function default_order_id_reference($order_id, $options, $to_business)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $order_id_reference_checked = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEFAULT_ORDER_ID_REFERENCE);
        if (!$order_id_reference_checked) {
            return false;
        }

        $allowed_shipping_options = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CAPABILITY_ORDER_OPTIONS, array(
            'order_id'    => $order_id,
            'options'     => $options,
            'to_business' => $to_business,
        ));

        $exclusions = $this->get_exclusions($allowed_shipping_options, $options);

        // Disable automatic checking of order id reference if there are no parceltypes for it
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE, $allowed_shipping_options)
            || in_array(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE, $exclusions)) {
            return false;
        }

        return true;
    }

    public function default_order_id_reference2($order_id, $options, $to_business)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $order_id_reference2_checked = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEFAULT_ORDER_ID_REFERENCE2);
        if (!$order_id_reference2_checked) {
            return false;
        }

        $allowed_shipping_options = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CAPABILITY_ORDER_OPTIONS, array(
            'order_id'    => $order_id,
            'options'     => $options,
            'to_business' => $to_business,
        ));

        $exclusions = $this->get_exclusions($allowed_shipping_options, $options);

        // Disable automatic checking of order id reference2 if there are no parceltypes for it
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE2, $allowed_shipping_options)
            || in_array(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE2, $exclusions)) {
            return false;
        }

        return true;
    }

    public function default_return($order_id, $options, $to_business)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $return_checked = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEFAULT_RETURN);
        if (!$return_checked) {
            return false;
        }

        $allowed_shipping_options = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CAPABILITY_ORDER_OPTIONS, array(
            'order_id'    => $order_id,
            'options'     => $options,
            'to_business' => $to_business,
        ));

        $exclusions = $this->get_exclusions($allowed_shipping_options, $options);

        // Disable automatic checking of add return label if there are no parceltypes for it
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_ADD_RETURN_LABEL, $allowed_shipping_options)
            || in_array(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_ADD_RETURN_LABEL, $exclusions)) {
            return false;
        }

        return true;
    }

    public function send_with_bp($order_id)
    {
        /** @var WC_Order $order */
        $order = new WC_Order($order_id);
        $eligible = true;
        $fill_percentage = 0;

        $items = $order->get_items();
        if (count($items) < 1) {
            return false;
        }

        foreach($items as $item) {
            if (get_post_meta($item['product_id'], 'dhlpwc_send_with_bp', true) !== 'yes') {
                $eligible = false;
                break;
            } else {
                $quantity = $item['quantity'];
                $count = intval(get_post_meta($item['product_id'], 'dhlpwc_send_with_bp_count', true));
                if ($count < 1) {
                    $count = 1;
                }

                $fill_percentage += 1 / $count * $quantity * 100;

                // If the total volume (calculated based on 'count' setting) exceeds 1 package, do not use BP
                if ($fill_percentage > 100) {
                    $eligible = false;
                    break;
                }

                // If mixing products is not allowed, the order should not have other products
                if (get_post_meta($item['product_id'], 'dhlpwc_send_with_bp_mix', true) !== 'yes' && count($items) > 1) {
                    $eligible = false;
                    break;
                }
            }
        }

        return $eligible;
    }

    public function get_parcelshop($order_id)
    {
        /** @var WC_Order $order */
        $order = new WC_Order($order_id);

        $service = DHLPWC_Model_Service_Order_Meta_Option::instance();
        $parcelshop_meta = $service->get_option_preference($order->get_id(), DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS);

        if (!$parcelshop_meta) {
            return null;
        }

        $service = DHLPWC_Model_Service_Parcelshop::instance();
        if (is_callable(array($order, 'get_shipping_country'))) {
            // WooCommerce 3.2.0+
            $parcelshop = $service->get_parcelshop($parcelshop_meta['input'], $order->get_shipping_country());
        } else {
            // WooCommerce < 3.2.0
            $parcelshop = $service->get_parcelshop($parcelshop_meta['input'], $order->shipping_country);
        }
        if (!$parcelshop || !isset($parcelshop->name) || !isset($parcelshop->address)) {
            return null;
        }

        return $parcelshop;
    }

    public function add_key_value_to_stack($key, $value, &$array)
    {
        if (!is_array($array)) {
            if (!$array) {
                $array = array();
            } else {
                return $array;
            }
        }

        if (!in_array($key, $array)) {
            $array[$key] = $value;
        }
        return $array;
    }

    public function add_key_to_stack($key, &$array)
    {
        if (!is_array($array)) {
            if (!$array) {
                $array = array();
            } else {
                return $array;
            }
        }

        if (!in_array($key, $array)) {
            $array[] = $key;
        }
        return $array;
    }

    public function update_connectors_data($order_id, $value, $is_option = true)
    {
        $connectors_data = DHLPWC_Model_Logic_Order_Meta::instance()->get_stack(self::ORDER_CONNECTORS_DATA, $order_id);
        if ($is_option) {
            $options = array();
            if (isset($connectors_data['options'])) {
                $options = explode(',', $connectors_data['options']);
            }
            if (!in_array($value, $options)) {
                $options[] = $value;
            }
            $connectors_data['options'] = implode(',', $options);
        } else {
            $connectors_data['id'] = $value;
        }
        update_post_meta($order_id, self::ORDER_CONNECTORS_DATA, $connectors_data);
    }

    protected function get_exclusions($allowed_shipping_options, $options)
    {
        $exclusions = [];
        foreach ($options as $option) {
            if (array_key_exists($option, $allowed_shipping_options)) {
                foreach ($allowed_shipping_options[$option]->exclusions as $exclusion) {
                    if (!in_array($exclusion, $exclusions)) {
                        $exclusions[] = $exclusion->key;
                    }
                }
            }
        }
        return $exclusions;
    }

}

endif;
