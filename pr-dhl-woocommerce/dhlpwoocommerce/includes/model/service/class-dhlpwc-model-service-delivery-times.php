<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Delivery_Times')) :

class DHLPWC_Model_Service_Delivery_Times extends DHLPWC_Model_Core_Singleton_Abstract
{

    const ORDER_TIME_SELECTION = '_dhlpwc_order_time_selection';

    const SHIPPING_PRIORITY_BACKLOG = 'shipping_priority_backlog';
    const SHIPPING_PRIORITY_SOON = 'shipping_priority_soon';
    const SHIPPING_PRIORITY_TODAY = 'shipping_priority_today';
    const SHIPPING_PRIORITY_ASAP = 'shipping_priority_asap';

    /**
     * @param $order_id
     * @param $data
     * @return bool|int
     */
    public function save_order_time_selection($order_id)
    {
        $sync = WC()->session->get('dhlpwc_delivery_time_selection_sync');
        if ($sync) {
            list($selected, $date, $start_time, $end_time) = $sync;
        } else {
            list($selected, $date, $start_time, $end_time) = array(null, null, null, null);
        }

        unset($selected);

        if (empty($date) || empty($start_time) || empty($end_time)) {
            return false;
        }

        $meta_object = new DHLPWC_Model_Meta_Order_Time_Selection(array(
            'date'       => $date,
            'start_time' => $start_time,
            'end_time'   => $end_time,
            'timestamp' => strtotime($date . ' ' . $start_time . ' ' . wc_timezone_string()),
        ));

        return update_post_meta($order_id, self::ORDER_TIME_SELECTION, $meta_object->to_array());
    }

    /**
     * @param $order_id
     * @return bool
     */
    public function delete_order_time_selection($order_id)
    {
        return delete_post_meta($order_id, self::ORDER_TIME_SELECTION);
    }

    /**
     * @param $order_id
     * @return DHLPWC_Model_Meta_Order_Time_Selection|null
     */
    public function get_order_time_selection($order_id)
    {
        $meta_data = get_post_meta($order_id, self::ORDER_TIME_SELECTION, true);
        if (empty($meta_data)) {
            return null;
        }

        return new DHLPWC_Model_Meta_Order_Time_Selection($meta_data);
    }

    /**
     * @param DHLPWC_Model_Meta_Shipping_Preset $preset
     * @return bool
     */
    public function check_checkout_delivery_time_selected($preset)
    {
        $delivery_time_preset_ids = array(
            'home',
            'evening',
            'same_day',
            'no_neighbour',
            'no_neighbour_evening',
            'no_neighbour_same_day',
        );

        if (!in_array($preset->setting_id, $delivery_time_preset_ids)) {
            return false;
        }

        $sync = WC()->session->get('dhlpwc_delivery_time_selection_sync');
        if ($sync) {
            list($selected, $date, $start_time, $end_time) = $sync;
        } else {
            list($selected, $date, $start_time, $end_time) = array(null, null, null, null);
        }

        unset($selected);

        if (empty($date) || empty($start_time) || empty($end_time)) {
            return false;
        }

        $service = DHLPWC_Model_Service_Access_Control::instance();
        $delivery_times = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DELIVERY_TIMES_ACTIVE);
        if (!$delivery_times) {
            return false;
        }

