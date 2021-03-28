<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\fpcExcludePostTableRowMarkup; ?>
<?php use function Servebolt\Optimizer\Helpers\fullPageCache; ?>
<?php
$nginxFpcActive = fullPageCache()->fpcIsActive();
$postTypesToCache  = fullPageCache()->getPostTypesToCache(false, false);
$availablePostTypes = fullPageCache()->getAvailablePostTypesToCache(true);
?>
<form method="post" action="options.php">
    <?php settings_fields( 'fpc-options-page' ) ?>
    <?php do_settings_sections( 'fpc-options-page' ) ?>
    <div class="nginx_switch">
        <table class="form-table" id="sb-nginx-fpc-form"<?php echo ( $nginxFpcActive ? '' : ' style="display: none;"' ); ?>>
            <tr>
                <th scope="row"><?php _e('HTML Cache', 'servebolt-wp'); ?></th>
                <td>
                    <input id="sb-nginx_cache_switch" name="servebolt_fpc_switch" type="checkbox"<?php echo $nginxFpcActive ? ' checked' : ''; ?>><label for="sb-nginx_cache_switch"><?php _e('Enabled', 'servebolt-wp'); ?></label>
                </td>
            </tr>
        </table>
    </div>
    <table class="form-table" id="sb-nginx-fpc-form"<?php echo ( $nginxFpcActive ? '' : ' style="display: none;"' ); ?>>
        <tr>
            <th scope="row">Cache post types</th>
            <td>
                <?php $allChecked = in_array('all', (array) $postTypesToCache); ?>
                <?php foreach ($availablePostTypes as $postType => $postTypeName) : ?>
                    <?php $checked = in_array($postType, (array) $postTypesToCache) ? ' checked' : ''; ?>
                    <span class="<?php if ( $allChecked && $postType !== 'all' ) echo ' disabled'; ?>"><input id="sb-cache_post_type_<?php echo $postType; ?>" class="servebolt_fpc_settings_item" name="servebolt_fpc_settings[<?php echo $postType; ?>]" value="1" type="checkbox"<?php echo $checked; ?>> <label for="sb-cache_post_type_<?php echo $postType; ?>"><?php echo $postTypeName; ?></label></span><br>
                <?php endforeach; ?>
                <p><?php _e('By default this plugin enables HTML caching of posts, pages and products.
                            Activate post types here if you want a different cache setup. If none of the post types above is checked the plugin will use default settings.
                            This will override the default setup.', 'servebolt-wp'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">Posts to exclude from caching</th>
            <td>
                <?php $idsToExclude = fullPageCache()->getIdsToExcludeFromCache() ?: []; ?>

                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <button type="button" class="button action sb-remove-selected-exclude-items" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
                    </div>
                    <div class="alignleft actions bulkactions">
                        <button type="button" style="float:left;" class="button action sb-flush-fpc-exclude-items"<?php if ( count($idsToExclude) === 0 ) echo ' disabled'; ?>><?php _e('Flush posts', 'servebolt-wp'); ?></button>
                    </div>
                    <div class="alignleft actions bulkactions">
                        <button class="button button-primary sb-add-exclude-post" type="button">Add post to exclude</button>
                    </div>
                    <span class="spinner flush-fpc-exlcude-list-loading-spinner"></span>
                    <br class="clear">
                </div>

                <table class="wp-list-table widefat striped" id="nginx-fpc-ids-to-exclude-table">

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
    </table>
    <?php submit_button(); ?>

</form>