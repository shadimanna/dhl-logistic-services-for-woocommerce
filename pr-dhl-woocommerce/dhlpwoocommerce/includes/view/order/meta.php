<?php if (!defined('ABSPATH')) { exit; } ?>
<h3><?php echo esc_html(__('Create a new label', 'dhlpwc')) ?></h3>
<?php if (isset($to_business)) : ?>
    <?php echo dhlpwc_esc_template($to_business) ?><br/>
<?php endif ?>
<?php if (isset($options)) : ?>
    <?php echo dhlpwc_esc_template($options) ?><br/>
<?php endif ?>
<?php if (isset($sizes)) : ?>
    <small><?php echo esc_html(__('Size and weight', 'dhlpwc')) ?></small>
    <?php echo dhlpwc_esc_template($sizes) ?><br/>
<?php else : ?>
    <?php echo esc_html(__("Can't load parcel types", 'dhlpwc')) ?>
<?php endif ?>

<input type="hidden" name="my_ajax_nonce" value="<?php echo wp_create_nonce('my_ajax_action') ?>" />
<button id="dhlpwc-label-create" type="submit"><?php echo esc_html(__('Create', 'dhlpwc')) ?></button>
