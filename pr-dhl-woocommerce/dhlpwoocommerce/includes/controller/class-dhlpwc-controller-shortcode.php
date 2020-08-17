<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Shortcode')) :

class DHLPWC_Controller_Shortcode
{
    public function __construct()
    {
        add_shortcode('dhlpwc-track-and-trace-links', [$this, 'add_track_and_trace_links_shortcode']);
    }

    public function add_track_and_trace_links_shortcode($attributes)
    {
        $attributes = shortcode_atts( array(
            'order_id' => get_the_ID(),
            'glue'     => '<br>',
        ), $attributes);

        $order = new WC_Order($attributes['order_id']);

        if (!$order || !$order->get_id()) {
            return;
        }

        $locale = str_replace('_', '-', get_locale());

        $service = DHLPWC_Model_Service_Postcode::instance();
        $postcode = $service->get_postcode_from_order($order->get_id());

        $service = DHLPWC_Model_Service_Track_Trace::instance();
        $tracking_codes = $service->get_track_trace_from_order($order->get_id());

        // Add urls
        $tracking_codesets = array();
        foreach($tracking_codes as $tracking_code) {
            $tracking_codeset = array();
            $tracking_codeset['url'] = $service->get_url($tracking_code, $postcode, $locale);
            $tracking_codeset['code'] = $tracking_code;
            $tracking_codesets[] = $tracking_codeset;
        }

        echo implode($attributes['glue'], array_map(function ($tracking_code) {
            return sprintf('<a href="%s">%s</a>', $tracking_code['url'], $tracking_code['code']);
        }, $tracking_codesets));
    }
}

endif;
