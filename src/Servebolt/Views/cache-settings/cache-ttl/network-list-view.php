<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<br>

<table class="wp-list-table widefat striped">
    <thead>
    <tr>
        <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
        <th><?php _e('URL', 'servebolt-wp'); ?></th>
        <th><?php _e('Controls', 'servebolt-wp'); ?></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
        <th><?php _e('URL', 'servebolt-wp'); ?></th>
        <th><?php _e('Controls', 'servebolt-wp'); ?></th>
    </tr>
    </tfoot>
    <tbody>
    <?php foreach (get_sites() as $site) : ?>
        <tr>
            <td><?php echo $site->blog_id; ?></td>
            <td><?php echo $site->domain . $site->path; ?></td>
            <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-cache-ttl' ); ?>" class="button btn"><?php _e('Go to site cache TTL settings', 'servebolt-wp'); ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
