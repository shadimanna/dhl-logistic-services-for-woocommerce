<?php if (!defined('ABSPATH')) { exit; } ?>
<a class="button tips dhlpwc_admin_action_button <?php echo esc_attr($action['action']) ?>"
   href="<?php echo esc_url($action['url']) ?>"
   data-tip="<?php echo esc_attr($action['name']) ?>"
   data-post-id="<?php echo esc_attr($post_id) ?>"
   <?php if (isset($external_link) && $external_link !== false) : ?>
       target="_blank"
   <?php else : ?>
       target="_self"
    <?php endif ?>
   <?php if (isset($label_id)) : ?>
        label-id="<?php echo esc_attr($label_id) ?>"
    <?php endif ?>>
    <?php echo esc_attr($action['name']) ?>
</a>
