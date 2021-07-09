<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php use function Servebolt\Optimizer\Helpers\arrayGet; ?>

<?php settings_errors(); ?>

<form method="post" autocomplete="off" action="options.php">
    <?php settings_fields('sb-cache-ttl-options-page'); ?>
    <?php do_settings_sections('sb-cache-ttl-options-page'); ?>

    <br>

    <pre><?php print_r($settings); ?></pre>

    <table class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th><?php _e('Post type', 'servebolt-wp'); ?></th>
                <th><?php _e('TTL', 'servebolt-wp'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (get_post_types([], 'objects') as $postType): ?>
        <?php $currentTtl = arrayGet($postType->name, arrayGet('cache_ttl_preset', $settings)); ?>
        <?php $customTtl = arrayGet($postType->name, arrayGet('custom_cache_ttl', $settings)); ?>
            <tr>
                <td><?php echo $postType->label; ?></td>
                <td>
                    <select class="sb-post-type-ttl-selector" name="<?php echo getOptionName('cache_ttl_preset[' . $postType->name . ']'); ?>">
                        <?php foreach($cacheTtlOptions as $key => $value): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($value == $currentTtl); ?>><?php echo $key . (is_numeric($value) ? ' (' . $value . ' seconds)' : '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" min="0" <?php if ($currentTtl !== 'custom') echo 'style="display: none;"'; ?> placeholder="<?php _e('Must be a number', 'servebolt-wp'); ?>" value="<?php echo esc_attr($customTtl); ?>" name="<?php echo getOptionName('custom_cache_ttl[' . $postType->name . ']'); ?>">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php submit_button(); ?>

</form>
