<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\fpcExcludePostTableRowMarkup; ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php use Servebolt\Optimizer\FullPageCache\FullPageCacheHeaders; ?>
<?php use Servebolt\Optimizer\FullPageCache\CachePostExclusion; ?>
<?php use Servebolt\Optimizer\FullPageCache\FullPageCacheSettings; ?>
<?php
$fpcActive = FullPageCacheSettings::fpcIsActive();
$fpcActiveOverridden = FullPageCacheSettings::fpcActiveStateIsOverridden();
$postTypesToCache  = FullPageCacheHeaders::getPostTypesToCache(false, false);
$availablePostTypes = FullPageCacheHeaders::getAvailablePostTypesToCache(true);
?>
<form method="post" action="options.php">
    <?php settings_fields( 'fpc-options-page' ) ?>
    <?php do_settings_sections( 'fpc-options-page' ) ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php _e('HTML Cache', 'servebolt-wp'); ?></th>
                <td>
                    <input id="sb-fpc_switch" name="<?php echo getOptionName('fpc_switch')?>"<?php if ($fpcActiveOverridden) echo ' disabled'; ?> type="checkbox"<?php if ($fpcActive) echo ' checked'; ?>><label for="sb-fpc_switch"><?php _e('Enabled', 'servebolt-wp'); ?></label>
                    <?php if ($fpcActiveOverridden): ?>
                    <p class="description">HTML cache is automatically enabled when Accelerated Domain-feature is active.</p>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
        <tbody id="sb-fpc-form"<?php echo ( $fpcActive ? '' : ' style="display: none;"' ); ?>>
            <tr>
                <th scope="row">Cache post types</th>
                <td>
                    <?php $allChecked = in_array('all', (array) $postTypesToCache); ?>
                    <?php foreach ($availablePostTypes as $postType => $postTypeName) : ?>
                        <?php $checked = in_array($postType, (array) $postTypesToCache) ? ' checked' : ''; ?>
                        <span class="<?php if ( $allChecked && $postType !== 'all' ) echo ' disabled'; ?>"><input id="sb-cache_post_type_<?php echo $postType; ?>" class="servebolt_fpc_settings_item" name="<?php echo getOptionName('fpc_settings')?>[<?php echo $postType; ?>]" value="1" type="checkbox"<?php echo $checked; ?>> <label for="sb-cache_post_type_<?php echo $postType; ?>"><?php echo $postTypeName; ?></label></span><br>
                    <?php endforeach; ?>
                    <p><?php _e('By default this plugin enables HTML caching of posts, pages and products.
                                Activate post types here if you want a different cache setup. If none of the post types above is checked the plugin will use default settings.
                                This will override the default setup.', 'servebolt-wp'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">Posts to exclude from caching</th>
                <td>
                    <?php $idsToExclude = CachePostExclusion::getIdsToExcludeFromCache() ?: []; ?>

                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <button type="button" class="button action sb-remove-selected-exclude-items" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
                        </div>
                        <div class="alignleft actions bulkactions">
                            <button type="button" style="float:left;" class="button action sb-flush-fpc-exclude-items"<?php if ( count($idsToExclude) === 0 ) echo ' disabled'; ?>><?php _e('Flush posts', 'servebolt-wp'); ?></button>
                        </div>
                        <div class="alignleft actions bulkactions">
                            <button type="button" class="button button-primary sb-add-exclude-post">Add post to exclude</button>
                        </div>
                        <span class="spinner flush-fpc-exclude-list-loading-spinner"></span>
                        <br class="clear">
                    </div>

                    <table class="wp-list-table widefat striped" id="fpc-ids-to-exclude-table">

                        <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'servebolt-wp'); ?></label><input id="cb-select-all-1" type="checkbox"></td>
                            <th scope="col" id="post_id" class="manage-column column-post-id"><?php _e('Post ID', 'servebolt-wp'); ?></th>
                            <th scope="col" id="post_id" class="manage-column column-post-id"><?php _e('Post title', 'servebolt-wp'); ?></th>
                            <th scope="col" id="url" class="manage-column column-url"><?php _e('URL', 'servebolt-wp'); ?></th>
                        </tr>
                        </thead>

                        <tfoot>
                        <tr>
                            <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2"><?php _e('Select All', 'servebolt-wp'); ?></label><input id="cb-select-all-2" type="checkbox"></td>
                            <th scope="col" class="manage-column column-title column-primary"><?php _e('Post ID', 'servebolt-wp'); ?></th>
                            <th scope="col" class="manage-column column-title column-primary"><?php _e('Post title', 'servebolt-wp'); ?></th>
                            <th scope="col" class="manage-column column-author"><?php _e('URL', 'servebolt-wp'); ?></th>
                        </tr>
                        </tfoot>

                        <tbody id="the-list">
                        <tr class="no-items<?php if ( count($idsToExclude) > 0 ) echo ' hidden'; ?>"><td colspan="100%"><?php _e('No posts set to be excluded', 'servebolt-wp'); ?></td></tr>
                        <?php foreach($idsToExclude as $i => $postId) : ?>
                            <?php fpcExcludePostTableRowMarkup($postId); ?>
                        <?php endforeach; ?>
                        </tbody>

                    </table>

                    <div class="tablenav bottom">
                        <div class="alignleft actions bulkactions">
                            <button type="button" id="doaction" class="button action sb-remove-selected-exclude-items" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
                        </div>
                    </div>

                </td>
            </tr>
        </tbody>
    </table>
    <?php submit_button(); ?>

</form>
