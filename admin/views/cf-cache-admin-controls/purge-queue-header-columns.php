<thead>
<tr>
    <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php sb_e('Select All'); ?></label><input id="cb-select-all-1" type="checkbox"<?php if ( count($items_to_purge) === 0 ) echo ' disabled'; ?>></td>
    <th scope="col" id="item_id" class="manage-column column-item-id"><?php sb_e('Item ID'); ?></th>
    <th scope="col" id="item_title" class="manage-column column-item-title"><?php sb_e('Item title'); ?></th>
    <th scope="col" id="item_type" class="manage-column column-item-type"><?php sb_e('Item type'); ?></th>
    <th scope="col" id="datetime_added" class="manage-column column-datetime-added"><?php sb_e('Datetime added'); ?></th>
    <th scope="col" id="url" class="manage-column column-url"><?php sb_e('URL'); ?></th>
</tr>
</thead>

<tfoot>
<tr>
    <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2"><?php sb_e('Select All'); ?></label><input id="cb-select-all-2" type="checkbox"<?php if ( count($items_to_purge) === 0 ) echo ' disabled'; ?>></td>
    <th scope="col" class="manage-column column-item-id column-primary"><?php sb_e('Item ID'); ?></th>
    <th scope="col" class="manage-column column-item-title column-primary"><?php sb_e('Item title'); ?></th>
    <th scope="col" class="manage-column column-item-type column-primary"><?php sb_e('Item type'); ?></th>
    <th scope="col" class="manage-column column-datetime-added column-primary"><?php sb_e('Datetime added'); ?></th>
    <th scope="col" class="manage-column column-url"><?php sb_e('URL'); ?></th>
</tr>
</tfoot>
