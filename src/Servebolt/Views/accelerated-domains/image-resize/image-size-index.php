<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<div class="tablenav top">
    <div class="alignleft actions bulkactions">
        <button type="button" class="button action sb-remove-selected-exclude-items" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
    </div>
    <div class="alignleft actions bulkactions">
        <button type="button" class="button button-primary" id="sb-add-acd-image-size">Add size</button>
    </div>
    <span class="spinner acd-image-size-index-loading-spinner"></span>
    <br class="clear">
</div>

<table class="wp-list-table widefat striped" id="acd-image-size-index">

    <thead>
    <tr>
        <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'servebolt-wp'); ?></label><input id="cb-select-all-1" type="checkbox"></td>
        <th scope="col" id="post_id" class="manage-column column-image-size"><?php _e('Size', 'servebolt-wp'); ?></th>
    </tr>
    </thead>

    <tfoot>
    <tr>
        <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2"><?php _e('Select All', 'servebolt-wp'); ?></label><input id="cb-select-all-2" type="checkbox"></td>
        <th scope="col" class="manage-column column-title column-image-size"><?php _e('Size', 'servebolt-wp'); ?></th>
    </tr>
    </tfoot>

    <tbody id="the-list">


    <tr class="no-items<?php if ( count($extraSizes) > 0 ) echo ' hidden'; ?>"><td colspan="100%"><?php _e('No sizes', 'servebolt-wp'); ?></td></tr>
    <?php foreach($extraSizes as $i => $size) : ?>
        <tr class="exclude-item">
            <th scope="row" class="check-column">
                <label class="screen-reader-text" for="cb-select-<?php echo $postId; ?>">Select "<?php echo $isPost ? $title : $url; ?>"</label>
                <input type="hidden" class="exclude-item-input" value="<?php echo esc_attr($postId); ?>">
                <input id="cb-select-<?php echo $postId; ?>" type="checkbox">
            </th>
            <td class="column-image-size has-row-actions">
                <?php echo $size['value'] . $size['descriptor'] ?>
                <div class="row-actions">
                    <span class="trash"><a href="#" class="sb-remove-acd-image-size"><?php _e('Delete', 'servebolt-wp'); ?></a></span>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>

</table>

<div class="tablenav bottom">
    <div class="alignleft actions bulkactions">
        <button type="button" id="doaction" class="button action sb-remove-selected-exclude-items" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
    </div>
</div>
