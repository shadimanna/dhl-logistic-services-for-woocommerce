<?php if (!defined('ABSPATH')) { exit; } ?>
<input class="dhlpwc-option-data" id="dhlpwc-metabox-parcelshop-hidden-input" type="hidden" value="<?php echo !empty($parcelshop) ? esc_attr($parcelshop->id) : '' ?>"/>
<div id="dhlpwc-metabox-parcelshop-preview-box">
    <div id="dhlpwc-metabox-parcelshop-preview-text">
        <?php if (!empty($parcelshop)) : ?>
            <?php echo esc_attr($parcelshop->name) ?>
        <?php else : ?>
            <?php _e('Select a DHL ServicePoint', 'dhlpwc') ?>
        <?php endif ?>
    </div>
    <button id="dhlpwc-metabox-parcelshop-display" type="button" class="button button-primary" value="<?php echo esc_attr(__('Change', 'dhlpwc')) ?>"><?php echo esc_attr(__('Change', 'dhlpwc')) ?></button>
    <div class="clear"></div>
    <div id="dhlpwc-metabox-parcelshop-preview-description">
        <?php if (!empty($parcelshop)) : ?>
            <?php echo esc_attr($parcelshop->address->street) ?> <?php echo esc_attr($parcelshop->address->number) ?>,
            <?php echo esc_attr($parcelshop->address->postal_code) ?>,
            <?php echo esc_attr($parcelshop->address->city) ?>
        <?php endif ?>
    </div>
    <div class="clear"></div>
</div>
<div id="dhlpwc-metabox-parcelshop-select-box" style="display:none;">
    <input id="dhlpwc-metabox-parcelshop-search-field"
           placeholder="<?php _e('Search with postal code or city', 'dhlpwc') ?>" type="text"/>
    <div id="dhlpwc-metabox-parcelshop-select-list">
    </div>
    <div class="clear"></div>
</div>
