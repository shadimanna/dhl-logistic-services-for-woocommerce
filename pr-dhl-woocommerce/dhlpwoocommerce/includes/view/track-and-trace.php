<?php if (!defined('ABSPATH')) { exit; } ?>
<?php if (isset($tracking_code) && $tracking_code) : ?>
<div id="dhl-track-and-trace-component"
    data-tracking-code="<?php echo $tracking_code ?>"
    <?php if (isset($postcode) && $postcode) : ?>
        data-postcode="<?php echo $postcode ?>"
     <?php endif ?>
    data-locale="<?php echo $locale ?>">
</div>
<?php endif ?>
