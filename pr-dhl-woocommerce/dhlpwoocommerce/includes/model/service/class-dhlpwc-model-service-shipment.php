<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Shipment')) :

/**
 * This service offers functions to manage shipments
 */
class DHLPWC_Model_Service_Shipment extends DHLPWC_Model_Core_Singleton_Abstract
{
    const CREATE_ERROR = 'create';

    protected $errors = array();

    /**
     * TODO update for multiple pieces in the future
     * Create a shipment with label data attached to order_id. Optionally, request specific sizes
     *
     * @param $order_id
     * @param null $label_size
     * @return boolean
     */
    public function create($order_id, $label_size = null, $shipment_options = array(), $shipment_option_data = array(), $to_business = false)
    {
        $this->clear_error(self::CREATE_ERROR);
        $logic = DHLPWC_Model_Logic_Shipment::instance();

        // Return label logic
        $return_option = $logic->check_return_option($shipment_options);
        if ($return_option) {
            $shipment_options = $logic->remove_return_option($shipment_options);
        }

        // Hide sender label logic
        $hide_sender_data = $logic->get_hide_sender_data($shipment_option_data);
        if ($hide_sender_data) {
            $shipment_option_data = $logic->remove_hide_sender_data($shipment_option_data);
        }

        /** @var DHLPWC_Model_API_Data_Shipment_Request $shipment_data */
        $shipment_data = $logic->prepare_data($order_id, array(
            'label_size' => $label_size,
            'label_options' => $shipment_options, // TODO Temp
            'label_option_data' => $shipment_option_data, // TODO Temp
            'to_business' => $to_business,
        ), $hide_sender_data);

        // Get validation rules
        $access_service = DHLPWC_Model_Service_Access_Control::instance();
        $validate_address_number = $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_VALIDATION_RULE, 'address_number');

        // Cancel request if no street and housenumber are set
        if (empty($shipment_data->shipper->address->street)) {
            $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Shipper %s field is required.', 'dhlpwc'), __('street', 'dhlpwc'))));
            return false;
        }
        if (empty($shipment_data->shipper->address->number) && $validate_address_number) {
            $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Shipper %s field is required.', 'dhlpwc'), __('house number', 'dhlpwc'))));
            return false;
        }

        if (empty($shipment_data->receiver->address->street)) {
            $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Receiver %s field is required.', 'dhlpwc'), __('street', 'dhlpwc'))));
            return false;
        }
        if (empty($shipment_data->receiver->address->number) && $validate_address_number) {
            $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Receiver %s field is required.', 'dhlpwc'), __('house number', 'dhlpwc'))));
            return false;
        }

        // Validate if using hide_sender_data
        if ($hide_sender_data) {
            if ((empty($shipment_data->on_behalf_of->name->first_name) || empty($shipment_data->on_behalf_of->name->last_name)) && empty($shipment_data->on_behalf_of->name->company_name)) {
                $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Hide shipper %s field is required.', 'dhlpwc'), __('company', 'dhlpwc'))));
                return false;
            }
            if (empty($shipment_data->on_behalf_of->address->street)) {
                $this->set_error(self::CREATE_ERROR, ucfirst(printf(__('Hide shipper %s field is required.', 'dhlpwc'), __('street', 'dhlpwc'))));
                return false;
            }
            if (empty($shipment_data->on_behalf_of->address->city)) {
                $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Hide shipper %s field is required.', 'dhlpwc'), __('city', 'dhlpwc'))));
                return false;
            }
            if (empty($shipment_data->on_behalf_of->address->number) && $validate_address_number) {
                $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Hide shipper %s field is required.', 'dhlpwc'), __('house number', 'dhlpwc'))));
                return false;
            }
        }

        $response = $logic->send_request($shipment_data);
        if (!$response) {
            return false;
        }
        $label = $this->get_first_piece($response);
        if (!$label) {
            return false;
        }

        $label_logic = DHLPWC_Model_Logic_Label::instance();
        $pdf_info = $label_logic->create_pdf_file($order_id, $label['pdf']);

	    $label_data = array(
		    'label_id' => $label['labelId'],
		    'label_type' => $label['labelType'],
		    'label_size' => $label_size,
		    'tracker_code' => $label['trackerCode'],
		    'routing_code' => $label['routingCode'],
		    'order_reference' => $label['orderReference'],

		    'pdf' => array(
			    'url' => $pdf_info['url'],
			    'path' => $pdf_info['path'],
		    )
	    );

