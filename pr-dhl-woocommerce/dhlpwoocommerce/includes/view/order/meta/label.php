<?php if (!defined('ABSPATH')) { exit; } ?>
<?php if (!empty($url)) : ?>
    <a href="<?php echo esc_url($url) ?>"
        <?php if (isset($external_link)) : ?>
            target="_blank"
        <?php endif ?>
    >
<?php endif ?>

    <b><?php echo esc_attr($label_description) ?></b> -
    <?php if (empty($is_return)) : ?>
        <?php echo esc_attr($tracker_code) ?>
    <?php else : ?>
        <?php _e('Return label', 'dhlpwc') ?>
    <?php endif ?>

<?php if (!empty($url)) : ?>
    </a>
<?php endif ?>
<br/>
<?php if (isset($actions)) : ?>
    <?php echo $actions ?>
<?php endif ?>
