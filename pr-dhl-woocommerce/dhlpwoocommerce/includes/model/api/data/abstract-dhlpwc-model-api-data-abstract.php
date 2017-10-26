<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Abstract')) :

abstract class DHLPWC_Model_API_Data_Abstract extends DHLPWC_Model_Core_Arraymap_Abstract
{

    protected function store_key($key)
    {
        return $this->snake_case($key);
    }

    protected function format_key($key)
    {
        return $this->camelcase($key);
    }

    protected function camelcase($string)
    {
        $separated_string = ucwords(str_replace('_', ' ', $string));
        $string = str_replace(' ', '', $separated_string);
        return lcfirst($string);
    }

    protected function snake_case($data) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $data, $matches);
        $results = $matches[0];
        foreach ($results as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $results);
    }

}

endif;
