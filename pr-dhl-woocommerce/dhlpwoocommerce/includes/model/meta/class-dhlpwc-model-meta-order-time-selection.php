<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Meta_Order_Time_Selection')) :

class DHLPWC_Model_Meta_Order_Time_Selection extends DHLPWC_Model_Meta_Abstract
{

    public $date;
    public $start_time;
    public $end_time;
    public $timestamp;

}

endif;
