<?php use Servebolt\Optimizer\CachePurge\CachePurge; ?>
<table class="wp-list-table widefat striped">
    <thead>
    <tr>
        <th><?php _e('Blog ID'); ?></th>
        <th><?php _e('URL'); ?></th>
        <th><?php _e('Cache Purge Active'); ?></th>
        <th><?php _e('Cache Provider'); ?></th>
        <th><?php _e('Controls'); ?></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th><?php _e('Blog ID'); ?></th>
        <th><?php _e('URL'); ?></th>
        <th><?php _e('Cache Purge Active'); ?></th>
        <th><?php _e('Cache Provider'); ?></th>
        <th><?php _e('Controls'); ?></th>
    </tr>
    </tfoot>
    <tbody>
    <?php foreach (get_sites() as $site) : ?>
    <?php



    ?>
        <tr>
            <td><?php echo $site->blog_id; ?></td>
            <td><?php echo $site->domain . $site->path; ?></td>
            <td><?php echo CachePurge::cachePurgeIsActive($site->blog_id) ? __('Yes') : __('No'); ?></td>
            <td><?php echo CachePurge::resolveDriver($site->blog_id, true); ?></td>
            <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-cache-purge-control' ); ?>" class="button btn"><?php _e('Go to site Cloudflare settings'); ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
