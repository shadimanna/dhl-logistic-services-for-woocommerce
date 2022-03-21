<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="notice notice-warning is-dismissible dhlpwc-dismissable-migrate-notice" <?php if (isset($priority) && $priority === 1) : ?>style="background-color: #ffcc00;"<?php endif ?>>
    <div style="position: absolute;">
        <img src="https://ps.w.org/dhlpwc/assets/icon.svg?rev=2688756" class="plugin-icon" style="<?php if (isset($priority) && $priority === -1) : ?>width: 85px; height: 85px<?php else : ?>width: 128px; height: 128px<?php endif ?>; padding-right: 20px;" alt="">
    </div>
    <div style="position:relative; <?php if (isset($priority) && $priority === -1) : ?>left: 105px; margin: 0 105px 0 0;<?php else : ?>left: 150px; margin: 0 150px 0 0;<?php endif ?> padding: 0 0 20px 20px;">
        <span>
            <?php if (isset($priority) && $priority === -1) : ?>

                <h2><?php _e('DHL Parcel for WooCommerce notice', 'dhlpwc') ?></h2>
                <?php echo sprintf(
                    __('DHL Parcel services are no longer available in this plugin. To continue using our services, please install the new plugin %shere%s.', 'dhlpwc'),
                    '<a href="' . esc_url(admin_url('plugin-install.php?s=DHL Parcel for WooCommerce dhlpwc&tab=search&type=term')) . '">',
                    '</a>'
                ) ?>
                <br/><br/>

                <a href="#" id="dhlpwc-dismiss-migrate-notice-forever">
                    <b><?php _e('Click here to never show this again', 'dhlpwc') ?></b>
                </a>

            <?php else : ?>

                <h1 <?php if (isset($priority) && $priority === 1) : ?>style="color: #d40511;"<?php endif ?>><?php _e('Important notice', 'dhlpwc') ?></h1>
                <?php _e('Thank you for using DHL for WooCommerce and we hope you enjoy using our plugin.', 'dhlpwc') ?><br/>
                <br/>
                <?php _e('You have been using this plugin for DHL Parcel services.', 'dhlpwc') ?>
                <u><?php _e('In the future these services will no longer be available in this plugin.', 'dhlpwc') ?></u><br/><br/>

                <?php _e('To continue using DHL Parcel services, please install the new plugin.', 'dhlpwc') ?><br/>
                <?php _e("Don't worry, it's the same services as you're used to, just under a slightly new name.", 'dhlpwc') ?><br/>
                <ul style="list-style: revert;">
                    <li><?php _e('All the settings will automatically carry over.', 'dhlpwc') ?></li>
                    <li><?php _e('Exactly the same interface.', 'dhlpwc') ?></li>
                    <li><?php _e('Exactly the same features.', 'dhlpwc') ?></li>
                    <li><?php _e('New name!', 'dhlpwc') ?></li>
                </ul>
                <a class="button-primary" href="<?php echo esc_url(admin_url('plugin-install.php?s=DHL Parcel for WooCommerce dhlpwc&tab=search&type=term')) ?>"><?php _e('Install from WordPress', 'dhlpwc') ?></a><br/>
                <?php _e('Click here to install DHL Parcel for WooCommerce.', 'dhlpwc') ?><br/><br/>

                <?php echo sprintf(
                    __('Send us an %se-mail%s if you have any questions or if you need help.', 'dhlpwc'),
                    '<a href="mailto: cimparcel@dhl.com">',
                    '</a>'
                ) ?>
                <br/><br/>

            <?php endif ?>

            <div class="clear"></div>
        </span>
    </div>
</div>
