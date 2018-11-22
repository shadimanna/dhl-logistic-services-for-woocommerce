<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Label')) :

/**
 * This service offers functions to manage labels
 */
class DHLPWC_Model_Service_Label extends DHLPWC_Model_Core_Singleton_Abstract
{
    const CREATE_ERROR = 'create';

    protected $errors = array();

    /**
     * Create a label with data attached to order_id. Optionally, request a specific size
     *
     * @param $order_id
     * @param null $label_size
     */
    public function create($order_id, $label_size = null, $label_options = array(), $label_option_data = array(), $to_business = false)
    {
        $this->clear_error(self::CREATE_ERROR);

        $logic = DHLPWC_Model_Logic_Label::instance();

        // Return label logic
        $return_option = $logic->check_return_option($label_options);
        if ($return_option) {
            $label_options = $logic->remove_return_option($label_options);
        }

        // Hide sender label logic
        $hide_sender_data = $logic->get_hide_sender_data($label_option_data);
        if ($hide_sender_data) {
            $label_option_data = $logic->remove_hide_sender_data($label_option_data);
        }

        /** @var DHLPWC_Model_API_Data_Label $label_data */
        $label_data = $logic->prepare_data($order_id, array(
            'label_size' => $label_size,
            'label_options' => $label_options,
            'label_option_data' => $label_option_data,
            'to_business' => $to_business,
        ), $hide_sender_data);

        // Cancel request if no street and housenumber are set
        if (empty($label_data->shipper->address->street)) {
            $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Shipper %s field is required.', 'dhlpwc'), __('street', 'dhlpwc'))));
            return false;
        }

        if (empty($label_data->shipper->address->number)) {
            $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Shipper %s field is required.', 'dhlpwc'), __('house number', 'dhlpwc'))));
            return false;
        }

        if (empty($label_data->receiver->address->street)) {
            $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Receiver %s field is required.', 'dhlpwc'), __('street', 'dhlpwc'))));
            return false;
        }

        if (empty($label_data->receiver->address->number)) {
            $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Shipper %s field is required.', 'dhlpwc'), __('house number', 'dhlpwc'))));
            return false;
        }

        // Validate if using hide_sender_data
        if ($hide_sender_data) {
            if ((empty($label_data->on_behalf_of->name->first_name) || empty($label_data->on_behalf_of->name->last_name)) && empty($label_data->on_behalf_of->name->company_name)) {
                $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Hide shipper %s field is required.', 'dhlpwc'), __('company', 'dhlpwc'))));
                return false;
            }

            if (empty($label_data->on_behalf_of->address->street)) {
                $this->set_error(self::CREATE_ERROR, ucfirst(printf(__('Hide shipper %s field is required.', 'dhlpwc'), __('street', 'dhlpwc'))));
                return false;
            }

            if (empty($label_data->on_behalf_of->address->city)) {
                $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Hide shipper %s field is required.', 'dhlpwc'), __('city', 'dhlpwc'))));
                return false;
            }

            if (empty($label_data->on_behalf_of->address->number)) {
                $this->set_error(self::CREATE_ERROR, ucfirst(sprintf(__('Hide shipper %s field is required.', 'dhlpwc'), __('house number', 'dhlpwc'))));
                return false;
            }
        }

        $response = $logic->send_request($label_data);
        if (!$response) {
            return false;
        }

        $pdf_info = $logic->create_pdf_file($order_id, $response);

        $meta = new DHLPWC_Model_Service_Order_Meta();
        $meta->save_label($order_id, array(
            'label_id' => $response['labelId'],
            'label_type' => $response['labelType'],
            'label_size' => $label_size,
            'tracker_code' => $response['trackerCode'],
            'routing_code' => $response['routingCode'],
            'order_reference' => $response['orderReference'],

            'pdf' => array(
                'url' => $pdf_info['url'],
                'path' => $pdf_info['path'],
            ),
        ));

        /** Create return label if requested */
        if ($return_option) {
            $return_label_data = $logic->get_return_data($label_data);

            $return_response = $logic->send_request($return_label_data);
            if (!$return_response) {
                // This failed. Remove original successfully created label as well and return.
                $this->delete($order_id, $response['labelId']);
                return false;
            }

            $return_pdf_info = $logic->create_pdf_file($order_id, $return_response);

            $return_meta = new DHLPWC_Model_Service_Order_Meta();
            $return_meta->save_label($order_id, array(
                'label_id' => $return_response['labelId'],
                'label_type' => $return_response['labelType'],
                'label_size' => $label_size,
                'tracker_code' => $return_response['trackerCode'],
                'routing_code' => $return_response['routingCode'],
                'order_reference' => $return_response['orderReference'],
                'is_return' => true,

                'pdf' => array(
                    'url' => $return_pdf_info['url'],
                    'path' => $return_pdf_info['path'],
                ),
            ));
        }

        return true;
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
            $default_signature = $option_service->default_signature($order_id, $preselected_options, $to_business);
            if ($default_signature) {
                $option_service->add_key_to_stack(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_HANDT, $preselected_options);
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

            $service = DHLPWC_Model_Service_Label::instance();
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

    public function combine($order_ids)
    {
        if (!is_array($order_ids) || empty($order_ids)) {
            return null;
        }

        $logic = DHLPWC_Model_Logic_Label::instance();
        $combined = $logic->combine_pdfs($order_ids);

        if (!$combined) {
            return null;
        }

        return $combined['url'];
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

}

endif;
