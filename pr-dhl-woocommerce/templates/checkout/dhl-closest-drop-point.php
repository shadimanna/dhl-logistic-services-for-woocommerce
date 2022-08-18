<?php
defined( 'ABSPATH' ) or exit;

$logo_url = PR_DHL_PLUGIN_DIR_URL . '/assets/img/dhl-official.png';
?>
<tr class="dhl-co-tr dhl-co-tr-fist">
    <td colspan="2"><img src="<?php echo $logo_url; ?>" alt="DHL logo" class="dhl-co-logo"></td>
</tr>
<tr class="dhl-co-tr">
    <th class="dhl-cdp"><?php _e('Delivery option', 'dhl-for-woocommerce'); ?></th>
    <td class="dhl-cdp">
        <ul class="dhl-preferred-location">
            <li>
                <input
                        checked="checked"
                        type="radio"
                        name="pr_dhl_delivery_option"
                        data-index="0" id="dhl_home_deliver_option"
                        value="0"
                        class="home_deliver" >
                <label for="dhl_home_deliver_option"><?php _e('Home delivery', 'dhl-for-woocommerce'); ?></label>
            </li>
            <li>
                <input
                        type="radio"
                        name="pr_dhl_delivery_option"
                        data-index="0" id="dhl_cdp_option"
                        value="cdp"
                        class="" >
                <label for="dhl_cdp_option"><?php _e('Closest Drop Point', 'dhl-for-woocommerce'); ?></label>
            </li>
        </ul>
    </td>
</tr>
<tr class="dhl-co-tr dhl-co-tr-last">
    <td colspan="2"></td>
</tr>