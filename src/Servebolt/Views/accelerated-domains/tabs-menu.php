<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
<?php use function Servebolt\Optimizer\Helpers\featureIsAvailable; ?>
<?php
$urlMethod = is_multisite() && is_network_admin() ? 'network_admin_url' : 'admin_url';
$tabs = [
    [
        'id' => 'servebolt-acd',
        'url' => $urlMethod('admin.php?page=servebolt-acd'),
        'title' => 'Home',
    ],
    [
        'id' => 'servebolt-acd-image-resize',
        'url' => $urlMethod('admin.php?page=servebolt-acd-image-resize'),
        'title' => 'Image Resizing (beta)',
    ],
    [
        'id' => 'servebolt-acd-cache',
        'url' => $urlMethod('admin.php?page=servebolt-html-cache'),
        'title' => 'Cache →'
    ],
];

if (featureIsAvailable('prefetching')) {
    $tabs[] = [
        'id' => 'servebolt-prefetching',
        'url' => $urlMethod('admin.php?page=servebolt-prefetching'),
        'title' => 'Prefetching (beta)',
    ];
}

$defaultTab = current($tabs)['id'];
$selectedTab = isset($selectedTab) ? $selectedTab : $defaultTab;
view('general.tabs', compact('tabs', 'selectedTab'));
?>
