<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Logic_Label')) :

class DHLPWC_Model_Logic_Label extends DHLPWC_Model_Core_Singleton_Abstract
{

    const FILE_PREFIX = 'dhlpwc-label-';
    const BATCH_FILE_PREFIX = 'dhlpwc-labels-';

    public function prepare_data($order_id, $options = array(), $replace_shipping_address = null)
    {
        $order = wc_get_order($order_id);
        $receiver_address = $order->get_address('shipping') ?: $order->get_address();
        $receiver_billing_address = $order->get_address('billing') ?: $order->get_address();
        $receiver_address['email'] = $receiver_billing_address['email'];
        $receiver_address['phone'] = preg_replace('/\D+/', '', $receiver_billing_address['phone']);
        unset($receiver_billing_address);

        $business = isset($options['to_business']) && $options['to_business'] ? true : false;

        $service = DHLPWC_Model_Service_Settings::instance();
        $shipper_address = $service->get_default_address()->to_array();

        $keys = isset($options['label_options']) && is_array($options['label_options']) ? $options['label_options'] : array();
        $option_data = isset($options['label_option_data']) && is_array($options['label_option_data']) ? $options['label_option_data'] : array();
        $service = DHLPWC_Model_Service_Label_Option::instance();
        $request_options = $service->get_request_options($keys, $option_data);

        $label = new DHLPWC_Model_API_Data_Label(array(
            'label_id'        => (string)new DHLPWC_Model_UUID(),
            'order_reference' => (string)$order_id,
            'parcel_type_key' => isset($options['label_size']) ? $options['label_size'] : 'SMALL',
            'receiver'        => $this->prepare_address_data($receiver_address, $business),
            'shipper'         => $this->prepare_address_data($shipper_address, true),
            'account_id'      => $this->get_account_id(),
            'options'         => $request_options,
            'piece_number'    => 1,
            'quantity'        => 1,
            'application'     => $this->version_string(),
        ));

        if ($replace_shipping_address) {
            // Use the same country as the default
            $replace_shipping_address['country'] = $shipper_address['country'];
            $label->on_behalf_of = $this->prepare_address_data($replace_shipping_address, true);
        }

        return $label;
    }

    public function get_return_data($label_data)
    {
        /* @var DHLPWC_Model_API_Data_Label $label_data */
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $alternate_return = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_ALTERNATE_RETURN_ADDRESS);

        if ($alternate_return) {
            $service = DHLPWC_Model_Service_Settings::instance();
            $receiver = $this->prepare_address_data($service->get_return_address()->to_array(), true);
        } else {
            $receiver = $label_data->shipper;
        }
        $shipper = $label_data->receiver;

        $label_data->label_id = (string)new DHLPWC_Model_UUID();
        $label_data->return_label = true;
        $label_data->shipper = $shipper;
        $label_data->receiver = $receiver;

        $service = DHLPWC_Model_Service_Label_Option::instance();
        $label_data->options = $service->get_request_options(array(
            DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR,
        ));

