<?php if (!defined('ABSPATH')) { exit; } ?>

<div class="woocommerce-shipping-fields__field-wrapper">
    <?php $fields = $checkout->get_checkout_fields('dhlpwc_parcelshop'); ?>

    <?php foreach ($fields as $key => $field) : ?>
        <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
    <?php endforeach ?>
</div>
