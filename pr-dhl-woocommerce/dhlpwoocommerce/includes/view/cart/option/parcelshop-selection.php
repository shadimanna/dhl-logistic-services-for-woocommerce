<?php if (!defined('ABSPATH')) { exit; } ?>
<?php foreach ($fields as $key => $field) : ?>
    <?php woocommerce_form_field($key, $field, WC_Checkout::instance()->get_value($key)); ?>
<?php endforeach ?>

