<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Order_Meta_Option')) :

class DHLPWC_Model_Service_Order_Meta_Option extends DHLPWC_Model_Core_Singleton_Abstract
{

    const ORDER_OPTION_PREFERENCES = '_dhlpwc_order_option_preferences';

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

}

endif;
