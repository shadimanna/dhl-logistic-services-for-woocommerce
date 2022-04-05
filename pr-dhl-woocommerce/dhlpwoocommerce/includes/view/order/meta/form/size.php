<?php if (!defined('ABSPATH')) { exit; } ?>
<input type="radio" class="dhlpwc-label-create-size" name="dhlpwc-label-create-size" value="<?php echo esc_attr($parceltype->key) ?>" /><strong><?php echo esc_html($description) ?></strong>
( <i><?php echo esc_html($parceltype->min_weight_kg) ?>-<?php echo esc_html($parceltype->max_weight_kg) ?> kg, <?php echo esc_html($parceltype->dimensions->max_length_cm) ?>x<?php echo esc_html($parceltype->dimensions->max_width_cm) ?>x<?php echo esc_html($parceltype->dimensions->max_height_cm) ?> cm</i> )<br/>
