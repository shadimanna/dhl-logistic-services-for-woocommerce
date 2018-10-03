<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Terminal')) :

class DHLPWC_Model_Service_Terminal extends DHLPWC_Model_Core_Singleton_Abstract
{

    protected $url = 'https://my.dhlparcel.nl/api/terminals/';

    public function __construct()
    {
        // Debug
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $debug_url = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEBUG_EXTERNAL);
        if ($debug_url) {
            $this->url = $debug_url;
        }
    }

    public function get_terminals($search, $country)
    {
        if (!$search) {
            return array();
        }

        // TODO whitelist countries
//        $service = DHLPWC_Model_Service_Postcode::instance();
//        $validation = $service->get_validation($country);
        if (!$this->validate_country($country)) {
            return array();
        }

        $request = wp_remote_get($this->url . $country, array(
            'data_format' => 'query',
            'body' => array(
                'q' => $search,
            )
        ));

        if ($request instanceof WP_Error) {
            return array();
        }

        if (!isset($request['body'])) {
            return array();
        }

        $body_data = json_decode($request['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array();
        }

        if (!$body_data || !is_array($body_data)) {
            return array();
        }

        $terminals = array();
        foreach ($body_data as $terminal_data) {
            $terminal = new DHLPWC_Model_API_External_Terminal($terminal_data);
            $terminal->country = $country;
            $terminals[] = $terminal;
        }

        return $terminals;
    }

    protected function validate_country($country)
    {
        if (!ctype_upper($country)) {
            return false;
        }

        if (strlen($country) != 2) {
            return false;
        }

        return true;
    }

}

endif;
