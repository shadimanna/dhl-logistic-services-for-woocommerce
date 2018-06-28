<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_ParcelShop_OpeningTimes')) :

class DHLPWC_Model_API_Data_ParcelShop_OpeningTimes extends DHLPWC_Model_API_Data_Abstract
{

    public $time_from;
    public $time_to;
    public $week_day;

}

endif;
