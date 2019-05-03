<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Data_Condition_Rule')) :

class DHLPWC_Model_Data_Condition_Rule extends DHLPWC_Model_Core_Arraymap_Abstract
{

    const INPUT_TYPE_WEIGHT = 'weight';
    const INPUT_TYPE_CART_TOTAL = 'cart_total';

    const INPUT_ACTION_DISABLE = 'disable';
    const INPUT_ACTION_CHANGE_PRICE = 'change_price';
    const INPUT_ACTION_FEE = 'add_fee';
    const INPUT_ACTION_FEE_REPEAT = 'add_fee_repeat';

    public $input_type;
    public $input_data;
    public $input_action;
    public $input_action_data;

}

endif;
