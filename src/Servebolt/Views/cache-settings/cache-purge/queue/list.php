<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>

<tr class="sb-config-field-hidden feature_cf_cron_purge sb-toggle-active-cron-item<?php if ( ! sb_cf_cache()->cron_purge_is_active() ) echo ' cf-hidden-cron'; ?>">
    <th scope="row" colspan="2" style="padding-bottom: 5px;">
        <label for="items_to_purge"><?php _e('Cache Purge Queue', 'servebolt-wp'); ?></label>
        <p style="font-weight: normal;"><?php echo sprintf(__('The list below contains all the posts/URLs that are scheduled for cache purge. The max number of items in the list is %s, the rest will be unavailable for display. The most recently added item can be seen at the bottom of the list.%s Note: If you have more than %s items in the list then that would indicate that there is something wrong with the cron-setup. If so please investigate and/or contact support.', 'servebolt-wp'), $maxNumberOfCachePurgeQueueItems, '<br>', $maxNumberOfCachePurgeQueueItems); ?></p>
    </th>
</tr>
<tr class="sb-toggle-active-cron-item<?php if ( ! sb_cf_cache()->cronPurgeIsActive() ) echo ' cf-hidden-cron'; ?>">
    <td colspan="2" style="padding-top:0;padding-left:0;">

        <?php //$itemsToPurge = sb_cf_cache()->getItemsToPurge($maxNumberOfCachePurgeQueueItems); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <button type="button" class="button action remove-selected-purge-items" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
            </div>
            <div class="alignleft actions bulkactions">
                <button type="button" style="float:left;" class="button action flush-purge-items-queue"<?php if (count($itemsToPurge) === 0) echo ' disabled'; ?>><?php _e('Flush queue', 'servebolt-wp'); ?></button>
            </div>
            <div class="alignleft actions bulkactions">
                <button type="button" style="float:left;" class="button action refresh-purge-items-queue"><?php _e('Refresh queue', 'servebolt-wp'); ?></button>
            </div>

            <span class="spinner purge-queue-loading-spinner"></span>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat striped" id="purge-items-table">
            <?php Helpers\view('cache-settings.cache-purge.queue.list-header-columns'); ?>
            <?php //sb_cf_cache_admin_controls()->purge_queue_list($itemsToPurge); ?>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <button type="button" id="doaction" class="button action remove-selected-purge-items" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
            </div>
        </div>

    </td>
</tr>
