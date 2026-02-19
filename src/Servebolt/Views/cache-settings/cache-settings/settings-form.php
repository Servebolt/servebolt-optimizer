<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\htmlCacheExcludePostTableRowMarkup; ?>
<?php use function Servebolt\Optimizer\Helpers\getOptionName; ?>
<?php use Servebolt\Optimizer\FullPageCache\FullPageCacheHeaders; ?>
<?php use Servebolt\Optimizer\FullPageCache\CachePostExclusion; ?>
<?php use Servebolt\Optimizer\FullPageCache\FullPageCacheSettings; ?>
<?php use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains; ?>
<?php use Servebolt\Optimizer\CachePurge\CachePurge; ?>
<?php use Servebolt\Optimizer\AcceleratedDomains\VaryHeadersConfig; ?>
<?php
$htmlCacheActive = FullPageCacheSettings::htmlCacheIsActive();
$htmlCacheActiveOverridden = FullPageCacheSettings::htmlCacheActiveStateIsOverridden();
$postTypesToCache  = FullPageCacheHeaders::getPostTypesToCache(false, false);
$availablePostTypes = FullPageCacheHeaders::getAvailablePostTypesToCache(true);
$cachePurgeIsActive = CachePurge::isActive();
$acdIsActive = AcceleratedDomains::isActive();
$selectedVaryHeaders = VaryHeadersConfig::selection($acdIsActive);
?>
<form method="post" action="options.php">
    <?php settings_fields('html-cache-options-page') ?>
    <?php do_settings_sections('html-cache-options-page') ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php _e('HTML Cache', 'servebolt-wp'); ?></th>
                <td>
                    <input id="sb-html-cache-switch" name="<?php echo getOptionName('fpc_switch')?>"<?php if ($htmlCacheActiveOverridden) echo ' disabled'; ?> type="checkbox"<?php if ($htmlCacheActive) echo ' checked'; ?>><label for="sb-html-cache-switch"><?php _e('Enable', 'servebolt-wp'); ?></label>
                    <?php if ($htmlCacheActiveOverridden): ?>
                    <p class="description">HTML Cache is automatically enabled when Accelerated Domain-feature is active.</p>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
        <tbody id="sb-html-cache-form"<?php echo ($htmlCacheActive ? '' : ' style="display: none;"'); ?>>
            <?php if ($acdIsActive): ?>
            <tr class="sb-config-field-general <?php if (!$cachePurgeIsActive) echo ' sb-config-field-hidden'; ?>">
                <th scope="row">
                    <?php _e('Vary headers', 'servebolt-wp'); ?>
                    <span class="description" style="display: inline-block; margin-left: 6px; padding: 1px 8px; border-radius: 999px; font-weight: 600; color: #056839; background: #e6f6ee; border: 1px solid #b8e5cd;"><?php _e('New in 3.6.1', 'servebolt-wp'); ?></span>
                </th>
                <td>
                    <?php
                    $availableHeaders = VaryHeadersConfig::availableHeaders();
                    $varyHeaderOptions = [
                        'br' => [
                            'label' => __('User-Agent', 'servebolt-wp'),
                            'description' => __('Split cache by Mobile, Tablet, Desktop user agents.', 'servebolt-wp'),
                        ],
                        'lang' => [
                            'label' => __('Language (Accept-Language)', 'servebolt-wp'),
                            'description' => __('Cache varies by primary browser language.', 'servebolt-wp'),
                        ],
                        'co' => [
                            'label' => __('Origin country (X-Origin-Country)', 'servebolt-wp'),
                            'description' => __('Cache varies by visitor country.', 'servebolt-wp'),
                        ],
                    ];
                    ?>
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Select which headers to vary cache by', 'servebolt-wp'); ?></span></legend>
                        <input type="hidden" name="<?php echo getOptionName(VaryHeadersConfig::optionKey()); ?>[]" value="">
                        <?php foreach ($availableHeaders as $key => $headerName) : ?>
                            <?php $option = $varyHeaderOptions[$key] ?? ['label' => $headerName, 'description' => '']; ?>
                            <div style="margin-bottom: 10px;">
                                <label for="cache_purge_vary_header_<?php echo esc_attr($key); ?>">
                                    <input
                                        name="<?php echo getOptionName(VaryHeadersConfig::optionKey()); ?>[]"
                                        type="checkbox"
                                        id="cache_purge_vary_header_<?php echo esc_attr($key); ?>"
                                        value="<?php echo esc_attr($key); ?>"
                                        <?php checked(in_array($key, $selectedVaryHeaders, true)); ?>
                                    >
                                    <?php echo esc_html($option['label']); ?>
                                </label>
                                <?php if (!empty($option['description'])) : ?>
                                    <p class="description" style="margin: 4px 0 0 24px;"><?php echo esc_html($option['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <p class="description"><?php _e('Accelerated Domains will vary cache by the headers selected above.', 'servebolt-wp'); ?></p>
                    </fieldset>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th scope="row">Cache post types</th>
                <td>
                    <?php $allChecked = in_array('all', (array) $postTypesToCache); ?>
                    <?php foreach ($availablePostTypes as $postType => $postTypeName) : ?>
                        <?php $checked = in_array($postType, (array) $postTypesToCache) ? ' checked' : ''; ?>
                        <span class="<?php if ( $allChecked && $postType !== 'all' ) echo ' disabled'; ?>"><input id="sb-cache_post_type_<?php echo $postType; ?>" class="servebolt-html-cache-post-type-settings-item" name="<?php echo getOptionName('fpc_settings')?>[<?php echo $postType; ?>]" value="1" type="checkbox"<?php echo $checked; ?>> <label for="sb-cache_post_type_<?php echo $postType; ?>"><?php echo $postTypeName; ?></label></span><br>
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
                            <button type="button" style="float:left;" class="button action sb-flush-html-cache-exclude-items"<?php if (count($idsToExclude) === 0) echo ' disabled'; ?>><?php _e('Flush posts', 'servebolt-wp'); ?></button>
                        </div>
                        <div class="alignleft actions bulkactions">
                            <button type="button" class="button button-primary sb-add-exclude-post"><?php _e('Add post to exclude', 'servebolt-wp'); ?></button>
                        </div>
                        <span class="spinner flush-html-cache-exclude-list-loading-spinner"></span>
                        <br class="clear">
                    </div>

                    <table class="wp-list-table widefat striped" id="html-cache-ids-to-exclude-table">

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
                            <?php htmlCacheExcludePostTableRowMarkup($postId); ?>
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
            <tr>
                <th scope="row">Caching of 404 pages</th>
                <td>
                    <input id="sb-404-cache-switch" name="<?php echo getOptionName('cache_404_switch')?>" type="checkbox"<?php if (FullPageCacheSettings::isCacheKeyActive('cache_404_switch')) echo ' checked'; ?>><label for="sb-404-cache-switch"><?php _e('Enable', 'servebolt-wp'); ?></label>
                    <p class="description">When enabled, 404 pages are cached.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Fast static 404's</th>
                <td>
                    <input id="sb-fast-404-switch" name="<?php echo getOptionName('fast_404_switch')?>" type="checkbox"<?php if (FullPageCacheSettings::isCacheKeyActive('fast_404_switch')) echo ' checked'; ?>>
                    <label for="sb-fast-404-switch"><?php _e('Enable', 'servebolt-wp'); ?></label>
                    <p class="description">When enabled, 404's for all static files with known extension will be sent much earlier, using much less resources.</p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php submit_button(); ?>

</form>
