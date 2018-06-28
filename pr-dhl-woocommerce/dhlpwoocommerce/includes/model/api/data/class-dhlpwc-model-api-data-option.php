<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Option')) :

class DHLPWC_Model_API_Data_Option extends DHLPWC_Model_API_Data_Abstract
{

    protected $array_class_map = array(
        'exclusions'     => 'DHLPWC_Model_API_Data_Option'
    );

    public $key;
    public $description;
    public $rank;
    public $code;
    public $input_type;
    public $input_max;

    public $exclusions;

}

endif;
