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

            if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_CREATE)) {
                add_filter('bulk_actions-edit-shop_order', array($this, 'add_bulk_create_action'));
                add_action('admin_action_dhlpwc_create_labels', array($this, 'create_multiple_labels'));
                add_action('admin_notices', array($this, 'bulk_create_notice'));
            }

            if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_PRINT)) {
                add_filter('bulk_actions-edit-shop_order', array($this, 'add_bulk_print_action'));
                add_action('admin_action_dhlpwc_print_labels', array($this, 'print_multiple_labels'));
            }
        }
    }

    public function add_bulk_create_action($bulk_actions)
    {
        $bulk_actions['dhlpwc_create_labels'] = __('DHL - Create label', 'dhlpwc');
        return $bulk_actions;
    }

    public function create_multiple_labels()
    {
        if (!isset($_REQUEST['post']) && !is_array($_REQUEST['post'])) {
            return;
        }

        $order_ids = isset($_GET['post']) && is_array($_GET['post']) ? wc_clean($_GET['post']) : array();

        $service = DHLPWC_Model_Service_Label::instance();
        $success_data = $service->bulk($order_ids);

        // of course using add_query_arg() is not required, you can build your URL inline
        $location = add_query_arg(array(
            'post_type'             => 'shop_order',
            'dhlpwc_labels_created' => 1,
            'dhlpwc_create_count'   => $success_data['success'],
            'dhlpwc_fail_count'     => $success_data['fail'],
        ), 'edit.php');

        wp_redirect(admin_url($location));
        exit;
    }

    public function bulk_create_notice()
    {
        $screen = get_current_screen();
        if ($screen->base == 'edit' && $screen->post_type == 'shop_order') {
            if (isset($_GET['dhlpwc_labels_created'])) {
                $created = isset($_GET['dhlpwc_create_count']) && is_numeric($_GET['dhlpwc_create_count']) ? wc_clean($_GET['dhlpwc_create_count']) : 0;
                $failed = isset($_GET['dhlpwc_fail_count']) && is_numeric($_GET['dhlpwc_fail_count']) ? wc_clean($_GET['dhlpwc_fail_count']) : 0;

                $messages = array();
                if ($created) {
                    $messages[] = sprintf(_n('Label successfully created.', '%s labels successfully created.', number_format_i18n($created), 'dhlpwc'), number_format_i18n($created));
                }
                if ($failed) {
                    $messages[] = sprintf(_n('Label could not be created.', '%s labels failed to create.', number_format_i18n($failed), 'dhlpwc'), number_format_i18n($failed));
                }

                if (!empty($messages)) {
                    $view = new DHLPWC_Template('admin.notice');
                    $view->render(array(
                        'messages'   => $messages,
                    ));
                }
            }
        }
    }

    public function add_bulk_print_action($bulk_actions)
    {
        $bulk_actions['dhlpwc_print_labels'] = __('DHL - Print label', 'dhlpwc');
        return $bulk_actions;
    }

    public function print_multiple_labels()
    {
        $order_ids = isset($_GET['post']) && is_array($_GET['post']) ? wc_clean($_GET['post']) : array();

        $service = DHLPWC_Model_Service_Label::instance();
        $url = $service->combine($order_ids);

        if (!$url) {
            wp_redirect('');
        }

        wp_redirect($url);
        exit;
    }

    public function add_label_column($columns)
    {
        $offset = (integer)array_search("order_total", array_keys($columns));

        return array_slice($columns, 0, ++$offset, true) +
            array('dhlpwc_label_created' => __('DHL label info', 'dhlpwc')) +
            array_slice($columns, $offset, null, true);
    }

    public function add_label_column_content($column)
    {
        switch($column) {
            case 'dhlpwc_label_created':
                $service = DHLPWC_Model_Service_Access_Control::instance();
                $external_link = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPEN_LABEL_LINKS_EXTERNAL);

                $service = DHLPWC_Model_Service_Order_Meta::instance();
                $labels = $service->get_labels(get_the_ID());

                foreach($labels as $label) {
                    $view = new DHLPWC_Template('order.meta.label');
                    if (!is_array($label) || !isset($label['label_size']) || !isset($label['tracker_code'])) {
                        continue;
                    }
                    $is_return = (!empty($label['is_return'])) ? $label['is_return'] : false;

                    $view->render(array(
                        'url'               => $label['pdf']['url'],
                        'label_size'        => $label['label_size'],
                        'label_description' => __(sprintf('PARCELTYPE_%s', $label['label_size']), 'dhlpwc'),
                        'tracker_code'      => $label['tracker_code'],
                        'is_return'         => $is_return,
                        'external_link'     => $external_link,
                    ));
                }
                break;
            case 'shipping_address':
                $this->parcelshop_info(new WC_Order(get_the_ID()), true);
                break;
        }
    }

    /**
     * DHL ServicePoint information screen for an order.
     * Note: we're not using $order, but wanted to add the $compact var, WooCommerce automatically passes the order when hooked
     * into 'woocommerce_admin_order_data_after_shipping_address'
     *
     * @param null $order
     * @param bool $compact
     */
    public function parcelshop_info($order, $compact = false)
    {
        $service = new DHLPWC_Model_Service_Order_Meta_Option();
        $parcelshop_meta = $service->get_option_preference($order->get_order_number(), DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS);

        if ($parcelshop_meta) {
            $service = new DHLPWC_Model_Service_Parcelshop();
            /** @var WC_Order $order */
            $parcelshop = $service->get_parcelshop($parcelshop_meta['input'], $order->get_shipping_country());

            if (!$parcelshop || !isset($parcelshop->name) || !isset($parcelshop->address)) {
                $view = new DHLPWC_Template('unavailable');
                $view->render();
                return;
            }

            $view = new DHLPWC_Template('parcelshop-info');
            $view->render(array(
                'warning' => __('Send to DHL ServicePoint', 'dhlpwc'),
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
            wp_enqueue_style('dhlpwc-admin-order-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.admin.css');
        }
    }

}

endif;