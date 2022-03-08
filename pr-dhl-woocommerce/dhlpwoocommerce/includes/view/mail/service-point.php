<?php

/**
 * @var string                        $label
 * @var DHLPWC_Model_API_Data_Address $address
 */

if (!defined('ABSPATH')) { exit; }

?>
<h2 class="font-size: 18px; line-height: 130%; margin: 0 0 18px;"><?php echo esc_html($label) ?></h2>
<table cellspacing="0" cellpadding="0" border="0" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding: 0;">
    <tr>
        <td style="border: 0; padding: 0;">
            <address style="padding: 12px; color: #636363; border: 1px solid #e5e5e5;">
                <strong><?php echo esc_html($name) ?></strong><br/>
                <?php echo esc_html($address->street) ?> <?php echo esc_html($address->number) ?><br/>
                <?php echo esc_html($address->postal_code) ?> <?php echo esc_html($address->city) ?> <?php echo esc_html($address->country_code) ?>
            </address>
        </td>
    </tr>
</table>
