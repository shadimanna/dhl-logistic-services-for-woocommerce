<?php if (!defined('ABSPATH')) { exit; } ?>
<?php if (!empty($delivery_options)) : ?>
    <h3><?php _e('Delivery method', 'dhlpwc') ?></h3>

    <div class="dhlpwc-order-metabox-form-deliverymethods">
        <div class="clear"></div>
        <?php foreach($delivery_options as $option) : ?>
            <div class="dhlpwc-label-create-delivery-option-container" data-key="<?php echo $option->key ?>">

                <input class="dhlpwc-label-create-delivery-option" name="dhlpwc-label-create-delivery-option[]" id="dhlpwc-label-delivery-option-<?php echo $option->key ?>" value="<?php echo $option->key ?>" type="radio" data-exclusions="<?php echo esc_attr(json_encode($option->exclusion_list)) ?>"
                    <?php if ($option->preselected === true) : ?>
                        checked="checked"
                    <?php endif ?>
                />
                <label class="dhlpwc-order-metabox-form-deliverymethod" for="dhlpwc-label-delivery-option-<?php echo $option->key ?>">
                    <div class="dhlpwc-order-metabox-form-deliverymethod-icon">
                        <img src="<?php echo esc_url($option->image_url) ?>">
                    </div>
                    <div class="dhlpwc-label-create-delivery-option-description">
                    <?php _e($option->description, 'dhlpwc') ?>
                    </div>
                </label>

            </div>
        <?php endforeach ?>
        <div class="clear"></div>

        <?php foreach($delivery_options as $option) : ?>
            <?php if (!empty($option->input_template)): ?>
            <div style="display:none" class="dhlpwc-metabox-delivery-input" data-option-input="<?php echo $option->key ?>">
                <?php echo $option->input_template ?>
            </div>
            <?php endif ?>
        <?php endforeach ?>

    </div>
<?php endif ?>
<hr/>
<?php if (!empty($service_options)) : ?>
    <h3><?php _e('Extra services', 'dhlpwc') ?></h3>

    <div class="dhlpwc-order-metabox-form-services">
    <?php foreach($service_options as $option) : ?>
        <div class="dhlpwc-label-create-service-option-container" data-key="<?php echo $option->key ?>">

            <input id="dhlpwc-label-option-id-<?php echo $option->key ?>" class="dhlpwc-label-create-option" name="dhlpwc-label-create-option[]" value="<?php echo $option->key ?>" type="checkbox" data-exclusions="<?php echo esc_attr(json_encode($option->exclusion_list)) ?>"
                <?php if ($option->preselected === true) : ?>
                    checked="checked"
                <?php endif ?>
            />
            <label for="dhlpwc-label-option-id-<?php echo $option->key ?>">
                <div class="dhlpwc-order-metabox-form-delivery-option-icon">
                    <img src="<?php echo esc_url($option->image_url) ?>">
                </div>
                <?php _e($option->description, 'dhlpwc') ?>
            </label>
            <?php if (!empty($option->input_template)): ?>
            <div style="display:none" class="dhlpwc-metabox-service-input" data-option-input="<?php echo $option->key ?>">
                <?php echo $option->input_template ?>
            </div>
            <?php endif ?>
            <hr/>

        </div>
    <?php endforeach ?>
    </div>
<?php endif ?>
