<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Meta_Address')) :

class DHLPWC_Model_Meta_Address extends DHLPWC_Model_Meta_Abstract
{

    public $first_name;
    public $last_name;
    public $company;

    public $country;
    public $postcode;
    public $city;
    public $street;
    public $number;
    public $addition;

    public $email;
    public $phone;

}

endif;
