<?php if (!defined('ABSPATH')) { exit; } ?>
<?php if (isset($tracking_codesets) && is_array($tracking_codesets) && !empty($tracking_codesets)) : ?>
    <span><?php echo esc_html($text) ?></span><br/>

    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;" border="1">

        <tbody>
        <?php foreach ($tracking_codesets as $tracking_codeset) : ?>
            <tr>
            <td class="td" style="text-align:<?php echo $text_align; ?>;">
                <a href="<?php echo esc_url($tracking_codeset['url']) ?>"><?php echo esc_attr($tracking_codeset['code']); ?></a>
            </td>
            </tr>
        <?php endforeach ?>
        </tbody>

    </table>
<?php endif ?>

