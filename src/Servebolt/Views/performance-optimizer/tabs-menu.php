<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<?php
    $urlMethod = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url';
    $tabs = [
        [
            'id' => 'servebolt-performance-optimizer',
            'url' => $urlMethod('admin.php?page=servebolt-performance-optimizer'),
            'title' => 'General',
        ],
        [
            'id' => 'servebolt-prefetching',
            'url' => $urlMethod('admin.php?page=servebolt-prefetching'),
            'title' => 'Prefetching',
        ],
        [
            'id' => 'servebolt-performance-optimizer-advanced',
            'url' => $urlMethod('admin.php?page=servebolt-performance-optimizer-advanced'),
            'title' => 'Advanced',
        ],
    ];
    $defaultTab = current($tabs)['id'];
    $selectedTab = isset($selectedTab) ? $selectedTab : $defaultTab;
    view('general.tabs', compact('tabs', 'selectedTab'));
?>


