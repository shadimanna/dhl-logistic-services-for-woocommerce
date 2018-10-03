<?php if (!defined('ABSPATH')) { exit; } ?>
<hr/>
<div class="dhlpwc-metabox-terminal-location" data-terminal-id="<?php echo esc_attr($terminal->id) ?>">
    <strong><?php echo esc_attr($terminal->name) ?></strong><br/>
    <span>
        <?php echo esc_attr($terminal->address->street) ?> <?php echo esc_attr($terminal->address->number) ?>,
        <?php echo esc_attr($terminal->address->postal_code) ?>,
        <?php echo esc_attr($terminal->address->city) ?>
    </span>
</div>
