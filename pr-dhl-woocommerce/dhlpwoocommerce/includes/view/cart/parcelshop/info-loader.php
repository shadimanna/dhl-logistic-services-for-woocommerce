<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="dhlpwc-map-info-loader">
    <?php if (isset($image)) : ?>
        <img src="<?php echo $image; ?>"/>
    <?php else : ?>
        <img src="<?php echo includes_url('images/wpspin.gif'); ?>"/>
    <?php endif ?>
</div>