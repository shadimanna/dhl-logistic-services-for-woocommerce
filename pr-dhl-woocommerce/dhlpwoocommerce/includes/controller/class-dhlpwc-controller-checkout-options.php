<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Checkout_Options')) :

class DHLPWC_Controller_Checkout_Options
{

    public function __construct()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPTION_PARCELSHOP)) {
            add_filter('woocommerce_checkout_fields', array($this, 'update_checkout_fields'));

            add_filter('woocommerce_form_field_dhlpwc_display_options_off', array($this, 'hide_field'), 10, 4);

            add_action('woocommerce_checkout_update_order_review', array($this, 'add_fields_to_session'), 10, 1);
            add_action('woocommerce_after_shipping_rate', array($this, 'display_random'), 10, 2);
        }
    }

    public function hide_field($field, $key, $args, $value)
    {
        return $field;
    }

    public function add_fields_to_session($post_data)
    {
        if (isset($_POST['post_data'])) {
            parse_str($_POST['post_data'], $post_data);
            $post_data = wc_clean($post_data);
            $data = array();
            if ($post_data && is_array($post_data)) {
                foreach($post_data as $key => $value) {
                    $search = 'dhlpwc';
                    $search_length = strlen($search);
                    if (substr($key, 0, $search_length) == $search) {
                        $data[$key] = $value;
                    }
                }
            }
            WC()->session->set('dhlpwc_post_data', $data);
        }
    }

    public function display_random($method, $index)
    {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];

        if ($method->id == $chosen_shipping) {
            switch($chosen_shipping) {
                case 'dhlpwc-parcelshop':

                    $post_data = WC()->session->get('dhlpwc_post_data');
                    if (is_array($post_data)) {
                        if (isset($post_data['dhlpwc_parcelshop_select'])) {
                            $service = new DHLPWC_Model_Service_Checkout();
                            $parcelshop = $service->get_parcelshop($post_data['dhlpwc_parcelshop_select']);
                        }
                    }

                    $view = new DHLPWC_Template('checkout.option.parcelshop');
                    $view->render(array(
                        'parcelshop' => isset($parcelshop) ? $parcelshop : null,
                    ));
                    break;
            }
        }
    }

    public function update_checkout_fields($fields)
    {
        $fields['dhlpwc_options'] = array(
            'dhlpwc_options' => array(
                'type' => 'dhlpwc_display_options_off',
            ),
        );

        return $fields;
    }

}

endif;
