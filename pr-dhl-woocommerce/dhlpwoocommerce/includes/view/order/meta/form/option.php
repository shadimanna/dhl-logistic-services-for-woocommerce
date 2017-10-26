<?php if (!defined('ABSPATH')) { exit; } ?>
<input
    type="checkbox"
    class="dhlpwc-label-create-option"
    name="dhlpwc-label-create-option[]"
    value="<?php echo $option->key ?>"
    <?php if (isset($checked)) : ?>
    checked="checked"
    <?php endif ?>
/>
<label><?php echo $option->key ?></label><br/>
