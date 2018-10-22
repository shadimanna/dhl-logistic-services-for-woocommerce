<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Time_Window')) :

class DHLPWC_Model_API_Data_Time_Window extends DHLPWC_Model_API_Data_Abstract
{

    public $postcode;
    public $delivery_date;
    public $type;
    public $start_time;
    public $end_time;
    public $status;

}

endif;
