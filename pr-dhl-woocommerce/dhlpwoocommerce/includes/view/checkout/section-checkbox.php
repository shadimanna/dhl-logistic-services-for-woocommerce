<?php if (!defined('ABSPATH')) { exit; } ?>
<h3 id="dhlpwc-ship-to-parcelshop-address">
    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox <?php echo implode(' ', $label_class) ?>" <?php echo implode(' ', $custom_attributes) ?>>
        <input id="<?php echo esc_attr($id) ?>"
               class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox <?php echo esc_attr(implode(' ', $input_class)) ?>"
               name="<?php echo esc_attr($name) ?>"
               value="1" <?php echo checked($value, 1, false) ?>
               type="<?php echo esc_attr($type) ?>" />
        <span><?php echo $label ?></span>
    </label>
</h3>