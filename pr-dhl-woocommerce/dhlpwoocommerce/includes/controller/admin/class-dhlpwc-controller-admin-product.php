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
            add_action('admin_enqueue_scripts', array($this, 'load_scripts'));

            add_action('woocommerce_product_options_shipping', array($this, 'add_product_fields'));

            add_action('woocommerce_process_product_meta', array($this, 'save_custom_fields'));
        }
    }

    public function load_scripts()
    {
        wp_enqueue_script('dhlpwc-admin-products', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.admin.product.js', array('jquery'));
    }

    public function add_product_fields()
    {
        // Limit DHL
        woocommerce_wp_checkbox(array(
            'label'       => __('Limit DHL methods', 'dhlpwc'),
            'id'          => 'dhlpwc_enable_method_limit',
            'desc_tip'    => false,
            'description' => __('When shipping with DHL this product is limited to the following shipping methods', 'dhlpwc')
        ));

        $options = array();
        $service = DHLPWC_Model_Service_Shipping_Preset::instance();
        foreach ($service->get_presets() as $method) {
            $options[$method['frontend_id']] = $method['title'];
        }

        $this->woocommerce_wp_select_multiple(
            'dhlpwc_selected_method_limit',
            __('Use control + click to add/remove single shipping methods', 'dhlpwc'),
            $options
        );

        // Autoselect services
        woocommerce_wp_checkbox(array(
            'label'       => __('Send as a DHL mailbox parcel', 'dhlpwc'),
            'id'          => 'dhlpwc_send_with_bp',
            'desc_tip'    => false,
            'description' => __('If this is the only product ordered, automatically select the parcel type: mailbox', 'dhlpwc')
        ));

        woocommerce_wp_text_input(array(
            'label'       => '',
            'id'          => 'dhlpwc_send_with_bp_count',
            'desc_tip'    => false,
            'description' => __('Amount that fits into one mailbox parcel', 'dhlpwc'),
            'type'        => 'number',
            'placeholder' => '1',
            "custom_attributes" => array(
                'min' => '1',
            )
        ));

        woocommerce_wp_checkbox(array(
            'label'       => '',
            'id'          => 'dhlpwc_send_with_bp_mix',
            'desc_tip'    => false,
            'description' => __('Also allow mixing with other products. Total amount must not exceed the maximum for one mailbox parcel.', 'dhlpwc'),
        ));

        woocommerce_wp_text_input(array(
            'label'       => __('DHL additional shipping fee', 'dhlpwc'),
            'id'          => 'dhlpwc_additional_shipping_fee',
            'desc_tip'    => true,
            'description' => __('Additional shipping fee. Will be multiplied by quantity. This price will still be added when shipping is free.', 'dhlpwc'),
            'type'        => 'number',
            'placeholder' => '0.00',
            "custom_attributes" => array(
                'min' => '0',
            )
        ));
    }

    public function save_custom_fields($post_id)
    {
        $product = wc_get_product($post_id);

        $value = isset($_POST['dhlpwc_enable_method_limit']) ? $_POST['dhlpwc_enable_method_limit'] : '';
        $product->update_meta_data('dhlpwc_enable_method_limit', $value);

        $value = isset($_POST['dhlpwc_selected_method_limit']) ? $_POST['dhlpwc_selected_method_limit'] : '';
        $product->update_meta_data('dhlpwc_selected_method_limit', $value);

        $value = isset($_POST['dhlpwc_additional_shipping_fee']) ? $_POST['dhlpwc_additional_shipping_fee'] : '';
        $product->update_meta_data('dhlpwc_additional_shipping_fee', str_replace(',', '.', $value));

        $value = isset($_POST['dhlpwc_send_with_bp']) ? $_POST['dhlpwc_send_with_bp'] : '';
        $product->update_meta_data('dhlpwc_send_with_bp', $value);

        $value = isset($_POST['dhlpwc_send_with_bp_count']) && is_numeric($_POST['dhlpwc_send_with_bp_count']) && intval($_POST['dhlpwc_send_with_bp_count']) > 0 ? $_POST['dhlpwc_send_with_bp_count'] : '';
        $product->update_meta_data('dhlpwc_send_with_bp_count', $value);

        $value = isset($_POST['dhlpwc_send_with_bp_mix']) ? $_POST['dhlpwc_send_with_bp_mix'] : '';
        $product->update_meta_data('dhlpwc_send_with_bp_mix', $value);

        $product->save();
    }

    public function woocommerce_wp_select_multiple($id, $description, $options)
    {
        $post_id = get_the_ID();
        $value = get_post_meta($post_id, $id, true) ? get_post_meta($post_id, $id, true) : array();

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
