<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly ?>
<?php use function Servebolt\Optimizer\Helpers\view; ?>
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
        'title' => 'Image resizing (beta)',
    ],
    [
        'id' => 'servebolt-acd-cache',
        'url' => $urlMethod('admin.php?page=servebolt-fpc'),
        'title' => 'Cache'
    ],
];
$defaultTab = current($tabs)['id'];
$selectedTab = isset($selectedTab) ? $selectedTab : $defaultTab;
view('general.tabs', compact('tabs', 'selectedTab'));
?>
