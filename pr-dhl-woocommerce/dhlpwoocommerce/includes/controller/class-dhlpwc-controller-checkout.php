<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Checkout')) :

class DHLPWC_Controller_Checkout
{

    public function __construct()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPTION_PARCELSHOP)) {

            add_action('wp_enqueue_scripts', array($this, 'load_styles'));
            add_action('wp_enqueue_scripts', array($this, 'load_scripts'));
            add_filter('script_loader_tag', array($this, 'update_scripts'), 10, 3);

            add_filter('woocommerce_checkout_fields', array($this, 'update_checkout_fields'));
            //        add_action('woocommerce_after_checkout_billing_form', array($this, 'show_parcelshops'));
            add_action('woocommerce_before_order_notes', array($this, 'show_parcelshops'));

            // Custom fields
            add_filter('woocommerce_form_field_dhlpwc_section_checkbox', array($this, 'section_checkbox'), 10, 4);

            add_filter('woocommerce_form_field_dhlpwc_parcelshop_info', array($this, 'parcelshop_info'), 10, 4);
            add_filter('woocommerce_form_field_dhlpwc_parcelshop_map', array($this, 'parcelshop_map'), 10, 4);

            add_filter('woocommerce_form_field_dhlpwc_section_start', array($this, 'section_start'), 10, 4);
            add_filter('woocommerce_form_field_dhlpwc_section_map_start', array($this, 'section_map_start'), 10, 4);
            add_filter('woocommerce_form_field_dhlpwc_section_end', array($this, 'section_end'), 10, 4);

            add_filter('woocommerce_form_field_text', array($this, 'remove_field_class'), 10, 4);
            add_filter('woocommerce_form_field_select', array($this, 'remove_field_class'), 10, 4);
            // End of custom fields

            add_action('woocommerce_after_checkout_validation', array($this, 'validate_parcelshop_selection'), 10, 2);
            //add_action('woocommerce_checkout_create_order', array($this, 'parse_parcelshop_order_data'), 10, 2);
            add_action('woocommerce_checkout_update_order_meta', array($this, 'add_option_meta'), 10, 2);

            add_action('wp_ajax_dhlpwc_parcelshop_list', array($this, 'parcelshop_list_content'));
            add_action('wp_ajax_nopriv_dhlpwc_parcelshop_list', array($this, 'parcelshop_list_content'));

            add_action('wp_ajax_dhlpwc_parcelshop_info', array($this, 'parcelshop_info_content'));
            add_action('wp_ajax_nopriv_dhlpwc_parcelshop_info', array($this, 'parcelshop_info_content'));
        }
    }

    /**
     * Remove generated 'class' attribute to prevent WooCommerce javascript from trying to
     * sort it based on priority (the WooCommerce JS tries to find classes marked by the WooCommerce form_field generator)
     *
     * @param $field
     * @param $key
     * @param $args
     * @param $value
     * @return mixed
     */
    public function remove_field_class($field, $key, $args, $value)
    {
        switch($key) {
            case 'dhlpwc_parcelshop_postcode':
            case 'dhlpwc_parcelshop_select':
                $field = preg_replace('#\s(class)="[^"]+"#', '', $field);
        }
        return $field;
    }

    public function parcelshop_info($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('checkout.parcelshop.info-wrapper');
        $field .= $view->render(array(), false);
        return $field;
    }

    public function parcelshop_map($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('checkout.parcelshop.map');
        $field .= $view->render(array(
            //'geo_locations' => $geo_locations,
            'marker_image' => array(
                'url' => DHLPWC_PLUGIN_URL . 'assets/images/marker/dhl_marker_mini_aa.png',
                'droplet_url' => DHLPWC_PLUGIN_URL . 'assets/images/marker/dhl_marker_mini_aa_droplet.png',
            )
        ), false);

        return $field;
    }

    public function parcelshop_list_content()
    {
        $postcode = $_POST['postcode'];

        $service = new DHLPWC_Model_Service_Checkout();
        $parcelshops = $service->get_parcelshops($postcode);
        $parcelshop1 = reset($parcelshops);
        $geo_locations = array();
        foreach($parcelshops as $parcelshop) {
            $maps = new DHLPWC_Model_Service_Google_Maps();
            $distance = $maps->calculate_distance(
                $parcelshop1->geo_location->latitude,
                $parcelshop1->geo_location->longitude,
                $parcelshop->geo_location->latitude,
                $parcelshop->geo_location->longitude
            );
            $measure_unit = 'km';
            $description = sprintf(
                __('%1$s | %2$s %3$s | %4$s %5$s | Distance: %6$s %7$s', 'dhlpwc'),
                $parcelshop->name,
                $parcelshop->address->street,
                $parcelshop->address->number,
                $parcelshop->address->postal_code,
                $this->format_city($parcelshop->address->city),
                $distance, $measure_unit
            );

            $geo_locations[$parcelshop->id] = array(
                'id' => $parcelshop->id,
                'latitude' => $parcelshop->geo_location->latitude,
                'longitude' => $parcelshop->geo_location->longitude,
                'name' => $parcelshop->name,
                'description' => $description,
            );
        }
        // TODO Remove old code above

        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'geo_locations' => $geo_locations,
        ));

        wp_send_json($json_response->to_array(), 200);
    }

    public function parcelshop_info_content()
    {
        $parcelshop_id = $_POST['parcelshop_id'];
        $service = new DHLPWC_Model_Service_Checkout();
        $parcelshop = $service->get_parcelshop($parcelshop_id);

        $service = new DHLPWC_Model_Service_Parcelshop();
        $times = $service->get_formatted_times($parcelshop->opening_times);

        $view = new DHLPWC_Template('checkout.parcelshop.info');
        $info_view = $view->render(array(
            'name' => $parcelshop->name,
            'address' => $parcelshop->address,
            'times' => $times,
        ), false);

        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $info_view,
        ));

        wp_send_json($json_response->to_array(), 200);
    }

    public function section_map_start($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('checkout.section-start');
        $field .= $view->render(array(
            'section_tag' => 'dhlpwc-checkout-subsection-map'
        ), false);
        return $field;
    }

    public function section_start($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('checkout.section-start');
        $field .= $view->render(array(), false);
        return $field;
    }

    public function section_end($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('checkout.section-end');
        $field .= $view->render(array(), false);
        return $field;
    }

    public function section_checkbox($field, $key, $args, $value)
    {
        $custom_attributes         = array();
        $args['custom_attributes'] = array_filter( (array) $args['custom_attributes'] );
        if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
            foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
                $custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
            }
        }

        $view = new DHLPWC_Template('checkout.section-checkbox');
        $field .= $view->render(array(
            'label_class'       => $args['label_class'],
            'custom_attributes' => $custom_attributes,
            'id'                => $args['id'],
            'input_class'       => $args['input_class'],
            'name'              => $key,
            'value'             => $value,
            'type'              => 'checkbox',
            'label'             => $args['label'],
        ), false);

        return $field;
    }

    public function add_option_meta($order_id, $data)
    {

        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-home', $data['shipping_method'])) {
            $service = new DHLPWC_Model_Service_Order_Meta_Option();
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR);
        }

        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-home-signed', $data['shipping_method'])) {
            $service = new DHLPWC_Model_Service_Order_Meta_Option();
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR);
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_HANDT);
        }

        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-home-no-neighbour', $data['shipping_method'])) {
            $service = new DHLPWC_Model_Service_Order_Meta_Option();
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR);
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_NBB);
        }

        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-home-evening', $data['shipping_method'])) {
            $service = new DHLPWC_Model_Service_Order_Meta_Option();
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_DOOR);
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_EVE);
        }

        if ((string) $data['dhlpwc_parcelshop_section_check'] === "1") {
            $parcelshop_id = $data['dhlpwc_parcelshop_select'];
            $service = new DHLPWC_Model_Service_Order_Meta_Option();
            //$service->save_parcelshop($order_id, $parcelshop_id);
            $service->save_option_preference($order_id, DHLPWC_Model_Meta_Order_Option_Preference::OPTION_PS, $parcelshop_id);
        }



    }

    public function validate_parcelshop_selection($data, $errors)
    {
        if (isset($data['shipping_method']) && is_array($data['shipping_method']) && in_array('dhlpwc-parcelshop', $data['shipping_method'])) {
            if ((string) $data['dhlpwc_parcelshop_section_check'] !== "1" || empty($data['dhlpwc_parcelshop_select']) || (string) $data['dhlpwc_parcelshop_select'] === "0") {
                $errors->add('dhlpwc_parcelshop_select', __('Choose a parcelshop location', 'dhlpwc'));
            }
        }
    }

    public function update_checkout_fields($fields)
    {
        $fields['dhlpwc_parcelshops'] = array(
            'dhlpwc_parcelshop_section_check' => array(
                'type' => 'dhlpwc_section_checkbox',
                'class' => array('dhlpwc_use_parcelshop_address_checkbox'),
                'label' => __('Ship to a DHL Parcelshop?', 'dhlpwc'),
            ),
            'dhlpwc_parcelshop_subsection_start' => array(
                'type' => 'dhlpwc_section_start',
            ),
            'dhlpwc_parcelshop_postcode' => array(
                'type' => 'text',
                'label' => __('Postcode', 'dhlpwc'),
            ),
            'dhlpwc_parcelshop_subsection_map_start' => array(
                'type' => 'dhlpwc_section_map_start',
            ),
            'dhlpwc_parcelshop_select' => array(
                'type'      => 'select',
                'label'     => __('Select Parcelshop', 'dhlpwc'),
                'options' 	=> array('' => '---'),
            ),
            'dhlpwc_parcelshop_info' => array(
                'type' => 'dhlpwc_parcelshop_info',
            ),
            'dhlpwc_parcelshop_map' => array(
                'type' => 'dhlpwc_parcelshop_map',
            ),
            'dhlpwc_parcelshop_subsection_map_end' => array(
                'type' => 'dhlpwc_section_end',
            ),
            'dhlpwc_parcelshop_subsection_end' => array(
                'type' => 'dhlpwc_section_end',
            ),
        );

        return $fields;
    }

    protected function format_city($string)
    {
        $parts = explode(' ', $string);
        $formatted = array();
        foreach($parts as $part) {
            $formatted[] = strlen($part) > 1 ? ucfirst(strtolower($part)) : "'".strtolower($part);
        }
        return implode(' ', $formatted);
    }

    public function show_parcelshops($checkout)
    {
        $view = new DHLPWC_Template('checkout.parcelshops');
        $view->render(array(
            'checkout' => $checkout,
        ));
    }

    public function load_styles()
    {
        if (is_checkout()) {
            wp_enqueue_style('dhlpwc-checkout-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.checkout.css');
            wp_enqueue_style('dhlpwc-checkout-stylishselect-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.stylishselect.css');
        }
    }

    public function load_scripts()
    {
        if (is_checkout()) {
            wp_enqueue_script( 'dhlpwc-checkout-parcelshop-map-script', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.parcelshop.map.js', array('jquery'));
            wp_localize_script(
                'dhlpwc-checkout-parcelshop-map-script',
                'dhlpwc_frontend_ps_map',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'image_mini' => DHLPWC_PLUGIN_URL . 'assets/images/marker/dhl_marker_mini_aa.png',
                    'image_droplet' => DHLPWC_PLUGIN_URL . 'assets/images/marker/dhl_marker_mini_aa_droplet.png'
                )
            );

            wp_enqueue_script( 'dhlpwc-checkout-stylishselect-script', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.stylishselect.js', array('jquery'));
            wp_localize_script(
                'dhlpwc-checkout-stylishselect-script',
                'dhlpwc_stylishselect',
                array(
                    'identifier' => 'select#dhlpwc_parcelshop_select',
                )
            );

            wp_enqueue_script( 'dhlpwc-checkout-parcelshop-select-script', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.parcelshop.select.js', array('jquery'));
            wp_localize_script(
                'dhlpwc-checkout-parcelshop-select-script',
                'dhlpwc_frontend_select',
                array(
                    'ajax_url' => admin_url('admin-ajax.php')
                )
            );

            $google_api_key = 'AIzaSyA-ps3nbypDLIbqMuNJ4wqCNAUVytJdBAw';
            wp_enqueue_script( 'dhlpwc-checkout-map-script', 'https://maps.googleapis.com/maps/api/js?key='.$google_api_key.'&callback=dhlpwc_parcelshop_init_map', array('jquery'), null, true);
        }
    }

    public function update_scripts($tag, $handle, $src)
    {
        if ($handle == 'dhlpwc-checkout-map-script') {
            return str_replace('<script', '<script async defer', $tag);
        }

        return $tag;
    }

}

endif;