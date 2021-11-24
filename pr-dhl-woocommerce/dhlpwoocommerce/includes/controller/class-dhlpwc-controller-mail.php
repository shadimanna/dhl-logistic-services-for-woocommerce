<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Mail')) :

class DHLPWC_Controller_Mail
{

    public function __construct()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_TRACK_TRACE_MAIL)) {
            add_action('woocommerce_email_after_order_table', array($this, 'add_track_trace_to_completed_order_mail'), 10, 4);
        }

        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SERVICEPOINT_IN_ORDER_MAIL)) {
            add_action('woocommerce_email_customer_details', array($this, 'add_service_point_to_customer_details_order_email'), 100, 4);
        }
    }

    public function add_service_point_to_customer_details_order_email($order, $sent_to_admin, $plain_text, $email)
    {
        $allowed_email_ids = array(
            'new_order',
            'customer_on_hold_order',
            'customer_completed_order',
        );

        // Continue only if it's a valid template
        if (!$email || !isset($email->id) || !in_array($email->id, $allowed_email_ids)) {
            return;
        }
        // Continue only if order id is set
        if (!$order || !$order->get_id()) {
            return;
        }

        $service = new DHLPWC_Model_Service_Order_Meta_Option();
        $parcelshop = $service->get_parcelshop($order->get_id());
        if (!$parcelshop) {
            return;
        }

        // Don't generate HTML when using plain text
        if ($plain_text) {
            $view = new DHLPWC_Template('mail.plain.service-point');
        } else {
            $view = new DHLPWC_Template('mail.service-point');
        }

        $view->render(array(
            'label'   => __('Selected delivery location', 'dhlpwc'),
            'name'    => $parcelshop->name,
            'address' => $parcelshop->address
        ));

    }

    public function add_track_trace_to_completed_order_mail($order, $sent_to_admin, $plain_text, $email )
    {
        // Continue only if it's a completed order email
        if(!$email || !isset($email->id) || $email->id != 'customer_completed_order') {
            return;
        }

        // Continue only if order id is set
        if (!$order || !$order->get_id()) {
            return;
        }

        /** @var WC_Order $order **/
        $locale = str_replace('_', '-', get_locale());

        $service = DHLPWC_Model_Service_Postcode::instance();
        $postcode = $service->get_postcode_from_order($order->get_id());

        $service = DHLPWC_Model_Service_Track_Trace::instance();
        $tracking_codes = $service->get_track_trace_from_order($order->get_id());

        // Add urls
        $tracking_codesets = array();
        foreach($tracking_codes as $tracking_code) {
            $tracking_codeset = array();
            $tracking_codeset['url'] = $service->get_url($tracking_code, $postcode, $locale);
            $tracking_codeset['code'] = $tracking_code;
            $tracking_codesets[] = $tracking_codeset;
        }

        // Don't generate HTML when using plain text
        if ($plain_text) {
            $view = new DHLPWC_Template('mail.plain.track-and-trace');
        } else {
            $view = new DHLPWC_Template('mail.track-and-trace');
        }

        $text = DHLPWC_Model_Service_Translation::instance()->custom(DHLPWC_Model_Service_Translation::CUSTOM_TRACK_TRACE_MAIL);

        // Create track and trace output
        $view->render(array(
            'text' => $text,
            'tracking_codesets' => $tracking_codesets)
        );
    }

}

endif;
