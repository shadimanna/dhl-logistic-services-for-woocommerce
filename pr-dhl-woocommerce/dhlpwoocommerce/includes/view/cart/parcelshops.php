<?php if (!defined('ABSPATH')) { exit; } ?>

<div class="woocommerce-shipping-fields__field-wrapper">
    <!-- The Modal -->
    <div class="dhlpwc-checkout-modal">

        <!-- Modal content -->
        <div class="dhlpwc-checkout-modal-content">
            <div class="dhlpwc-checkout-modal-close-wrapper">
                <img src="<?php echo $logo ?>" />
                <span class="dhlpwc-checkout-modal-close">&times;</span>
            </div>
            <?php $fields = $checkout->get_checkout_fields( 'dhlpwc_parcelshops' ); ?>

            <?php foreach ($fields as $key => $field) : ?>
                <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
            <?php endforeach ?>
            <div class="clear"></div>
        </div>
    </div>
</div>

