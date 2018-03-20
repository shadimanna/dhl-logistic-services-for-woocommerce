<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Debug')) :

class DHLPWC_Model_Service_Debug extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function mail($error_id, $error_message, $endpoint, $request)
    {
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
