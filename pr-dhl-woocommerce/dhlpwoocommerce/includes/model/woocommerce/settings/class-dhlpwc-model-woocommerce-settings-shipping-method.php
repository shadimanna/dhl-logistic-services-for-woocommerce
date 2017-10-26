<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_WooCommerce_Settings_Shipping_Method')) :

class DHLPWC_Model_WooCommerce_Settings_Shipping_Method extends WC_Shipping_Method
{

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id = 'dhlpwc';
        $this->method_title = __('DHL Parcel', 'dhlpwc');
        $this->method_description = __('Settings for DHL Parcel services', 'dhlpwc');

        $this->init();
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    public function init()
    {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Define settings field for this shipping
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            // Enable plugin
            'plugin_settings' => array(
                'title'       => __('Plugin Settings', 'dhlpwc'),
                'type'        => 'title',
                'description' => __('Enable features of this plugin.', 'dhlpwc'),
            ),
            'enable_all' => array(
                'title' => __( 'Enable plugin', 'dhlpwc' ),
                'type' => 'checkbox',
                'label' => __('Enable', 'dhlpwc'),
                'description' => __( "Disabling this turns all of the plugin's features off.", 'dhlpwc' ),
                'default' => 'yes'
            ),
            'enable_column_info' => array(
                'title' => __( 'DHL Column Info', 'dhlpwc' ),
                'type' => 'checkbox',
                'label' => __('Show', 'dhlpwc'),
                'description' => __( "Shows additional DHL information when viewing orders in the admin.", 'dhlpwc' ),
                'default' => 'yes'
            ),

            // Shipment options
            'shipment_options_settings' => array(
                'title'       => __('Shipment Options Settings', 'dhlpwc'),
                'type'        => 'title',
                'description' => __("Choose which DHL shipment options are available. Note that prices are without tax. Use the 'Included tax percentage' field to enter with tax.", 'dhlpwc'),
                'default' => 'yes'
            ),

            'enable_option_free' => array(
                'title' => __( 'Free shipment cost', 'dhlpwc' ),
                'type' => 'checkbox',
                'label' => __('Enable', 'dhlpwc'),
                'description' => __( "Disable shipment cost if total price reaches a certain threshold.", 'dhlpwc' )
            ),
            'price_option_free' => array(
                'title' => __( 'Free shipping starting from', 'dhlpwc' ),
                'type' => 'price',
                'default' => '0.00',
            ),

            'price_tax_assistance' => array(
                'title' => __( 'Included tax percentage', 'dhlpwc' ),
                'type' => 'decimal',
                'description' => __( "In case the prices below are entered with included tax, please enter the amount of tax percentage. The tax will be automatically be substracted from the entered price when used in tax calculations. Leave at 0 in case the prices are already excluded from tax.", 'dhlpwc' ),
                'default' => '21',
            ),

            'enable_option_home' => array(
                'title' => __( 'Home delivery selection on checkout', 'dhlpwc' ),
                'type' => 'checkbox',
                'label' => __('Enable', 'dhlpwc'),
                'description' => __( "Regular delivery to door.", 'dhlpwc' ),
                'default' => 'yes'
            ),
            'price_option_home' => array(
                'title' => __( 'Home price', 'dhlpwc' ),
                'type' => 'price',
                'default' => '0.00',
            ),

            'enable_option_signed' => array(
                'title' => __( 'Signed delivery selection on checkout', 'dhlpwc' ),
                'type' => 'checkbox',
                'label' => __('Enable', 'dhlpwc'),
                'description' => __( "Allow signing requirement when receiving, if available.", 'dhlpwc' ),
                'default' => 'yes'
            ),
            'price_option_signed' => array(
                'title' => __( 'Signed price', 'dhlpwc' ),
                'type' => 'price',
                'default' => '0.00',
            ),

            'enable_option_no_neighbour' => array(
                'title' => __( 'Do not deliver to neighbours selection on checkout', 'dhlpwc' ),
                'type' => 'checkbox',
                'label' => __('Enable', 'dhlpwc'),
                'description' => __( "Allow shipment to not to be delivered to neighbours, if available.", 'dhlpwc' ),
                'default' => 'yes'
            ),
            'price_option_no_neighbour' => array(
                'title' => __( 'No neighbour price', 'dhlpwc' ),
                'type' => 'price',
                'default' => '0.00',
            ),

            'enable_option_evening' => array(
                'title' => __( 'Evening delivery selection on checkout', 'dhlpwc' ),
                'type' => 'checkbox',
                'label' => __('Enable', 'dhlpwc'),
                'description' => __( "Allow shipment to be delivered in the evening, if available.", 'dhlpwc' ),
                'default' => 'yes'
            ),
            'price_option_evening' => array(
                'title' => __( 'Evening price', 'dhlpwc' ),
                'type' => 'price',
                'default' => '0.00',
            ),

            'enable_option_parcelshop' => array(
                'title' => __( 'Parcelshop selection on checkout', 'dhlpwc' ),
                'type' => 'checkbox',
                'label' => __('Enable', 'dhlpwc'),
                'description' => __( "Allow Parcelshop selection, if available.", 'dhlpwc' ),
                'default' => 'yes'
            ),
            'price_option_parcelshop' => array(
                'title' => __( 'Parcelshop price', 'dhlpwc' ),
                'type' => 'price',
                'default' => '0.00',
            ),


            // API settings
            'api_settings' => array(
                'title'       => __('API Settings', 'dhlpwc'),
                'type'        => 'title',
                'description' => __('Please configure your credentials for the DHL API.', 'dhlpwc'),
            ),
            'account_id'      => array(
                'title' => __('AccountID', 'dhlpwc'),
                'type'  => 'text',
            ),
            'user_id'      => array(
                'title' => __('UserID', 'dhlpwc'),
                'type'  => 'text',
            ),
            'key'          => array(
                'title' => __('Key', 'dhlpwc'),
                'type'  => 'text',
            ),

            // Default shipping address
            'default_shipping_address_settings' => array(
                'title'       => __('Default Shipping Address', 'dhlpwc'),
                'type'        => 'title',
                'description' => __('Please set your default shipping address.', 'dhlpwc'),
            ),
            'first_name' => array(
                'title'       => __('First Name', 'dhlpwc'),
                'type'        => 'text',
            ),
            'last_name'  => array(
                'title'       => __('Last Name', 'dhlpwc'),
                'type'        => 'text',
            ),
            'company'  => array(
                'title'       => __('Company', 'dhlpwc'),
                'type'        => 'text',
            ),
            'postcode'   => array(
                'title'       => __('Postcode', 'dhlpwc'),
                'type'        => 'text',
            ),
            'city'       => array(
                'title'       => __('City', 'dhlpwc'),
                'type'        => 'text',
            ),
            'street'     => array(
                'title'       => __('Street', 'dhlpwc'),
                'type'        => 'text',
            ),
            'number'     => array(
                'title'       => __('Number', 'dhlpwc'),
                'type'        => 'text',
            ),
            'country'    => array(
                'title'       => __('Country code', 'dhlpwc'),
                'type'        => 'text',
            ),
            'email'      => array(
                'title'       => __('Email', 'dhlpwc'),
                'type'        => 'text',
            ),
            'phone'      => array(
                'title'       => __('Phone', 'dhlpwc'),
                'type'        => 'text',
            ),

            // Debug
            'developer_settings' => array(
                'title'       => __('Debug Settings', 'dhlpwc'),
                'type'        => 'title',
                'description' => __('Settings for developers.', 'dhlpwc'),
            ),
            'enable_debug' => array(
                'title' => __( 'Send debug data', 'dhlpwc' ),
                'type' => 'checkbox',
                'label' => __('Enable', 'dhlpwc'),
                'description' => __('When enabled, debug data will be sent to developers.', 'dhlpwc'),
            ),
            'debug_url' => array(
                'title' => __( 'Debug URL', 'dhlpwc' ),
                'type'  => 'text',
                'description' => __("Debug URL used by developers. Improper usage can cause errors on the website, so it's recommended to be left empty.", 'dhlpwc'),
            ),

        );

    }

    public function calculate_shipping($package = array())
    {
        $price_service = DHLPWC_Model_Service_Settings::instance();
        $access_service = DHLPWC_Model_Service_Access_Control::instance();

        $free_shipping = false;
        if ($access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPTION_FREE)) {
            $price_free = $price_service->get_price_free();
            if ($package['cart_subtotal'] >= $price_free) {
                $free_shipping = true;
            }
        }

        if ($access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPTION_HOME)) {
            $this->add_rate(array(
                'id'    => 'dhlpwc-home',
                'label' => __('Home delivery', 'dhlpwc'),
                'cost'  => $free_shipping ? 0 : $price_service->get_price_home(),
            ));
        }

        if ($access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPTION_SIGNED)) {
            $this->add_rate(array(
                'id'    => 'dhlpwc-home-signed',
                'label' => __('Home delivery, signed', 'dhlpwc'),
                'cost'  => $free_shipping ? 0 : $price_service->get_price_signed(),
            ));
        }

        if ($access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPTION_NO_NEIGHBOUR)) {
            $this->add_rate(array(
                'id'    => 'dhlpwc-home-no-neighbour',
                'label' => __('Home delivery, no neighbours', 'dhlpwc'),
                'cost'  => $free_shipping ? 0 : $price_service->get_price_no_neighbour(),
            ));
        }

        if ($access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPTION_EVENING)) {
            $this->add_rate(array(
                'id'    => 'dhlpwc-home-evening',
                'label' => __('Home delivery, evening', 'dhlpwc'),
                'cost'  => $free_shipping ? 0 : $price_service->get_price_evening(),
            ));
        }

        if ($access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_OPTION_PARCELSHOP)) {
            $this->add_rate(array(
                'id'    => 'dhlpwc-parcelshop',
                'label' => __('Deliver to a nearby pickup point', 'dhlpwc'),
                'cost'  => $free_shipping ? 0 : $price_service->get_price_parcelshop(),
            ));
        }
    }

}

endif;
