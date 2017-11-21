<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Admin_Order_Metabox')) :

/**
 * Handles all logic tied to the metabox in the order edit screen on the admin side.
 * This includes both the Metabox creation part and the AJAX communication.
 */
class DHLPWC_Controller_Admin_Order_Metabox
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'load_styles'));
            add_action('admin_enqueue_scripts', array($this, 'load_scripts'));

            add_action('add_meta_boxes', array($this, 'add_meta_box'), 10);

            add_action('wp_ajax_dhlpwc_label_create',  array($this, 'create_label'));
            add_action('wp_ajax_dhlpwc_label_delete', array($this, 'delete_label'));
            add_action('wp_ajax_dhlpwc_label_refresh', array($this, 'refresh_label'));
        }
    }

    /**
     * Handler for the label_create AJAX call
     */
    public function create_label()
    {
        $post_id = wc_clean($_POST['post_id']);
        $label_size = wc_clean($_POST['label_size']);
        $label_options = isset($_POST['label_options']) && is_array($_POST['label_options']) ? wc_clean($_POST['label_options']) : array();
        $to_business = (isset($_POST['to_business']) && wc_clean($_POST['to_business']) == 'yes') ? true : false;

        $service = new DHLPWC_Model_Service_Label();
        $success = $service->create($post_id, $label_size, $label_options, $to_business);

        // Set Flash message
        $messages = DHLPWC_Model_Core_Flash_Message::instance();
        if ($success) {
            $messages->add_notice(__('Label created.', 'dhlpwc'), 'dhlpwc_label_meta');
        } else {
            $messages->add_error(__('Label could not be created.', 'dhlpwc'), 'dhlpwc_label_meta');
            // Attempt to retrieve last API error
            $connector = DHLPWC_Model_API_Connector::instance();
            if (isset($connector->error_id) && isset($connector->error_message)) {
                $messages->add_error(sprintf(__('The API responded with [%1$s]: %2$s', 'dhlpwc'), $connector->error_id, $connector->error_message), 'dhlpwc_label_meta');
            }
        }

        // Send JSON response
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $this->content_view($post_id)
        ));
        wp_send_json($json_response->to_array(), 200);
    }

    /**
     * Handler for the label_delete AJAX call
     */
    public function delete_label()
    {
        $post_id = wc_clean($_POST['post_id']);
        $label_id = wc_clean($_POST['label_id']);

        $service = new DHLPWC_Model_Service_Label();
        $service->delete($post_id, $label_id);

        // Set Flash message
        $messages = DHLPWC_Model_Core_Flash_Message::instance();
        $messages->add_warning(__('Label deleted.', 'dhlpwc'), 'dhlpwc_label_meta');

        // Send JSON response
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $this->content_view($post_id)
        ));
        wp_send_json($json_response->to_array(), 200);
    }

    public function refresh_label()
    {
        $post_id = wc_clean($_POST['post_id']);

        $label_options = isset($_POST['label_options']) && is_array($_POST['label_options']) ? wc_clean($_POST['label_options']) : array();
        $to_business = isset($_POST['to_business']) && wc_clean($_POST['to_business']) == 'yes' ? true : false;

        // Set Flash message
        $messages = DHLPWC_Model_Core_Flash_Message::instance();
        $messages->add_warning(__('List updated.', 'dhlpwc'), 'dhlpwc_label_meta');

        // Send JSON response
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $this->parceltype_content($post_id, $label_options, $to_business)
        ));
        wp_send_json($json_response->to_array(), 200);
    }

    /**
     * Add a metabox for DHL labels on the admin order page
     */
    public function add_meta_box()
    {
        add_meta_box('dhlpwc-label', __('DHL', 'dhlpwc'), array($this, 'display_content'), 'shop_order', 'side', 'high');
    }

    /**
     * Display metabox content
     */
    public function display_content()
    {
        echo $this->content_view(get_the_ID());
    }

    /**
     * Creates a content view for the metabox, buffered
     *
     * @param $order_id
     * @return string
     */
    public function content_view($order_id)
    {
        $order_id = intval($order_id);

        $label_view = '';

        $service = new DHLPWC_Model_Service_Order_Meta();
        $labels = $service->get_labels($order_id);

        if (!empty($labels)) {
            foreach ($labels as $label) {
                $view = new DHLPWC_Template('order.meta.label');
                $label_view .= $view->render(array(
                    'label_size'        => $label['label_size'],
                    'label_description' => __(sprintf('PARCELTYPE_%s', $label['label_size']), 'dhlpwc'),
                    'tracker_code'      => $label['tracker_code'],
                    'actions'           => $this->get_label_actions($label, $order_id),
                ), false);
            }
        } else {
            $view = new DHLPWC_Template('order.meta.no-label');
            $label_view .= $view->render(array(), false);
        }

        $view = new DHLPWC_Template('order.meta.label-wrapper');
        $meta_view = $view->render(array(
            'content' => $label_view
        ), false);

        $meta_view .= '<hr/>';

        // Generate to business option
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $to_business = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEFAULT_TO_BUSINESS);
        $view = new DHLPWC_Template('order.meta.form.to-business');
        $to_business_view = $view->render(array(
            'checked' => $to_business,
        ), false);

        // Generate options
        $service = DHLPWC_Model_Service_Label_Option::instance();
        $options = $service->get_selectable_options($order_id);
        $option_view = '';
        foreach($options as $option) {
            $view = new DHLPWC_Template('order.meta.form.option');
            $option_view .= $view->render($option, false);
        }

        // Generate parceltypes (with requested options)
        $service = DHLPWC_Model_Service_Order_Meta_Option::instance();
        $options = $service->get_keys($order_id);

        $parceltype_content = $this->parceltype_content($order_id, $options, $to_business);
        $view = new DHLPWC_Template('order.meta.form.parceltype-wrapper');
        $parceltype_view = $view->render(array(
            'content' => $parceltype_content,
            'actions' => $this->get_refresh_action($order_id),
        ), false);

        $view = new DHLPWC_Template('order.meta');
        $meta_view .= $view->render(array(
            'to_business' => $to_business_view,
            'options' => trim($option_view),
            'parcel_types' => trim($parceltype_view),
        ), false);

        $messages = DHLPWC_Model_Core_Flash_Message::instance();
        $notices = $messages->get_notices('dhlpwc_label_meta');
        $warnings = $messages->get_warnings('dhlpwc_label_meta');
        $errors = $messages->get_errors('dhlpwc_label_meta');

        $view = new DHLPWC_Template('order.meta-wrapper');
        return $view->render(array(
            'content' => $meta_view,
            'notices' => $notices,
            'warnings' => $warnings,
            'errors' => $errors,
        ), false);
    }

    public function parceltype_content($order_id, $options, $to_business)
    {
        $view = new DHLPWC_Template('order.meta.form.parceltype-headline');
        $option_texts = $options;
        array_walk($option_texts, function(&$value, &$key) {
            $value = __(sprintf('OPTION_%s', $value), 'dhlpwc');
        });

        $parceltype_view = $view->render(array(
            'message' => implode(' - ', $option_texts),
        ), false);

        $service = DHLPWC_Model_Service_Access_Control::instance();
        $parceltypes = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CAPABILITY_PARCELTYPE, array(
            'order_id' => $order_id,
            'options' => $options,
            'to_business' => $to_business,
        ));

        if (!empty($parceltypes)) {
            foreach ($parceltypes as $parceltype) {
                $view = new DHLPWC_Template('order.meta.form.parceltype');
                $parceltype_view .= $view->render(array(
                    'parceltype' => $parceltype,
                    'description' => __(sprintf('PARCELTYPE_%s', $parceltype->key), 'dhlpwc'),
                ), false);

            }
        } else {
            $view = new DHLPWC_Template('order.meta.form.no-parceltype');
            $parceltype_view .= $view->render(array(), false);
        }

        return $parceltype_view;
    }

    protected function get_label_actions($label, $post_id = null)
    {
        $post_id = $post_id ?: get_the_ID();

        $actions = array(
            array(
                'url'    => $label['pdf']['url'],
                'name'   => __('Download PDF label', 'dhlpwc'),
                'action' => "dhlpwc_action_download",
            ),
            array(
                'url'    => 'https://www.dhlparcel.nl/nl/volg-uw-zending?tt='.$label['tracker_code'],
                'name'   => __('Follow Track & Trace', 'dhlpwc'),
                'action' => "dhlpwc_action_follow_tt",
            ),
            array(
                'url'    => admin_url('post.php?post=' . $post_id . '&action=edit'),
                'name'   => __('Delete PDF label', 'dhlpwc'),
                'action' => "dhlpwc_action_delete",
            ),
        );

        // Create template
        $action_view = '';

        foreach ($actions as $action) {
            $view = new DHLPWC_Template('admin.action-button');
            $action_view .= $view->render(array(
                'action'  => $action,
                'post_id' => $post_id,
                'label_id' => $label['label_id'],
            ), false);
        }

        $view = new DHLPWC_Template('admin.action-button-wrapper');
        return $view->render(array(
            'content'  => $action_view,
        ), false);
    }

    protected function get_refresh_action($post_id = null)
    {
        $post_id = $post_id ?: get_the_ID();

        $action = array(
            'url'    => admin_url('post.php?post=' . $post_id . '&action=edit'),
            'name'   => __('Update available sizes based on selected options', 'dhlpwc'),
            'action' => "dhlpwc_action_refresh",

        );

        $view = new DHLPWC_Template('admin.action-button');
        $action_view = $view->render(array(
            'action'  => $action,
            'post_id' => $post_id,
        ), false);

        $view = new DHLPWC_Template('admin.action-button-wrapper');
        return $view->render(array(
            'content'  => $action_view,
        ), false);
    }


    public function load_styles()
    {
        wp_enqueue_style('dhlpwc-admin-order-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.admin.css');
    }

    public function load_scripts()
    {
        $screen = get_current_screen();
        if ($screen->base == 'post' && $screen->post_type == 'shop_order') {
            wp_enqueue_script( 'dhlpwc-metabox-action', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.metabox.js', array('jquery'));
            wp_localize_script( 'dhlpwc-metabox-action', 'dhlpwc_metabox_object', array(
                'post_id' => get_the_ID()
            ));
        }
    }

}

endif;
