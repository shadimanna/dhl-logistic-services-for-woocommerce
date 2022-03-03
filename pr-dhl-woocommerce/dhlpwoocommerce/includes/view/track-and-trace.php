<?php if (!defined('ABSPATH')) { exit; } ?>
<?php if (isset($tracking_code) && $tracking_code) : ?>
<div id="dhl-track-and-trace-component"
    data-tracking-code="<?php echo esc_attr($tracking_code) ?>"
    <?php if (isset($postcode) && $postcode) : ?>
        data-postcode="<?php echo esc_attr($postcode) ?>"
     <?php endif ?>
    data-locale="<?php echo esc_attr($locale) ?>">
</div>
<?php endif ?>
