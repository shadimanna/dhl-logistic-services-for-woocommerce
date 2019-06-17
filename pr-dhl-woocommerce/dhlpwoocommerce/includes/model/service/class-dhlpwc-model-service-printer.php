<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Printer')) :

class DHLPWC_Model_Service_Printer extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function get_printers()
    {
        $connector = DHLPWC_Model_API_Connector::instance();
        $printers_data = $connector->get('printers');

        if (!$printers_data || !is_array($printers_data) || empty($printers_data)) {
            return false;
        }

        $printers = array();
        foreach($printers_data as $printer_data) {
            $printer = new DHLPWC_Model_API_Data_Printer($printer_data);
            $printers[] = $printer->to_array();
        }

        return array(
            'printers' => $printers
        );
    }

    public function send($label_ids)
    {
        $service = DHLPWC_Model_Service_Settings::instance();
        $printer_id = $service->get_printer_id();

        if (!$this->validate_printer_id($printer_id)) {
            return false;
        }

        if (!is_array($label_ids)) {
            $label_ids = array($label_ids);
        }

        $connector = DHLPWC_Model_API_Connector::instance();
        $connector->post('printers/'.$printer_id.'/jobs', array(
            'id' => (string)new DHLPWC_Model_UUID(),
            'labelIds' => $label_ids
        ));

        if ($connector->is_error) {
            return false;
        }

        return true;
    }

    public function get_label_ids($order_ids)
    {
        if (!is_array($order_ids) || empty($order_ids)) {
            return array();
        }

        $label_ids = array();
        foreach ($order_ids as $order_id) {
            $meta_service = new DHLPWC_Model_Service_Order_Meta();
            $labels = $meta_service->get_labels($order_id);

            if (!empty($labels)) {
                foreach ($labels as $label_data) {
                    $label = new DHLPWC_Model_Meta_Order_Label($label_data);
                    if ($label->label_id) {
                        $label_ids[] = $label->label_id;
                    }
                }
            }
        }

        return $label_ids;
    }

    protected function validate_printer_id($uuid)
    {
        if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
            return false;
        }
        return true;
    }
}

endif;
