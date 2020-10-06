<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Settings')) :

class DHLPWC_Model_Service_Settings extends DHLPWC_Model_Core_Singleton_Abstract
{
    public function get_maps_key()
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');
        if (empty($shipping_method['google_maps_key'])) {
            return null;
        }
        return $shipping_method['google_maps_key'];
    }

    public function get_api_account()
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');
        if (!isset($shipping_method['account_id'])) {
            return null;
        }
        return $shipping_method['account_id'];
    }

    public function get_api_user()
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');
        if (!isset($shipping_method['user_id'])) {
            return null;
        }
        return $shipping_method['user_id'];
    }

    public function get_api_key()
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');
        if (!isset($shipping_method['key'])) {
            return null;
        }
        return $shipping_method['key'];
    }

    public function country_is_set()
    {
        $logic = DHLPWC_Model_Logic_Access_Control::instance();
        return $logic->check_application_country();
    }

    public function plugin_is_enabled()
    {
        $logic = DHLPWC_Model_Logic_Access_Control::instance();
        return $logic->check_enabled();
    }

    public function get_default_address($prefix = null)
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');

        // Backwards compatibility checks
        if (!isset($shipping_method[$prefix.'addition'])) {
            $shipping_method[$prefix.'addition'] = '';
        }

        return new DHLPWC_Model_Meta_Address(array(
            'first_name' => $shipping_method[$prefix.'first_name'],
            'last_name'  => $shipping_method[$prefix.'last_name'],
            'company'    => $shipping_method[$prefix.'company'],

            'country'    => $prefix !== 'hide_sender_address_' ? $shipping_method[$prefix.'country'] : $shipping_method['country'],
            'postcode'   => $shipping_method[$prefix.'postcode'],
            'city'       => $shipping_method[$prefix.'city'],
            'street'     => $shipping_method[$prefix.'street'],
            'number'     => $shipping_method[$prefix.'number'],
            'addition'   => $shipping_method[$prefix.'addition'],

            'email'      => $shipping_method[$prefix.'email'],
            'phone'      => $shipping_method[$prefix.'phone'],
        ));
    }

    public function get_return_address()
    {
        return $this->get_default_address('return_address_');
    }

    public function get_hide_sender_address()
    {
        return $this->get_default_address('hide_sender_address_');
    }

    public function get_printer_id()
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');
        if (!isset($shipping_method['printer_id'])) {
            return null;
        }
        return $shipping_method['printer_id'];
    }

    public function format_settings($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (strpos($key, 'price_option_') !== false) {
                $data[$key] = wc_format_localized_price($value);
            }

            if (strpos($key, 'option_condition_') !== false) {
                $data[$key] = $this->format_option_conditions($value);
            }
        }

        return $data;
    }

    protected function format_option_conditions($value)
    {
        $conditions = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        if (!$this->is_iterable($conditions)) {
            return $value;
        }

        foreach ($conditions as $key => $condition) {
            $conditions[$key] = $this->format_option_condition($condition);
        }

        return json_encode($conditions);
    }

    protected function is_iterable($var)
    {
        return is_array($var)
            || $var instanceof Traversable
            || $var instanceof Iterator
            || $var instanceof IteratorAggregate;
    }

    protected function format_option_condition($condition)
    {
        if (!is_array($condition)) {
            return $condition;
        }

        if (array_key_exists('input_data', $condition)) {
            $condition['input_data'] = wc_format_localized_price($condition['input_data']);
        }

        if (array_key_exists('input_action_data', $condition)) {
            $condition['input_action_data'] = wc_format_localized_price($condition['input_action_data']);
        }

        return $condition;
    }

    public function update_settings($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (strpos($key, 'price_option_') !== false) {
                $data[$key] = wc_format_decimal($value);
            }

            if (strpos($key, 'option_condition_') !== false) {
                $data[$key] = $this->update_option_conditions($value);
            }
        }

        return $data;
    }

    protected function update_option_conditions($value)
    {
        $conditions = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        foreach ($conditions as $key => $condition) {
            $conditions[$key] = $this->update_option_condition($condition);
        }

        return json_encode($conditions);
    }

    protected function update_option_condition($condition)
    {
        if (!is_array($condition)) {
            return $condition;
        }

        if (array_key_exists('input_data', $condition)) {
            $condition['input_data'] = wc_format_decimal($condition['input_data']);
        }

        if (array_key_exists('input_action_data', $condition)) {
            $condition['input_action_data'] = wc_format_decimal($condition['input_action_data']);
        }

        return $condition;
    }
}

endif;
