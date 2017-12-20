<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Access_Control')) :

class DHLPWC_Model_Service_Access_Control extends DHLPWC_Model_Core_Singleton_Abstract
{

    const SIMPLE_OPTION_PREFIX = 'dhlpwc_';

    const ACCESS_API = 'api';
    const ACCESS_COLUMN_INFO = 'column_info';

    const ACCESS_DEFAULT_TO_BUSINESS = 'default_to_business';

    const ACCESS_OPTION_FREE = 'option_free';
    const ACCESS_OPTION_HOME = 'option_home';
    const ACCESS_OPTION_NO_NEIGHBOUR = 'option_no_neighbour';
    const ACCESS_OPTION_EVENING = 'option_evening';
    const ACCESS_OPTION_PARCELSHOP = 'option_parcelshop';

    const ACCESS_DEBUG = 'debug';

    const ACCESS_CAPABILITY_PARCELTYPE = 'capability_parceltype';

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

            case self::ACCESS_DEFAULT_TO_BUSINESS:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_default_send_to_business();
                break;

            case self::ACCESS_OPTION_FREE:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_enable_free();
                break;

            case self::ACCESS_OPTION_HOME:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_enable_home();
                break;

            case self::ACCESS_OPTION_NO_NEIGHBOUR:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_enable_no_neighbour();
                break;

            case self::ACCESS_OPTION_EVENING:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_enable_evening();
                break;

            case self::ACCESS_OPTION_PARCELSHOP:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_enable_parcelshop();
                break;

            case self::ACCESS_CAPABILITY_PARCELTYPE:
                $logic = DHLPWC_Model_Logic_Access_Control_Capabilities::instance();
                $capabilities = $logic->check_capabilities($args);
                return $logic->filter_unique_parceltypes($capabilities);
                break;

            case self::ACCESS_DEBUG:
                $logic = DHLPWC_Model_Logic_Access_Control::instance();
                return $logic->check_debug();
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
