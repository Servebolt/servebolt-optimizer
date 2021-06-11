<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<nav class="nav-tab-wrapper">
    <?php foreach ($tabs as $tab): ?>
        <a href="<?php echo esc_url($tab['url']); ?>" class="nav-tab <?php if($selectedTab === $tab['id']):?>nav-tab-active<?php endif; ?>"><?php echo $tab['title']; ?></a>
    <?php endforeach; ?>
</nav>
