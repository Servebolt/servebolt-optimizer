<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<?php
    $urlMethod = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url';
    $tabs = [
        [
            'id' => 'cache-settings',
            'url' => $urlMethod('admin.php?page=servebolt-html-cache'),
            'title' => 'Cache settings',
        ],
        [
            'id' => 'cache-ttl-settings',
            'url' => $urlMethod('admin.php?page=servebolt-cache-ttl'),
            'title' => 'Cache TTL',
        ],
        [
            'id' => 'cache-purge-settings',
            'url' => $urlMethod('admin.php?page=servebolt-cache-purge-control'),
            'title' => 'Cache purging',
        ],
    ];
    $defaultTab = current($tabs)['id'];
    $selectedTab = isset($selectedTab) ? $selectedTab : $defaultTab;
    view('general.tabs', compact('tabs', 'selectedTab'));
?>