        return $label_data;
    }

    public function check_return_option($label_options)
    {
        return in_array(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_ADD_RETURN_LABEL, $label_options);
    }

    public function remove_return_option($label_options)
    {
        return array_diff($label_options, array(
            DHLPWC_Model_Meta_Order_Option_Preference::OPTION_ADD_RETURN_LABEL,
        ));
    }

    public function get_hide_sender_data($label_data)
    {
        if (!array_key_exists(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_SSN, $label_data)) {
            return null;
        }

        $cleaned_data = wp_unslash(wc_clean($label_data[DHLPWC_Model_Meta_Order_Option_Preference::OPTION_SSN]));
        $parsed_data = json_decode($cleaned_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (!$this->validate_flat_address($parsed_data)) {
            return null;
        }

        return $parsed_data;
    }

    public function validate_flat_address($data)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        // Check if all keys exist
        $expected_keys = array(
            'first_name',
            'last_name',
            'company',
            'postcode',
            'city',
            'number',
            'email',
            'phone',
        );

        foreach($expected_keys as $expected_key) {
            if (!array_key_exists($expected_key, $data)) {
                return false;
            }
        }

        return true;
    }

    public function remove_hide_sender_data($label_data)
    {
        return array_diff_key($label_data, array(
            DHLPWC_Model_Meta_Order_Option_Preference::OPTION_SSN => true,
        ));
    }

    public function send_request($label)
    {
        $connector = DHLPWC_Model_API_Connector::instance();
        return $connector->post('labels', $label->to_array());
    }

    public function create_pdf_file($order_id, $request)
    {
        $pdf = base64_decode($request["pdf"]);

        $file_name = self::FILE_PREFIX . $order_id . '_' . str_shuffle((string)time() . rand(1000, 9999)) . '.pdf';
        $upload_path = wp_upload_dir();
        $path = $upload_path['path'] . DIRECTORY_SEPARATOR . $file_name;
        $url = $upload_path['url'] . '/' . $file_name;

        // TODO, handle errors
        $file_save_status = file_put_contents($path, $pdf);

        return array(
            'url' => $url,
            'path' => $path
        );
    }

    public function combine_pdfs($order_ids)
    {
        $loader = DHLPWC_Libraryloader::instance();
        $pdf_merger = $loader->get_pdf_merger();
        $files = 0;

        foreach ($order_ids as $order_id) {
            $meta_service = new DHLPWC_Model_Service_Order_Meta();
            $labels = $meta_service->get_labels($order_id);

            if (!empty($labels)) {

                foreach ($labels as $label_data) {
                    $label = new DHLPWC_Model_Meta_Order_Label($label_data);
                    $path = $label->pdf->path;
                    if (!file_exists($path)) {
                        $path = $this->restore_pdf_path($path);
                        if (!$path) {
                            // Could not fix
                            continue;
                        }
                    }
                    $pdf_merger->addPDF($path, 'all');
                    $files++;
                }
            }
        }

        if (!$files) {
            return null;
        }

        $order_id_tag = implode('_', $order_ids);
        $order_id_tag = substr($order_id_tag,0,20); // Limit the length if a lot of orders are selected

        $file_name = self::BATCH_FILE_PREFIX . $order_id_tag . '_' . str_shuffle((string)time() . rand(1000, 9999)) . '.pdf';
        $upload_dir = wp_upload_dir();
        $path = $upload_dir['path'] . DIRECTORY_SEPARATOR . $file_name;
        $url = $upload_dir['url'] . '/' . $file_name;

        $pdf_merger->merge('file', $path);

        return array(
            'url' => $url,
            'path' => $path
        );
    }

    protected function restore_pdf_path($path)
    {
        $upload_dir = wp_upload_dir();

        // This is an attempt to fix the path if backslashes have been removed by wordpress
        if (file_exists(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path))) {

            $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);

        } else {

            $stripped_path = str_replace(array('/', '\\'), '', $path);
            $stripped_upload_path = str_replace(array('/', '\\'), '', $upload_dir['basedir']);

            if (!substr($stripped_path, 0, strlen($stripped_upload_path)) === $stripped_upload_path) {
                // Upload base dir has since changed. Unfortunately impossible to determine the path now
                return null;
            }

            $end_path = substr($path, strlen($stripped_upload_path));

            if (!file_exists($upload_dir['basedir'] . DIRECTORY_SEPARATOR . $end_path)) {
                // End path seems incorrect. Attempt to fix it
                $number_start = strcspn($end_path, '0123456789');
                $end_path = substr($end_path, $number_start);

                $path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $end_path;
                $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);

                if (!file_exists($path)) {
                    // Final attempt, try to inject a DS after the date, before filename
                    $position = strpos($path, self::FILE_PREFIX);
                    $path = substr_replace($path, DIRECTORY_SEPARATOR, $position, 0);
                }
            }

        }

        if (!file_exists($path)) {
            // Still can't find it
            return null;
        }

        return $path;
    }

    public function delete_pdf_file($path)
    {
        if (!$this->validate_pdf_file($path)) {
            return false;
        }

        unlink($path);
    }

    protected function version_string()
    {
        $wp_version = get_bloginfo('version');
        $application_string = sprintf('WordPress:%1$s', $wp_version);
        return substr($application_string, 0, 16);
    }

    protected function validate_pdf_file($path)
    {
        $upload_dir = wp_upload_dir();
        if (!strpos($path, $upload_dir['basedir']) === 0) {
            return false;
        }

        $file = basename($path);
        if (!strpos($file, self::FILE_PREFIX) === 0) {
            return false;
        }

        $extension = 'pdf';
        if (!substr_compare($file, $extension, -strlen($extension)) === 0) {
            return false;
        }

        return true;
    }

    protected function prepare_address_data($address, $business = false)
    {
        $address = $this->prepare_street_address($address);

        return new DHLPWC_Model_API_Data_Label_Address(array(
            'name'          => array(
                'first_name'   => $address['first_name'],
                'last_name'    => $address['last_name'],
                'company_name' => $address['company'],
            ),
            'address'       => array(
                'country_code' => $address['country'],
                'postal_code'  => WC_Validation::format_postcode($address['postcode'], $address['country']),
                'city'         => $address['city'],
                'street'       => $address['street'],
                'number'       => $address['number'],
                'is_business'  => $business,
                'addition'     => '',
            ),
            'email'         => $address['email'],
            'phone_number' => $address['phone'],
        ));
    }

    protected function prepare_street_address($address)
    {
        if (!isset($address['street'])) {
            $address['street'] = join(' ', array($address['address_1'], $address['address_2']));
        }

        if (!isset($address['number'])) {
            preg_match('/([^\d]+)\s?(.+)/i', $address['street'], $street_parts);
            $street = trim($street_parts[1]);
            $number = trim($street_parts[2]);

            // Check if $number has numbers
            if (preg_match("/\d/", $number) === 0) {
                // Try a reverse parse
                preg_match('/([\d]\w+)\s?(.+)/i', $address['street'], $street_parts);
                $number = trim($street_parts[1]);
                $street = trim($street_parts[2]);
            }

            // Check if $number has numbers
            if (preg_match("/\d/", $number) > 0) {
                $address['street'] = $street;
                $address['number'] = $number;
            }
        }

        return $address;
    }

    protected function get_account_id()
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');
        return $shipping_method['account_id'];
    }

}

endif;
