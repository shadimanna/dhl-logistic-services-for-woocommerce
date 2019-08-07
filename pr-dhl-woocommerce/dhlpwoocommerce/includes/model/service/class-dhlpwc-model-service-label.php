<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Label')) :

/**
 * This service offers functions to manage labels
 */
class DHLPWC_Model_Service_Label extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function combine($order_ids)
    {
        if (!is_array($order_ids) || empty($order_ids)) {
            return null;
        }

        $logic = DHLPWC_Model_Logic_Access_Control::instance();
        $bulk_combine = $logic->check_bulk_combine();

        $logic = DHLPWC_Model_Logic_Label::instance();

        switch ($bulk_combine) {
            case DHLPWC_Model_WooCommerce_Settings_Shipping_Method::COMBINE_A4:
                $combined = $logic->combine_pdfs($order_ids, 'L', 3);
                break;
            default:
                $combined = $logic->combine_pdfs($order_ids);
        }

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

}

endif;
