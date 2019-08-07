<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Condition_Rule')) :

class DHLPWC_Model_Service_Condition_Rule extends DHLPWC_Model_Core_Singleton_Abstract
{

    /**
     * @param $price
     * @param $conditions_input
     * @return mixed
     */
    public function calculate_price($price, $conditions_input, $cart_subtotal = 0)
    {
        $conditions_data = json_decode($conditions_input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $price;
        }

        $conditions = $this->applicable_conditions($conditions_data, $cart_subtotal);
        foreach ($conditions as $condition) {
            $price = $this->update_price($price, $condition, $cart_subtotal);
        }

        return $price;
    }

    /**
     * @param $conditions_input
     * @return bool
     */
    public function is_disabled($conditions_input, $cart_subtotal = 0)
    {
        $conditions_data = json_decode($conditions_input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        $conditions = $this->applicable_conditions($conditions_data, $cart_subtotal);
        foreach ($conditions as $condition) {
            if ($condition->input_action === DHLPWC_Model_Data_Condition_Rule::INPUT_ACTION_DISABLE) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $conditions_data
     * @return DHLPWC_Model_Data_Condition_Rule[]
     */
    protected function applicable_conditions($conditions_data, $cart_subtotal = 0)
    {
        if (!is_array($conditions_data)) {
            return [];
        }

        $cart = WC()->cart;
        // Can't calculate without a cart
        if (!$cart) {
            return [];
        }

        $conditions = array();
        foreach ($conditions_data as $condition_data) {
            $condition = new DHLPWC_Model_Data_Condition_Rule($condition_data);
            // Validate
            if (!array_key_exists($condition->input_type, $this->get_input_types())) {
                continue;
            }
            if (strlen($condition->input_data) === 0) { // null or empty string check only, 0 is not a false positive
                continue;
            }
            if (!array_key_exists($condition->input_action, $this->get_input_actions())) {
                continue;
            }
            if ($condition->input_action != DHLPWC_Model_Data_Condition_Rule::INPUT_ACTION_DISABLE && strlen($condition->input_action_data) === 0) {
                continue;
            }
            // Check if applicable
            if ($condition->input_type === DHLPWC_Model_Data_Condition_Rule::INPUT_TYPE_WEIGHT) {
                $weight = $cart->get_cart_contents_weight();
                if ($weight <= intval($condition->input_data)) {
                    continue;
                }
            } else if ($condition->input_type === DHLPWC_Model_Data_Condition_Rule::INPUT_TYPE_CART_TOTAL) {
                $subtotal = $cart_subtotal;
                if ($subtotal <= intval($condition->input_data)) {
                    continue;
                }
            }
            $conditions[] = $condition;
        }

        return $conditions;
    }

    /**
     * @param $price
     * @param DHLPWC_Model_Data_Condition_Rule $condition
     * @return mixed
     */
    protected function update_price($price, $condition, $cart_subtotal = 0)
    {
        if ($condition->input_action === DHLPWC_Model_Data_Condition_Rule::INPUT_ACTION_CHANGE_PRICE) {
            $price = floatval($condition->input_action_data);

        } else if ($condition->input_action === DHLPWC_Model_Data_Condition_Rule::INPUT_ACTION_FEE) {
            $price += floatval($condition->input_action_data);

        } else if ($condition->input_action === DHLPWC_Model_Data_Condition_Rule::INPUT_ACTION_FEE_REPEAT) {
            $cart = WC()->cart;
            if ($condition->input_type === DHLPWC_Model_Data_Condition_Rule::INPUT_TYPE_WEIGHT) {
                $weight = $cart->get_cart_contents_weight();
                while ($weight > floatval($condition->input_data)) {
                    $weight -= floatval($condition->input_data);
                    $price += floatval($condition->input_action_data);
                }
            } else if ($condition->input_type === DHLPWC_Model_Data_Condition_Rule::INPUT_TYPE_CART_TOTAL) {
                $subtotal = $cart_subtotal;
                while ($subtotal > floatval($condition->input_data)) {
                    $subtotal -= floatval($condition->input_data);
                    $price += floatval($condition->input_action_data);
                }
            }
        }

        return $price;
    }

    public function get_input_types()
    {
        return array(
            DHLPWC_Model_Data_Condition_Rule::INPUT_TYPE_WEIGHT     => __('Weight', 'dhlpwc'),
            DHLPWC_Model_Data_Condition_Rule::INPUT_TYPE_CART_TOTAL => __('Cart total', 'dhlpwc'),
        );
    }

    public function get_input_actions()
    {
        return array(
            DHLPWC_Model_Data_Condition_Rule::INPUT_ACTION_DISABLE     => __('Disable delivery option', 'dhlpwc'),
            DHLPWC_Model_Data_Condition_Rule::INPUT_ACTION_CHANGE_PRICE => __('Change price to', 'dhlpwc'),
            DHLPWC_Model_Data_Condition_Rule::INPUT_ACTION_FEE => __('Add additional fee', 'dhlpwc'),
            DHLPWC_Model_Data_Condition_Rule::INPUT_ACTION_FEE_REPEAT => __('Add fee (keep repeating)', 'dhlpwc'),
        );
    }

}

endif;
