<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Account')) :

class DHLPWC_Controller_Account
{

    public function __construct()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_TRACK_TRACE_COMPONENT)) {
            add_action('wp_enqueue_scripts', array($this, 'load_scripts'));
            add_action('wp_enqueue_scripts', array($this, 'load_styles'));
            add_action('woocommerce_order_details_after_order_table_items', array($this, 'track_and_trace'), 10, 1);
        }
    }

    public function load_styles()
    {
        if (is_account_page()) {
            wp_enqueue_style('dhlpwc-checkout-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.account.css');
        }
    }

    public function load_scripts()
    {
        if (is_account_page()) {
            wp_enqueue_script('dhlpwc-track-and-trace', 'https://track-and-trace.dhlparcel.nl/track-and-trace-iframe.js', array(), null, true);
        }
    }

    public function track_and_trace($wc_order)
    {
        /** @var WC_Order $wc_order **/
        $locale = str_replace('_', '-', get_locale());

        $service = DHLPWC_Model_Service_Postcode::instance();
        $postcode = $service->get_postcode_from_order($wc_order->get_id());

        $service = DHLPWC_Model_Service_Track_Trace::instance();
        $tracking_codes = $service->get_track_trace_from_order($wc_order->get_id());

        $view = new DHLPWC_Template('track-and-trace');

        $view->render(array(
            'tracking_code' => count($tracking_codes) ? reset($tracking_codes) : null, // Can only display one for now
            'postcode' => $postcode,
            'locale' =>  $locale
        ));
    }

}

endif;
