<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Cart')) :

class DHLPWC_Controller_Cart
{

    public function __construct()
    {
        add_action('wp_loaded', array($this, 'set_parcelshop_hooks'));
        add_action('wp_loaded', array($this, 'set_delivery_time_hooks'));
        add_filter('woocommerce_package_rates', array($this, 'sort_rates'), 10, 2);
    }

    public function set_parcelshop_hooks()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CHECKOUT_PARCELSHOP)) {
            add_action('wp_enqueue_scripts', array($this, 'load_parcelshop_styles'));
            add_action('wp_enqueue_scripts', array($this, 'load_parcelshop_scripts'));

            add_action('woocommerce_after_shipping_rate', array($this, 'show_parcelshop_shipping_method'), 10, 2);

            add_action('wp_ajax_dhlpwc_load_parcelshop_selection', array($this, 'parcelshop_modal_content'));
            add_action('wp_ajax_nopriv_dhlpwc_load_parcelshop_selection', array($this, 'parcelshop_modal_content'));

            add_action('wp_ajax_dhlpwc_parcelshop_selection_sync', array($this, 'parcelshop_selection_sync'));
            add_action('wp_ajax_nopriv_dhlpwc_parcelshop_selection_sync', array($this, 'parcelshop_selection_sync'));
        }
    }

    public function set_delivery_time_hooks()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DELIVERY_TIMES)) {
            add_action('wp_enqueue_scripts', array($this, 'load_delivery_time_scripts'));
        }

        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DELIVERY_TIMES_ACTIVE)) {
            add_action('woocommerce_after_shipping_rate', array($this, 'show_delivery_times_shipping_method'), 10, 2);

            add_action('wp_ajax_dhlpwc_delivery_time_selection_sync', array($this, 'delivery_time_selection_sync'));
            add_action('wp_ajax_nopriv_dhlpwc_delivery_time_selection_sync', array($this, 'delivery_time_selection_sync'));
        }
    }

    public function sort_rates($rates, $package)
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if (!$service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CHECKOUT_SORT)) {
            return $rates;
        }

        $service = DHLPWC_Model_Service_Shipping_Preset::instance();
        return $service->sort_rates($rates);
    }

    public function parcelshop_modal_content()
    {
        $view = new DHLPWC_Template('cart.parcelshop-locator');
        $parcelshop_locator_view = $view->render(array(), false);

        $view = new DHLPWC_Template('modal');
        $modal_view = $view->render(array(
            'content' => $parcelshop_locator_view,
            'logo' => DHLPWC_PLUGIN_URL . 'assets/images/dhlpwc_logo.png',
        ), false);

        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'view' => $modal_view,
        ));

        wp_send_json($json_response->to_array(), 200);
    }

    public function parcelshop_selection_sync()
    {
        $json_response = new DHLPWC_Model_Response_JSON();

        if (isset($_POST['parcelshop_id']) && isset($_POST['country_code'])) {
            $parcelshop_id = wc_clean($_POST['parcelshop_id']);
            $country_code = wc_clean($_POST['country_code']);
        } else {
            $parcelshop_id = null;
            $country_code = null;
        }

        $service = DHLPWC_Model_Service_Checkout::instance();
        $postal_code = $service->get_cart_shipping_postal_code() ?: null;

        WC()->session->set('dhlpwc_parcelshop_selection_sync', array($parcelshop_id, $country_code, $postal_code));
        wp_send_json($json_response->to_array(), 200);
    }

    public function delivery_time_selection_sync()
    {
        $json_response = new DHLPWC_Model_Response_JSON();

        $selected = !empty($_POST['selected']) ? wc_clean($_POST['selected']) :  null;
        $date = !empty($_POST['date']) ? wc_clean($_POST['date']) : null;
        $start_time = !empty($_POST['start_time']) ? wc_clean($_POST['start_time']): null;
        $end_time = !empty($_POST['end_time']) ? wc_clean($_POST['end_time']) : null;

        WC()->session->set('dhlpwc_delivery_time_selection_sync', array($selected, $date, $start_time, $end_time));

        wp_send_json($json_response->to_array(), 200);
    }

    public function show_delivery_times_shipping_method($method, $index)
    {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        // This logic shows extra content on the currently selected shipment method
        if ($method->id == $chosen_shipping) {
            switch($chosen_shipping) {
                case 'dhlpwc-home-no-neighbour':
                case 'dhlpwc-home-no-neighbour-same-day':
                case 'dhlpwc-home-no-neighbour-evening':
                    $no_neighbour = true;

                case 'dhlpwc-home':
                case 'dhlpwc-home-same-day':
                case 'dhlpwc-home-evening':
                    // Get variables
                    $sync = WC()->session->get('dhlpwc_delivery_time_selection_sync');
                    if ($sync) {
                        list($selected) = $sync;
                    } else {
                        list($selected) = array(null, null, null, null);
                    }

                    $service = DHLPWC_Model_Service_Checkout::instance();
                    $postal_code = $service->get_cart_shipping_postal_code();
                    $country_code = $service->get_cart_shipping_country_code();

                    $service = DHLPWC_Model_Service_Delivery_Times::instance();
                    $delivery_times = $service->get_time_frames($postal_code, $country_code, $selected);
                    $delivery_times = $service->filter_time_frames($delivery_times, !empty($no_neighbour), $selected);

                    // Show delivery times
                    if (!empty($delivery_times)) {
                        $view = new DHLPWC_Template('cart.delivery-times-option');
                        $view->render(array(
                            'country_code'   => $country_code,
                            'postal_code'    => $postal_code,
                            'delivery_times' => $delivery_times,
                        ));
                    }

                    break;
                default:
                    // Always empty selection sync if it's a different method
                    WC()->session->set('dhlpwc_delivery_time_selection_sync', array(null, null, null, null));
                    break;
            }
        }
    }

    public function show_parcelshop_shipping_method($method, $index)
    {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        // This logic shows extra content on the currently selected shipment method
        if ($method->id == $chosen_shipping) {
            switch($chosen_shipping) {
                case 'dhlpwc-parcelshop':
                    $sync = WC()->session->get('dhlpwc_parcelshop_selection_sync');
                    if ($sync) {
                        list($parcelshop_id, $country_code, $postal_code_memory) = $sync;
                    } else {
                        list($parcelshop_id, $country_code, $postal_code_memory) = array(null, null, null);
                    }

                    $service = DHLPWC_Model_Service_Checkout::instance();

                    // Validate country change
                    $cart_country = $service->get_cart_shipping_country_code();
                    if (!empty($country_code) && $country_code != $cart_country) {
                        // Reset selection, due to countries being out of sync
                        list($parcelshop_id, $country_code, $postal_code_memory) = array(null, null, null);
                        WC()->session->set('dhlpwc_parcelshop_selection_sync', array(null, null, null));
                    }

                    $postal_code = $service->get_cart_shipping_postal_code() ?: null;
                    $country_code = $country_code ?: $cart_country;

                    // Attempt to select a default parcelshop when none is selected or postal code is changed
                    $service = DHLPWC_Model_Service_Parcelshop::instance();
                    if (!$parcelshop_id || $postal_code != $postal_code_memory) {
                        $parcelshop = $service->search_parcelshop($postal_code, $country_code);
                        if ($parcelshop) {
                            WC()->session->set('dhlpwc_parcelshop_selection_sync', array($parcelshop->id, $country_code, $postal_code));
                        }
                    } else {
                        $parcelshop = $service->get_parcelshop($parcelshop_id, $country_code);
                    }

                    $view = new DHLPWC_Template('cart.parcelshop-option');
                    $view->render(array(
                        'country_code' => $country_code,
                        'postal_code' => $postal_code,
                        'parcelshop' => $parcelshop,
                    ));

                    break;
                default:
                    // Always empty selection sync if it's a different method
                    WC()->session->set('dhlpwc_parcelshop_selection_sync', array(null, null, null));
                    break;
            }
        }
    }

    public function load_parcelshop_scripts()
    {
        if (is_cart() || is_checkout()) {

            $dependencies = array('jquery');

            $service = DHLPWC_Model_Service_Settings::instance();
            $google_map_key = $service->get_maps_key();

            $service = DHLPWC_Model_Service_Parcelshop::instance();
            $gateway = $service->get_parcelshop_gateway();

            if ($google_map_key) {

                $translations = $service->get_component_translations();

                $service = DHLPWC_Model_Service_Checkout::instance();
                $country_code = $service->get_cart_shipping_country_code();
                $postal_code = $service->get_cart_shipping_postal_code();

                $view = new DHLPWC_Template('cart.parcelshop.confirm-button');
                $confirm_button_view = $view->render(array(), false);

                wp_enqueue_script('dhlpwc-checkout-parcelshop-locator-script', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.parcelshop.locator.js', $dependencies);
                wp_localize_script(
                    'dhlpwc-checkout-parcelshop-locator-script',
                    'dhlpwc_parcelshop_locator',
                    array(
                        'gateway'          => $gateway,
                        'postal_code'      => $postal_code,
                        'country_code'     => $country_code,
                        'limit'            => 7,
                        'ajax_url'         => admin_url('admin-ajax.php'),
                        'modal_background' => DHLPWC_PLUGIN_URL . 'assets/images/dhlpwc_top_bg.jpg',
                        'confirm_button'   => $confirm_button_view,
                        'google_map_key'   => $google_map_key,
                        'translations'     => $translations,
                    )
                );
            } else {
                wp_enqueue_script('dhlpwc-checkout-parcelshop-locator-script', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.parcelshop.select.js', $dependencies);
                wp_localize_script(
                    'dhlpwc-checkout-parcelshop-locator-script',
                    'dhlpwc_parcelshop_selector',
                    array(
                        'servicepoint_url' => $gateway . '/parcel-shop-locations/',
                        'limit'            => 7,
                        'ajax_url'         => admin_url('admin-ajax.php')
                    )
                );
            }

        }
    }

    public function load_delivery_time_scripts()
    {
        if (is_cart() || is_checkout()) {
            $select_woo_active = $this->version_check('3.2');
            $dependencies = array('jquery');
            if ($select_woo_active) {
                $dependencies[] = 'selectWoo';
            }

            wp_enqueue_script( 'dhlpwc-checkout-delivery-time-script', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.deliverytime.js', $dependencies);
            wp_localize_script(
                'dhlpwc-checkout-delivery-time-script',
                'dhlpwc_delivery_time_object',
                array(
                    'ajax_url'          => admin_url('admin-ajax.php'),
                    'select_woo_active' => $select_woo_active ? 'true' : 'false',
                )
            );
        }
    }

    public function load_parcelshop_styles()
    {
        if (is_cart() || is_checkout()) {
            wp_enqueue_style('dhlpwc-checkout-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.cart.css');
            wp_enqueue_style('dhlpwc-checkout-modal-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.modal.css');
            wp_enqueue_style('dhlpwc-checkout-parcelshop-no-map-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.parcelshop_no_map.css');
            wp_enqueue_style('dhlpwc-checkout-parcelshop-dsl-style', 'https://servicepoint-locator.dhlparcel.nl/servicepoint-locator.css');
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

}

endif;
