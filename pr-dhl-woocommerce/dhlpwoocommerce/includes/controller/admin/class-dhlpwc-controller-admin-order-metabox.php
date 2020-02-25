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

            add_action('wp_ajax_dhlpwc_load_options', array($this, 'load_options'));
            add_action('wp_ajax_dhlpwc_load_sizes', array($this, 'load_sizes'));

            add_action('wp_ajax_dhlpwc_metabox_terminal_search', array($this, 'terminal_search'));
            add_action('wp_ajax_dhlpwc_metabox_parcelshop_search', array($this, 'parcelshop_search'));

	        $service = DHLPWC_Model_Service_Access_Control::instance();
	        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_PRINTER)) {
		        add_action('wp_ajax_dhlpwc_label_print',  array($this, 'print_label'));
	        }

	        $service = DHLPWC_Model_Service_Access_Control::instance();
	        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_LABEL_REQUEST)) {
		        add_action('wp_ajax_dhlpwc_print_label_request',  array($this, 'print_label_request'));
	        }
        }
    }

    public function terminal_search()
    {
        $post_id = wc_clean($_POST['post_id']);
        $search = wc_clean($_POST['search']);

        $order = new WC_Order($post_id);
        $country = $order->get_shipping_country();

        $service = DHLPWC_Model_Service_Terminal::instance();
        $terminals = $service->get_terminals($search, $country);

        if (!empty($terminals)) {
            $terminal_view = '';
            foreach($terminals as $terminal)
            {
                $view = new DHLPWC_Template('order.meta.form.input.terminal.location');
                $terminal_view .= $view->render(array(
                    'terminal' => $terminal,
                ), false);
            }
        } else {
            $view = new DHLPWC_Template('order.meta.form.input.terminal.none');
            $terminal_view = $view->render(array(), false);
        }

        // Send JSON response
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $terminal_view,
        ));

        wp_send_json($json_response->to_array(), 200);
    }

    public function parcelshop_search()
    {
        $post_id = wc_clean($_POST['post_id']);
        $search = wc_clean($_POST['search']);

        $order = new WC_Order($post_id);
        $country = $order->get_shipping_country();

        $service = DHLPWC_Model_Service_Parcelshop::instance();
        $parcelshops = $service->get_parcelshops($search, $country);

        if (!empty($parcelshops)) {
            $parcelshop_view = '';
            foreach($parcelshops as $parcelshop)
            {
                $view = new DHLPWC_Template('order.meta.form.input.parcelshop.location');
                $parcelshop_view .= $view->render(array(
                    'parcelshop' => $parcelshop,
                ), false);
            }
        } else {
            $view = new DHLPWC_Template('order.meta.form.input.parcelshop.none');
            $parcelshop_view = $view->render(array(), false);
        }

        // Send JSON response
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $parcelshop_view,
        ));

        wp_send_json($json_response->to_array(), 200);
    }

    /**
     * Handler for the label_create AJAX call
     */
    public function create_label()
    {
        $post_id = wc_clean($_POST['post_id']);
        $label_size = wc_clean($_POST['label_size']);
        $label_options = isset($_POST['label_options']) && is_array($_POST['label_options']) ? wc_clean($_POST['label_options']) : array();
        $label_option_data = isset($_POST['label_option_data']) && is_array($_POST['label_option_data']) ? wc_clean($_POST['label_option_data']) : array();
        $to_business = (isset($_POST['to_business']) && wc_clean($_POST['to_business']) == 'yes') ? true : false;

        $service = DHLPWC_Model_Service_Shipment::instance();
        $success = $service->create($post_id, $label_size, $label_options, $label_option_data, $to_business);

        // Set Flash message
        $messages = DHLPWC_Model_Core_Flash_Message::instance();
        if ($success) {
            $messages->add_notice(__('Label created.', 'dhlpwc'), 'dhlpwc_label_meta');
        } else {
            $messages->add_error(__('Label could not be created.', 'dhlpwc'), 'dhlpwc_label_meta');
            // Check if internal error
            if ($error_message = $service->get_error(DHLPWC_Model_Service_Shipment::CREATE_ERROR)) {
                $messages->add_error($error_message, 'dhlpwc_label_meta');
            } else {
                // Attempt to retrieve last API error
                $connector = DHLPWC_Model_API_Connector::instance();
                if (isset($connector->error_id) && isset($connector->error_message)) {
                    $messages->add_error(sprintf(__('The API responded with [%1$s]: %2$s', 'dhlpwc'), $connector->error_id, $connector->error_message), 'dhlpwc_label_meta');
                }
            }
        }

        // Send JSON response
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $this->load_all($post_id, $label_options, $label_option_data, $to_business)
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
            'view' => $this->load_all($post_id)
        ));
        wp_send_json($json_response->to_array(), 200);
    }

	/**
	 * Handler for the label_print AJAX call
	 */
	public function print_label()
	{
		$post_id = wc_clean($_POST['post_id']);
		$label_id = wc_clean($_POST['label_id']);

		$service = new DHLPWC_Model_Service_Printer();
		$success = $service->send($label_id);

		// Set Flash message
		$messages = DHLPWC_Model_Core_Flash_Message::instance();
		if ($success) {
			$messages->add_notice(__('Sent to printer successfully.', 'dhlpwc'), 'dhlpwc_label_meta');
		} else {
			$messages->add_warning(__('Failed to send to printer.', 'dhlpwc'), 'dhlpwc_label_meta');
		}

		// Send JSON response
		$json_response = new DHLPWC_Model_Response_JSON();
		$json_response->set_data(array(
			'view' => $this->load_all($post_id)
		));
		wp_send_json($json_response->to_array(), 200);
	}

	/**
	 * Handler for the label_print AJAX call
	 */
	public function print_label_request()
	{
		$post_id = wc_clean($_GET['post_id']);
		$label_id = wc_clean($_GET['label_id']);

		// Get Label
		$label_service = DHLPWC_Model_Service_Order_Meta::instance();
		$label = $label_service->get_label($post_id, $label_id);

		if ($label === false) {
			echo __('Label not found');
			exit;
		}

		if (empty($label['request'])) {
			echo __('Label request not found');
			exit;
		}

		echo $label['request'];
		exit;
	}

    public function load_options()
    {
        $post_id = wc_clean($_POST['post_id']);
        $to_business = isset($_POST['to_business']) && wc_clean($_POST['to_business']) == 'yes' ? true : false;

        $selected_options = isset($_POST['label_options']) && is_array($_POST['label_options']) ? wc_clean($_POST['label_options']) : array();

        // Set Flash message
        $messages = DHLPWC_Model_Core_Flash_Message::instance();
        $messages->add_warning(__('List updated.', 'dhlpwc'), 'dhlpwc_label_meta');

        // Send JSON response
        $service = DHLPWC_Model_Service_Label_Metabox::instance();
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $service->options_form($post_id, $selected_options, null, $to_business),
        ));
        wp_send_json($json_response->to_array(), 200);
    }

    public function load_sizes()
    {
        $post_id = wc_clean($_POST['post_id']);

        $label_options = isset($_POST['label_options']) && is_array($_POST['label_options']) ? wc_clean($_POST['label_options']) : array();
        $to_business = isset($_POST['to_business']) && wc_clean($_POST['to_business']) == 'yes' ? true : false;

        // Set Flash message
        $messages = DHLPWC_Model_Core_Flash_Message::instance();
        $messages->add_warning(__('List updated.', 'dhlpwc'), 'dhlpwc_label_meta');

        // Send JSON response
        $service = DHLPWC_Model_Service_Label_Metabox::instance();
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $service->sizes_form($post_id, $label_options, $to_business)
        ));
        wp_send_json($json_response->to_array(), 200);
    }

    /**
     * Add a metabox for DHL labels on the admin order page
     */
    public function add_meta_box()
    {
        $title = "<img src='".DHLPWC_PLUGIN_URL . "assets/images/dhlpwc_logo_mini.png' alt='".esc_attr(__('DHL', 'dhlpwc'))."'/>";
        add_meta_box('dhlpwc-label', $title, array($this, 'metabox_content'), 'shop_order', 'side', 'high');
    }

    /**
     * Display metabox content
     */
    public function metabox_content()
    {
        echo $this->load_all(get_the_ID());
    }

    /**
     * Creates a content view for the metabox, buffered
     *
     * @param $order_id
     * @return string
     */
    public function load_all($order_id, $preselected_options = null, $option_data = null, $to_business = null)
    {
        $order_id = intval($order_id);

        $metabox_service = DHLPWC_Model_Service_Label_Metabox::instance();

        // Generate label section
        $meta_service = new DHLPWC_Model_Service_Order_Meta();
        $labels = $meta_service->get_labels($order_id);

        $meta_content = '';
        $meta_content .= $metabox_service->order_labels($order_id, $labels);
        $meta_content .= '<hr/>';

        // Generate to business option
        $access_service = DHLPWC_Model_Service_Access_Control::instance();
        if ($to_business === null || !is_bool($to_business)) {
            $to_business = $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEFAULT_TO_BUSINESS);
        }

        $business_form = $metabox_service->private_or_business_form($to_business);

        // Generate options
        $option_service = DHLPWC_Model_Service_Order_Meta_Option::instance();
        if ($preselected_options === null) {
            $preselected_options = $option_service->get_keys($order_id);

            // Default option settings
            $default_signature = $option_service->default_signature($order_id, $preselected_options, $to_business);
            if ($default_signature) {
                $option_service->add_key_to_stack(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_HANDT, $preselected_options);
            }

            // Default option settings
            $default_age_check = $option_service->default_age_check($order_id, $preselected_options, $to_business);
            if ($default_age_check) {
                $option_service->add_key_to_stack(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_AGE_CHECK, $preselected_options);
            }

            // Default option settings
            $default_order_id_reference = $option_service->default_order_id_reference($order_id, $preselected_options, $to_business);
            if ($default_order_id_reference) {
                $option_service->add_key_to_stack(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE, $preselected_options);
                $option_service->add_key_value_to_stack(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_REFERENCE, $order_id, $option_data);
            }

            // Default option settings
            $default_return = $option_service->default_return($order_id, $preselected_options, $to_business);
            if ($default_return) {
                $option_service->add_key_to_stack(DHLPWC_Model_Meta_Order_Option_Preference::OPTION_ADD_RETURN_LABEL, $preselected_options);
            }
        }

        $view = new DHLPWC_Template('order.meta.form.options-container');
        $options_form = $view->render(array(
            'content' => $metabox_service->options_form($order_id, $preselected_options, $option_data, $to_business),
        ), false);

        // Generate sizes (with requested options)
        $view = new DHLPWC_Template('order.meta.form.sizes-container');
        $sizes_form = $view->render(array(
            'content' => $metabox_service->sizes_form($order_id, $preselected_options, $to_business),
        ), false);

        // Assemble all content pieces
        $view = new DHLPWC_Template('order.meta');
        $meta_content .= $view->render(array(
            'to_business' => $business_form,
            'options' => trim($options_form),
            'sizes' => trim($sizes_form),
        ), false);

        // Create notices
        $messages = DHLPWC_Model_Core_Flash_Message::instance();
        $notices = $messages->get_notices('dhlpwc_label_meta');
        $warnings = $messages->get_warnings('dhlpwc_label_meta');
        $errors = $messages->get_errors('dhlpwc_label_meta');

        // Send as metabox
        $view = new DHLPWC_Template('order.meta-container');
        return $view->render(array(
            'content' => $meta_content,
            'notices' => $notices,
            'warnings' => $warnings,
            'errors' => $errors,
        ), false);
    }

    public function load_styles()
    {
        wp_enqueue_style('dhlpwc-admin-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.admin.css');
    }

    public function load_scripts()
    {
        if (!function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();
        if (!isset($screen)) {
            return;
        }

        if ($screen->base == 'post' && $screen->post_type == 'shop_order') {
            wp_enqueue_script( 'dhlpwc-metabox-action', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.metabox.js', array('jquery'));
            wp_localize_script( 'dhlpwc-metabox-action', 'dhlpwc_metabox_object', array(
                'post_id' => get_the_ID(),
            ));

            wp_enqueue_script( 'dhlpwc-metabox-parcelshop-action', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.metabox.parcelshop.js', array('jquery'));
            wp_localize_script( 'dhlpwc-metabox-parcelshop-action', 'dhlpwc_metabox_parcelshop_object', array(
                'post_id' => get_the_ID(),
            ));

            wp_enqueue_script( 'dhlpwc-metabox-terminal-action', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.metabox.terminal.js', array('jquery'));
            wp_localize_script( 'dhlpwc-metabox-terminal-action', 'dhlpwc_metabox_terminal_object', array(
                'post_id' => get_the_ID(),
            ));
        }
    }

}

endif;
