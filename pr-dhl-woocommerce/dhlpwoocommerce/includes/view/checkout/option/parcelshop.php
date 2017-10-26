<?php if (!defined('ABSPATH')) { exit; } ?>
<div>
    <?php if (isset($parcelshop)) : ?>
        <span class="dhlpwc_notice"><?php echo $parcelshop->name ?></span>
    <?php else : ?>
        <span class="dhlpwc_warning">No location selected.</span>
    <?php endif ?>
</div>
