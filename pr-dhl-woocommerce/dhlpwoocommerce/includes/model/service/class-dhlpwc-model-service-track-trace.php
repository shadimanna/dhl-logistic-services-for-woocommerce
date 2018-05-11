<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Track_Trace')) :

class DHLPWC_Model_Service_Track_Trace extends DHLPWC_Model_Core_Singleton_Abstract
{

    protected $url = 'https://www.dhlparcel.nl/nl/volg-uw-zending-0?tt=%1$s&pc=%2$s&lc=%3$s';

    public function get_url($tracking_code, $postcode, $locale)
    {
        return sprintf($this->url,
            urlencode($tracking_code),
            urlencode($postcode),
            urlencode($locale)
        );
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
            if (array_key_exists('tracker_code', $label)) {
                $tracker_codes[] = $label['tracker_code'];
            }
        }

        return $tracker_codes;
    }

}

endif;
