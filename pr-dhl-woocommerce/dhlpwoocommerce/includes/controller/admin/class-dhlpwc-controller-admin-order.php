<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Admin_Order')) :

class DHLPWC_Controller_Admin_Order
{

    public function __construct()
    {
        if (is_admin()) {

            add_action('admin_enqueue_scripts', array($this, 'load_styles'));
            add_action('admin_enqueue_scripts', array($this, 'load_scripts'));

            add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'parcelshop_info'), 10, 1);

            $service = DHLPWC_Model_Service_Access_Control::instance();
            if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_COLUMN_INFO)) {
                add_filter('manage_edit-shop_order_columns', array($this, 'add_label_column'), 10, 1);
                add_action('manage_shop_order_posts_custom_column', array($this, 'add_label_column_content'), 10, 2);
            }

            if ($bulk_options = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_CREATE)) {
                add_filter('bulk_actions-edit-shop_order', array($this, 'add_bulk_create_actions'));
                foreach ($bulk_options as $bulk_option) {
                    add_action('admin_action_dhlpwc_create_labels_' . $bulk_option, array($this, 'create_multiple_labels_' . $bulk_option));
                }
                add_action('admin_notices', array($this, 'bulk_create_notice'));
            }

            if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_DOWNLOAD)) {
                add_filter('bulk_actions-edit-shop_order', array($this, 'add_bulk_download_action'));
                add_action('admin_action_dhlpwc_download_labels', array($this, 'download_multiple_labels'));
            }

            if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_PRINTER)) {
                add_filter('bulk_actions-edit-shop_order', array($this, 'add_bulk_print_action'));
                add_action('admin_action_dhlpwc_print_labels', array($this, 'print_multiple_labels'));
                add_action('admin_notices', array($this, 'bulk_print_notice'));
            }

            if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DELIVERY_TIMES)) {
                add_filter('views_edit-shop_order',array($this, 'add_delivery_times_filter'), 10, 1);

                add_filter('manage_edit-shop_order_columns', array($this, 'add_delivery_time_column'));
                add_action( 'manage_shop_order_posts_custom_column', array($this, 'add_delivery_time_column_content'), 10, 2 );

                add_filter( 'manage_edit-shop_order_sortable_columns', array($this, 'sort_delivery_time_column'));
                add_action( 'pre_get_posts', array($this, 'delivery_date_orderby'));
            }
        }
    }

    public function add_delivery_times_filter($views)
    {
        $result = new WP_Query(array(
            'post_type'   => 'shop_order',
            'post_status' => $this->get_available_statuses(),
            'meta_key'  => DHLPWC_Model_Service_Delivery_Times::ORDER_TIME_SELECTION,
            )
        );

        $views['dhlpwc_delivery_date'] = sprintf('%1$s%2$s%3$s%4$s',
            '<a href="'.admin_url('edit.php?post_type=shop_order&orderby=dhlpwc_delivery_date&order=asc').'">',
            esc_attr(__('Delivery date', 'dhlpwc')),
            '</a>',
            '<span class="count">(' . $result->found_posts . ')</span>');

        return $views;
    }

    public function sort_delivery_time_column($columns)
    {
        $columns['dhlpwc_delivery_time'] = 'dhlpwc_delivery_date';
        return $columns;
    }

    public function delivery_date_orderby($query)
    {
        if (!is_admin()) {
            return;
        }

        if (!$this->is_ordergrid_screen()) {
            return;
        }

        $orderby = $query->get('orderby');

        if ($orderby === 'dhlpwc_delivery_date') {
            $meta_query = array(
                array(
                    'key'     => DHLPWC_Model_Service_Delivery_Times::ORDER_TIME_SELECTION,
                    'value' => serialize('timestamp'),
                    'compare' => 'LIKE',
                ),
            );

            $query->set('meta_query', $meta_query);
            $query->set('post_status', $this->get_available_statuses());
            $query->set('orderby', 'meta_value');
        }
    }

    public function add_delivery_time_column($columns) {
        $columns['dhlpwc_delivery_time'] = __('Delivery date', 'dhlpwc');
        return $columns;
    }

    public function add_delivery_time_column_content($column, $order_id)
    {
        if ($column !== 'dhlpwc_delivery_time') {
            return;
        }

        $service = DHLPWC_Model_Service_Delivery_Times::instance();
        $time_selection = $service->get_order_time_selection($order_id);
        if ($time_selection) {
            $current_timestamp = time();
            $time_left = human_time_diff($current_timestamp, $time_selection->timestamp);
            if ($current_timestamp > $time_selection->timestamp) {
                $time_left = null;
            }
            $delivery_time = $service->parse_time_frame($time_selection->date, $time_selection->start_time, $time_selection->end_time);
            $shipping_advice = $service->get_shipping_advice($time_selection->timestamp);
            $shipping_advice_class = $service->get_shipping_advice_class($time_selection->timestamp);

            if (!empty($delivery_time)) {
                $view = new DHLPWC_Template('admin.order.delivery-times');
                $view->render(array(
                    'time_left'             => $time_left,
                    'delivery_time'         => $delivery_time,
                    'shipping_advice'       => $shipping_advice,
                    'shipping_advice_class' => $shipping_advice_class,
                ));
            }
        }
    }

    public function add_bulk_create_actions($bulk_actions)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $bulk_options = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_CREATE);
        foreach ($bulk_options as $bulk_option) {
            $bulk_string = DHLPWC_Model_Service_Translation::instance()->bulk($bulk_option);
            $bulk_actions['dhlpwc_create_labels_' . $bulk_option] = sprintf(__('DHL - Create label (%s)', 'dhlpwc'), $bulk_string);
        }
        return $bulk_actions;
    }

    public function create_multiple_labels_bp_only()
    {
        $this->create_multiple_labels('bp_only');
    }

    public function create_multiple_labels_smallest()
    {
        $this->create_multiple_labels('smallest');
    }

    public function create_multiple_labels_small_only()
    {
        $this->create_multiple_labels('small_only');
    }

    public function create_multiple_labels_medium_only()
    {
        $this->create_multiple_labels('medium_only');
    }

    public function create_multiple_labels_large_only()
    {
        $this->create_multiple_labels('large_only');
    }

    public function create_multiple_labels_xsmall_only()
    {
        $this->create_multiple_labels('xsmall_only');
    }

    public function create_multiple_labels_xlarge_only()
    {
        $this->create_multiple_labels('xlarge_only');
    }

    public function create_multiple_labels_largest()
    {
        $this->create_multiple_labels('largest');
    }

    public function create_multiple_labels($option)
    {
        if (!isset($_REQUEST['post']) && !is_array($_REQUEST['post'])) {
            return;
        }

        $order_ids = isset($_GET['post']) && is_array($_GET['post']) ? wc_clean($_GET['post']) : array();

        $service = DHLPWC_Model_Service_Shipment::instance();
        $success_data = $service->bulk($order_ids, $option);

        $download_id = crc32(json_encode($order_ids));
        set_transient('dhlpwc_bulk_download_' . $download_id, $order_ids, 1 * DAY_IN_SECONDS);

        $location = add_query_arg(array(
            'post_type'             => 'shop_order',
            'dhlpwc_labels_created' => 1,
            'dhlpwc_create_count'   => $success_data['success'],
            'dhlpwc_fail_count'     => $success_data['fail'],
            'dhlpwc_download_id'    => $download_id,
        ), 'edit.php');

        wp_redirect(admin_url($location));
        exit;
    }

    public function bulk_create_notice()
    {
        if ($this->is_ordergrid_screen()) {
            if (isset($_GET['dhlpwc_labels_created'])) {
                $created = isset($_GET['dhlpwc_create_count']) && is_numeric($_GET['dhlpwc_create_count']) ? wc_clean($_GET['dhlpwc_create_count']) : 0;
                $failed = isset($_GET['dhlpwc_fail_count']) && is_numeric($_GET['dhlpwc_fail_count']) ? wc_clean($_GET['dhlpwc_fail_count']) : 0;
                $download_id = isset($_GET['dhlpwc_download_id']) && is_numeric($_GET['dhlpwc_download_id']) ? wc_clean($_GET['dhlpwc_download_id']) : null;

                $messages = array();
                if ($created) {
                    $messages[] = sprintf(_n('Label successfully created.', '%s labels successfully created.', number_format_i18n($created), 'dhlpwc'), number_format_i18n($created));
                }
                if ($failed) {
                    $messages[] = sprintf(_n('Label could not be created.', '%s labels failed to create.', number_format_i18n($failed), 'dhlpwc'), number_format_i18n($failed));
                }

                // Create action links
                $links = array();
                $service = DHLPWC_Model_Service_Access_Control::instance();
                if ($created && $download_id) {
                    $order_ids = get_transient('dhlpwc_bulk_download_' . $download_id);
                    if (!empty($order_ids) && is_array($order_ids)) {
                        $order_texts = preg_filter('/^/', '#', $order_ids);
                        $order_list = implode(', ', $order_texts);

                        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_DOWNLOAD)) {
                            $link = new DHLPWC_Model_Data_Notice_Custom_Links();
                            $link->url = add_query_arg(array(
                                'post' => $order_ids,
                            ), admin_url('edit.php?post_type=shop_order&action=dhlpwc_download_labels'));
                            $link->message = sprintf(_n('%sDownload label%s For order: %s.', '%sDownload labels%s For orders: %s.', count($order_ids), 'dhlpwc'), '%s', '%s', $order_list);
                            $link->target = '_blank';
                            $links[] = $link;
                        }

                        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_PRINTER)) {
                            $link = new DHLPWC_Model_Data_Notice_Custom_Links();
                            $link->url = add_query_arg(array(
                                'post' => $order_ids,
                            ), admin_url('edit.php?post_type=shop_order&action=dhlpwc_print_labels'));
                            $link->message = sprintf(_n('%sPrint label%s For order: %s.', '%sPrint labels%s For orders: %s.', count($order_ids), 'dhlpwc'), '%s', '%s', $order_list);
                            $link->target = '_self';
                            $links[] = $link;
                        }
                    }
                }

                if (!empty($messages)) {
                    $view = new DHLPWC_Template('admin.notice');
                    $view->render(array(
                        'messages'     => $messages,
                        'custom_links' => $links,
                    ));
                }
            }
        }
    }

    public function add_bulk_download_action($bulk_actions)
    {
        $bulk_actions['dhlpwc_download_labels'] = __('DHL - Download label', 'dhlpwc');
        return $bulk_actions;
    }

    public function download_multiple_labels()
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

    public function add_bulk_print_action($bulk_actions)
    {
        $bulk_actions['dhlpwc_print_labels'] = __('DHL - Print label', 'dhlpwc');
        return $bulk_actions;
    }

    public function print_multiple_labels()
    {
        $order_ids = isset($_GET['post']) && is_array($_GET['post']) ? wc_clean($_GET['post']) : array();
        $order_count = intval(count($order_ids));

        $service = DHLPWC_Model_Service_Printer::instance();
        $label_ids = $service->get_label_ids($order_ids);
        $label_count = intval(count($label_ids));

        $success = $service->send($label_ids);

        $download_id = crc32(json_encode($order_ids));
        set_transient('dhlpwc_bulk_download_' . $download_id, $order_ids, 1 * DAY_IN_SECONDS);

        $location = add_query_arg(array(
            'post_type'             => 'shop_order',
            'dhlpwc_labels_printed' => 1,
            'dhlpwc_order_count'    => $order_count,
            'dhlpwc_label_count'    => $label_count,
            'dhlpwc_success'        => $success ? 'true' : 'false',
            'dhlpwc_download_id'    => $download_id,
        ), 'edit.php');

        wp_redirect(admin_url($location));
        exit;
    }

    public function bulk_print_notice()
    {
        if ($this->is_ordergrid_screen()) {
            if (isset($_GET['dhlpwc_labels_printed'])) {
                $orders = isset($_GET['dhlpwc_order_count']) && is_numeric($_GET['dhlpwc_order_count']) ? wc_clean($_GET['dhlpwc_order_count']) : 0;
                $labels = isset($_GET['dhlpwc_label_count']) && is_numeric($_GET['dhlpwc_label_count']) ? wc_clean($_GET['dhlpwc_label_count']) : 0;
                $success = isset($_GET['dhlpwc_success']) ? boolval(wc_clean($_GET['dhlpwc_success']) == 'true') : false;
                $download_id = isset($_GET['dhlpwc_download_id']) && is_numeric($_GET['dhlpwc_download_id']) ? wc_clean($_GET['dhlpwc_download_id']) : null;

                $messages = array();
                if ($orders) {
                    $messages[] = sprintf(_n('Order processed.', '%s orders processed.', number_format_i18n($orders), 'dhlpwc'), number_format_i18n($orders));
                }
                if ($success && $labels) {
                    $messages[] = sprintf(_n('Label sent to printer.', '%s labels sent to printer.', number_format_i18n($labels), 'dhlpwc'), number_format_i18n($labels));
                } else {
                    $messages[] = sprintf(_n('Failed to print label.', 'Failed to print labels.', number_format_i18n($labels), 'dhlpwc'), number_format_i18n($labels));
                }

                // Create action links
                $links = array();
                $service = DHLPWC_Model_Service_Access_Control::instance();
                if ($download_id) {
                    $order_ids = get_transient('dhlpwc_bulk_download_' . $download_id);
                    if (!empty($order_ids) && is_array($order_ids)) {
                        $order_texts = preg_filter('/^/', '#', $order_ids);
                        $order_list = implode(', ', $order_texts);

                        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_BULK_DOWNLOAD)) {
                            $link = new DHLPWC_Model_Data_Notice_Custom_Links();
                            $link->url = add_query_arg(array(
                                'post' => $order_ids,
                            ), admin_url('edit.php?post_type=shop_order&action=dhlpwc_download_labels'));
                            $link->message = sprintf(_n('%sDownload label%s For order: %s.', '%sDownload labels%s For orders: %s.', count($order_ids), 'dhlpwc'), '%s', '%s', $order_list);
                            $link->target = '_blank';
                            $links[] = $link;
                        }
                    }
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

    public function add_label_column($columns)
    {
        $offset = (integer)array_search("order_total", array_keys($columns));

        return array_slice($columns, 0, ++$offset, true) +
            array('dhlpwc_label_created' => __('DHL label info', 'dhlpwc')) +
            array_slice($columns, $offset, null, true);
    }

    public function add_label_column_content($column, $order_id)
    {
        switch($column) {
            case 'dhlpwc_label_created':
                $service = DHLPWC_Model_Service_Access_Control::instance();
                $external_link = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPEN_LABEL_LINKS_EXTERNAL);

                $service = DHLPWC_Model_Service_Order_Meta::instance();
                $labels = $service->get_labels($order_id);

                foreach($labels as $label) {
                    $view = new DHLPWC_Template('order.meta.label');
                    if (!is_array($label) || !isset($label['label_size']) || !isset($label['tracker_code'])) {
                        continue;
                    }
                    $is_return = (!empty($label['is_return'])) ? $label['is_return'] : false;

                    $view->render(array(
                        'url'               => $label['pdf']['url'],
                        'label_size'        => $label['label_size'],
                        'label_description' => DHLPWC_Model_Service_Translation::instance()->parcelType($label['label_size']),
                        'tracker_code'      => $label['tracker_code'],
                        'is_return'         => $is_return,
                        'external_link'     => $external_link,
                    ));
                }
                break;
            case 'shipping_address':
                $this->parcelshop_info(new WC_Order($order_id), true);
                break;
        }
    }

    /**
     * DHL ServicePoint information screen for an order.
     *
     * @param WC_Order $order
     * @param bool $compact
     */
    public function parcelshop_info($order, $compact = false)
    {
        $service = new DHLPWC_Model_Service_Order_Meta_Option();
        $parcelshop_meta = $service->get_option_preference($order->get_id(), DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS);

        if ($parcelshop_meta) {
            $service = new DHLPWC_Model_Service_Parcelshop();
            if (is_callable(array($order, 'get_shipping_country'))) {
                // WooCommerce 3.2.0+
                $parcelshop = $service->get_parcelshop($parcelshop_meta['input'], $order->get_shipping_country());
            } else {
                // WooCommerce < 3.2.0
                $parcelshop = $service->get_parcelshop($parcelshop_meta['input'], $order->shipping_country);
            }

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
        if ($this->is_ordergrid_screen()) {
            wp_enqueue_style('dhlpwc-admin-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.admin.css');
        }
    }

    public function load_scripts()
    {
        if ($this->is_ordergrid_screen()) {
            $service = DHLPWC_Model_Service_Access_Control::instance();
            $external_link = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPEN_LABEL_LINKS_EXTERNAL);
            if ($external_link) {
                wp_enqueue_script('dhlpwc-admin-external', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.admin.external.js', array('jquery'));
            }
        }
    }

    protected function is_ordergrid_screen()
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        if (!isset($screen)) {
            return false;
        }

        if ($screen->base !== 'edit' || $screen->post_type !== 'shop_order') {
            return false;
        }

        return true;
    }

    protected function get_available_statuses()
    {
        $statuses = wc_get_order_statuses();

        unset($statuses['wc-completed']);
        unset($statuses['wc-refunded']);
        unset($statuses['wc-failed']);

        return array_keys($statuses);
    }

}

endif;
