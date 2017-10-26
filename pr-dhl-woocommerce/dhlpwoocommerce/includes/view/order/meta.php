<?php if (!defined('ABSPATH')) { exit; } ?>
<h3><?php echo __('Create a new label', 'dhlpwc') ?></h3>
<?php if (isset($options)) : ?>
    <strong><?php echo __('Shipment options', 'dhlpwc') ?></strong><br/>
    <small><?php echo __('Customer preferences are automatically checked', 'dhlpwc') ?></small><br/>
    <?php echo $options ?><br/>
<?php endif ?>
<?php if (isset($parcel_types)) : ?>
    <strong><?php echo __('Size', 'dhlpwc') ?></strong><br/>
    <small><?php echo __('Create a label for a specific package size', 'dhlpwc') ?></small>
    <?php echo $parcel_types ?><br/>
<?php else : ?>
    <?php echo __("Can't load parcel types", 'dhlpwc') ?>
<?php endif ?>

<input type="hidden" name="my_ajax_nonce" value="<?php echo wp_create_nonce('my_ajax_action') ?>" />
<button id="dhlpwc-label-create" type="submit"><?php echo __('Create', 'dhlpwc'); ?></button>
