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

    public function sort_rates($rates)
    {
        if (!$rates || !is_array($rates)) {
            return $rates;
        }

        $logic = DHLPWC_Model_Logic_Access_Control::instance();
        $custom_sort = $logic->check_custom_sort();

        switch ($custom_sort) {
            case DHLPWC_Model_WooCommerce_Settings_Shipping_Method::SORT_COST_LOW:
                $rates = $this->sort_rates_by_cost($rates);
                break;
            case DHLPWC_Model_WooCommerce_Settings_Shipping_Method::SORT_COST_HIGH:
                $rates = $this->sort_rates_by_cost($rates, false);
                break;
            case DHLPWC_Model_WooCommerce_Settings_Shipping_Method::SORT_CUSTOM:
                $rates = $this->sort_rates_by_position_setting($rates);
                break;
        }

        return $rates;
    }

    public function get_presets()
    {
        return array(
            array(
                'frontend_id' => 'parcelshop',
                'setting_id' => 'parcelshop',
                'title' => __('ServicePoint delivery', 'dhlpwc'),
                'options' => array(
                    DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS,
                ),
            ),
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
            // Home, Evening and Same Day set for No Neighbours, Delivery Times.
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
        );
    }

    protected function sort_rates_by_position_setting($rates)
    {
        $filter = array();
        foreach ($rates as $rate) {
            /** @var WC_Shipping_Rate $rate */
            if ($rate->get_method_id() != 'dhlpwc') {
                $filter[] = 0;
            } else {
                $preset = $this->find_rate($rate->get_id());
                if ($preset) {
                    $filter[] = $this->get_sort_position($rate);
                } else {
                    $filter[] = 0;
                }
            }
        }

        array_multisort($filter, $rates);

        return $rates;
    }

    protected function sort_rates_by_cost($rates, $asc = true)
    {
        $cost_filter = array();
        foreach ($rates as $rate) {
            /** @var WC_Shipping_Rate $rate */
            $cost_filter[] = $rate->cost;
        }

        $sort = ($asc === true) ? SORT_ASC : SORT_DESC;

        array_multisort($cost_filter, $sort, $rates);
        return $rates;
    }

    /**
     * @param WC_Shipping_Rate $rate
     * @return int
     */
    protected function get_sort_position($rate)
    {
        $meta_data = $rate->get_meta_data();

        if (empty($meta_data) || !is_array($meta_data)) {
            return 0;
        }

        if (!array_key_exists('sort_position', $meta_data)) {
            return 0;
        }

        return intval($meta_data['sort_position']);
    }

    /**
     * @param $frontend_id
     * @return DHLPWC_Model_Meta_Shipping_Preset|null
     */
    protected function find_rate($frontend_id)
    {
        $presets = $this->get_presets();
        foreach($presets as $preset_data)
        {
            $preset = new DHLPWC_Model_Meta_Shipping_Preset($preset_data);
            if ('dhlpwc-' . $preset->frontend_id === $frontend_id) {
                return $preset;
            }
        }
        return null;
    }

}

endif;
