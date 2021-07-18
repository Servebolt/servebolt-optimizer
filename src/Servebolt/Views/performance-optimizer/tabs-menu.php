<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\featureIsAvailable;use function Servebolt\Optimizer\Helpers\view; ?>
<?php
    $urlMethod = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url';
    $tabs = [];
    $tabs[] = [
        'id' => 'servebolt-performance-optimizer',
        'url' => $urlMethod('admin.php?page=servebolt-performance-optimizer'),
        'title' => 'General',
    ];
    if (is_super_admin()) {
        $tabs[] = [
            'id' => 'servebolt-performance-optimizer-database',
            'url' => network_admin_url('admin.php?page=servebolt-performance-optimizer-database'),
            'title' => 'Database',
        ];
    }
    if (featureIsAvailable('prefetching')) {
        $tabs[] = [
            'id' => 'servebolt-prefetching',
            'url' => $urlMethod('admin.php?page=servebolt-prefetching'),
            'title' => 'Prefetching',
        ];
    }
    $tabs[] = [
        'id' => 'servebolt-menu-cache',
        'url' => $urlMethod('admin.php?page=servebolt-menu-cache'),
        'title' => 'Menu Cache',
    ];
    $tabs[] = [
        'id' => 'servebolt-performance-optimizer-advanced',
        'url' => $urlMethod('admin.php?page=servebolt-performance-optimizer-advanced'),
        'title' => 'Advanced',
    ];
    $defaultTab = current($tabs)['id'];
    $selectedTab = isset($selectedTab) ? $selectedTab : $defaultTab;
    view('general.tabs', compact('tabs', 'selectedTab'));
?>


