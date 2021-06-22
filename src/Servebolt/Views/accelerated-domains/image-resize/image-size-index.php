<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<div class="tablenav top">
    <div class="alignleft actions bulkactions">
        <button type="button" class="button action sb-remove-selected-acd-image-sizes" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
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
        <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'servebolt-wp'); ?></label><input id="cb-select-all-1" type="checkbox"<?php if (count($extraSizes) == 0) echo ' disabled'; ?>></td>
        <th scope="col" id="post_id" class="manage-column column-image-size"><?php _e('Size', 'servebolt-wp'); ?></th>
    </tr>
    </thead>

    <tfoot>
    <tr>
        <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2"><?php _e('Select All', 'servebolt-wp'); ?></label><input id="cb-select-all-2" type="checkbox"<?php if (count($extraSizes) == 0) echo ' disabled'; ?>></td>
        <th scope="col" class="manage-column column-title column-image-size"><?php _e('Size', 'servebolt-wp'); ?></th>
    </tr>
    </tfoot>

    <tbody id="the-list">
        <?php view('accelerated-domains.image-resize.image-size-index-list', compact('extraSizes')); ?>
    </tbody>

</table>

<div class="tablenav bottom">
    <div class="alignleft actions bulkactions">
        <button type="button" id="doaction" class="button action sb-remove-selected-acd-image-sizes" disabled><?php _e('Remove selected', 'servebolt-wp'); ?></button>
    </div>
</div>
