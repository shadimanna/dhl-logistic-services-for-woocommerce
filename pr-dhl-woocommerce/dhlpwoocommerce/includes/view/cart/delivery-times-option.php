<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="dhlpwc-shipping-method-delivery-times-option"
    <?php echo !empty($postal_code) ? 'data-postal-code-value="' . $postal_code . '"' : '' ?>
    <?php echo !empty($country_code) ? 'data-country-code="' . $country_code . '"' : '' ?>
>

    <?php if (!empty($delivery_times)) : ?>

    <select>

        <?php foreach($delivery_times as $delivery_time) : ?>
            <?php /** @var DHLPWC_Model_Data_Delivery_Time $delivery_time **/ ?>
            <option value="<?php echo esc_attr($delivery_time->identifier) ?>"
                data-date="<?php echo esc_attr($delivery_time->source->delivery_date) ?>"
                data-start-time="<?php echo esc_attr($delivery_time->source->start_time) ?>"
                data-end-time="<?php echo esc_attr($delivery_time->source->end_time) ?>"
                data-frontend-id="<?php echo esc_attr($delivery_time->preset_frontend_id) ?>"
                <?php if ($delivery_time->selected) : ?>
                    selected="selected"
                <?php endif ?>
            >
                <?php echo esc_attr($delivery_time->date) ?> (<?php echo esc_attr($delivery_time->start_time) ?> - <?php echo esc_attr($delivery_time->end_time) ?>)
            </option>
        <?php endforeach ?>
    </select>

    <?php endif ?>

</div>
