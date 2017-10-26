<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="dhlpwc-order-metabox-content">
    <?php echo $content; ?>
    <?php if (isset($notices)) : ?>
        <?php foreach($notices as $notice) : ?>
            <p class="dhlpwc_notice"><?php echo $notice ?></p>
        <?php endforeach ?>
    <?php endif ?>
    <?php if (isset($warnings)) : ?>
        <?php foreach($warnings as $warning) : ?>
            <p class="dhlpwc_warning"><?php echo $warning ?></p>
        <?php endforeach ?>
    <?php endif ?>
    <?php if (isset($errors)) : ?>
        <?php foreach($errors as $error) : ?>
            <p class="dhlpwc_error"><?php echo $error ?></p>
        <?php endforeach ?>
    <?php endif ?>
    <div class="dhlpwc-meta-loader">
        <img src="<?php echo includes_url('js/thickbox/loadingAnimation.gif'); ?>"/>
    </div>
</div>