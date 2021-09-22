<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Postcode')) :

class DHLPWC_Model_Service_Postcode extends DHLPWC_Model_Core_Singleton_Abstract
{

    protected $cached_countries;

    protected $url = 'http://i18napis.appspot.com/address/data/';

    public function validate($postcode, $country_code)
    {
        $expression = $this->get_validation_expression($country_code);
        if (!$expression) {
            // Due to this being an external service, with a possibility of not being able to retrieve an expression
            // We will skip the validation process and let the user continue
            return true;
        }

        $valid = (bool)preg_match('/^' . $expression . '$/', $postcode);
        return $valid;
    }

    /**
     * @param $country_code
     * @return bool|DHLPWC_Model_Data_Postcode_Validation
     */
    protected function get_validation_expression($country_code)
    {
        // Try to get the expression from cache
        if ($expression = $this->get_cached_expression($country_code)) {
            return $expression;
        }

        try {
            $request = wp_remote_get($this->url . $country_code);
        } catch (Exception $exception) {
            return false;
        }

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
        if (!$data->expression) {
            return false;
        }

        // Cache and return expression
        $this->cache_expression($data->expression, $country_code);
        return $data->expression;
    }

    protected function cache_expression($expression, $country_code)
    {
        if (!isset($this->cached_countries) || !is_array($this->cached_countries)) {
            $this->cached_countries = array();
        }
        set_site_transient('dhlpwc_postcode_validation_' . $country_code, $expression, 7 * DAY_IN_SECONDS);
        $this->cached_countries[$country_code] = $expression;
    }

    protected function get_cached_expression($country_code)
    {
        if (!isset($this->cached_countries) || !is_array($this->cached_countries)) {
            $this->cached_countries = array();
        }

        if (!array_key_exists($country_code, $this->cached_countries)) {
            if ($expression = get_site_transient('dhlpwc_postcode_validation' . $country_code)) {
                $this->cached_countries[$country_code] = $expression;
            } else {
                return false;
            }
        }

        return $this->cached_countries[$country_code];
    }

    public function get_postcode_from_order($order_id)
    {
        $order = wc_get_order($order_id);
        $address = $order->get_address('shipping') ?: $order->get_address();
        return wc_format_postcode($address['postcode'], $address['country']);
    }

}

endif;
