<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Translation')) :

class DHLPWC_Model_Service_Translation extends DHLPWC_Model_Core_Singleton_Abstract
{
    const CUSTOM_TRACK_TRACE_MAIL = 'track_trace_mail';

    protected $options;
    protected $parcel_types;
    protected $bulk_operations;
    protected $custom = [];

    public function option($key)
    {
        $key = strtoupper($key);
        if (!$this->options) {
            $this->options = [
                // Delivery option
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS               => __('DHL Servicepoint', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR             => __('Door delivery', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_BP               => __('Mailbox delivery', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_H                => __('Hold for collection', 'dhlpwc'),

                // Service option
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_EXP              => __('Expresser', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_BOUW             => __('Delivery to construction site', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_EXW              => __('Ex Works', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_EA               => __('Extra assurance', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_EVE              => __('Evening delivery', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_RECAP            => __('Recap', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_INS              => __('All risks insurance', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE        => __('Reference', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE2       => __('Second reference', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_HANDT            => __('Signature on delivery', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_NBB              => __('No neighbour delivery', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_ADD_RETURN_LABEL => __('Print extra label for return shipment', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_SSN              => __('Undisclosed sender', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_SDD              => __('Same-day delivery', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_S                => __('Saturday delivery', 'dhlpwc'),
                DHLPWC_Model_Meta_Order_Option_Preference::OPTION_AGE_CHECK        => __('Age check 18+', 'dhlpwc'),
            ];
        }
        if (!array_key_exists($key, $this->options)) {
            return $key;
        }
        return $this->options[$key];
    }

    public function parcelType($key)
    {
        $key = strtoupper($key);
        if (!$this->parcel_types) {
            $this->parcel_types = [
                'PARCELTYPE_SMALL'  => __('Small', 'dhlpwc'),
                'PARCELTYPE_MEDIUM' => __('Medium', 'dhlpwc'),
                'PARCELTYPE_LARGE'  => __('Large', 'dhlpwc'),
                'PARCELTYPE_PALLET' => __('Pallet', 'dhlpwc'),
                'PARCELTYPE_BULKY'  => __('Bulky', 'dhlpwc'),
                'PARCELTYPE_XSMALL' => __('Extra Small', 'dhlpwc'),
                'PARCELTYPE_XLARGE' => __('Extra Large', 'dhlpwc'),
            ];
        }
        if (!array_key_exists($key, $this->parcel_types)) {
            return $key;
        }
        return $this->parcel_types[$key];
    }

    public function bulk($key)
    {
        $key = strtoupper($key);
        if (!$this->bulk_operations) {
            $this->bulk_operations = [
                'BP_ONLY'     => __('Mailbox only', 'dhlpwc'),
                'SMALLEST'    => __('Smallest', 'dhlpwc'),
                'SMALL_ONLY'  => __('Small only', 'dhlpwc'),
                'MEDIUM_ONLY' => __('Medium only', 'dhlpwc'),
                'LARGE_ONLY'  => __('Large only', 'dhlpwc'),
                'XSMALL_ONLY' => __('Extra Small only', 'dhlpwc'),
                'XLARGE_ONLY' => __('Extra Large only', 'dhlpwc'),
                'LARGEST'     => __('Largest only', 'dhlpwc'),
            ];
        }
        if (!array_key_exists($key, $this->bulk_operations)) {
            return $key;
        }
        return $this->bulk_operations[$key];
    }

    public function custom($key)
    {
        if (!array_key_exists($key, $this->custom)) {
            switch($key) {
                case self::CUSTOM_TRACK_TRACE_MAIL:
                    $service = DHLPWC_Model_Service_Access_Control::instance();
                    $mail_text = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_TRACK_TRACE_MAIL_TEXT);
                    if ($mail_text) {
                        $string = __($mail_text, 'dhlpwc');
                    } else {
                        $string = __('Once the shipment has been scanned, simply follow it with track & trace. Once the delivery is planned you will see the expected delivery time.', 'dhlpwc');
                    }
                    break;
                default:
                    return $key;
            }
            $this->custom[$key] = $string;
        }
        return $this->custom[$key];
    }
}

endif;
