<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="dhlpwc-meta-to-business">
    <input
        type="checkbox"
        id="dhlpw-label-create-to-business"
        class="dhlpwc-label-create-option"
        name="dhlpwc-label-create-to-business"
        value="yes"
        <?php if (isset($checked) && !empty($checked)) : ?>
            checked="checked"
        <?php endif ?>
    />
    <label id="dhlpw-label-create-to-business-private" for="dhlpw-label-create-to-business"><?php _e('Private', 'dhlpwc') ?></label>
    <label id="dhlpw-label-create-to-business-business" for="dhlpw-label-create-to-business"><?php _e('Business', 'dhlpwc') ?></label>
</div>
