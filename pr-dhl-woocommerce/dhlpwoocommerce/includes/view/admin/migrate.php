<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="notice notice-warning is-dismissible dhlpwc-dismissable-migrate-notice" <?php if (isset($priority) && $priority === 1) : ?>style="background-color: #ffcc00;"<?php endif ?>>
    <div style="position: absolute;">
        <img src="https://ps.w.org/dhlpwc/assets/icon.svg?rev=2688756" class="plugin-icon" style="<?php if (isset($priority) && $priority === -1) : ?>width: 85px; height: 85px<?php else : ?>width: 128px; height: 128px<?php endif ?>; padding-right: 20px;" alt="">
    </div>
    <div style="position:relative; <?php if (isset($priority) && $priority === -1) : ?>left: 105px; margin: 0 105px 0 0;<?php else : ?>left: 150px; margin: 0 150px 0 0;<?php endif ?> padding: 0 0 20px 20px;">
        <span>
            <?php if (isset($priority) && $priority === -1) : ?>

                <h2>DHL Parcel for WooCommerce notice</h2>
                DHL Parcel services are no longer available in this plugin since September 1st 2022. To continue using DHL Parcel services, please install the new plugin <a href="<?php echo esc_url(admin_url('plugin-install.php?s=DHL Parcel for WooCommerce dhlpwc&tab=search&type=term')) ?>">here</a>.<br/><br/>

                <a href="#" id="dhlpwc-dismiss-migrate-notice-forever">
                    <b>Click here to never show this again</b>
                </a>

            <?php else : ?>

                <h1 <?php if (isset($priority) && $priority === 1) : ?>style="color: #d40511;"<?php endif ?>>Important notice</h1>
                Thank you for using DHL for WooCommerce and we hope you enjoy using our plugin.<br/>
                <br/>
                You have been using this plugin for DHL Parcel services. <u>Starting from <b>September 1st 2022</b>, these services will no longer be available in this plugin.</u><br/><br/>

                To continue using DHL Parcel services, please install the new plugin.<br/>
                Don't worry, it's the same services as you're used to, just under a slightly new name.<br/>
                <ul style="list-style: revert;">
                    <li>All the settings will automatically carry over.</li>
                    <li>Exactly the same interface.</li>
                    <li>Exactly the same features.</li>
                    <li>New name!</li>
                </ul>
                <a href="<?php echo esc_url(admin_url('plugin-install.php?s=DHL Parcel for WooCommerce dhlpwc&tab=search&type=term')) ?>">
                    <b>Click here to install DHL Parcel for WooCommerce</b>
                </a>.
                Send us an <a href="mailto: cimparcel@dhl.com">e-mail</a> if you have any questions or if you need help.

            <?php endif ?>

            <div class="clear"></div>
        </span>
    </div>
</div>
