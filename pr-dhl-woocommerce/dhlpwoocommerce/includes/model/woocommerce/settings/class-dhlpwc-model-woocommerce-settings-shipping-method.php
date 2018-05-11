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
        $this->method_title = __('DHL for WooCommerce', 'dhlpwc');
        $this->method_description = __('This is the official DHL Plugin for WooCommerce in WordPress. Do you have a WooCommerce webshop and are you looking for an easy way to process shipments within the Netherlands and abroad? This plugin offers you many options. You can easily create shipping labels and offer multiple delivery options in your webshop. Set up your account below.', 'dhlpwc');

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
     * @inheritdoc
     */
    public function get_admin_options_html() {
        return '<div id="dhlpwc_shipping_method_settings">' . parent::get_admin_options_html() . '</div>';
    }

    /**
     * Define settings field for this shipping
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            // Enable plugin
            'plugin_settings'              => array(
                'title'       => __('Plugin Settings', 'dhlpwc'),
                'type'        => 'title',
                'description' => __('Enable features of this plugin.', 'dhlpwc'),
            ),
            'enable_all'                   => array(
                'title'       => __('Enable plugin', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __("Disabling this turns all of the plugin's features off.", 'dhlpwc'),
                'default'     => 'yes',
            ),
            'enable_column_info'           => array(
                'title'       => __('DHL label info', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Show', 'dhlpwc'),
                'description' => __("Add shipping information in an additional column in your order overview.", 'dhlpwc'),
                'default'     => 'yes',
            ),
            'enable_track_trace_mail' => array(
                'title'       => __('Track & trace in mail', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __("Add track & trace information to the default WooCommerce completed order e-mail if available.", 'dhlpwc'),
                'default'     => 'no',
            ),
            'enable_track_trace_component' => array(
                'title'       => __('Track & trace component', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Show', 'dhlpwc'),
                'description' => __("Customers can see a track & trace component in the order summary.", 'dhlpwc'),
                'default'     => 'yes',
            ),

            // API settings
            'api_settings'                      => array(
                'title'       => __('Account details', 'dhlpwc'),
                'type'        => 'title',
                'description' => sprintf(
                    __('Please configure your credentials for the DHL API. No API credentials yet? Get it %shere%s.', 'dhlpwc'),
                    '<a href="https://my.dhlparcel.nl/" target="_blank">',
                    '</a>'
                ),
            ),
            'user_id'    => array(
                'title'       => __('UserID', 'dhlpwc'),
                'type'        => 'text',
                'placeholder' => sprintf(__('Example: %s', 'dhlpwc'), '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d'),
            ),
            'key'        => array(
                'title'       => __('Key', 'dhlpwc'),
                'type'        => 'text',
                'placeholder' => sprintf(__('Example: %s', 'dhlpwc'), '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d'),
            ),
            'test_connection' => array(
                'title'       => __('Test connection', 'dhlpwc'),
                'type'        => 'button',
                'disabled'    => true,
            ),
            'account_id' => array(
                'title'       => __('AccountID', 'dhlpwc'),
                'type'        => 'text',
                'placeholder' => sprintf(__('Example: %s', 'dhlpwc'), '01234567'),
            ),
            'organization_id' => array(
                'title'       => __('OrganizationID', 'dhlpwc'),
                'type'        => 'text',
                'placeholder' => sprintf(__('Example: %s', 'dhlpwc'), '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d'),
            ),

            // Shipment options
            'shipment_options_settings' => array(
                'title'       => __('Shipment options', 'dhlpwc'),
                'type'        => 'title',
                'description' => __("Choose the shipment options for the recipients of your webshop.", 'dhlpwc'),
            ),

            'default_send_to_business' => array(
                'title' => __('Send to business by default', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __("When enabled, by default labels will be created for business-to-business shipments.", 'dhlpwc'),
                'default'     => 'no',
            ),
            'enable_option_free' => array(
                'title'       => __('Free shipping', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __("Offer free shipping (over a certain amount).", 'dhlpwc'),
            ),
            'price_option_free' => array(
                'title'             => __('Free shipping over', 'dhlpwc'),
                'type'              => 'price',
                'default'           => '0.00',
                'class'             => 'dhlpwc-price-input',
                'custom_attributes' => array(
                    'data-dhlpwc-currency-symbol'     => get_woocommerce_currency_symbol(),
                    'data-dhlpwc-currency-pos' => get_option('woocommerce_currency_pos'),
                ),
            ),

            'price_tax_assistance' => array(
                'title'       => __('VAT percentage assistance', 'dhlpwc'),
                'type'        => 'decimal',
                'description' => __("If VAT is added to your shipping costs, but you wish to enter the total costs here, then use this assistance calculation by entering the VAT percentage. If your costs are excluded from VAT, please enter '0'.", 'dhlpwc'),
                'default'     => '0',
            ),

            'enable_option_home' => array(
                'title'       => __('Home delivery', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __("Delivery of the parcel at the address of the recipient.", 'dhlpwc'),
                'default'     => 'yes',
            ),
            'price_option_home'  => array(
                'title'             => __('Shipping costs home delivery', 'dhlpwc'),
                'type'              => 'price',
                'default'           => '0.00',
                'class'             => 'dhlpwc-price-input',
                'custom_attributes' => array(
                    'data-dhlpwc-currency-symbol' => get_woocommerce_currency_symbol(),
                    'data-dhlpwc-currency-pos'    => get_option('woocommerce_currency_pos'),
                ),
            ),

            'enable_option_no_neighbour' => array(
                'title'       => __('No delivery to neighbour', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __("No delivery at neighbours in case the recipient is not at home.", 'dhlpwc'),
                'default'     => 'yes',
            ),
            'price_option_no_neighbour'  => array(
                'title'             => __('Shipping costs no delivery to neighbour', 'dhlpwc'),
                'type'              => 'price',
                'default'           => '0.00',
                'class'             => 'dhlpwc-price-input',
                'custom_attributes' => array(
                    'data-dhlpwc-currency-symbol' => get_woocommerce_currency_symbol(),
                    'data-dhlpwc-currency-pos'    => get_option('woocommerce_currency_pos'),
                ),
            ),

            'enable_option_evening' => array(
                'title'       => __('Evening delivery', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __("Delivery of the parcel between 6 p.m. and 9 p.m. at the address of the recipient.", 'dhlpwc'),
                'default'     => 'yes',
            ),
            'price_option_evening'  => array(
                'title'             => __('Shipping costs evening delivery', 'dhlpwc'),
                'type'              => 'price',
                'default'           => '0.00',
                'class'             => 'dhlpwc-price-input',
                'custom_attributes' => array(
                    'data-dhlpwc-currency-symbol' => get_woocommerce_currency_symbol(),
                    'data-dhlpwc-currency-pos'    => get_option('woocommerce_currency_pos'),
                ),
            ),

            'enable_option_parcelshop'          => array(
                'title'       => __('DHL ServicePoint', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __("Delivery of the parcel at a DHL ServicePoint near to the recipient.", 'dhlpwc'),
                'default'     => 'yes',
            ),
            'price_option_parcelshop'           => array(
                'title'             => __('Shipping costs DHL ServicePoint', 'dhlpwc'),
                'type'              => 'price',
                'default'           => '0.00',
                'class'             => 'dhlpwc-price-input',
                'custom_attributes' => array(
                    'data-dhlpwc-currency-symbol' => get_woocommerce_currency_symbol(),
                    'data-dhlpwc-currency-pos'    => get_option('woocommerce_currency_pos'),
                ),
            ),

            // Default shipping address
            'default_shipping_address_settings' => array(
                'title'       => __('Default Shipping Address', 'dhlpwc'),
                'type'        => 'title',
                'description' => __('Fill in the details of your shipping address.', 'dhlpwc'),
            ),
            'first_name'                        => array(
                'title' => __('First Name', 'dhlpwc'),
                'type'  => 'text',
            ),
            'last_name'                         => array(
                'title' => __('Last Name', 'dhlpwc'),
                'type'  => 'text',
            ),
            'company'                           => array(
                'title' => __('Company', 'dhlpwc'),
                'type'  => 'text',
            ),
            'postcode'                          => array(
                'title' => __('Postcode', 'dhlpwc'),
                'type'  => 'text',
            ),
            'city'                              => array(
                'title' => __('City', 'dhlpwc'),
                'type'  => 'text',
            ),
            'street'                            => array(
                'title' => __('Street', 'dhlpwc'),
                'type'  => 'text',
            ),
            'number'                            => array(
                'title' => __('Number', 'dhlpwc'),
                'type'  => 'text',
            ),
            'country' => array(
                'title' => __('Country', 'dhlpwc'),
                'type' => 'select',
                'options' => array(
                    'NL' => __('Netherlands', 'dhlpwc'),
                    'BE' => __('Belgium', 'dhlpwc'),
                    'LU' => __('Luxemburg', 'dhlpwc'),
                    'ES' => __('Spain', 'dhlpwc'),
                    'PT' => __('Portugal', 'dhlpwc'),
                ),
                'default' => 'NL',
            ),
            'email'                             => array(
                'title' => __('Email', 'dhlpwc'),
                'type'  => 'text',
            ),
            'phone'                             => array(
                'title' => __('Phone', 'dhlpwc'),
                'type'  => 'text',
            ),

            // Debug
            'developer_settings'                => array(
                'title'       => __('Debug Settings', 'dhlpwc'),
                'type'        => 'title',
                'description' => __('Settings for developers.', 'dhlpwc'),
            ),
            'enable_debug'                      => array(
                'title'       => __('Enable reporting', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __('Allow the debug options below.', 'dhlpwc'),
            ),
            'enable_debug_mail'                 => array(
                'title'       => __('Report by mail', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __('Problems with the DHL API are automatically reported to the plugin developers.', 'dhlpwc'),
            ),
            'debug_url'                         => array(
                'title'       => __('Report with custom URL', 'dhlpwc'),
                'type'        => 'text',
                'description' => __("Debug URL used by developers. Please contact support if active monitoring is required and for the correct value. Will not be used if left empty.", 'dhlpwc'),
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
                'label' => __('Deliver at a DHL ServicePoint', 'dhlpwc'),
                'cost'  => $free_shipping ? 0 : $price_service->get_price_parcelshop(),
            ));
        }
    }

}

endif;
