<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Access_Control')) :

class DHLPWC_Model_Service_Access_Control extends DHLPWC_Model_Core_Singleton_Abstract
{

    const SIMPLE_OPTION_PREFIX = 'dhlpwc_';

    const ACCESS_API = 'api';
    const ACCESS_COLUMN_INFO = 'column_info';
    const ACCESS_TRACK_TRACE_MAIL = 'track_trace_mail';
    const ACCESS_TRACK_TRACE_COMPONENT = 'track_trace_component';

    const ACCESS_DEFAULT_TO_BUSINESS = 'default_to_business';
    const ACCESS_DEFAULT_SEND_SIGNATURE = 'default_send_signature';
    const ACCESS_CHECKOUT_PARCELSHOP = 'checkout_parcelshop';

    const ACCESS_DEBUG = 'debug';
    const ACCESS_DEBUG_MAIL = 'debug_mail';

    const ACCESS_CAPABILITY_PARCELTYPE = 'capability_parceltype';
    const ACCESS_CAPABILITY_OPTIONS = 'capability_options';
    const ACCESS_CAPABILITY_ORDER_OPTIONS = 'capability_order_options';

    const ACCESS_CREATE_LABEL = 'create_label';

    // Simple options can be set and retrieved without custom logic
    protected $simple_options = array(
    );

    public function check($access_option, $args = array())
    {
        switch($access_option) {
            case self::ACCESS_API:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                if (!$logic->check_enabled()) {
                    return false;
                }

                if (!$logic->check_application_country()) {
                    return false;
                }
                if (!$logic->check_account()) {
                    return false;
                }

                return true;
                break;

            case self::ACCESS_CREATE_LABEL:
                if (!$this->check(self::ACCESS_API)) {
                    return false;
                }
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_default_shipping_address();
                break;

            case self::ACCESS_COLUMN_INFO:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_column_info();
                break;

            case self::ACCESS_TRACK_TRACE_MAIL:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_track_trace_mail();
                break;

            case self::ACCESS_TRACK_TRACE_COMPONENT:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_track_trace_component();
                break;

            case self::ACCESS_DEFAULT_TO_BUSINESS:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_default_send_to_business();
                break;

            case self::ACCESS_DEFAULT_SEND_SIGNATURE:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_default_send_signature();
                break;

            case self::ACCESS_CHECKOUT_PARCELSHOP:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_parcelshop_enabled();
                break;

            case self::ACCESS_CAPABILITY_PARCELTYPE:
                $logic = DHLPWC_Model_Logic_Access_Control_Capabilities::instance();
                $capabilities = $logic->check_order_capabilities($args);
                return $logic->filter_unique_parceltypes($capabilities);
                break;

            case self::ACCESS_CAPABILITY_OPTIONS:
                $logic = DHLPWC_Model_Logic_Access_Control_Capabilities::instance();
                $capabilities = $logic->check_shipping_capabilities();
                return $logic->filter_unique_options($capabilities);
                break;

            case self::ACCESS_CAPABILITY_ORDER_OPTIONS:
                $logic = DHLPWC_Model_Logic_Access_Control_Capabilities::instance();
                $capabilities = $logic->check_order_capabilities($args);
                return $logic->filter_unique_options($capabilities);
                break;

            case self::ACCESS_DEBUG:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_debug();
                break;

            case self::ACCESS_DEBUG_MAIL:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_debug_mail();
                break;

        }

        return false;
    }

    public function save($access_option, $boolean)
    {
        // Only allow simple options to be saved through this service
        // Complicated options are not set by this service, but externally through other classes
        if (in_array($access_option, $this->simple_options) && is_bool($boolean)) {
            update_option(self::SIMPLE_OPTION_PREFIX . $access_option, $boolean);
        }
    }

    protected function search($access_option)
    {
        return get_option(self::SIMPLE_OPTION_PREFIX . $access_option, null);
    }

}

endif;
