<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php use function Servebolt\Optimizer\Helpers\arrayGet; ?>

<?php settings_errors(); ?>

<?php if (AcceleratedDomains::isActive()): ?>

<form method="post" autocomplete="off" action="options.php">
    <?php settings_fields('sb-cache-ttl-options-page'); ?>
    <?php do_settings_sections('sb-cache-ttl-options-page'); ?>

    <br>

    <fieldset>
        <legend class="screen-reader-text"><span><?php _e('Custom cache TTL-feature active?', 'servebolt-wp'); ?></span></legend>
        <label for="custom_cache_ttl_switch">
            <input name="<?php echo getOptionName('custom_cache_ttl_switch'); ?>" type="checkbox" class="options-field-switch" id="custom_cache_ttl_switch" value="1" <?php checked($settings['custom_cache_ttl_switch']); ?>>
            <?php _e('Enable custom cache TTL', 'servebolt-wp'); ?>
        </label><br><br>
    </fieldset>

    <table class="wp-list-table widefat striped" id="options-fields"<?php if (!$settings['custom_cache_ttl_switch']) echo ' style="display: none;"'; ?>>
        <thead>
            <tr>
                <th><?php _e('Post type', 'servebolt-wp'); ?></th>
                <th><?php _e('TTL', 'servebolt-wp'); ?></th>
            </tr>
        </thead>

        <?php foreach ($postTypes as $postType): ?>
        <?php $currentTtlPreset = arrayGet($postType->name, arrayGet('cache_ttl_by_post_type', $settings)); ?>
        <?php $customTtl = arrayGet($postType->name, arrayGet('custom_cache_ttl_by_post_type', $settings)); ?>
            <tr>
                <td><?php echo $postType->label; ?></td>
                <td>
                    <select class="sb-post-type-ttl-selector" name="<?php echo getOptionName('cache_ttl_by_post_type[' . $postType->name . ']'); ?>">
                        <?php foreach($cacheTtlOptions as $key => $value): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($key == $currentTtlPreset); ?>><?php echo $value['label'] . (isset($value['ttl']) ? ' (' . $value['ttl'] . ' seconds)' : '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" min="0" <?php if ($currentTtlPreset !== 'custom') echo 'style="display: none;"'; ?> placeholder="<?php _e('Seconds', 'servebolt-wp'); ?>" value="<?php echo esc_attr($customTtl); ?>" name="<?php echo getOptionName('custom_cache_ttl_by_post_type[' . $postType->name . ']'); ?>">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th><?php _e('Post type', 'servebolt-wp'); ?></th>
                <th><?php _e('TTL', 'servebolt-wp'); ?></th>
            </tr>
        </tfoot>
    </table>

    <br>

    <table class="wp-list-table widefat striped" id="options-fields"<?php if (!$settings['custom_cache_ttl_switch']) echo ' style="display: none;"'; ?>>
        <thead>
        <tr>
            <th><?php _e('Taxonomy Archives', 'servebolt-wp'); ?></th>
            <th><?php _e('TTL', 'servebolt-wp'); ?></th>
        </tr>
        </thead>

        <?php foreach ($taxonomies as $taxonomy): ?>
            <?php $currentTtlPreset = arrayGet($taxonomy->name, arrayGet('cache_ttl_by_taxonomy', $settings)); ?>
            <?php $customTtl = arrayGet($taxonomy->name, arrayGet('custom_cache_ttl_by_taxonomy', $settings)); ?>
            <tr>
                <td><?php echo $taxonomy->label; ?></td>
                <td>
                    <select class="sb-post-type-ttl-selector" name="<?php echo getOptionName('cache_ttl_by_taxonomy[' . $taxonomy->name . ']'); ?>">
                        <?php foreach($cacheTtlOptions as $key => $value): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($key == $currentTtlPreset); ?>><?php echo $value['label'] . (isset($value['ttl']) ? ' (' . $value['ttl'] . ' seconds)' : '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" min="0" <?php if ($currentTtlPreset !== 'custom') echo 'style="display: none;"'; ?> placeholder="<?php _e('Seconds', 'servebolt-wp'); ?>" value="<?php echo esc_attr($customTtl); ?>" name="<?php echo getOptionName('custom_cache_ttl_by_taxonomy[' . $taxonomy->name . ']'); ?>">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <th><?php _e('Taxonomy Archives', 'servebolt-wp'); ?></th>
            <th><?php _e('TTL', 'servebolt-wp'); ?></th>
        </tr>
        </tfoot>
    </table>

    <?php submit_button(); ?>

</form>

<?php else: ?>

<div class="notice inline notice-warning">
    <p><a href="<?php echo admin_url('admin.php?page=servebolt-acd'); ?>">Accelerated Domains</a> needs to be activated for this feature to work.</p>
</div>

<?php endif; ?>
