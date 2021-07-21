<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains; ?>

<table class="wp-list-table widefat striped">
    <thead>
    <tr>
        <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
        <th><?php _e('URL', 'servebolt-wp'); ?></th>
        <th><?php _e('Accelerated Domains Active', 'servebolt-wp'); ?></th>
        <th><?php _e('Controls', 'servebolt-wp'); ?></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
        <th><?php _e('URL', 'servebolt-wp'); ?></th>
        <th><?php _e('Accelerated Domains Active', 'servebolt-wp'); ?></th>
        <th><?php _e('Controls', 'servebolt-wp'); ?></th>
    </tr>
    </tfoot>
    <tbody>
    <?php foreach (get_sites() as $site): ?>
        <tr>
            <td><?php echo $site->blog_id; ?></td>
            <td><?php echo $site->domain . $site->path; ?></td>
            <td><?php echo AcceleratedDomains::isActive($site->blog_id) ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></td>
            <td><a href="<?php echo get_admin_url($site->blog_id, 'admin.php?page=servebolt-acd'); ?>" class="button btn"><?php _e('Go to site Accelerated Domains settings', 'servebolt-wp'); ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
