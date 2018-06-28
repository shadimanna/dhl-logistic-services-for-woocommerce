<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Data_Postcode_Validation')) :

class DHLPWC_Model_Data_Postcode_Validation extends DHLPWC_Model_Core_Arraymap_Abstract
{

    protected $rename_map = array(
        'zipex' => 'example',
        'zip'   => 'expression',
        'fmt'   => 'format',
    );

    public $id;
    public $posturl;
    public $require;
    public $name;

    public $example;
    public $expression;
    public $fmt;

}

endif;