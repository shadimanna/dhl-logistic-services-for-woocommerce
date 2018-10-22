<?php if (!defined('ABSPATH')) { exit; } ?>

<div class="dhlpwc-delivery-times-container">
    <div class="dhlpwc-delivery-times-shipping-day">
        <div class="dhlpwc-delivery-times-shipping-day-content <?php echo esc_attr($shipping_advice_class)?>">
            <?php echo wpautop(esc_attr($shipping_advice)) ?>
        </div>
    </div>
    <div class="dhlpwc-delivery-times-receiving-day">
        <div class="dhlpwc-delivery-times-receiving-day-content">
            <?php if (!empty($time_left)) : ?>
                <?php echo sprintf(__('Expected in %s', 'dhlpwc'), '<b>'.esc_attr($time_left).'</b>') ?><br/>
            <?php else : ?>
                <b><?php echo __('Selected date has passed', 'dhlpwc') ?></b><br/>
            <?php endif ?>
            <i>- <?php echo esc_attr($delivery_time->date) ?> <?php _e('from', 'dhlpwc') ?> <?php echo esc_attr($delivery_time->start_time) ?> <?php _e('to', 'dhlpwc') ?> <?php echo esc_attr($delivery_time->end_time) ?></i>
        </div>
    </div>
</div>
