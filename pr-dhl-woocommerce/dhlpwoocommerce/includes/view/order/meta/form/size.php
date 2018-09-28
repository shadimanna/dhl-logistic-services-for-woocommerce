<?php if (!defined('ABSPATH')) { exit; } ?>
<input type="radio" class="dhlpwc-label-create-size" name="dhlpwc-label-create-size" value="<?php echo $parceltype->key ?>" /><strong><?php echo $description ?></strong>
( <i><?php echo $parceltype->min_weight_kg ?>-<?php echo $parceltype->max_weight_kg ?> kg, <?php echo $parceltype->dimensions->max_length_cm ?>x<?php echo $parceltype->dimensions->max_width_cm ?>x<?php echo $parceltype->dimensions->max_height_cm ?> cm</i> )<br/>
