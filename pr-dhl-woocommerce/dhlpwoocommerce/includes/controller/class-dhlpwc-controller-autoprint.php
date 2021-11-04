<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Autoprint')) :

class DHLPWC_Controller_Autoprint
{

    public function __construct()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_AUTO_PRINT)) {
            add_action('woocommerce_order_status_changed', array($this, 'auto_print'), 10, 3);
        }
    }

    public function auto_print($order_id)
    {
        $shipping_method = get_option('woocommerce_dhlpwc_settings');
        if (!isset($shipping_method['auto_print_on_status'])) {
            return;
        }

        $order = new WC_Order($order_id);
        if (
            !$order ||
            !$order->get_id() ||
            !$order->get_status() ||
            $order->get_status() !== str_replace('wc-', '', $shipping_method['auto_print_on_status'])
        ) {
            return;
        }

        $order_meta_service = DHLPWC_Model_Service_Order_Meta::instance();
        $labels = $order_meta_service->get_labels($order_id);

        // Only create and auto print orders without labels
        if (!empty($labels)) {
            return;
        }

        // Create Label
        $shipment_service = DHLPWC_Model_Service_Shipment::instance();
        $shipment_service->bulk(array($order_id), 'smallest');

        // Get Label ID
        $labels = $order_meta_service->get_labels($order_id);

        if (empty($labels)) {
            return;
        }

        // Send label to printer
        foreach ($labels as $label) {
            $print_service = new DHLPWC_Model_Service_Printer();
            $print_service->send($label['label_id']);
        }
    }
}

endif;
