<tr class="no-items<?php if (count($extraSizes) > 0) echo ' hidden'; ?>"><td colspan="100%"><?php _e('No sizes', 'servebolt-wp'); ?></td></tr>
<?php foreach($extraSizes as $i => $size) : ?>
    <?php $size = $size['value'] . $size['descriptor']; ?>
    <tr class="exclude-item" id="size-<?php echo esc_attr($size); ?>">
        <th scope="row" class="check-column">
            <label class="screen-reader-text" for="cb-select-<?php echo esc_attr($size); ?>">Select "<?php echo esc_attr($size); ?>"</label>
            <input id="cb-select-<?php echo esc_attr($size); ?>" type="checkbox" value="<?php echo esc_attr($size); ?>">
        </th>
        <td class="column-image-size has-row-actions">
            <span class="row-value"><?php echo $size; ?></span>
            <div class="row-actions">
                <span class="trash"><a href="#" class="sb-remove-acd-image-size"><?php _e('Delete', 'servebolt-wp'); ?></a></span>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
