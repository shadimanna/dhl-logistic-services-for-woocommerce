<?php ?>
<p class="form-field <?php echo esc_attr($id) ?>_field">
    <label for="<?php echo esc_attr($id) ?>"></label>
    <select id="<?php echo esc_attr($id) ?>" name="<?php echo esc_attr($id) ?>[]" multiple="multiple">
        <?php foreach ($options as $key => $option) : ?>
            <option value="<?php echo esc_attr($key) ?>" <?php echo(in_array($key, $value) ? 'selected="selected"' : '') ?>><?php echo esc_html($option) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if (!empty($description)) : ?>
        <span class="description"><?php echo wp_kses_post($description) ?></span>
    <?php endif; ?>
</p>