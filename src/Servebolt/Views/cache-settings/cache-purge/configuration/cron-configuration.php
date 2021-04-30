<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\booleanToString; ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>

<tbody class="sb-config-field-general sb-config-field-cloudflare <?php if (!$cachePurgeIsActive) echo 'sb-config-field-hidden'; ?>">

    <tr>
        <th scope="row" colspan="100%" style="padding-bottom: 0;">
            <h3 style="margin-bottom: 0;"><?php _e('Queue based cache purge', 'servebolt-wp'); ?></h3>
            <p style="font-weight: normal;"><?php echo sprintf(__('Use this feature to trigger cache purge using a queue instead of doing it immediately. The queue system uses the WP Cron and will run every minute. We recommend that you set WordPress up to use the UNIX-based cron. Read about how to achieve this %shere%s.', 'servebolt-wp'), '<a href="https://servebo.lt/vkr8-" target="_">', '</a>'); ?></p>
        </th>
    </tr>
    <tr>
        <th scope="row"><?php _e('Use the queue system for cache purge?', 'servebolt-wp'); ?></th>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><span><?php _e('Cache purge via cron', 'servebolt-wp'); ?></span></legend>
                <label for="queue_based_cache_purge">
                    <input name="<?php if (!$queueBasedCachePurgeActiveStateIsOverridden) echo getOptionName('queue_based_cache_purge'); ?>" type="checkbox" id="queue_based_cache_purge" value="1"<?php if ($queueBasedCachePurgeActiveStateIsOverridden) echo ' disabled'; ?> <?php checked($queueBasedCachePurgeIsActive); ?>>
                    <?php if ($queueBasedCachePurgeActiveStateIsOverridden) : ?>
                        <input type="hidden" name="<?php echo getOptionName('queue_based_cache_purge'); ?>" value="<?php echo $queueBasedCachePurgeIsActive ? '1' : '0'; ?>">
                    <?php endif; ?>
                    <?php _e('Use the queue system when purging cache?', 'servebolt-wp'); ?>
                    <?php if ($queueBasedCachePurgeActiveStateIsOverridden) : ?>
                        <p><em>This value is overridden by the constant "SERVEBOLT_QUEUE_BASED_CACHE_PURGE" which is currently set to "<?php echo booleanToString($queueBasedCachePurgeIsActive); ?>".</em></p>
                    <?php endif; ?>
                </label>
            </fieldset>
        </td>
    </tr>
</tbody>
