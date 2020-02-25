<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Access_Control')) :

class DHLPWC_Model_Service_Access_Control extends DHLPWC_Model_Core_Singleton_Abstract
{

    const SIMPLE_OPTION_PREFIX = 'dhlpwc_';

    const ACCESS_API = 'api';
    const ACCESS_SUBMENU_LINK = 'submenu_link';

    const ACCESS_COLUMN_INFO = 'column_info';
    const ACCESS_OPEN_LABEL_LINKS_EXTERNAL = 'open_label_links_external';

    const ACCESS_BULK_CREATE = 'bulk_create';
    const ACCESS_BULK_DOWNLOAD = 'bulk_download';

    const ACCESS_TRACK_TRACE_MAIL = 'track_trace_mail';
    const ACCESS_TRACK_TRACE_MAIL_TEXT = 'track_trace_mail_text';
    const ACCESS_TRACK_TRACE_COMPONENT = 'track_trace_component';

    const ACCESS_DEFAULT_TO_BUSINESS = 'default_to_business';
    const ACCESS_DEFAULT_SEND_SIGNATURE = 'default_send_signature';
    const ACCESS_DEFAULT_AGE_CHECK = 'default_age_check';
    const ACCESS_DEFAULT_ORDER_ID_REFERENCE = 'default_order_id_reference';
    const ACCESS_DEFAULT_RETURN = 'default_return';

    const ACCESS_CHECKOUT_SORT = 'checkout_sort';
    const ACCESS_CHECKOUT_PRESET = 'checkout_preset';
    const ACCESS_CHECKOUT_PARCELSHOP = 'checkout_parcelshop';

    const ACCESS_DELIVERY_TIMES = 'delivery_times';
    const ACCESS_DELIVERY_TIMES_ACTIVE = 'delivery_times_active';
    const ACCESS_SHIPPING_DAY = 'shipping_day';

    const ACCESS_ALTERNATE_RETURN_ADDRESS = 'alternate_return_address';
    const ACCESS_DEFAULT_HIDE_SENDER_ADDRESS = 'default_hide_sender_address';

    const ACCESS_DEBUG = 'debug';
    const ACCESS_DEBUG_EXTERNAL = 'debug_external';
    const ACCESS_DEBUG_MAIL = 'debug_mail';

    const ACCESS_CAPABILITY_PARCELTYPE = 'capability_parceltype';
    const ACCESS_CAPABILITY_OPTIONS = 'capability_options';
    const ACCESS_CAPABILITY_ORDER_OPTIONS = 'capability_order_options';

    const ACCESS_CREATE_LABEL = 'create_label';
    const ACCESS_VALIDATION_RULE = 'validation_rule';

    const ACCESS_PRINTER = 'printer';

    const ACCESS_LABEL_REQUEST = 'debug_label_request';

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

            case self::ACCESS_SUBMENU_LINK:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_submenu_link();
                break;

            case self::ACCESS_CREATE_LABEL:
                if (!$this->check(self::ACCESS_API)) {
                    return false;
                }
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_default_shipping_address();
                break;

            case self::ACCESS_VALIDATION_RULE:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_validation_rule($args);
                break;

            case self::ACCESS_COLUMN_INFO:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_column_info();
                break;

            case self::ACCESS_OPEN_LABEL_LINKS_EXTERNAL:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_open_label_links_external();
                break;

            case self::ACCESS_BULK_CREATE:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_bulk_create();
                break;

            case self::ACCESS_BULK_DOWNLOAD:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_bulk_download();
                break;

            case self::ACCESS_TRACK_TRACE_MAIL:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_track_trace_mail();
                break;

            case self::ACCESS_TRACK_TRACE_MAIL_TEXT:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_track_trace_mail_text();
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

            case self::ACCESS_DEFAULT_AGE_CHECK:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_default_age_check();
                break;

            case self::ACCESS_DEFAULT_ORDER_ID_REFERENCE:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_default_order_id_reference();
                break;

            case self::ACCESS_DEFAULT_RETURN:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_default_return();
                break;

            case self::ACCESS_CHECKOUT_SORT:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_custom_sort();
                break;

            case self::ACCESS_CHECKOUT_PRESET:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_shipping_preset_enabled($args);
                break;

            case self::ACCESS_CHECKOUT_PARCELSHOP:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_parcelshop_enabled();
                break;

            case self::ACCESS_DELIVERY_TIMES:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_delivery_times_enabled();
                break;

            case self::ACCESS_DELIVERY_TIMES_ACTIVE:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_delivery_times_active();
                break;

            case self::ACCESS_SHIPPING_DAY:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_shipping_day($args);
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

            case self::ACCESS_ALTERNATE_RETURN_ADDRESS:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_alternate_return_address();
                break;

            case self::ACCESS_DEFAULT_HIDE_SENDER_ADDRESS:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_default_hide_sender_address();
                break;

	        case self::ACCESS_PRINTER:
		        $logic = DHLPWC_Model_Logic_Access_Control::instance();
		        return $logic->check_printer();
		        break;

	        case self::ACCESS_LABEL_REQUEST:
		        $logic = DHLPWC_Model_Logic_Access_Control::instance();
		        return $logic->check_label_request();
		        break;

            case self::ACCESS_DEBUG:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_debug();
                break;

            case self::ACCESS_DEBUG_EXTERNAL:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_debug_external();
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
