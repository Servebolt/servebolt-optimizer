<?php use Servebolt\Optimizer\CachePurge\CachePurge; ?>
<table class="wp-list-table widefat striped">
    <thead>
    <tr>
        <th><?php sb_e('Blog ID'); ?></th>
        <th><?php sb_e('URL'); ?></th>
        <th><?php sb_e('Cache Purge Active'); ?></th>
        <th><?php sb_e('Cache Provider'); ?></th>
        <th><?php sb_e('Controls'); ?></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th><?php sb_e('Blog ID'); ?></th>
        <th><?php sb_e('URL'); ?></th>
        <th><?php sb_e('Cache Purge Active'); ?></th>
        <th><?php sb_e('Cache Provider'); ?></th>
        <th><?php sb_e('Controls'); ?></th>
    </tr>
    </tfoot>
    <tbody>
    <?php foreach (get_sites() as $site) : ?>
    <?php



    ?>
        <tr>
            <td><?php echo $site->blog_id; ?></td>
            <td><?php echo $site->domain . $site->path; ?></td>
            <td><?php echo CachePurge::cachePurgeIsActive($site->blog_id) ? sb__('Yes') : sb__('No'); ?></td>
            <td><?php echo CachePurge::resolveDriver($site->blog_id, true); ?></td>
            <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-cache-purge-control' ); ?>" class="button btn"><?php sb_e('Go to site Cloudflare settings'); ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
