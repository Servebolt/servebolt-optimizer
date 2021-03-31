<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\formatPostTypeSlug; ?>
<?php use function Servebolt\Optimizer\Helpers\fullPageCache; ?>
<?php use Servebolt\Optimizer\FullPageCache\FullPageCache; ?>

<table class="wp-list-table widefat striped">
    <thead>
    <tr>
        <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
        <th><?php _e('URL', 'servebolt-wp'); ?></th>
        <th><?php _e('Full Page Cache Active', 'servebolt-wp'); ?></th>
        <th><?php _e('Post types', 'servebolt-wp'); ?></th>
        <th><?php _e('Controls', 'servebolt-wp'); ?></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th><?php _e('Blog ID', 'servebolt-wp'); ?></th>
        <th><?php _e('URL', 'servebolt-wp'); ?></th>
        <th><?php _e('Full Page Cache Active', 'servebolt-wp'); ?></th>
        <th><?php _e('Post types', 'servebolt-wp'); ?></th>
        <th><?php _e('Controls', 'servebolt-wp'); ?></th>
    </tr>
    </tfoot>
    <tbody>
    <?php foreach (get_sites() as $site) : ?>
        <?php $sbFpcSettings = fullPageCache()->getPostTypesToCache(false, false, $site->blog_id); ?>
        <tr>
            <td><?php echo $site->blog_id; ?></td>
            <td><?php echo $site->domain . $site->path; ?></td>
            <td><?php echo FullPageCache::fpcIsActive($site->blog_id) ? __('Yes', 'servebolt-wp') : __('No', 'servebolt-wp'); ?></td>
            <td>
                <?php if ( ! empty($sbFpcSettings) ) : ?>
                    <?php if ( in_array('all', $sbFpcSettings) ) : ?>
                        All
                    <?php else: ?>
                        <?php foreach ($sbFpcSettings as $postType) : ?>
                            <?php echo formatPostTypeSlug($postType) . '<br>'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php else : ?>
                    None
                <?php endif; ?>
            </td>
            <td><a href="<?php echo get_admin_url( $site->blog_id, 'admin.php?page=servebolt-fpc' ); ?>" class="button btn"><?php _e('Go to site FPC settings', 'servebolt-wp'); ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
