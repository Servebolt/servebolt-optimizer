<?php sb_view('admin/views/cf-cache-admin-controls/purge-queue-header-columns', compact('items_to_purge')); ?>

<tbody id="the-list">
<tr class="no-items<?php if ( count($items_to_purge) > 0 ) echo ' hidden'; ?>"><td colspan="100%"><?php sb_e('No purge items found.'); ?></td></tr>
<?php foreach ( $items_to_purge as $i => $item ) : ?>
    <tr class="purge-item">

        <th scope="row" class="check-column">
            <label class="screen-reader-text" for="cb-select-<?php echo $i; ?>">Select "<?php echo $item->get_title(); ?>"</label>
            <input type="hidden" class="purge-item-input" value="<?php echo esc_attr($item->get_identifier()); ?>">
            <input id="cb-select-<?php echo $i; ?>" type="checkbox">
        </th>

        <?php if ( $item->is_wp_object() ) : ?>

            <td class="column-post-id has-row-actions purge-item-column">
                <?php echo $item->get_id(); ?>
                <div class="row-actions">
                    <span class="trash"><a href="#" class="remove-purge-item-from-queue"><?php sb_e('Delete'); ?></a> | </span>
                    <span class="view"><a href="<?php echo esc_attr($item->get_url()); ?>" target="_blank"><?php sb_e('View'); ?></a><?php if ( $item->get_edit_url() ) echo ' | '; ?></span>
                    <?php if ( $item->get_edit_url() ) : ?>
                        <span class="view"><a href="<?php echo $item->get_edit_url(); ?>" target="_blank"><?php sb_e('Edit'); ?></a></span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="purge-item-column"><strong><?php echo $item->get_title(); ?></strong></td>

        <?php elseif ( $item->is_purge_all_item() ) : ?>

            <td class="column-post-id has-row-actions purge-item-column" colspan="2">
                <?php sb_e('Purge all-request') ?>
                <div class="row-actions">
                    <span class="trash"><a href="#" class="remove-purge-item-from-queue"><?php sb_e('Delete'); ?></a><?php if ( $item->is_url() ) : ?> | <?php endif; ?></span>
                </div>
            </td>

        <?php else : ?>

            <td class="column-post-id has-row-actions purge-item-column" colspan="2">
                <?php sb_e('Purged via URL only, no WP object available.') ?>
                <div class="row-actions">
                    <span class="trash"><a href="#" class="remove-purge-item-from-queue"><?php sb_e('Delete'); ?></a><?php if ( $item->is_url() ) : ?> | <?php endif; ?></span>
                    <?php if ( $item->is_url() ) : ?>
                        <span class="view"><a href="<?php echo esc_attr($item->get_url()); ?>" target="_blank"><?php sb_e('View'); ?></a></span>
                    <?php endif; ?>
                </div>
            </td>

        <?php endif; ?>

        <td class="column-type">
            <?php echo $item->get_item_type(); ?>
        </td>

        <td class="column-datetime-added" title="UNIX timestamp <?php echo $item->get_time_added(); ?>">
            <?php echo $item->get_datetime_added(); ?>
        </td>

        <td class="column-url">
            <?php if ( $item->is_clickable() ) : ?>
                <a href="<?php echo esc_attr($item->get_url()); ?>" target="_blank"><?php echo $item->get_url(); ?></a>
            <?php else: ?>
                <?php echo $item->get_url(); ?>
            <?php endif; ?>
        </td>

    </tr>
<?php endforeach; ?>
</tbody>
