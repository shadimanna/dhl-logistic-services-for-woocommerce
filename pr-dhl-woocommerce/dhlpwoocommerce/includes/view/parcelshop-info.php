<?php if (!defined('ABSPATH')) { exit; } ?>

<?php if (isset($warning)) : ?>
<div id="dhlpwc-parcelshop-info-message">
    <span class="dhlpwc_warning"><?php echo esc_html($warning) ?></span>
</div>
<?php endif ?>

<div id="dhlpwc-parcelshop-info-address">
    <?php if (!isset($compact) || !$compact) : ?>
        <strong><?php echo esc_html($name) ?></strong><br/>
        <?php echo esc_html($address->street) ?> <?php echo esc_html($address->number) ?><br/>
        <?php echo esc_html($address->postal_code) ?> <?php echo esc_html($address->city) ?><br/>
        <?php echo esc_html($address->country_code) ?><br/>
    <?php else : ?>
        <?php _e('DHL ServicePoint', 'dhlpwc'); ?>
        <strong><?php echo esc_html($name) ?></strong><br/>
        <?php echo esc_html($address->street) ?> <?php echo esc_html($address->number) ?>,
        <?php echo esc_html($address->postal_code) ?> <?php echo esc_html($address->city) ?>, <?php echo esc_html($address->country_code) ?>
    <?php endif ?>
</div>

<?php if (isset($times)) : ?>
<div id="dhlpwc-parcelshop-info-time-table">
    <table>
        <tbody>
        <?php foreach ($times as $time) : ?>
            <tr>
                <td class="dhlpwc-parcelshop-info-time-day"><?php echo esc_html($time['day']) ?></td>
                <td class="dhlpwc-parcelshop-info-time-period"><?php echo esc_html($time['period']) ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>