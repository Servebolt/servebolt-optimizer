<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\arrayGet; ?>
<?php
    if ((isset($skipIfOnlyOneTab) && $skipIfOnlyOneTab === true) && count($tabs) <= 1) {
        return;
    }
?>
<nav class="nav-tab-wrapper">
    <?php foreach ($tabs as $tab): ?>
        <a
            href="<?php echo esc_url(arrayGet('url', $tab)); ?>"
            class="nav-tab<?php
                if (arrayGet('disabled', $tab) === true) {
                    echo ' nav-tab-inactive';
                } elseif ($selectedTab === arrayGet('id', $tab)) {
                    echo ' nav-tab-active';
                }
            ?>">
            <?php echo arrayGet('title', $tab); ?>
        </a>
    <?php endforeach; ?>
</nav>
