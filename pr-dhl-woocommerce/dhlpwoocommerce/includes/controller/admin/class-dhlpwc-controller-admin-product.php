<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('DHLPWC_Controller_Admin_Product')) :

class DHLPWC_Controller_Admin_Product
{
    public function __construct()
    {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'load_scripts']);

            add_action('woocommerce_product_options_shipping', [$this, 'add_product_fields']);

            add_action('woocommerce_process_product_meta', [$this, 'save_custom_fields']);
        }
    }

    public function load_scripts()
    {
        wp_enqueue_script('dhlpwc-admin-products', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.admin.product.js', ['jquery']);
    }

    public function add_product_fields()
    {
        $checkboxArgs = array(
            'label'       => __('Limit DHL methods'),
            'id'          => 'dhlpwc_enable_method_limit',
            'name'        => 'dhlpwc_enable_method_limit',
            'desc_tip'    => false,
            'description' => 'When shipping with DHL this product is limited to the following shipping methods'
        );

        woocommerce_wp_checkbox($checkboxArgs);

        $options = array();
        $service = DHLPWC_Model_Service_Shipping_Preset::instance();
        foreach ($service->get_presets() as $method) {
            $options[$method['frontend_id']] = $method['title'];
        }

        $this->woocommerce_wp_select_multiple(
            'dhlpwc_selected_method_limit',
            __('Use control + click to add/remove single shipping methods'),
            $options
        );
    }

    public function save_custom_fields($post_id)
    {
        $product = wc_get_product($post_id);

        $dhlpwc_enable_method_limit = isset($_POST['dhlpwc_enable_method_limit']) ? $_POST['dhlpwc_enable_method_limit'] : '';
        $product->update_meta_data('dhlpwc_enable_method_limit', $dhlpwc_enable_method_limit);

        $dhlpwc_selected_method_limit = isset($_POST['dhlpwc_selected_method_limit']) ? $_POST['dhlpwc_selected_method_limit'] : '';
        $product->update_meta_data('dhlpwc_selected_method_limit', $dhlpwc_selected_method_limit);

        $product->save();
    }

    public function woocommerce_wp_select_multiple($id, $description, $options)
    {
        $post_id = get_the_ID();
        $value = get_post_meta($post_id, $id, true) ? get_post_meta($post_id, $id, true) : [];

        $view = new DHLPWC_Template('admin.multi-select');
        $view->render(array(
            'id'          => $id,
            'description' => $description,
            'options'     => $options,
            'value'       => $value
        ));
    }
}

endif;
