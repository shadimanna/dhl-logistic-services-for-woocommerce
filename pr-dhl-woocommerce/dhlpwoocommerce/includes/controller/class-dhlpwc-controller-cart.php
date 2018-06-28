<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Cart')) :

class DHLPWC_Controller_Cart
{

    public function __construct()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPTION_PARCELSHOP)) {
            add_action('wp_enqueue_scripts', array($this, 'load_styles'));
            add_action('wp_enqueue_scripts', array($this, 'load_scripts'));
            add_filter('script_loader_tag', array($this, 'update_scripts'), 10, 3);

            add_action('woocommerce_after_shipping_rate', array($this, 'show_custom_shipping_method'), 10, 2);

            // Custom fields
            add_filter('woocommerce_form_field_dhlpwc_parcelshop_info', array($this, 'parcelshop_info'), 10, 4);
            add_filter('woocommerce_form_field_dhlpwc_parcelshop_map', array($this, 'parcelshop_map'), 10, 4);

            add_filter('woocommerce_form_field_dhlpwc_section_side_start', array($this, 'section_side_start'), 10, 4);
            add_filter('woocommerce_form_field_dhlpwc_section_sub_side_start', array($this, 'section_sub_side_start'), 10, 4);
            add_filter('woocommerce_form_field_dhlpwc_section_map_start', array($this, 'section_map_start'), 10, 4);
            add_filter('woocommerce_form_field_dhlpwc_section_end', array($this, 'section_end'), 10, 4);

            add_filter('woocommerce_form_field_text', array($this, 'remove_field_class'), 10, 4);
            add_filter('woocommerce_form_field_select', array($this, 'remove_field_class'), 10, 4);
            // End of custom fields

            add_action('wp_ajax_dhlpwc_load_parcelshop_selection', array($this, 'parcelshop_selection_content'));
            add_action('wp_ajax_nopriv_dhlpwc_load_parcelshop_selection', array($this, 'parcelshop_selection_content'));

            add_action('wp_ajax_dhlpwc_parcelshop_list', array($this, 'parcelshop_list_content'));
            add_action('wp_ajax_nopriv_dhlpwc_parcelshop_list', array($this, 'parcelshop_list_content'));

            add_action('wp_ajax_dhlpwc_parcelshop_info', array($this, 'parcelshop_info_content'));
            add_action('wp_ajax_nopriv_dhlpwc_parcelshop_info', array($this, 'parcelshop_info_content'));

            add_action('wp_ajax_dhlpwc_parcelshop_passive_sync', array($this, 'parcelshop_passive_sync'));
            add_action('wp_ajax_nopriv_dhlpwc_parcelshop_passive_sync', array($this, 'parcelshop_passive_sync'));
        }
    }

    /** Custom field handlers */
    public function parcelshop_info($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('cart.parcelshop.info-wrapper');
        $field .= $view->render(array(), false);
        return $field;
    }

    public function parcelshop_map($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('cart.parcelshop.map');
        $field .= $view->render(array(
            'marker_image' => array(
                'url' => DHLPWC_PLUGIN_URL . 'assets/images/marker/dhl_marker_mini_aa.png',
                'droplet_url' => DHLPWC_PLUGIN_URL . 'assets/images/marker/dhl_marker_mini_aa_droplet.png',
            )
        ), false);

        return $field;
    }

    public function section_map_start($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('cart.section-start');
        $field .= $view->render(array(
            'section_tag' => 'dhlpwc-checkout-subsection-map'
        ), false);
        return $field;
    }

    public function section_side_start($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('cart.section-start');
        $field .= $view->render(array(
            'section_tag' => 'dhlpwc-checkout-subsection-side'
        ), false);
        return $field;
    }

    public function section_sub_side_start($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('cart.section-start');
        $field .= $view->render(array(
            'section_tag' => 'dhlpwc-checkout-subsection-sub-side'
        ), false);
        return $field;
    }

    public function section_end($field, $key, $args, $value)
    {
        $view = new DHLPWC_Template('cart.section-end');
        $field .= $view->render(array(), false);
        return $field;
    }

    /**
     * Remove generated 'class' attribute from our custom fields to prevent WooCommerce javascript from trying to
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
    /** End of custom field handlers */


    public function parcelshop_selection_content()
    {
        $view = new DHLPWC_Template('cart.option.parcelshop-selection');
        $parcelshop_selection_view = $view->render(array(
            'fields' => $this->get_custom_fields()
        ), false);

        $view = new DHLPWC_Template('modal');
        $selection_view = $view->render(array(
            'content' => $parcelshop_selection_view,
            'logo' => DHLPWC_PLUGIN_URL . 'assets/images/dhlpwc_logo.png',
        ), false);

        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $selection_view,
        ));

        wp_send_json($json_response->to_array(), 200);
    }

    public function parcelshop_list_content()
    {
        $postcode = wc_clean($_POST['postcode']);
        $country = wc_clean($_POST['country']);

        $service = new DHLPWC_Model_Service_Checkout();
        $parcelshops = $service->get_parcelshops($postcode, $country);
        $geo_locations = array();
        foreach($parcelshops as $parcelshop) {
            $measure_unit = 'km';
            $description = sprintf(
                __('%1$s | %2$s %3$s | %4$s %5$s | Distance: %6$s %7$s', 'dhlpwc'),
                $parcelshop->name,
                $parcelshop->address->street,
                $parcelshop->address->number,
                $parcelshop->address->postal_code,
                $this->format_city($parcelshop->address->city),
                round((float) ($parcelshop->distance / 1000), 2), $measure_unit
            );

            $geo_locations[$parcelshop->id] = array(
                'id' => $parcelshop->id,
                'latitude' => $parcelshop->geo_location->latitude,
                'longitude' => $parcelshop->geo_location->longitude,
                'name' => $parcelshop->name,
                'description' => $description,
            );
        }

        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'geo_locations' => $geo_locations,
        ));

        wp_send_json($json_response->to_array(), 200);
    }

    public function parcelshop_info_content()
    {
        $parcelshop_id = wc_clean($_POST['parcelshop_id']);
        $country = wc_clean($_POST['country']);
        $service = new DHLPWC_Model_Service_Checkout();
        $parcelshop = $service->get_parcelshop($parcelshop_id, $country);

        $service = new DHLPWC_Model_Service_Parcelshop();
        $times = $service->get_formatted_times($parcelshop->opening_times);

        $view = new DHLPWC_Template('cart.parcelshop.info');
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

    public function parcelshop_passive_sync()
    {
        if (isset($_POST['parcelshop_id']) && isset($_POST['country'])) {
            $parcelshop_id = wc_clean($_POST['parcelshop_id']);
            $country = wc_clean($_POST['country']);

            WC()->session->set('dhlpwc_parcelshop_passive_sync', array($parcelshop_id, $country));
        }
    }

    public function show_custom_shipping_method($method, $index)
    {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        // This will always display
        if ($method->id == 'dhlpwc-parcelshop') {
            //$this->show_parcelshop_selection_content();
        }

        // This will only display on selection
        if ($method->id == $chosen_shipping) {
            switch($chosen_shipping) {
                case 'dhlpwc-parcelshop':
                    list($parcelshop_id, $country) = WC()->session->get('dhlpwc_parcelshop_passive_sync');
                    $country = $country ?: wc_get_base_location();
                    $country = isset($country['country']) ? $country['country'] : $country;

                    $countries = new WC_Countries();

                    $service = new DHLPWC_Model_Service_Checkout();
                    $parcelshop = $service->get_parcelshop($parcelshop_id, $country);

                    $view = new DHLPWC_Template('cart.option.parcelshop');
                    $view->render(array(
                        'country' => $country,
                        'parcelshop' => $parcelshop,
                        'countries' => $countries->__get('countries'),
                    ));

                    break;
            }
        }
    }

    public function get_custom_fields()
    {
        return array(
            'dhlpwc_parcelshop_subsection_side_start' => array(
                'type' => 'dhlpwc_section_side_start',
            ),
            'dhlpwc_parcelshop_postcode' => array(
                'type' => 'text',
                'label' => __('Postcode', 'dhlpwc'),
            ),

            'dhlpwc_parcelshop_subsection_sub_side_start' => array(
                'type' => 'dhlpwc_section_sub_side_start',
            ),
            'dhlpwc_parcelshop_select' => array(
                'type'      => 'select',
                'label'     => __('Select DHL ServicePoint', 'dhlpwc'),
                'options' 	=> array('' => '---'),
            ),
            'dhlpwc_parcelshop_subsection_sub_side_end' => array(
                'type' => 'dhlpwc_section_end',
            ),

            'dhlpwc_parcelshop_subsection_side_end' => array(
                'type' => 'dhlpwc_section_end',
            ),

            'dhlpwc_parcelshop_subsection_map_start' => array(
                'type' => 'dhlpwc_section_map_start',
            ),
            'dhlpwc_parcelshop_map' => array(
                'type' => 'dhlpwc_parcelshop_map',
            ),
            'dhlpwc_parcelshop_subsection_map_end' => array(
                'type' => 'dhlpwc_section_end',
            ),
        );
    }

    public function load_scripts()
    {
        $select_woo_active = $this->version_check('3.2');

        if (is_cart() || is_checkout()) {
            wp_enqueue_script( 'dhlpwc-checkout-parcelshop-map-script', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.parcelshop.map.js', array('jquery'));
            wp_localize_script(
                'dhlpwc-checkout-parcelshop-map-script',
                'dhlpwc_frontend_ps_map',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'image_mini' => DHLPWC_PLUGIN_URL . 'assets/images/marker/dhlpwc_marker_active.png',
                    'image_droplet' => DHLPWC_PLUGIN_URL . 'assets/images/marker/dhlpwc_marker_neutral.png',
                    'info_loader_view' => (new DHLPWC_Template('cart.parcelshop.info-loader'))->render(array(), false),
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

            $dependencies = array('jquery');
            if ($select_woo_active) {
                $dependencies[] = 'selectWoo';
            }

            wp_enqueue_script( 'dhlpwc-checkout-parcelshop-select-script', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.parcelshop.select.js', $dependencies);
            wp_localize_script(
                'dhlpwc-checkout-parcelshop-select-script',
                'dhlpwc_frontend_select',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'modal_background' => DHLPWC_PLUGIN_URL . 'assets/images/dhlpwc_top_bg.jpg',
                    'search_loader_image' => includes_url('images/wpspin.gif'),
                    'search_default_text' => __('Search', 'dhlpwc'),
                    'confirm_default_text' => __('OK', 'dhlpwc'),
                    'select_woo_active' => $select_woo_active ? 'true' : 'false',
                )
            );

            $google_api_key = 'AIzaSyA-ps3nbypDLIbqMuNJ4wqCNAUVytJdBAw';
            $google_api_key = 'AIzaSyAV9qJVXDBnVHWwU01bjHO3wJCUxffYZyw';
            wp_enqueue_script( 'dhlpwc-checkout-map-script', '//maps.googleapis.com/maps/api/js?key='.$google_api_key.'&callback=dhlpwc_parcelshop_maps_loaded_callback', array('jquery'), null, true);
        }
    }

    protected function version_check($version = '3.2')
    {
        if (class_exists('WooCommerce')) {
            global $woocommerce;
            if (version_compare($woocommerce->version, $version, ">=")) {
                return true;
            }
        }
        return false;
    }

    public function load_styles()
    {
        if (is_cart() || is_checkout()) {
            wp_enqueue_style('dhlpwc-checkout-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.cart.css');
            wp_enqueue_style('dhlpwc-checkout-stylishselect-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.stylishselect.css');
            wp_enqueue_style('dhlpwc-checkout-modal-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.modal.css');
        }
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

    public function update_scripts($tag, $handle, $src)
    {
        if ($handle == 'dhlpwc-checkout-map-script') {
            return str_replace('<script', '<script async defer', $tag);
        }

        return $tag;
    }

}

endif;
