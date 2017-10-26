<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Google_Maps')) :

class DHLPWC_Model_Service_Google_Maps extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function get_script_url()
    {

    }

    public function calculate_distance($latitude1, $longitude1, $latitude2, $longitude2, $unit = 'K')
    {
        $theta = $longitude1 - $longitude2;
        $dist = sin(deg2rad($latitude1)) * sin(deg2rad($latitude2)) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == 'K') {
            return ($miles * 1.609344);
        } else {
            return $miles;
        }
    }

    protected function add_asyncdefer_attribute($tag, $handle)
    {
        $param = '';
        if (strpos($handle, 'async') !== false) {
            $param = 'async ';
        }
        if (strpos($handle, 'defer') !== false) {
            $param .= 'defer ';
        }
        if ($param) {
            return str_replace('<script ', '<script ' . $param, $tag);
        } else {
            return $tag;
        }
    }

}

endif;