        return true;
    }

    /**
     * @param $postal_code
     * @param $country
     * @param bool $to_business
     * @return DHLPWC_Model_Data_Delivery_Time[]
     */
    public function get_time_frames($postal_code, $country_code, $selected = null)
    {
        if (!$postal_code || !$country_code) {
            return array();
        }

        $postal_code_trim = preg_replace('/\s+/', '', $postal_code);

        $connector = DHLPWC_Model_API_Connector::instance();
        $time_windows = $connector->get('time-windows', array(
            'countryCode' => $country_code,
            'postalCode'  => strtoupper($postal_code_trim),
        ), 30 * MINUTE_IN_SECONDS);

        if (!$time_windows || !is_array($time_windows) || empty($time_windows)) {
            return array();
        }

        $delivery_times = array();
        foreach($time_windows as $time_window_data)
        {
            $time_window = new DHLPWC_Model_API_Data_Time_Window($time_window_data);
            $delivery_times[] = $this->parse_time_frame($time_window->delivery_date, $time_window->start_time, $time_window->end_time, $time_window, $selected);
        }

        return $delivery_times;
    }

    /**
     * @param DHLPWC_Model_Data_Delivery_Time[] $delivery_times
     * @return DHLPWC_Model_Data_Delivery_Time[] array
     */
    public function filter_time_frames($delivery_times, $no_neighbour = false, $selected = null)
    {
        $filtered_times = array();

        if ($no_neighbour) {
            $code_same_day = 'no_neighbour_same_day';
            $code_evening = 'no_neighbour_evening';
            $code_home = 'no_neighbour';
        } else {
            $code_same_day = 'same_day';
            $code_evening = 'evening';
            $code_home = 'home';
        }

        $timestamp_same_day = $this->get_minimum_timestamp($code_same_day);
        $timestamp_home = $this->get_minimum_timestamp($code_home);

        $datetime = new DateTime('today 23:59:59', new DateTimeZone(wc_timezone_string()));
        $today_midnight_timestamp = $datetime->getTimestamp();

        $number_of_days = $this->get_number_of_days_setting();
        // When setting 'number_of_days' is 1, it should show tomorrow as available. This means its actually 2 days worth showing
        // Today and tomorrow. Thus number_of_days is always +1
        $number_of_days += 1;
        $max_timestamp = intval(strtotime('+' . $number_of_days . ' day', $today_midnight_timestamp));

        $datetime = new DateTime('yesterday 23:59:59', new DateTimeZone(wc_timezone_string()));
        $min_timestamp = $datetime->getTimestamp();

        $access_service = DHLPWC_Model_Service_Access_Control::instance();
        $allowed_shipping_options = $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CAPABILITY_OPTIONS);

        $shipping_days = array(
            1 => $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SHIPPING_DAY, 'monday'),
            2 => $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SHIPPING_DAY, 'tuesday'),
            3 => $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SHIPPING_DAY, 'wednesday'),
            4 => $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SHIPPING_DAY, 'thursday'),
            5 => $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SHIPPING_DAY, 'friday'),
            6 => $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SHIPPING_DAY, 'saturday'),
            7 => $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SHIPPING_DAY, 'sunday'),
        );

        $service = DHLPWC_Model_Service_Shipping_Preset::instance();

        $preset = $service->find_preset($code_same_day);
        $same_day_id = $preset->frontend_id;
        $same_day_allowed = $this->check_allowed_options($preset->options, $allowed_shipping_options);
        $same_day_enabled = $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CHECKOUT_PRESET, $code_same_day);

        $preset = $service->find_preset($code_home);
        $home_id = $preset->frontend_id;
        $home_allowed = $this->check_allowed_options($preset->options, $allowed_shipping_options);
        $home_enabled = $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CHECKOUT_PRESET, $code_home);

        $preset = $service->find_preset($code_evening);
        $evening_id = $preset->frontend_id;
        $evening_allowed = $this->check_allowed_options($preset->options, $allowed_shipping_options);
        $evening_enabled = $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CHECKOUT_PRESET, $code_evening);

        foreach($delivery_times as $delivery_time) {
            /** @var DHLPWC_Model_Data_Delivery_Time $delivery_time */
            $timestamp = strtotime($delivery_time->source->delivery_date . ' ' . $delivery_time->source->start_time . ' ' . wc_timezone_string());

            if ($timestamp < $min_timestamp || $timestamp > $max_timestamp) {
                continue;
            }

            if ($timestamp < $today_midnight_timestamp) {
                // Today's logic
                if ($timestamp_same_day !== null && $timestamp_same_day < $timestamp &&
                    intval($delivery_time->source->start_time) > 1400 &&
                    (intval($delivery_time->source->end_time) > 1800 || $delivery_time->source->end_time === '0000')) {
                    // Check if today is a shipping day
                    if ($shipping_days[date_i18n('N')] === true) {
                        // Check if same day shipping is allowed
                        if ($same_day_enabled && $same_day_allowed) {
                            $delivery_time->preset_frontend_id = $same_day_id;
                            $filtered_times[] = $delivery_time;
                        }
                    }
                }

            } else {
                // All other day's logic
                if ($timestamp_home !== null) {
                    $system_timestamp = strtotime($delivery_time->source->delivery_date . ' ' . $delivery_time->source->start_time);

                    if ($this->validate_with_shipping_days($timestamp_home, $timestamp, $system_timestamp, $shipping_days)) {
                        if (intval($delivery_time->source->start_time) > 1400
                            && (intval($delivery_time->source->end_time) > 1800 || $delivery_time->source->end_time === '0000')
                        ) {
                            if ($evening_enabled && $evening_allowed) {
                                $delivery_time->preset_frontend_id = $evening_id;
                                $filtered_times[] = $delivery_time;
                            }

                        } else {
                            if ($home_enabled && $home_allowed) {
                                $delivery_time->preset_frontend_id = $home_id;

                                // Auto-select first default entry (non-evening) if no selection has been made
                                if ($selected === null) {
                                    $selected = true;
                                    $delivery_time->selected = true;
                                }

                                $filtered_times[] = $delivery_time;
                            }
                        }
                    }
                }
            }
        }

        return $filtered_times;
    }

    protected function validate_with_shipping_days($minimum_timestamp, $timestamp, $system_timestamp, $shipping_days)
    {
        // First check if the day before the select date is a shipping day. It will be impossible to deliver on time if not delivered the day before.
        // TODO Note, currently using a hardcoded check for Sundays. Drop off timing does not work for Sundays.
        $day_before_timestamp = intval(strtotime('-1 day', $system_timestamp));
        $day_before = date('N', $day_before_timestamp);
        if (($shipping_days[$day_before] !== true && $day_before != 7) || ($day_before == 7 && $shipping_days[6] !== true)) {
            return false;
        }

        $datetime = new DateTime('yesterday 23:59:59', new DateTimeZone(wc_timezone_string()));
        $timestamp_today = $datetime->getTimestamp();

        $timestamp_difference = $timestamp - $timestamp_today;
        if ($timestamp_difference < 0) {
            // Unknown validation, shipping day is lower than current timestamp
            return false;
        }

        $days_between = floor($timestamp_difference / DAY_IN_SECONDS);

        if ($days_between > 30) {
            // In case invalid timestamps are given, prevent endless loops and fail the validation
            return false;
        }

        $additional_days = 0;
        for($day_check = 0; $day_check < $days_between; $day_check++) {
            $the_day = date_i18n('N', strtotime('+' . $day_check . ' day', current_time( 'timestamp')));
            if ($shipping_days[$the_day] !== true) {
                $additional_days++;
            }
        }

        // Add the additional days to the minimum timestamp
        $minimum_timestamp = strtotime('+' . $additional_days . ' days', $minimum_timestamp);

        if ($minimum_timestamp > $timestamp) {
            return false;
        }

        return true;
    }

    protected function check_allowed_options($options, $allowed_shipping_options)
    {
        $check_allowed = true;
        foreach($options as $preset_option) {
            if (!array_key_exists($preset_option, $allowed_shipping_options)) {
                $check_allowed = false;
            }
        }
        return $check_allowed;
    }

    protected function get_minimum_timestamp($code)
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');

        if (empty($shipping_method)) {
            return null;
        }

        if (!isset($shipping_method['enable_delivery_time_' . $code])) {
            return null;
        }

        if ($shipping_method['enable_delivery_time_' . $code] != 'yes') {
            return null;
        }

        if (!isset($shipping_method['delivery_time_cut_off_' . $code])) {
            return null;
        }

        $cut_off_hour = (int) $shipping_method['delivery_time_cut_off_' . $code];
        $current_hour = (int) current_time('G');

        $cut_off = (bool) ($current_hour >= $cut_off_hour);

        if ($code === 'same_day' || $code === 'no_neighbour_same_day') {
            if ($cut_off) {
                return null;
            } else {
                // Hardcode evening time for now, because the API shows evening hours when it shouldn't
                $datetime = new DateTime('today 00:00:01', new DateTimeZone(wc_timezone_string()));
                return $datetime->getTimestamp();
            }
        }

        if (!isset($shipping_method['delivery_day_cut_off_' . $code])) {
            return null;
        }

        $days = (int) $shipping_method['delivery_day_cut_off_' . $code];
        $days += $cut_off ? 1 : 0;

        $datetime = new DateTime('yesterday 23:59:59', new DateTimeZone(wc_timezone_string()));
        $current_day_timestamp = $datetime->getTimestamp();

        $cut_off_timestamp = strtotime('+' . $days . ' days', $current_day_timestamp);

        return $cut_off_timestamp;
    }

    public function get_number_of_days_setting()
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');
        if (!isset($shipping_method['delivery_times_number_of_days'])) {
            return 14;
        }
        return intval($shipping_method['delivery_times_number_of_days']);
    }

    /**
     * @param $source_delivery_date
     * @param $source_start_time
     * @param $source_end_time
     * @param null $time_window
     * @param null $compare
     * @return DHLPWC_Model_Data_Delivery_Time
     */
    public function parse_time_frame($source_delivery_date, $source_start_time, $source_end_time, $time_window = null, $compare = null)
    {
        $delivery_date = strtotime($source_delivery_date);
        $date = date_i18n('D. j M.', $delivery_date);
        $week_day = date_i18n('w', $delivery_date);
        $day = date_i18n('w', $delivery_date);
        $month = date_i18n('n', $delivery_date);
        $year = date_i18n('Y', $delivery_date);

        $start_time = date_i18n('H:i', strtotime($source_start_time));
        $end_time = date_i18n('H:i', strtotime($source_end_time));

        $identifier = $this->get_identifier($source_delivery_date, $source_start_time, $source_end_time);
        $selected = ($compare !== null && $compare === $identifier) ? true : false;

        return new DHLPWC_Model_Data_Delivery_Time(array(
            'source' => $time_window,

            'date'     => $date,
            'week_day' => $week_day,
            'day'      => $day,
            'month'    => $month,
            'year'     => $year,

            'start_time' => $start_time,
            'end_time'   => $end_time,

            'identifier' => $identifier,
            'selected' => $selected,
        ));
    }

    public function get_shipping_advice_class($selected_timestamp)
    {
        $shipping_priority = $this->get_shipping_priority($selected_timestamp);

        switch ($shipping_priority) {
            case self::SHIPPING_PRIORITY_TODAY:
                return 'dhlpwc-shipping-advice-today';
                break;

            case self::SHIPPING_PRIORITY_SOON:
                return 'dhlpwc-shipping-advice-soon';
                break;

            case self::SHIPPING_PRIORITY_ASAP:
                return 'dhlpwc-shipping-advice-asap';
                break;

            default:
                return 'dhlpwc-shipping-advice-backlog';
        }
    }

    public function get_shipping_advice($selected_timestamp)
    {
        $shipping_priority = $this->get_shipping_priority($selected_timestamp);

        switch ($shipping_priority) {
            case self::SHIPPING_PRIORITY_ASAP:
                return __("Send\nASAP", 'dhlpwc');
                break;

            case self::SHIPPING_PRIORITY_SOON:
                return __("Send\ntomorrow", 'dhlpwc');
                break;

            case self::SHIPPING_PRIORITY_BACKLOG:
                $datetime = new DateTime('tomorrow', new DateTimeZone(wc_timezone_string()));
                $tomorrow_day_timestamp = $datetime->getTimestamp();

                $datetime = new DateTime('@' . $selected_timestamp);
                $datetime->setTimezone(new DateTimeZone(wc_timezone_string()));
                $datetime->setTime(0, 0, 0);
                $selected_day_timestamp = $datetime->getTimestamp();

                $days_difference_timestamp = $selected_day_timestamp - $tomorrow_day_timestamp;
                $days_between = floor($days_difference_timestamp / DAY_IN_SECONDS);
                return sprintf(__("Send in\n%s days", 'dhlpwc'), $days_between);
                break;

            default:
                return __("Send\ntoday", 'dhlpwc');
        }
    }

    protected function get_shipping_priority($selected_timestamp)
    {
        if (time() > $selected_timestamp) {
            return self::SHIPPING_PRIORITY_ASAP;
        }

        $datetime = new DateTime('today', new DateTimeZone(wc_timezone_string()));
        $current_day_timestamp = $datetime->getTimestamp();

        $datetime = new DateTime('tomorrow', new DateTimeZone(wc_timezone_string()));
        $tomorrow_day_timestamp = $datetime->getTimestamp();

        $datetime = new DateTime('@' . $selected_timestamp);
        $datetime->setTimezone(new DateTimeZone(wc_timezone_string()));
        $datetime->setTime(0, 0, 0);
        $selected_day_timestamp = $datetime->getTimestamp();

        if ($current_day_timestamp >= $selected_day_timestamp) {
            return self::SHIPPING_PRIORITY_ASAP;
        }

        if ($tomorrow_day_timestamp < $selected_day_timestamp) {
            $days_difference_timestamp = $selected_day_timestamp - $tomorrow_day_timestamp;
            $days_between = floor($days_difference_timestamp / DAY_IN_SECONDS);

            if ($days_between == 1) {
                return self::SHIPPING_PRIORITY_SOON;
            }

            return self::SHIPPING_PRIORITY_BACKLOG;
        }

        return self::SHIPPING_PRIORITY_TODAY;
    }

    public function get_identifier($date, $start_time, $end_time)
    {
        return $date . '___' . $start_time . '___' . $end_time;
    }

}

endif;
