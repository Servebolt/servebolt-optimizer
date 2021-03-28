<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<?php
    $tabs = [
        [
            'id' => 'cache-settings',
            'url' => admin_url('admin.php?page=servebolt-fpc'),
            'title' => 'Cache settings',
        ],
        [
            'id' => 'cache-purge-settings',
            'url' => admin_url('admin.php?page=servebolt-cache-purge-control'),
            'title' => 'Cache purging',
        ],
    ];
    $defaultTab = current($tabs)['id'];
    $selectedTab = isset($selectedTab) ? $selectedTab : $defaultTab;
    view('general.tabs', compact('tabs', 'selectedTab'));
?>


