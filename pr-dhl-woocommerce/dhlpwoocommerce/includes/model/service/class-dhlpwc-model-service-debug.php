<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Debug')) :

class DHLPWC_Model_Service_Debug extends DHLPWC_Model_Core_Singleton_Abstract
{

    // An array of endpoints to ignore. Each key is a string.
    // Values is an array of error codes to ignore.
    // For example, if the endpoint 'search-people/%query%' that returns 404 doesn't need to be reported (because it's common), add:
    // 'search-people/' => array(404)
    protected $exclude_endpoint_codes = array(
        'parcel-shop-locations/' => array(404)
    );

    protected function is_excluded($endpoint, $error_code)
    {
        if (!$endpoint) {
            return false;
        }

        foreach ($this->exclude_endpoint_codes as $exclude_endpoint => $codes) {
            // Check if endpoints starts as one of the excluded endpoints
            if (substr($endpoint, 0, strlen($exclude_endpoint)) === $exclude_endpoint) {
                if (in_array($error_code, $codes)) {
                    return true;
                } else {
                    break;
                }
            }
        }

        return false;
    }

    public function mail($error_id, $error_code, $error_message, $endpoint, $request)
    {
        if ($this->is_excluded($endpoint, $error_code)) {
            return;
        }

        $cache_time = 15 * MINUTE_IN_SECONDS;
        $cache_id = $endpoint;
        $cache = get_transient('dhlpwc_debug_mail_cache_' . $cache_id);
        if (!empty($cache)) {
            // Already mailed the error for this endpoint. Throttle amount of error mailing
            return;
        }

        set_transient('dhlpwc_debug_mail_cache_' . $cache_id, true, $cache_time);

        $timestamp = current_time( 'timestamp', 1 );
        $title = 'ERROR: DHL for WooCommerce > ' . get_site_url() . ' > Endpoint:' . $endpoint . ' > (timestamp:' . $timestamp . ')';

        if (!is_string($request)) {
            $request = json_encode($request);
        }

        // Unfortunately, we can only get the plugin version if it's in the admin section
        if (is_admin()) {
            $plugin_data = get_plugin_data(DHLPWC_PLUGIN_FILE);
            $plugin_version = $plugin_data['Version'];
        } else {
            $plugin_version = 'N/A';
        }

        $view = new DHLPWC_Template('mail.debug');
        $message_view = $view->render(array(
            'title'          => $title,
            'site_url'       => get_site_url(),
            'error_id'       => $error_id,
            'error_code'     => $error_code,
            'error_message'  => $error_message,
            'endpoint'       => $endpoint,
            'request'        => trim($request),
            'wp_version'     => get_bloginfo('version'),
            'wc_version'     => WC()->version,
            'plugin_version' => $plugin_version,
        ), false);

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail('plugins@dhl.com', $title, $message_view, $headers);
    }

}

endif;
