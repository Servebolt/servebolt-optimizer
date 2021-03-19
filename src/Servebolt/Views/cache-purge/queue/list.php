<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<tr class="sb-config-field-hidden feature_cf_cron_purge sb-toggle-active-cron-item<?php if ( ! sb_cf_cache()->cron_purge_is_active() ) echo ' cf-hidden-cron'; ?>">
    <th scope="row" colspan="2" style="padding-bottom: 5px;">
        <label for="items_to_purge"><?php _e('Cache purge queue'); ?></label>
        <p style="font-weight: normal;"><?php echo sprintf(__('The list below contains all the posts/URLs that are scheduled for cache purge. The max number of items in the list is %s, the rest will be unavailable for display. The most recently added item can be seen at the bottom of the list.%s Note: If you have more than %s items in the list then that would indicate that there is something wrong with the cron-setup. If so please investigate and/or contact support.'), $max_number_of_cache_purge_queue_items, '<br>', $max_number_of_cache_purge_queue_items); ?></p>
    </th>
</tr>
<tr class="sb-toggle-active-cron-item<?php if ( ! sb_cf_cache()->cron_purge_is_active() ) echo ' cf-hidden-cron'; ?>">
    <td colspan="2" style="padding-top:0;padding-left:0;">

        <?php $items_to_purge = sb_cf_cache()->get_items_to_purge($max_number_of_cache_purge_queue_items); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <button type="button" class="button action remove-selected-purge-items" disabled><?php _e('Remove selected'); ?></button>
            </div>
            <div class="alignleft actions bulkactions">
                <button type="button" style="float:left;" class="button action flush-purge-items-queue"<?php if ( count($items_to_purge) === 0 ) echo ' disabled'; ?>><?php _e('Flush queue'); ?></button>
            </div>
            <div class="alignleft actions bulkactions">
                <button type="button" style="float:left;" class="button action refresh-purge-items-queue"><?php _e('Refresh queue'); ?></button>
            </div>

            <span class="spinner purge-queue-loading-spinner"></span>
            <br class="clear">
        </div>

        <table class="wp-list-table widefat striped" id="purge-items-table">
            <?php Helpers\view('cache-purge.queue.list-header-columns'); ?>
            <?php sb_cf_cache_admin_controls()->purge_queue_list($items_to_purge); ?>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <button type="button" id="doaction" class="button action remove-selected-purge-items" disabled><?php _e('Remove selected'); ?></button>
            </div>
        </div>

    </td>
</tr>
