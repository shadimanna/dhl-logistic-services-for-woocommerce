<?php if (!defined('ABSPATH')) { exit; } ?>
<input class="dhlpwc-option-data" id="dhlpwc-metabox-address-hidden-input" type="hidden"/>
<label class="dhlpwc-metabox-adress-label"><?php _e('First Name', 'dhlpwc') ?></label>
<input type="text" class="dhlpwc-metabox-address-input" id="dhlpwc-metabox-address-first_name"
    <?php if (!empty($address) && !empty($address->first_name)) : ?>
        value="<?php echo esc_attr($address->first_name) ?>"
    <?php endif ?>
/>

<label class="dhlpwc-metabox-adress-label"><?php _e('Last Name', 'dhlpwc') ?></label>
<input type="text" class="dhlpwc-metabox-address-input" id="dhlpwc-metabox-address-last_name"
    <?php if (!empty($address) && !empty($address->last_name)) : ?>
        value="<?php echo esc_attr($address->last_name) ?>"
    <?php endif ?>
/>

<label class="dhlpwc-metabox-adress-label"><?php _e('Company', 'dhlpwc') ?></label> <div class="dhlpwc-required-field-star">*</div>
<input type="text" class="dhlpwc-metabox-address-input" id="dhlpwc-metabox-address-company"
    <?php if (!empty($address) && !empty($address->company)) : ?>
        value="<?php echo esc_attr($address->company) ?>"
    <?php endif ?>
/>

<label class="dhlpwc-metabox-adress-label"><?php _e('Postcode', 'dhlpwc') ?></label> <div class="dhlpwc-required-field-star">*</div>
<input type="text" class="dhlpwc-metabox-address-input" id="dhlpwc-metabox-address-postcode"
    <?php if (!empty($address) && !empty($address->postcode)) : ?>
        value="<?php echo esc_attr($address->postcode) ?>"
    <?php endif ?>
/>

<label class="dhlpwc-metabox-adress-label"><?php _e('City', 'dhlpwc') ?></label> <div class="dhlpwc-required-field-star">*</div>
<input type="text" class="dhlpwc-metabox-address-input" id="dhlpwc-metabox-address-city"
    <?php if (!empty($address) && !empty($address->city)) : ?>
        value="<?php echo esc_attr($address->city) ?>"
    <?php endif ?>
/>

<label class="dhlpwc-metabox-adress-label"><?php _e('Street', 'dhlpwc') ?></label> <div class="dhlpwc-required-field-star">*</div>
<input type="text" class="dhlpwc-metabox-address-input" id="dhlpwc-metabox-address-street"
    <?php if (!empty($address) && !empty($address->street)) : ?>
        value="<?php echo esc_attr($address->street) ?>"
    <?php endif ?>
/>

<label class="dhlpwc-metabox-adress-label"><?php _e('Number', 'dhlpwc') ?></label> <div class="dhlpwc-required-field-star">*</div>
<input type="text" class="dhlpwc-metabox-address-input" id="dhlpwc-metabox-address-number"
    <?php if (!empty($address) && !empty($address->number)) : ?>
        value="<?php echo esc_attr($address->number) ?>"
    <?php endif ?>
/>

<label class="dhlpwc-metabox-adress-label"><?php _e('Addition', 'dhlpwc') ?></label>
<input type="text" class="dhlpwc-metabox-address-input" id="dhlpwc-metabox-address-addition"
    <?php if (!empty($address) && !empty($address->addition)) : ?>
        value="<?php echo esc_attr($address->addition) ?>"
    <?php endif ?>
/>

<label class="dhlpwc-metabox-adress-label"><?php _e('Email', 'dhlpwc') ?></label>
<input type="text" class="dhlpwc-metabox-address-input" id="dhlpwc-metabox-address-email"
    <?php if (!empty($address) && !empty($address->email)) : ?>
        value="<?php echo esc_attr($address->email) ?>"
    <?php endif ?>
/>

<label class="dhlpwc-metabox-adress-label"><?php _e('Phone', 'dhlpwc') ?></label>
<input type="text" class="dhlpwc-metabox-address-input" id="dhlpwc-metabox-address-phone"
    <?php if (!empty($address) && !empty($address->phone)) : ?>
        value="<?php echo esc_attr($address->phone) ?>"
    <?php endif ?>
/>
