<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Logic_Access_Control_Capabilities')) :

class DHLPWC_Model_Logic_Access_Control_Capabilities extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function filter_unique_parceltypes($capabilities)
    {
        $unique_parceltypes = array();
        if (is_array($capabilities)) {
            foreach ($capabilities as $capability) {
                if (isset($capability->parcel_type)) {
                    if (!array_key_exists($capability->parcel_type->key, $unique_parceltypes)) {
                        $unique_parceltypes[$capability->parcel_type->key] = $capability->parcel_type;
                    }
                }
            }
        }
        usort($unique_parceltypes, array($this, 'parceltype_weight_sort'));
        return $unique_parceltypes;
    }

    protected function parceltype_weight_sort($one, $two)
    {
        return $one->max_weight_kg > $two->max_weight_kg;
    }

    public function check_capabilities($check = array())
    {
        $order_id = is_array($check) && isset($check['order_id']) ? $check['order_id'] : null;
        $options = is_array($check) && isset($check['options']) && is_array($check['options']) ? $check['options'] : array();

        $data = $order_id ? $this->get_capability_check($order_id) : $this->get_default_capability_check();

        if ($options && is_array($options) && !empty($options)) {
            // TODO temp hack: if you want to send multiple, the API expects multiple option keys as querystring. This is somewhat impossible to do with default PHP and WordPress. For now, it's best to assume this should be fixed in the API and we only send 1 option for now.
            $options = array(reset($options));
            $data->option = implode(',', $options);
        }

        $connector = DHLPWC_Model_API_Connector::instance();
        $sender_type = 'business'; // A webshop is always a business, not a regular consumer type nor a parcelshop. Will leave this as hardcoded for now.
        $response = $connector->get(sprintf('capabilities/%s', $sender_type), $data->to_array());

        $capabilities = array();
        foreach($response as $entry) {
            $capabilities[] = new DHLPWC_Model_API_Data_Capability($entry);
        }

        return $capabilities;
    }

    protected function get_default_capability_check()
    {
        $data = new DHLPWC_Model_API_Data_Capability_Check();
        $data->from_country = 'NL';
        $data->to_country = 'NL';

        return $data;
    }

    protected function get_capability_check($order_id)
    {
        $order = wc_get_order($order_id);
        $receiver_address = new DHLPWC_Model_Meta_Address($order->get_address());

        $service = DHLPWC_Model_Service_Settings::instance();
        $shipper_address = $service->get_default_address();

        $data = new DHLPWC_Model_API_Data_Capability_Check();
        $data->from_country = $shipper_address->country;
        $data->to_country = $receiver_address->country;
        $data->to_business = false ? 'true' : 'false'; // TODO temporary do not send to business
        $data->return_product = null; // TODO
        //$data->parcel_type =
        //$data->option =
        $data->to_postal_code = $receiver_address->postcode;
        $data->account_number = null; // TODO
        $data->organisation_id = null; // TODO

        return $data;
    }

}

endif;
