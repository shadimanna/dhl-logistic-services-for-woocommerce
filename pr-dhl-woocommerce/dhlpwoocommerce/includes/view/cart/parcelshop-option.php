<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<div class="dhlpwc-shipping-method-parcelshop-option"
    <?php echo !empty($postal_code) ? 'data-search-value="' . $postal_code . '"' : '' ?>
    <?php echo !empty($country_code) ? 'data-country-code="' . $country_code . '"' : '' ?>
>
    <?php if (!empty($parcelshop)) : ?>
        <span class="dhlpwc-parcelshop-option-message dhlpwc_notice"><?php echo $parcelshop->name ?></span><br/>
        <input type="button" class="dhlpwc-parcelshop-option-change" value="<?php _e('Change', 'dhlpwc') ?>"/>
    <?php else : ?>
        <span class="dhlpwc-parcelshop-option-message dhlpwc_warning"><?php _e('No location selected.', 'dhlpwc') ?></span>
        <input type="button" class="dhlpwc-parcelshop-option-change" value="<?php _e('Select', 'dhlpwc') ?>"/>
    <?php endif ?>
</div>
