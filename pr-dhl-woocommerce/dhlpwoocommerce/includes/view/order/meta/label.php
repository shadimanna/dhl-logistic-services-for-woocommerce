<?php if (!defined('ABSPATH')) { exit; } ?>
<b><?php echo esc_attr($label_description) ?></b> - <?php echo esc_attr($tracker_code) ?><br/>
<?php if (isset($actions)) : ?>
    <?php echo $actions ?>
<?php endif ?>

