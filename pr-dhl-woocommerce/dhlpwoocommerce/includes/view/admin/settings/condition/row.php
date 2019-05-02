<?php if (!defined('ABSPATH')) { exit; } ?>
<tr class="dhlpwc-condition-rule">
    <td class="dhlpwc-condition-vellipsis dhlpwc-condition-rule-handle">&vellip;&vellip; <span class="dashicons dashicons-move"></span> &vellip;&vellip;</td>
    <td>
        <?php _e('If', 'dhlpwc') ?>
    </td>
    <td>
        <select class="dhlpwc-global-shipping-setting dhlpwc-condition-field dhlpwc-condition-input-type">
            <?php foreach ($input_types as $key => $input_type) : ?>
            <option value="<?php echo $key ?>"><?php echo $input_type ?></option>
            <?php endforeach ?>
        </select>
    </td>
    <td>
        <?php _e('exceeds', 'dhlpwc') ?>
    </td>
    <td>
        <input class="dhlpwc-global-shipping-setting dhlpwc-condition-field dhlpwc-condition-input-data"/>
    </td>
    <td>
        <?php _e('then', 'dhlpwc') ?>
    </td>
    <td>
        <select class="dhlpwc-global-shipping-setting dhlpwc-condition-field dhlpwc-condition-input-action">
            <?php foreach ($input_actions as $key => $input_action) : ?>
            <option value="<?php echo $key ?>"><?php echo $input_action ?></option>
            <?php endforeach ?>
        </select>
    </td>
    <td>
        <input class="dhlpwc-global-shipping-setting dhlpwc-condition-field dhlpwc-condition-input-action-data"/>
    </td>
    <td>
        <button class="dhlpwc-global-shipping-setting dhlpwc-condition-remove-button"><span class="dashicons dashicons-no-alt"></span></button>
    </td>
</tr>
