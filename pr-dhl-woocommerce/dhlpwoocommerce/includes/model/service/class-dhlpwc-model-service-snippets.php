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

}

endif;
