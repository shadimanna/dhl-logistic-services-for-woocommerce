<?php if (!defined('ABSPATH')) { exit; } ?>
<h3><?php echo __('Create a new label', 'dhlpwc') ?></h3>
<?php if (isset($to_business)) : ?>
    <?php echo $to_business ?><br/>
<?php endif ?>
<?php if (isset($options)) : ?>
    <?php echo $options ?><br/>
<?php endif ?>
<?php if (isset($sizes)) : ?>
    <small><?php echo __('Size and weight', 'dhlpwc') ?></small>
    <?php echo $sizes ?><br/>
<?php else : ?>
    <?php echo __("Can't load parcel types", 'dhlpwc') ?>
<?php endif ?>

<input type="hidden" name="my_ajax_nonce" value="<?php echo wp_create_nonce('my_ajax_action') ?>" />
<button id="dhlpwc-label-create" type="submit"><?php echo __('Create', 'dhlpwc'); ?></button>
