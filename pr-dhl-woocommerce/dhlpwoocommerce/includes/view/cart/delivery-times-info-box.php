<?php if (!defined('ABSPATH')) { exit; } ?>

<?php if (!isset($delivery_time)) {
    return;
}
?>

<div class="dhlpwc-time-window-info"
     data-value="<?php echo esc_attr($delivery_time->identifier) ?>"
     data-date="<?php echo esc_attr($delivery_time->source->delivery_date) ?>"
     data-start-time="<?php echo esc_attr($delivery_time->source->start_time) ?>"
     data-end-time="<?php echo esc_attr($delivery_time->source->end_time) ?>"
     data-frontend-id="<?php echo esc_attr($delivery_time->preset_frontend_id) ?>">
</div>
