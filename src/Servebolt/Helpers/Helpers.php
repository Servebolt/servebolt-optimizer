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
    $basePath = SERVEBOLT_PLUGIN_PSR4_PATH . 'Views/';
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
 * Create an array of paginated links based on URL and number of pages.
 *
 * @param $url
 * @param $pagesNeeded
 * @param array $args
 * @return array|string|void
 */
//function sb_paginate_links_as_array($url, $pages_needed, $args = [])
function paginateLinksAsArray($url, $pagesNeeded, $args = [])
{
    if ( ! is_numeric($pagesNeeded) || $pagesNeeded <= 1 ) {
        return [$url];
    }

    $baseArgs = apply_filters('sb_paginate_links_as_array_args', [
        'base'      => $url . '%_%',
        'type'      => 'array',
        'current'   => false,
        'total'     => $pagesNeeded,
        'show_all'  => true,
        'prev_next' => false,
    ]);

    $args = wp_parse_args($args, $baseArgs);
    $links = paginate_links($args);

    $links = array_map(function($link) {
        preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $link, $result);
        if ( array_key_exists('href', $result) && count($result['href']) === 1 && ! empty($result['href']) ) {
            $url = current($result['href']);
            $url = strtok($url, '?'); // Remove query string
            return $url;
        }
        return false;
    }, $links);

    $links = array_filter($links, function($link) {
        return $link !== false;
    });

    return $links;
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
function getServeboltAdminUrl() :string
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
function clearAllCookies(): void
{
    if ( ! class_exists('Servebolt_Nginx_FPC_Auth_Handling') ) {
        require_once SERVEBOLT_PLUGIN_DIR_PATH . 'classes/nginx-fpc/sb-nginx-fpc-auth-handling.php';
    }
    ( new Servebolt_Nginx_FPC_Auth_Handling )->clearNoCacheCookie();
}

/**
 * Delete plugin settings.
 *
 * @param bool $allSites
 */
function deleteAllSettings(bool $allSites = true): void
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
function checkAllCookies(): void
{
    if ( ! class_exists('Servebolt_Nginx_FPC_Auth_Handling') ) {
        require_once SERVEBOLT_PLUGIN_DIR_PATH . 'classes/nginx-fpc/sb-nginx-fpc-auth-handling.php';
    }
    ( new Servebolt_Nginx_FPC_Auth_Handling )->cache_cookie_check();
}

/**
 * Plugin deactivation event.
 */
function deactivatePlugin(): void
{
    clearAllCookies();
}

/**
 * Plugin activation event.
 */
function activatePlugin(): void
{
    new Servebolt\Optimizer\Database\PluginTables; // Run database migrations
    checkAllCookies();
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
 * Get item from array.
 *
 * @param $key
 * @param $array
 * @param bool $defaultValue
 *
 * @return bool
 */
function arrayGet($key, $array, $defaultValue = false)
{
    return array_key_exists($key, $array) ? $array[$key] : $defaultValue;
}

/**
 * Format a string with comma separated values.
 *
 * @param string $string Comma separated values.
 *
 * @return array
 */
function formatCommaStringToArray(string $string): array
{
    $string = trim($string);
    if (empty($string)) {
        return [];
    }
    $array = explode(',', $string);
    $array = array_map(function ($item) {
        return trim($item);
    }, $array);
    return array_filter($array, function ($item) {
        return !empty($item);
    });
}

/**
 * Convert a boolean to a human readable string.
 *
 * @param bool $state
 *
 * @return string
 */
function booleanToStateString(bool $state): string
{
    return $state === true ? 'active' : 'inactive';
}

/**
 * Get the title with optional blog-parameter.
 *
 * @param $postId
 * @param bool|int $blogId
 *
 * @return string
 */
function getPostTitleByBlog($postId, $blogId = false)
{
    if ( $blogId ) switch_to_blog($blogId);
    $title = get_the_title($postId);
    if ( $blogId ) restore_current_blog();
    return $title;
}

/**
 * Check if a value is either "on" or boolean and true.
 *
 * @param $value
 * @param string $onString
 * @return bool
 */
function checkboxIsChecked($value, string $onString = 'on'): bool
{
    return $value === $onString || filter_var($value, FILTER_VALIDATE_BOOLEAN) === true;
}

/**
 * Convert an array of post IDs into array of title and Post ID.
 *
 * @param $posts
 * @param bool $blog_id
 *
 * @return array
 */
function resolvePostIdsToTitleAndPostIdString($posts, $blog_id = false): array
{
    return array_map(function($post_id) use ($blog_id) {
        $title = getPostTitleByBlog($post_id, $blog_id);
        return $title ? $title . ' (' . $post_id . ')' : $post_id;
    }, $posts);
}

/**
 * Check if post exists.
 *
 * @param $postId
 * @return bool
 */
function postExists($postId): bool
{
    return get_post_status($postId) !== false;
}

/**
 * Convert a type boolean to a verbose boolean string.
 *
 * @param bool $state
 * @return string
 */
function booleanToString(bool $state): string
{
    return $state === true ? 'true' : 'false';
}

/**
 * Format a post type slug.
 *
 * @param string $postType
 * @return string
 */
function formatPostTypeSlug(string $postType): string
{
    $postType = str_replace('_', ' ', $postType);
    $postType = str_replace('-', ' ', $postType);
    $postType = ucfirst($postType);
    return $postType;
}

/**
 * Check whether a feature is active.
 *
 * @param string $feature
 *
 * @return bool|null
 */
function featureIsActive(string $feature): ?bool
{
    switch ($feature) {
        case 'cf_image_resize':
            // Only active when defined is set, or if already active - this is to keep it beta for now (slightly hidden).
            return ( defined('SERVEBOLT_CF_IMAGE_RESIZE_ACTIVE') && SERVEBOLT_CF_IMAGE_RESIZE_ACTIVE === true ) || ( sb_cf_image_resize_control() )->resizing_is_active();
            break;
        case 'sb_asset_auto_version':
        case 'asset_auto_version':
            $generalSettings = \Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings::getInstance();
            return $generalSettings->assetAutoVersion();
            break;
        case 'cf_cache':
            return true;
            break;
    }
    return null;

}

/**
 * Write to log.
 *
 * @param $log
 */
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
