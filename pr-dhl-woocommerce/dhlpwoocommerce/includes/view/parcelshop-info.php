<?php if (!defined('ABSPATH')) { exit; } ?>

<?php if (isset($warning)) : ?>
<div id="dhlpwc-parcelshop-info-message">
    <span class="dhlpwc_warning"><?php echo $warning; ?></span>
</div>
<?php endif ?>

<div id="dhlpwc-parcelshop-info-address">
    <?php if (!isset($compact) || !$compact) : ?>
        <strong><?php echo $name ?></strong><br/>
        <?php echo $address->street ?> <?php echo $address->number ?><br/>
        <?php echo $address->postal_code ?> <?php echo $address->city ?><br/>
        <?php echo $address->country_code ?><br/>
    <?php else : ?>
        <?php _e('DHL ServicePoint', 'dhlpwc'); ?>
        <strong><?php echo $name ?></strong><br/>
        <?php echo $address->street ?> <?php echo $address->number ?>,
        <?php echo $address->postal_code ?> <?php echo $address->city ?>, <?php echo $address->country_code ?>
    <?php endif ?>
</div>

<?php if (isset($times)) : ?>
<div id="dhlpwc-parcelshop-info-time-table">
    <table>
        <tbody>
        <?php foreach ($times as $time) : ?>
            <tr>
                <td class="dhlpwc-parcelshop-info-time-day"><?php echo $time['day'] ?></td>
                <td class="dhlpwc-parcelshop-info-time-period"><?php echo $time['period'] ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>