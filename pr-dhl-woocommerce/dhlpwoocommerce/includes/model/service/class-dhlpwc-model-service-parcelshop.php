<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Parcelshop')) :

class DHLPWC_Model_Service_Parcelshop extends DHLPWC_Model_Core_Singleton_Abstract
{
    /**
     * @param DHLPWC_Model_API_Data_ParcelShop_OpeningTimes[] $source_times
     * @return array
     */
    public function get_formatted_times($source_times)
    {
        $times = array();
        $source_references = array();
        foreach($source_times as $source_time) {
            $day = intval($source_time->week_day);
            $day_key = $this->get_day_key($day);

            if (isset($times[$day_key])) {
                $source_reference = $source_references[$day_key];
                $new_from = intval(str_replace(':', '', $source_time->time_from));
                $reference_from = intval(str_replace(':', '', $source_reference->time_from));
                if ($new_from < $reference_from) {
                    $source_references[$day_key]->time_from = $source_time->time_from;
                }

                $new_to = intval(str_replace(':', '', $source_time->time_to));
                $reference_to = intval(str_replace(':', '', $source_reference->time_to));
                if ($new_to > $reference_to) {
                    $source_references[$day_key]->time_to = $source_time->time_to;
                }
            }

            $time = array();
            // TODO optimize language
            $time['day'] = date('l', strtotime("Sunday +{$source_time->week_day} days"));
            $time['period'] = sprintf('%1$s - %2$s', $this->format_time($source_time->time_from), $this->format_time($source_time->time_to));
            $times[$day_key] = $time;
            $source_references[$day_key] = $source_time;
        }

        // Fill in missing days
        $day_checks = range(1, 7);
        foreach($day_checks as $day_check) {
            $day_key = $this->get_day_key($day_check);
            if (!isset($times[$day_key])) {
                $times[$day_key] = $this->unavailable_time($day_check);
            }
        }

        return $times;
    }

    protected function get_period($from, $to)
    {
        return sprintf('%1$s - %2$s', $this->format_time($from), $this->format_time($to));
    }

    protected function get_day_key($day)
    {
        return sprintf('day_%s', $day);
    }

    protected function unavailable_time($week_day)
    {
        return array(
            'day' => date('l', strtotime("Sunday +{$week_day} days")),
            'period' => __('Unavailable', 'dhlpwc'),
        );
    }

    protected function format_time($source_time)
    {
        $time_parts = explode(':', $source_time);
        return sprintf('%1$s:%2$s', $time_parts[0], $time_parts[1]);
    }
}

endif;