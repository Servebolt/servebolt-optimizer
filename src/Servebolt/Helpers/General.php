<?php

namespace Servebolt\Optimizer\Helpers;

/**
 * Display a view, Laravel style.
 *
 * @param string $templatePath
 * @param array $arguments
 * @param bool $echo
 * @return string|null
 */
function view(string $templatePath, $arguments = [], $echo = true): ?string
{
    $templatePath = str_replace('.', '/', $templatePath);
    $suffix = '.php';
    $basePath = SERVEBOLT_PSR4_PATH . 'Views/';
    $filePath = $basePath . $templatePath . $suffix;
    if (file_exists($filePath) && is_readable($filePath)) {
        extract($arguments, EXTR_SKIP);
        if (!$echo) {
            ob_start();
        }
        include $filePath;
        if (!$echo) {
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }
    }
    return null;
}

/**
 * Convert string from camel case to snake case.
 *
 * @param string $string
 * @return string
 */
function camelCaseToSnakeCase(string $string): string
{
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
}

/**
 * Get a link to the Servebolt admin panel.
 *
 * @return string
 */
function sbGetAdminUrl() :string
{
    if (!function_exists('get_home_path')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    $webRootPath = sb_is_dev_debug() ? '/kunder/serveb_1234/custom_4321/public' : get_home_path();
    if (preg_match("@kunder/[a-z_0-9]+/[a-z_]+(\d+)/@", $webRootPath, $matches) && isset($matches[1])) {
        return 'https://admin.servebolt.com/siteredirect/?site='. $matches[1];
    }
    return false;
}

/**
 * Clean the cookies we have been setting.
 */
function sbClearAllCookies(): void
{
    if ( ! class_exists('Servebolt_Nginx_FPC_Auth_Handling') ) {
        require_once SERVEBOLT_PATH . 'classes/nginx-fpc/sb-nginx-fpc-auth-handling.php';
    }
    ( new Servebolt_Nginx_FPC_Auth_Handling )->clearNoCacheCookie();
}

/**
 * Delete plugin settings.
 *
 * @param bool $allSites
 */
function sbDeleteAllSettings(bool $allSites = true): void
{
    $optionNames = [
        // General settings
        'asset_auto_version',
        'use_native_js_fallback',
        'use_cloudflare_apo',

        // Wipe nonce
        'ajax_nonce',
        'record_max_num_pages_nonce',

        // Wipe encryption keys
        'mcrypt_key',
        'openssl_key',
        'openssl_iv',

        // Wipe Cache purge-related options
        'cache_purge_switch',
        'cache_purge_driver',
        'cf_switch',
        'cf_zone_id',
        'cf_auth_type',
        'cf_email',
        'cf_api_key',
        'cf_api_token',
        'cf_items_to_purge',
        'cf_cron_purge',

        // Wipe SB FPC-related options
        'fpc_switch',
        'fpc_settings',
        'fpc_exclude',
    ];
    foreach ($optionNames as $optionName) {
        if (is_multisite() && $allSites) {
            sb_iterate_sites(function ($site) use ($optionName) {
                sb_delete_blog_option($site->blog_id, $optionName);
            });
        } else {
            sb_delete_option($optionName);
        }
    }
}


/**
 * Check the cookies we have been set.
 */
function sbCheckAllCookies(): void
{
    if ( ! class_exists('Servebolt_Nginx_FPC_Auth_Handling') ) {
        require_once SERVEBOLT_PATH . 'classes/nginx-fpc/sb-nginx-fpc-auth-handling.php';
    }
    ( new Servebolt_Nginx_FPC_Auth_Handling )->cache_cookie_check();
}

/**
 * Plugin deactivation event.
 */
function sbDeactivatePlugin(): void
{
    sbClearAllCookies();
}

/**
 * Plugin activation event.
 */
function sbActivatePlugin(): void
{
    new Servebolt\Optimizer\Database\PluginTables; // Run database migrations
    sbCheckAllCookies();
}

/**
 * Check if we are running as CLI.
 *
 * @return bool
 */
function isCli(): bool
{
    return (defined('WP_CLI') && WP_CLI);
}

/**
 * Check if this is a WP REST API request.
 *
 * @return bool
 */
function isWpRest(): bool
{
    return (defined('REST_REQUEST') && REST_REQUEST);
}

/**
 * Check if execution is initiated by cron.
 *
 * @return bool
 */
function isCron(): bool
{
    return (defined('DOING_CRON') && DOING_CRON);
}

/**
 * Write to log.
 *
 * @param $log
 */
//function sb_write_log($log)
function writeLog($log)
{
    if (!defined('WP_DEBUG') || WP_DEBUG == false) {
        return;
    }
    if (is_array($log) || is_object($log)) {
        error_log(print_r($log, true));
    } else {
        error_log($log);
    }
}
