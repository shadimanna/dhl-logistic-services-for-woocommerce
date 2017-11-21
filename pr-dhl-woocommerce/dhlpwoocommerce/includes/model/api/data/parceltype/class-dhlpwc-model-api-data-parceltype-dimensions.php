<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Parceltype_Dimensions')) :

class DHLPWC_Model_API_Data_Parceltype_Dimensions extends DHLPWC_Model_API_Data_Abstract
{

    public $max_length_cm;
    public $max_width_cm;
    public $max_height_cm;

}

endif;
