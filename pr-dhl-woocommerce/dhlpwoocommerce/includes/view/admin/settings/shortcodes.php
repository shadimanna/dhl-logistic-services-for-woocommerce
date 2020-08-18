<?php if (!defined('ABSPATH')) { exit; } ?>
<tr class="dhlpwc-debug-shortcodes">
    <th>
        <strong><?php _e('Shortcodes', 'dhlpwc') ?></strong>
    </th>
    <td>
        <p>
            <?php _e('The following shortcodes are available', 'dhlpwc') ?>:
        </p>

        <table class="striped">
            <thead>
                <tr>
                    <th><?php _e('Shortcode', 'dhlpwc') ?></th>
                    <th><?php _e('Optional attributes', 'dhlpwc') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <code>[dhlpwc-track-and-trace-links]</code>
                    </td>
                    <td>
                        <ul class="ul-disc">
                            <li>
                                <code>order_id</code> - <?php _e('defaults to', 'dlwpwc'); ?> <code><?php echo htmlspecialchars('get_the_ID()') ?></code>
                            </li>
                            <li>
                                <code>glue</code> - <?php _e('defaults to', 'dlwpwc'); ?> <code><?php echo htmlspecialchars('<br>') ?></code>
                            </li>
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>
