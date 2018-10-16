<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Option')) :

class DHLPWC_Model_API_Data_Option extends DHLPWC_Model_API_Data_Abstract
{

    const OPTION_TYPE_SERVICE = 'SERVICE_OPTION';
    const OPTION_TYPE_DELIVERY = 'DELIVERY_OPTION';

    const INPUT_TYPE_NUMBER = 'number';
    const INPUT_TYPE_TEXT = 'text';
    const INPUT_TYPE_ADDRESS = 'address';

    const INPUT_TEMPLATE_DOUBLE_TEXT = 'double-text';
    const INPUT_TEMPLATE_TEXT = 'text';
    const INPUT_TEMPLATE_PARCELSHOP = 'parcelshop';
    const INPUT_TEMPLATE_TERMINAL = 'terminal';
    const INPUT_TEMPLATE_PRICE = 'price';
    const INPUT_TEMPLATE_ADDRESS = 'address';


    protected $array_class_map = array(
        'exclusions'     => 'DHLPWC_Model_API_Data_Option'
    );

    protected $ignore_null_map = array(
        'image_url',
        'exclusion_list',
        'preselected',
    );

    public $key;
    public $description;
    public $rank;
    public $code;
    public $input_type;
    public $input_max;
    public $option_type;

    public $exclusions;

    /* Custom */
    public $image_url;
    public $exclusion_list;
    public $preselected;
    public $input_template;
    public $input_template_data;

}

endif;
