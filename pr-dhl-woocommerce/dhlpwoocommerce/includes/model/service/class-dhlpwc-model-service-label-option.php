<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Label_Option')) :

class DHLPWC_Model_Service_Label_Option extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function get_selectable_options($order_id, $send_signature_checked = false)
    {
        $service = DHLPWC_Model_Service_Order_Meta_Option::instance();
        $customer_options = $service->get_all($order_id);

        $option_data = array();
        foreach($customer_options as $customer_option) {
            $option = new DHLPWC_Model_Meta_Order_Option_Preference($customer_option);
            $option_data[] = array(
                'description' => __(sprintf('OPTION_%s', $option->key), 'dhlpwc'),
                'option' => $option,
                'checked' => true,
            );
        }

        // Custom functionality: Always add home delivery
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR, $customer_options)) {
            $this->add_option_to_array(
                $option_data,
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                false,
                true
            );
        }

        // Custom functionality: Add signed option
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_HANDT, $customer_options)) {
            $this->add_option_to_array(
                $option_data,
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_HANDT,
                $send_signature_checked
            );
        }

        // Custom functionality: Add letter mail option
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_BP, $customer_options)) {
            $this->add_option_to_array(
                $option_data,
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_BP
            );
        }

        return $option_data;
    }

    protected function add_option_to_array(&$data_array, $option_key, $checked = false, $prepend = false)
    {
        $option = array(
            'description' => __(sprintf('OPTION_%s', $option_key), 'dhlpwc'),
            'option' => new DHLPWC_Model_Meta_Order_Option_Preference(array(
                'key' => $option_key
            )),
            'checked' => $checked,
        );

        if (!$prepend) {
            $data_array[] = $option;
        } else {
            array_unshift($data_array, $option);
        }
    }

    public function get_request_options($order_id, $keys = array())
    {
        $service = DHLPWC_Model_Service_Order_Meta_Option::instance();

        $request_options = array();
        foreach($keys as $key) {
            switch($key) {
                case DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS:
                    // Use the pre-configured parcelshop data
                    $meta = $service->get_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS);
                    $option_preference = new DHLPWC_Model_Meta_Order_Option_Preference($meta);
                    if ($option_preference) {
                        $option = new DHLPWC_Model_API_Data_Label_Option(array(
                            'key'   => $key,
                            'input' => $option_preference->input,
                        ));
                        $request_options[] = $option->to_array();
                    }
                    break;
                default:
                    $option = new DHLPWC_Model_API_Data_Label_Option(array(
                        'key'   => $key,
                    ));
                    $request_options[] = $option->to_array();
            }
        }

        return $request_options;
    }

}

endif;
