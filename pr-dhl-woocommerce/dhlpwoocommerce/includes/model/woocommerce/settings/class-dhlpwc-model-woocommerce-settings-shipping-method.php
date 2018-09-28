<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_WooCommerce_Settings_Shipping_Method')) :

class DHLPWC_Model_WooCommerce_Settings_Shipping_Method extends WC_Shipping_Method
{

    const ENABLE_FREE = 'enable_option_free';
    const ENABLE_TAX_ASSISTANCE = 'enable_tax_assistance';

    const PRICE_FREE = 'price_option_free';
    const FREE_AFTER_COUPON = 'free_after_coupon';

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
        $this->instance_id           = absint( $instance_id );
        $this->title = $this->method_title;
        $this->supports              = array(
            'instance-settings',
            'instance-settings-modal',
            'settings'
        );
        if ($this->get_option('use_shipping_zones') === 'yes') {
            array_unshift($this->supports, 'shipping-zones');
        }
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
        $this->init_instance_form_fields();
        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * @inheritdoc
     */
    public function get_admin_options_html() {
        return '<div id="dhlpwc_shipping_method_settings">' . parent::get_admin_options_html() . '</div>';
    }

    public function init_instance_form_fields()
    {
        if ($this->get_option('use_shipping_zones') === 'yes') {
            $this->instance_form_fields = $this->get_shipping_method_fields(false);
        } else {
            $this->instance_form_fields = array(
                'plugin_settings'              => array(
                    'title'       => __('Shipping Zones Settings', 'dhlpwc'),
                    'type'        => 'title',
                    'description' => __('Please enable Shipping Zones to use this feature.', 'dhlpwc'),
                )
            );
        }
    }

