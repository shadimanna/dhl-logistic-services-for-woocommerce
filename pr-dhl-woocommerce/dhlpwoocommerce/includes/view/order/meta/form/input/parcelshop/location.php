<?php if (!defined('ABSPATH')) { exit; } ?>
<hr/>
<div class="dhlpwc-metabox-parcelshop-location" data-parcelshop-id="<?php echo esc_attr($parcelshop->id) ?>">
    <strong><?php echo esc_attr($parcelshop->name) ?></strong><br/>
    <span>
        <?php echo esc_attr($parcelshop->address->street) ?> <?php echo esc_attr($parcelshop->address->number) ?>,
        <?php echo esc_attr($parcelshop->address->postal_code) ?>,
        <?php echo esc_attr($parcelshop->address->city) ?>
    </span>
</div>
