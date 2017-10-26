<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Admin_Order')) :

class DHLPWC_Controller_Admin_Order
{

    public function __construct()
    {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'load_styles'));

            add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'parcelshop_info'), 10, 1);

            $service = DHLPWC_Model_Service_Access_Control::instance();
            if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_COLUMN_INFO)) {
                add_filter('manage_edit-shop_order_columns', array($this, 'add_label_column'), 10, 1);
                add_action('manage_shop_order_posts_custom_column', array($this, 'add_label_column_content'), 10, 1);
            }
        }
    }

    public function add_label_column($columns)
    {
        $offset = (integer)array_search("order_total", array_keys($columns));

        return array_slice($columns, 0, ++$offset, true) +
            array('dhlpwc_label_created' => __('DHL Label', 'dhlpwc')) +
            array_slice($columns, $offset, null, true);
    }

    public function add_label_column_content($column)
    {
        switch($column) {
            case 'dhlpwc_label_created':
                $service = DHLPWC_Model_Service_Order_Meta::instance();
                $labels = $service->get_labels(get_the_ID());

                foreach($labels as $label) {
                    $view = new DHLPWC_Template('order.meta.label');
                    if (!is_array($label) || !isset($label['label_size']) || !isset($label['tracker_code'])) {
                        continue;
                    }

                    $view->render(array(
                        'label_size' => $label['label_size'],
                        'tracker_code' => $label['tracker_code'],
                    ));
                }
                break;
            case 'shipping_address':
                $this->parcelshop_info(null, true);
                break;
        }
    }


    /**
     * Parcelshop information screen for an order.
     * Note: we're not using $order, but wanted to add the $compact var, WooCommerce automatically passes the order when hooked
     * into 'woocommerce_admin_order_data_after_shipping_address'
     *
     * @param null $order
     * @param bool $compact
     */
    public function parcelshop_info($order = null, $compact = false)
    {
        $service = new DHLPWC_Model_Service_Order_Meta_Option();
        $parcelshop_meta = $service->get_option_preference(get_the_ID(), DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS);

        if ($parcelshop_meta) {
            $service = new DHLPWC_Model_Service_Checkout();
            $parcelshop = $service->get_parcelshop($parcelshop_meta['input']);

            $view = new DHLPWC_Template('checkout.parcelshop.info');
            $view->render(array(
                'warning' => __('Send to DHL Parcelshop', 'dhlpwc'),
                'name' => $parcelshop->name,
                'address' => $parcelshop->address,
                'compact' => $compact
            ));
        }
    }

    public function load_styles()
    {
        $screen = get_current_screen();
        if ($screen->base == 'post' && $screen->post_type == 'shop_order') {
            wp_enqueue_style('dhlpwc-admin-order-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.admin.order.css');
        }
    }

}

endif;