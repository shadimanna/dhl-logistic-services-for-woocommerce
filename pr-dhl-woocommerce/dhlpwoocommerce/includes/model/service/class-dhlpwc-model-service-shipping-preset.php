<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Shipping_Preset')) :

class DHLPWC_Model_Service_Shipping_Preset extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function get_presets()
    {
        return array(
            array(
                'frontend_id' => 'home',
                'setting_id' => 'home',
                'title' => __('Door delivery', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                ),
            ),
            array(
                'frontend_id' => 'home-no-neighbour',
                'setting_id' => 'no_neighbour',
                'title' => __('Door delivery, avoid dropping at neighbours', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_NBB,
                ),
            ),
            array(
                'frontend_id' => 'home-evening',
                'setting_id' => 'evening',
                'title' => __('Door delivery in the evening', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_EVE,
                ),
            ),
            array(
                'frontend_id' => 'home-same-day',
                'setting_id' => 'same_day',
                'title' => __('Door delivery today', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_SDD,
                ),
            ),
            array(
                'frontend_id' => 'home-saturday',
                'setting_id' => 'saturday',
                'title' => __('Door delivery on Saturdays', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_S,
                ),
            ),
            array(
                'frontend_id' => 'home-morning',
                'setting_id' => 'morning',
                'title' => __('Door delivery before 11:00AM', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_EXP,
                ),
            ),
//
//        array(
//            'frontend_id' => 'terminal',
//            'setting_id' => 'terminal',
//            'title' => __('Terminal delivery', 'dhlpwc'),
//            'options' => array(
//                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_H,
//            ),
//        ),

            array(
                'frontend_id' => 'parcelshop',
                'setting_id' => 'parcelshop',
                'title' => __('ServicePoint delivery', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS,
                ),
            ),
        );
    }

}

endif;
