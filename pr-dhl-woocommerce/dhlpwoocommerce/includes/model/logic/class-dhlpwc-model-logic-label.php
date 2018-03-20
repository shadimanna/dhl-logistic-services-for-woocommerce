<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Logic_Label')) :

class DHLPWC_Model_Logic_Label extends DHLPWC_Model_Core_Singleton_Abstract
{

    const FILE_PREFIX = 'dhlpwc-label-';

    public function prepare_data($order_id, $options = array())
    {
        $order = wc_get_order($order_id);
        $receiver_address = $order->get_address('shipping') ?: $order->get_address();
        $business = isset($options['to_business']) && $options['to_business'] ? true : false;

        $service = DHLPWC_Model_Service_Settings::instance();
        $shipper_address = $service->get_default_address()->to_array();

        $keys = isset($options['label_options']) && is_array($options['label_options']) ? $options['label_options'] : array();
        $service = DHLPWC_Model_Service_Label_Option::instance();
        $request_options = $service->get_request_options($order_id, $keys);

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

        return $label;
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
        $path = $upload_path['path'] . '/' . $file_name;
        $url = $upload_path['url'] . '/' . $file_name;

        // TODO, handle errors
        $file_save_status = file_put_contents($path, $pdf);

        return array(
            'url' => $url,
            'path' => $path
        );
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
            $address['street'] = trim($street_parts[1]);
            $address['number'] = trim($street_parts[2]);
        }

        return $address;
    }

    protected function get_account_id()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();
        return $shipping_methods['dhlpwc']->settings['account_id'];
    }

}

endif;
