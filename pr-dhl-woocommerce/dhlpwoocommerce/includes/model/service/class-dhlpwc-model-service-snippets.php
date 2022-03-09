<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Snippets')) :

class DHLPWC_Model_Service_Snippets extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function default_order_reference()
    {
        $snippet = <<<'EOD'
// The following example code can be added to the child theme's functions.php file
add_filter('dhlpwc_default_reference_value', 'dhlpwc_change_reference_value', 10, 2);

function dhlpwc_change_reference_value($reference_value, $order_id)
{
    // Set a difference reference value
    $order = new WC_Order($order_id);
    $new_reference_value = $order->get_order_number();
    if (!$new_reference_value) {
        return $reference_value;
    }
    return $new_reference_value;
}
EOD;
        return esc_html($snippet);
    }

    public function default_order_reference2()
    {
        $snippet = <<<'EOD'
// The following example code can be added to the child theme's functions.php file
add_filter('dhlpwc_default_reference2_value', 'dhlpwc_change_reference2_value', 10, 2);

function dhlpwc_change_reference2_value($reference2_value, $order_id)
{
    // Set a difference reference value
    $order = new WC_Order($order_id);
    $new_reference2_value = $order->get_order_number();
    if (!$new_reference2_value) {
        return $reference2_value;
    }
    return $new_reference2_value;
}
EOD;
        return esc_html($snippet);
    }

    public function create_order_label_note()
    {
        $snippet = <<<'EOD'
// The following example code can be added to the child theme's functions.php file
add_action('dhlpwc_create_label', 'dhlpwc_add_order_note_on_create_label', 10, 2);

function dhlpwc_add_order_note_on_create_label($order_id, $label_data)
{
    // Create an order note with create label data
    $order = new WC_Order($order_id);
    if ($label_data['is_return'] !== true) {
        $note = __('Creating label with tracking code: ' . $label_data['tracker_code']);
    } else {
        $note = __('Creating a return label with tracking code: ' . $label_data['tracker_code']);
    }
    $order->add_order_note($note);
}
EOD;
        return esc_html($snippet);
    }

}

endif;
