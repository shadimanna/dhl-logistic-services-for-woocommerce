<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Shipping_Preset')) :

class DHLPWC_Model_Service_Shipping_Preset extends DHLPWC_Model_Core_Singleton_Abstract
{

    /**
     * @param $setting_id
     * @return DHLPWC_Model_Meta_Shipping_Preset|null
     */
    public function find_preset($setting_id)
    {
        $presets = $this->get_presets();
        foreach($presets as $preset_data)
        {
            $preset = new DHLPWC_Model_Meta_Shipping_Preset($preset_data);
            if ($preset->setting_id === $setting_id) {
                return $preset;
            }
        }
        return null;
    }

    public function get_presets()
    {
        return array(
            // Home, Evening and Same Day are also part of Delivery Times.
            array(
                'frontend_id' => 'home',
                'setting_id' => 'home',
                'title' => __('Door delivery', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
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
                'frontend_id' => 'home-no-neighbour',
                'setting_id' => 'no_neighbour',
                'title' => __('Door delivery, avoid dropping at neighbours', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_NBB,
                ),
            ),
            array(
                'frontend_id' => 'home-no-neighbour-evening',
                'setting_id' => 'no_neighbour_evening',
                'title' => __('Door delivery in the evening, avoid dropping at neighbours', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_NBB,
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_EVE,
                ),
            ),
            array(
                'frontend_id' => 'home-no-neighbour-same-day',
                'setting_id' => 'no_neighbour_same_day',
                'title' => __('Door delivery today, avoid dropping at neighbours', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_NBB,
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
