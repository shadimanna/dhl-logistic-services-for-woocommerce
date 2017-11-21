<?php if (!defined('ABSPATH')) { exit; } ?>
<input
    type="checkbox"
    class="dhlpwc-label-create-option"
    name="dhlpwc-label-create-to-business"
    value="yes"
    <?php if (isset($checked) && !empty($checked)) : ?>
        checked="checked"
    <?php endif ?>
/>
<label><?php _e('To business', 'dhlpwc') ?></label><br/>
