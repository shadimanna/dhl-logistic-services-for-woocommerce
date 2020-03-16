<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Data_Notice_Custom_Links')) :

class DHLPWC_Model_Data_Notice_Custom_Links extends DHLPWC_Model_Core_Arraymap_Abstract
{

    public $url;
    public $message;
    public $target;

}

endif;
