<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Label')) :

/**
 * This service offers functions to manage labels
 */
class DHLPWC_Model_Service_Label extends DHLPWC_Model_Core_Singleton_Abstract
{

    /**
     * Create a label with data attached to order_id. Optionally, request a specific size
     *
     * @param $order_id
     * @param null $label_size
     */
    public function create($order_id, $label_size = null, $label_options = array(), $to_business = false)
    {
        $logic = DHLPWC_Model_Logic_Label::instance();

        $label_data = $logic->prepare_data($order_id, array(
            'label_size' => $label_size,
            'label_options' => $label_options,
            'to_business' => $to_business,
        ));

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

        return true;
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

}

endif;