    /**
     * Define settings field for this shipping
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array_merge(
            array(
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
                'open_label_links_external'    => array(
                    'title'       => __('Open admin label links in a new window', 'dhlpwc'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable', 'dhlpwc'),
                    'description' => __("Label actions like downloading PDF or opening track & trace will open in a new window.", 'dhlpwc'),
                    'default'     => 'yes',
                ),
                'enable_track_trace_mail' => array(
                    'title'       => __('Track & trace in mail', 'dhlpwc'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable', 'dhlpwc'),
                    'description' => __("Add track & trace information to the default WooCommerce 'completed order' e-mail if available.", 'dhlpwc'),
                    'default'     => 'no',
                ),
                'enable_track_trace_component' => array(
                    'title'       => __('Track & trace component', 'dhlpwc'),
                    'type'        => 'checkbox',
                    'label'       => __('Show', 'dhlpwc'),
                    'description' => __("Include a track & trace component in the order summary for customers, when they log into the website and check their account information.", 'dhlpwc'),
                    'default'     => 'yes',
                ),
                'google_maps_key' => array(
                    'title'       => __('Google Maps key', 'dhlpwc'),
                    'type'        => 'text',
                    'placeholder' => sprintf(__('Example: %s', 'dhlpwc'), '1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f90a'),
                    'description' => sprintf(
                        __('Please configure your credentials for the Google Maps API. No Google Maps API credentials yet? Get it %shere%s.', 'dhlpwc'),
                        '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">',
                        '</a>'
                    ),
                ),

                // API settings
                'api_settings'                      => array(
                    'title'       => __('Account details', 'dhlpwc'),
                    'type'        => 'title',
                    'description' => sprintf(
                        __('DHL API settings. Still missing API credentials? Follow the instructions %shere%s.', 'dhlpwc'),
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
                    'title'       => __('Send to business by default', 'dhlpwc'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable', 'dhlpwc'),
                    'description' => __("When enabled, by default labels will be created for business shipments and the checkout will show business shipping options.", 'dhlpwc'),
                    'default'     => 'no',
                ),

                'check_default_send_signature' => array(
                    'title'       => __('Always enable required signature if available', 'dhlpwc'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable', 'dhlpwc'),
                    'description' => __("When creating a label, always select the signature option by default if the service is available.", 'dhlpwc'),
                    'default'     => 'no',
                ),

                'use_shipping_zones' => array(
                    'title'       => __('Use shipping zones', 'dhlpwc'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable', 'dhlpwc'),
                    'description' => __("Set shipping methods per shipping zone.", 'dhlpwc'),
                    'default'     => 'no',
                ),
            ),

            $this->get_shipping_method_fields(),

            array(
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
                    'title'       => __('Report errors', 'dhlpwc'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable', 'dhlpwc'),
                    'description' => __('Enable this and select one of the reporting methods below to automatically send errors of this plugin to the development team.', 'dhlpwc'),
                ),
                'enable_debug_mail'                 => array(
                    'title'       => __('By mail', 'dhlpwc'),
                    'type'        => 'checkbox',
                    'label'       => __('Enable', 'dhlpwc'),
                    'description' => __('Errors will be automatically forwarded by e-mail.', 'dhlpwc'),
                ),
                'debug_url'                         => array(
                    'title'       => __('By custom URL', 'dhlpwc'),
                    'type'        => 'text',
                    'description' => __("Monitoring URL. Used by developers. Can be used for active monitoring, please contact support for this feature. Will not be used if left empty.", 'dhlpwc'),
                ),
                'debug_external_url' => array(
                    'title'       => __('External custom URL', 'dhlpwc'),
                    'type'        => 'text',
                    'description' => __("Alternative external URL. Used by developers. Will not be used if left empty.", 'dhlpwc'),
                ),
            )
        );
    }

    protected function get_option_group_fields($code, $title, $class = '')
    {
        $option_settings = array(
            'enable_option_' . $code => array(
                'title'             => __($title, 'dhlpwc'),
                'type'              => 'checkbox',
                'class'             => "dhlpwc-grouped-option dhlpwc-option-grid['" . $code . "'] " . $class,
                'default'           => 'no',
                'custom_attributes' => array(
                    'data-option-group' => $code,
                ),
            ),

            'price_option_' . $code => array(
                'type'              => 'price',
                'class'             => "dhlpwc-grouped-option dhlpwc-price-input dhlpwc-option-grid['" . $code . "'] " . $class,
                'default'           => '0.00',
                'custom_attributes' => array(
                    'data-dhlpwc-currency-symbol' => get_woocommerce_currency_symbol(),
                    'data-dhlpwc-currency-pos'    => get_option('woocommerce_currency_pos'),
                    'data-option-group'           => $code,
                ),
            ),

            'enable_free_option_' . $code => array(
                'type'              => 'checkbox',
                'class'             => "dhlpwc-grouped-option dhlpwc-option-grid['" . $code . "'] " . $class,
                'default'           => 'no',
                'custom_attributes' => array(
                    'data-option-group' => $code,
                ),
            ),

            'free_price_option_' . $code => array(
                'type'              => 'price',
                'class'             => "dhlpwc-grouped-option dhlpwc-price-input dhlpwc-option-grid['" . $code . "'] " . $class,
                'default'           => '0.00',
                'custom_attributes' => array(
                    'data-dhlpwc-currency-symbol' => get_woocommerce_currency_symbol(),
                    'data-dhlpwc-currency-pos'    => get_option('woocommerce_currency_pos'),
                    'data-option-group'           => $code,
                ),
            ),
        );

        return $option_settings;
    }

    protected function get_shipping_method_fields($is_global = true)
    {
        if ($is_global) {
            $class = 'dhlpwc-global-shipping-setting';
        } else {
            $class = 'dhlpwc-instance-shipping-setting';
        }

        $option_settings = array(
            self::ENABLE_TAX_ASSISTANCE => array(
                'title'       => __('Enter prices with tax included', 'dhlpwc'),
                'type'        => 'checkbox',
                'description' => __("Turn this on to enter prices with the tax included. Turn this off to enter prices without tax.", 'dhlpwc'),
                'class'       => $class,
                'default'     => 'yes',
            ),

            self::ENABLE_FREE => array(
                'title'       => __('Free or discounted shipping', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Enable', 'dhlpwc'),
                'description' => __("Offer free shipping (over a certain amount).", 'dhlpwc'),
                'class'       => $class,
                'default'     => 'no',
            ),

            self::PRICE_FREE       => array(
                'title'             => __('Free or discounted shipping threshold', 'dhlpwc'),
                'type'              => 'price',
                'description'       => __("Free or discounted shipping prices are applied when the total price is over the inputted value.", 'dhlpwc'),
                'default'           => '0.00',
                'class'             => 'dhlpwc-price-input '.$class,
                'custom_attributes' => array(
                    'data-dhlpwc-currency-symbol'     => get_woocommerce_currency_symbol(),
                    'data-dhlpwc-currency-pos' => get_option('woocommerce_currency_pos'),
                ),
            ),

            self::FREE_AFTER_COUPON => array(
                'title'       => __('Free or discounted shipping and coupons', 'dhlpwc'),
                'type'        => 'checkbox',
                'label'       => __('Calculate after applying coupons', 'dhlpwc'),
                'description' => __("Calculate eligibility for free or discounted shipping after applying coupons.", 'dhlpwc'),
                'class'       => $class,
                'default'     => 'no',
            ),

        );

//        $connector = DHLPWC_Model_API_Connector::instance();
//        $sender_type = 'business'; // A webshop is always a business, not a regular consumer type nor a parcelshop. Will leave this as hardcoded for now.
//        $response = $connector->get(sprintf('shipment-options/%s', $sender_type), null, 20000);

        $service = DHLPWC_Model_Service_Shipping_Preset::instance();
        $presets = $service->get_presets();

        $option_settings['grouped_option_container'] = array(
            'type'  => 'dhlpwc_grouped_option_container',
        );

        foreach($presets as $data) {
            $preset = new DHLPWC_Model_Meta_Shipping_Preset($data);

            $option_settings = array_merge($option_settings, $this->get_option_group_fields($preset->setting_id, $preset->title, $class));
        }

        return $option_settings;
    }

    public function calculate_shipping($package = array())
    {
        $service = DHLPWC_Model_Service_Shipping_Preset::instance();
        $presets = $service->get_presets();

        $access_service = DHLPWC_Model_Service_Access_Control::instance();
        $allowed_shipping_options = $access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_CAPABILITY_OPTIONS);

        foreach($presets as $data) {
            $preset = new DHLPWC_Model_Meta_Shipping_Preset($data);

            $check_allowed_options = true;
            foreach($preset->options as $preset_option) {
                if (!array_key_exists($preset_option, $allowed_shipping_options)) {
                    $check_allowed_options = false;
                }
            }

            if ($this->get_option('enable_option_'.$preset->setting_id) === 'yes' && $check_allowed_options === true) {
                $this->add_rate(array(
                    'id'    => 'dhlpwc-'.$preset->frontend_id,
                    'label' => __($preset->title, 'dhlpwc'),
                    'cost'  => $this->calculate_cost($package, $preset->setting_id),
                ));
            }
        }

        $this->update_taxes();
    }

    protected function generate_dhlpwc_grouped_option_container_html($key, $data)
    {
        $view = new DHLPWC_Template('admin.settings.options-grid-header');
        return $view->render(array(), false);
    }

    protected function calculate_cost($package = array(), $option)
    {
        if ($this->get_option(self::ENABLE_FREE) === 'yes') {
            if ($this->get_subtotal_price($package) >= $this->get_option(self::PRICE_FREE)) {
                return $this->get_free_price($option);
            }
        }
        return $this->get_option('price_option_'.$option);
    }

    protected function get_free_price($option)
    {
        if ($this->get_option('enable_free_option_'.$option) === 'yes') {
            return round($this->get_option('free_price_option_'.$option), wc_get_price_decimals());
        }
        return $this->get_option('price_option_'.$option);
    }

    protected function get_subtotal_price($package = array())
    {
        if ($this->get_option(self::FREE_AFTER_COUPON) === 'yes') {
            $subtotal = 0;
            foreach($package['contents'] as $key => $order)
            {
                $subtotal += $order['line_total'] + $order['line_tax'];
            }
            return round($subtotal, wc_get_price_decimals());
        }

        return $package['cart_subtotal'];
    }

    protected function update_taxes()
    {
        if ($this->get_option(self::ENABLE_TAX_ASSISTANCE) === 'yes') {
            foreach($this->rates as $rate_id => $rate) {
                /** @var WC_Shipping_Rate $rate */
                $rate->set_cost($rate->get_cost() - $rate->get_shipping_tax());
            }
        }
    }
}

endif;