	    // Save label request or not
	    $service = DHLPWC_Model_Service_Access_Control::instance();
	    $debug_label_requests = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_LABEL_REQUEST);
	    if ($debug_label_requests) {
		    $label_data['request'] = json_encode($shipment_data);
	    }

        $meta = new DHLPWC_Model_Service_Order_Meta();
        $meta->save_label($order_id, $label_data);

        /** Create return label if requested */
        if ($return_option) {
            $return_shipment_data = $logic->get_return_data($shipment_data);

            $return_response = $logic->send_request($return_shipment_data);
            if (!$return_response) {
                // This failed. Remove original successfully created label as well and return.
                $this->delete($order_id, $label['labelId']);
                return false;
            }
            $return_label = $this->get_first_piece($return_response);
            if (!$return_label) {
                // This failed. Remove original successfully created label as well and return.
                $this->delete($order_id, $label['labelId']);
                return false;
            }

            $return_pdf_info = $label_logic->create_pdf_file($order_id, $return_label['pdf']);

	        $label_data = array(
		        'label_id' => $return_label['labelId'],
		        'label_type' => $return_label['labelType'],
		        'label_size' => $label_size,
		        'tracker_code' => $return_label['trackerCode'],
		        'routing_code' => $return_label['routingCode'],
		        'order_reference' => $return_label['orderReference'],
		        'is_return' => true,

		        'pdf' => array(
			        'url' => $return_pdf_info['url'],
			        'path' => $return_pdf_info['path'],
		        ),
	        );

	        // Save label request or not
	        $service = DHLPWC_Model_Service_Access_Control::instance();
	        $debug_label_requests = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_LABEL_REQUEST);
	        if ($debug_label_requests) {
		        $label_data['request'] = json_encode($return_shipment_data);
	        }

            $return_meta = new DHLPWC_Model_Service_Order_Meta();
            $return_meta->save_label($order_id, $label_data);
        }

        $this->update_order_status($order_id);

        return true;
    }

    protected function get_first_piece($response)
    {
        // TODO currently the code handles single pieces, but can be expanded for multiple pieces in the future
        $current_label_id = null;

        if (!empty($response['pieces'])) {
            foreach($response['pieces'] as $label_response) {
                if (!empty($label_response['labelId'])) {
                    $current_label_id = $label_response['labelId'];
                }
            }
        }

        if (!$current_label_id) {
            return false;
        }

        $connector = DHLPWC_Model_API_Connector::instance();
        $label = $connector->get(sprintf('labels/%s', $current_label_id));

        return $label;
    }

    public function bulk($order_ids, $bulk_size)
    {
        $bulk_success = 0;
        $bulk_fail = 0;

        foreach ($order_ids as $order_id) {

            // Generate to business option
            $access_service = DHLPWC_Model_Service_Access_Control::instance();
            $to_business = $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEFAULT_TO_BUSINESS);

            // Generate options
            $option_service = DHLPWC_Model_Service_Order_Meta_Option::instance();
            $preselected_options = $option_service->get_keys($order_id);

            // Default option settings
            $default_order_id_reference = $option_service->default_order_id_reference($order_id, $preselected_options, $to_business);
            if ($default_order_id_reference) {
                $option_service->add_key_to_stack(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE, $preselected_options);
            }

            // Default option settings
            $default_signature = $option_service->default_signature($order_id, $preselected_options, $to_business);
            if ($default_signature) {
                $option_service->add_key_to_stack(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_HANDT, $preselected_options);
            }

            // Default option settings
            $default_age_check = $option_service->default_age_check($order_id, $preselected_options, $to_business);
            if ($default_age_check) {
                $option_service->add_key_to_stack(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_AGE_CHECK, $preselected_options);
            }

            // Default option settings
            $default_return = $option_service->default_return($order_id, $preselected_options, $to_business);
            if ($default_return) {
                $option_service->add_key_to_stack(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_ADD_RETURN_LABEL, $preselected_options);
            }

            // Default option data
            $option_data = array();
            foreach($preselected_options as $preselected_option) {
                switch ($preselected_option) {
                    case (DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS):
                        $order_meta_service = new DHLPWC_Model_Service_Order_Meta_Option();
                        $parcelshop = $order_meta_service->get_parcelshop($order_id);
                        $option_data[DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS] = $parcelshop->id;
                        break;
                    case (DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE):
                        $option_data[DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE] = $order_id;
                        break;
                }
            }

            // BP preference
            if ($bulk_size == 'bp_only') {
                // Manual check for PS, due to the API not correctly invalidating this label combination
                if (in_array(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS, $preselected_options)) {
                    continue;
                }

                $key = array_search(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR, $preselected_options);
                if ($key !== false) {
                    unset($preselected_options[$key]);
                }
                $preselected_options[] = DHLPWC_Model_Meta_Order_Option_Preference::OPTION_BP;
            }

            // Generate sizes (with requested options)
            $service = DHLPWC_Model_Service_Access_Control::instance();
            $sizes = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CAPABILITY_PARCELTYPE, array(
                'order_id' => $order_id,
                'options' => $preselected_options,
                'to_business' => $to_business,
            ));

            // Skip if no sizes are found
            if (empty($sizes)) {
                continue;
            } else {
                $label_size = null;
                switch($bulk_size) {
                    case 'bp_only':
                    case 'smallest':
                        // Select smallest size available
                        $lowest_weight = null;
                        foreach($sizes as $size) {
                            /** @var DHLPWC_Model_API_Data_Parceltype $size */
                            if ($lowest_weight === null || $size->max_weight_kg < $lowest_weight) {
                                $lowest_weight = $size->max_weight_kg;
                                $label_size = $size->key;
                            }
                        }
                        break;
                    case 'small_only':
                        foreach($sizes as $size) {
                            /** @var DHLPWC_Model_API_Data_Parceltype $size */
                            if (strtolower($size->key) === 'small') {
                                $label_size = $size->key;
                                break;
                            }
                        }
                        break;
                    case 'medium_only':
                        foreach($sizes as $size) {
                            /** @var DHLPWC_Model_API_Data_Parceltype $size */
                            if (strtolower($size->key) === 'medium') {
                                $label_size = $size->key;
                                break;
                            }
                        }
                        break;
                    case 'large_only':
                        foreach($sizes as $size) {
                            /** @var DHLPWC_Model_API_Data_Parceltype $size */
                            if (strtolower($size->key) === 'large') {
                                $label_size = $size->key;
                                break;
                            }
                        }
                        break;
                    case 'xsmall_only':
                        foreach($sizes as $size) {
                            /** @var DHLPWC_Model_API_Data_Parceltype $size */
                            if (strtolower($size->key) === 'xsmall') {
                                $label_size = $size->key;
                                break;
                            }
                        }
                        break;
                    case 'xlarge_only':
                        foreach($sizes as $size) {
                            /** @var DHLPWC_Model_API_Data_Parceltype $size */
                            if (strtolower($size->key) === 'xlarge') {
                                $label_size = $size->key;
                                break;
                            }
                        }
                        break;
                    case 'largest':
                        // Select smallest size available
                        $highest_weight = null;
                        foreach($sizes as $size) {
                            /** @var DHLPWC_Model_API_Data_Parceltype $size */
                            if ($highest_weight === null || $size->max_weight_kg > $highest_weight) {
                                $highest_weight = $size->max_weight_kg;
                                $label_size = $size->key;
                            }
                        }
                        break;
                }
            }

            if (!$label_size) {
                // Couldn't find an appropriate label size
                continue;
            }

            $service = DHLPWC_Model_Service_Shipment::instance();
            $success = $service->create($order_id, $label_size, $preselected_options, $option_data, $to_business);

            if ($success) {
                $bulk_success++;
            } else {
                $bulk_fail++;
            }
        }

        return array(
            'success' => $bulk_success,
            'fail'    => $bulk_fail,
        );
    }

    /**
     * Delete a label attached to a specific order and with a specific label_id
     *
     * @param $order_id
     * @param $label_id
     */
    public function delete($order_id, $label_id)
    {
        $meta = new DHLPWC_Model_Service_Order_Meta();
        $label = $meta->delete_label($order_id, $label_id);
        if ($label) {
            $logic = DHLPWC_Model_Logic_Label::instance();
            $logic->delete_pdf_file($label['pdf']['path']);
        }
    }

    protected function clear_error($key)
    {
        if (array_key_exists($key, $this->errors)) {
            $this->errors[$key] = null;
            unset($this->errors[$key]);
        }
    }

    protected function set_error($key, $value)
    {
        $this->errors[$key] = $value;
    }

    public function get_error($key)
    {
        if (!array_key_exists($key, $this->errors)) {
            return null;
        }
        return $this->errors[$key];
    }

    protected function update_order_status($order_id)
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');
        if (!$shipping_method['change_order_status_to'] || $shipping_method['change_order_status_to'] === 'null') {
            return;
        }

        $order = wc_get_order($order_id);
        if ($order->get_status() === 'pending' && $shipping_method['change_order_status_from_wc-pending'] === 'yes'
            || $order->get_status() === 'processing' && $shipping_method['change_order_status_from_wc-processing'] === 'yes'
            || $order->get_status() === 'on-hold' && $shipping_method['change_order_status_from_wc-on-hold'] === 'yes') {
            $order->update_status($shipping_method['change_order_status_to']);
        }
        return;
    }
}

endif;
