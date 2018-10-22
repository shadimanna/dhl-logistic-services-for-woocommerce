<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Data_Delivery_Time')) :

class DHLPWC_Model_Data_Delivery_Time extends DHLPWC_Model_Core_Arraymap_Abstract
{

    protected $class_map = array(
        'source'     => 'DHLPWC_Model_API_Data_Time_window',
    );

    /** @var  DHLPWC_Model_API_Data_Time_Window $source */
    public $source;

    public $date;
    public $week_day;
    public $day;
    public $month;
    public $year;

    public $start_time;
    public $end_time;

    public $selected;
    public $identifier;
    public $preset_frontend_id;

}

endif;
