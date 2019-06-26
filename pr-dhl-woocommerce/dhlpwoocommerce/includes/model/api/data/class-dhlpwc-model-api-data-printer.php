<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Printer')) :

class DHLPWC_Model_API_Data_Printer extends DHLPWC_Model_API_Data_Abstract
{

    public $id;
    public $name;
    public $time_registered;

}

endif;
