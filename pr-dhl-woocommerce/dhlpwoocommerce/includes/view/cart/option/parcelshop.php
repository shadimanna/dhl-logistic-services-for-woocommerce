<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="dhlpwc-shipping-method-parcelshop-option">
    <?php if (isset($parcelshop)) : ?>
        <span class="dhlpwc-parcelshop-option-message dhlpwc_notice"><?php echo $parcelshop->name ?></span><br/>
        <input type="button" class="dhlpwc-parcelshop-option-change" value="<?php _e('Change', 'dhlpwc') ?>"/>
    <?php else : ?>
        <span class="dhlpwc-parcelshop-option-message dhlpwc_warning"><?php _e('No location selected.', 'dhlpwc') ?></span>
        <input type="button" class="dhlpwc-parcelshop-option-change" value="<?php _e('Select', 'dhlpwc') ?>"/>
    <?php endif ?>

    <div class="dhlpwc-parcelshop-option-country-select-container">
    <?php
    woocommerce_form_field('dhlpwc-parcelshop-option-country-select', array(
            'type'    => 'select',
            'default' => $country,
            'options' => $countries,
        )
    );
    ?>
    </div>

</div>
