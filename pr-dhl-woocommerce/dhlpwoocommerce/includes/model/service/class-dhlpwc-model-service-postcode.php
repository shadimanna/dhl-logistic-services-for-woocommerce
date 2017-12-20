<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Postcode')) :

class DHLPWC_Model_Service_Postcode extends DHLPWC_Model_Core_Singleton_Abstract
{

    protected $url = 'http://i18napis.appspot.com/address/data/';

    /**
     * @param $country_code
     * @return bool|DHLPWC_Model_Data_Postcode_Validation
     */
    public function get_validation($country_code)
    {
        $request = wp_remote_get($this->url . $country_code);

        if ($request instanceof WP_Error) {
            return false;
        }

        if (!isset($request['body'])) {
            return false;
        }

        $body = json_decode($request['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        $data = new DHLPWC_Model_Data_Postcode_Validation($body);

        return $data;
    }

}

endif;
