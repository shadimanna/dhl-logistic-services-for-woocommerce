<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Track_Trace')) :

class DHLPWC_Model_Service_Track_Trace extends DHLPWC_Model_Core_Singleton_Abstract
{

    const QUERY_TRACKING_CODE = 'tt';
    const QUERY_POSTCODE = 'pc';
    const QUERY_LANDCODE = 'lc';

    protected $url = 'https://www.dhlparcel.nl/nl/volg-uw-zending-0';

    public function get_url($tracking_code = null, $postcode = null, $locale = null)
    {
        $query_args = array();
        if ($tracking_code !== null) {
            $query_args[self::QUERY_TRACKING_CODE] = urlencode($tracking_code);
        }

        if ($postcode !== null) {
            $query_args[self::QUERY_POSTCODE] = urlencode($postcode);
        }

        if ($locale !== null) {
            $query_args[self::QUERY_LANDCODE] = urlencode($locale);
        }

        return add_query_arg($query_args, $this->url);
    }

    public function get_track_trace_from_order($order_id)
    {
        $service = DHLPWC_Model_Service_Order_Meta::instance();
        $labels = $service->get_labels($order_id);

        if (!$labels || !is_array($labels)) {
            return array();
        }

        $tracker_codes = array();
        foreach($labels as $label) {
            if (array_key_exists('tracker_code', $label) && empty($label['is_return'])) {
                $tracker_codes[] = $label['tracker_code'];
            }
        }

        return $tracker_codes;
    }

}

endif;
