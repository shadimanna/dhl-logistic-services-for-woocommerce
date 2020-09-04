<?php

/**
 * @var string                        $label
 * @var DHLPWC_Model_API_Data_Address $address
 */

if (!defined('ABSPATH')) { exit; }

?>
<h2 class="font-size: 18px; line-height: 130%; margin: 0 0 18px;"><?php echo $label; ?></h2>
<table cellspacing="0" cellpadding="0" border="0" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding: 0;">
    <tr>
        <td style="border: 0; padding: 0;">
            <address style="padding: 12px; color: #636363; border: 1px solid #e5e5e5;">
                <strong><?php echo $name ?></strong><br/>
                <?php echo $address->street ?> <?php echo $address->number ?><br/>
                <?php echo $address->postal_code ?> <?php echo $address->city ?> <?php echo $address->country_code ?>
            </address>
        </td>
    </tr>
</table>
