<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Label_Address_Name')) :

class DHLPWC_Model_API_Data_Label_Address_Name extends DHLPWC_Model_API_Data_Abstract
{
    
    public $first_name;
    public $last_name;
    public $company_name;

}

endif;
