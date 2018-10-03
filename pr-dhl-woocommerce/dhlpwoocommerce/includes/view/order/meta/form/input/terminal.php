<?php if (!defined('ABSPATH')) { exit; } ?>
<input class="dhlpwc-option-data" id="dhlpwc-metabox-terminal-hidden-input" type="hidden" value="<?php echo !empty($terminal) ? esc_attr($terminal->id) : '' ?>"/>
<div id="dhlpwc-metabox-terminal-preview-box">
    <div id="dhlpwc-metabox-terminal-preview-text">
        <?php if (!empty($terminal)) : ?>
            <?php echo esc_attr($terminal->name) ?>
        <?php else : ?>
            <?php _e('Select a terminal', 'dhlpwc') ?>
        <?php endif ?>
    </div>
    <button id="dhlpwc-metabox-terminal-display" type="button" class="button button-primary" value="<?php echo esc_attr(__('Change', 'dhlpwc')) ?>"><?php echo esc_attr(__('Change', 'dhlpwc')) ?></button>
    <div class="clear"></div>
    <div id="dhlpwc-metabox-terminal-preview-description">
        <?php if (!empty($terminal)) : ?>
            <?php echo esc_attr($terminal->address->street) ?> <?php echo esc_attr($terminal->address->number) ?>,
            <?php echo esc_attr($terminal->address->postal_code) ?>,
            <?php echo esc_attr($terminal->address->city) ?>
        <?php endif ?>
    </div>
    <div class="clear"></div>
</div>
<div id="dhlpwc-metabox-terminal-select-box" style="display:none;">
    <input id="dhlpwc-metabox-terminal-search-field" placeholder="Zoek op postcode, plaats of naam" type="text" />
    <div id="dhlpwc-metabox-terminal-select-list">
    </div>
    <div class="clear"></div>
</div>
