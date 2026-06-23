<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit; // Exit if uninstall.php is not called by WordPress

require __DIR__ . '/vendor/autoload.php';
use Servebolt\Optimizer\Utils\DatabaseMigration\MigrationRunner;
use function Servebolt\Optimizer\Helpers\deleteAllSettings;
use function Servebolt\Optimizer\Helpers\deleteAllSiteSettings;
use function Servebolt\Optimizer\Helpers\clearNoCacheCookie;

// Defines plugin paths and URLs
if (!defined('SERVEBOLT_PLUGIN_FILE')) {
    define('SERVEBOLT_PLUGIN_FILE', __DIR__ . '/servebolt-optimizer.php');
}

function sb_drop_index(string $tableName, string $indexName): void
{
    global $wpdb;

    $quotedTableName = '`' . str_replace('`', '``', $tableName) . '`';
    $quotedIndexName = '`' . str_replace('`', '``', $indexName) . '`';
    $indexExists = $wpdb->get_var($wpdb->prepare("SHOW INDEX FROM {$quotedTableName} WHERE Key_name = %s", $indexName));

    if ($indexExists) {
        $wpdb->query("ALTER TABLE {$quotedTableName} DROP INDEX {$quotedIndexName}");
    }
}

function sb_remove_db_indexes(): void
{
    global $wpdb;

    sb_drop_index($wpdb->postmeta, 'sbpmv');
    sb_drop_index($wpdb->options, 'autoload');
}

if (is_multisite()) {
    foreach (get_sites(['fields' => 'ids', 'number' => 0]) as $blogId) {
        switch_to_blog($blogId);
        sb_remove_db_indexes();
        restore_current_blog();
    }
} else {
    sb_remove_db_indexes();
}

MigrationRunner::cleanup();
deleteAllSettings(true, true);
if (is_multisite()) {
    deleteAllSiteSettings(true);
}
clearNoCacheCookie();
