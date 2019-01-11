<?php if (!defined('ABSPATH')) { exit; } ?>
<input
        class="dhlpwc-option-data"
        type="text"
        placeholder="<?php echo esc_attr($placeholder) ?>"
        <?php if (!empty($value)) : ?>
            value="<?php echo esc_attr($value) ?>"
        <?php endif ?>
/>
