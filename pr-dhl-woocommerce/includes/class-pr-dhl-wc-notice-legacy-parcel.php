<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * WooCommerce DHL Add DHL Parcel (Legacy) Notice
 *
 * @package  PR_DHL_WC_Notice_Legacy_Parcel
 * @category Admin Notice
 * @author   Shin Ho
 */

if ( ! class_exists( 'PR_DHL_WC_Notice_Legacy_Parcel' ) ) :

class PR_DHL_WC_Notice_Legacy_Parcel {

    const NOTICE_TAG_MIGRATE = 'dhlpwc_migrate_notice';
    const NOTICE_TAG_MIGRATE_FOREVER = 'dhlpwc_migrate_notice_forever';

    /**
     * Init and hook in the integration.
     */
    public function __construct( ) {
        if ( ! is_admin() ) {
            return;
        }

        if ( get_option( self::NOTICE_TAG_MIGRATE_FOREVER, null ) ) {
            return;
        }

        if ( ! $this->check_eligible_country() ) {
            return;
        }

        if ( ! $this->check_was_enabled_config() ) {
            return;
        }

        add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ));

        add_action( 'admin_notices', array( $this, 'show_migrate_notice' ) );
        add_action( 'wp_ajax_dhl_legacy_parcel_dismiss_migrate_notice', array( $this, 'dismiss_notice' ));
        add_action( 'wp_ajax_dhl_legacy_parcel_dismiss_migrate_notice_forever', array( $this, 'dismiss_notice_forever' ));
    }

    public function show_migrate_notice() {
        if ( get_site_transient( self::NOTICE_TAG_MIGRATE ) ) {
            return;
        }

        if (
            $this->is_home_screen() ||
            $this->is_ordergrid_screen() ||
            $this->is_order_screen() ||
            $this->is_wc_settings_screen()
        ) {
            ?>

            <div class="notice notice-warning is-dismissible dhl-legacy-parcel-dismiss-migrate-notice">
                <div style="position: absolute;">
                    <img src="https://ps.w.org/dhlpwc/assets/icon.svg?rev=2688756" class="plugin-icon" style="width: 85px; height: 85px; padding-right: 20px;" alt="">
                </div>
                <div style="position:relative; left: 105px; margin: 0 105px 0 0; padding: 0 0 20px 20px;">
                    <span>
                            <h2><?php _e('DHL Parcel for WooCommerce notice', 'dhlpwc') ?></h2>
                            <?php echo sprintf(
                                __('DHL Parcel services are no longer available in this plugin. To continue using Parcel services, please install the new plugin %shere%s.', 'dhl-for-woocommerce'),
                                '<a href="' . esc_url( admin_url( 'plugin-install.php?s=DHL Parcel for WooCommerce dhlpwc&tab=search&type=term' ) ) . '">',
                                '</a>'
                            ) ?>
                            <br/><br/>

                            <a href="#" id="dhl-legacy-parcel-dismiss-migrate-notice-forever">
                                <b><?php _e('Click here to never show this again', 'dhl-for-woocommerce') ?></b>
                            </a>
                        <div class="clear"></div>
                    </span>
                </div>
            </div>

            <?php
        }
    }

    public function dismiss_notice() {
        check_ajax_referer( 'pr-dhl-legacy-parcel-dismiss-notice', 'security' );

        // Low level priority
        $time = 14 * DAY_IN_SECONDS;
        set_site_transient(self::NOTICE_TAG_MIGRATE, true, $time);

        // Send JSON response
        wp_send_json(array(
            'status' => 'success',
            'data' => array(),
            'message' => null
        ), 200);
    }

    public function dismiss_notice_forever() {
        check_ajax_referer( 'pr-dhl-legacy-parcel-dismiss-notice', 'security' );

        update_option(self::NOTICE_TAG_MIGRATE_FOREVER, true);
        wp_send_json(array(
            'status' => 'success',
            'data' => array(),
            'message' => null
        ), 200);
    }

    protected function check_was_enabled_config() {
        $shipping_method = get_option( 'woocommerce_dhlpwc_settings' );

        if ( empty( $shipping_method ) ) {
            return false;
        }

        if ( ! isset( $shipping_method['enable_all'] ) ) {
            return false;
        }

        if ( $shipping_method[ 'enable_all' ] != 'yes' ) {
            return false;
        }

        if ( empty( $shipping_method['user_id'] ) ) {
            return false;
        }

        if ( empty( $shipping_method['key'] ) ) {
            return false;
        }

        if ( empty( $shipping_method['account_id'] ) ) {
            return false;
        }

        return true;
    }

    protected function check_eligible_country() {
        $country_code = wc_get_base_location();

        if ( ! in_array($country_code['country'], array(
            'NL',
            'BE',
            'LU'
        ) ) ) {
            return false;
        }
        return true;
    }

    protected function is_home_screen() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return false;
        }

        $screen = get_current_screen();
        if ( ! isset( $screen ) ) {
            return false;
        }

        if ( $screen->base !== 'dashboard' ) {
            return false;
        }

        return true;
    }

    protected function is_ordergrid_screen() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return false;
        }

        $screen = get_current_screen();
        if ( ! isset( $screen ) ) {
            return false;
        }

        if ( $screen->base !== 'edit' || $screen->post_type !== 'shop_order' ) {
            return false;
        }

        return true;
    }

    protected function is_order_screen() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return false;
        }

        $screen = get_current_screen();
        if ( ! isset( $screen ) ) {
            return false;
        }

        if ( $screen->base !== 'post' || $screen->post_type !== 'shop_order' ) {
            return false;
        }

        return true;
    }

    protected function is_wc_settings_screen() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return false;
        }

        $screen = get_current_screen();
        if ( ! isset( $screen ) ) {
            return false;
        }

        if ( $screen->base !== 'woocommerce_page_wc-settings' ) {
            return false;
        }

        return true;
    }

    public function load_scripts() {
        if (
            $this->is_home_screen() ||
            $this->is_ordergrid_screen() ||
            $this->is_order_screen() ||
            $this->is_wc_settings_screen()
        ) {
            $dismiss_data = array(
                'security' => wp_create_nonce( 'pr-dhl-legacy-parcel-dismiss-notice' )
            );

            wp_enqueue_script( 'wc-shipment-dhl-legacy-parcel-dismiss-notice-js', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-notice-legacy-parcel.js', array('jquery'), PR_DHL_VERSION );
            wp_localize_script( 'wc-shipment-dhl-legacy-parcel-dismiss-notice-js', 'dhl_legacy_parcel_dismiss_notice', $dismiss_data );
        }
    }
}

endif;
