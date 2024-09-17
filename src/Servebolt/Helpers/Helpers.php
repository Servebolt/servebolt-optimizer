<?php

namespace Servebolt\Optimizer\Helpers;

/**
 * Resolve the path to a view file.
 *
 * @param $templatePath
 * @return string|null
 */
function resolveViewPath($templatePath): ?string
{
    $templatePath = str_replace('.', '/', $templatePath);
    $suffix = '.php';
    $basePath = apply_filters('sb_optimizer_view_folder_path', SERVEBOLT_PLUGIN_PSR4_PATH . 'Views/');
    $filePath = $basePath . $templatePath . $suffix;
    if (file_exists($filePath) && is_readable($filePath)) {
        return $filePath;
    }
    return null;
}

/**
 * Check whether we could read environment file.
 *
 * @return bool
 */
function envFileRead(): bool
{
    return did_action('sb_optimizer_env_file_reader_failure') === 0;
}

/**
 * Return error in WP CLI if the environment file could not be read.
 *
 * @return void
 */
function envFileReadFailureCliHandling()
{
    if (!envFileRead()) {
        \WP_CLI::error(__('Could not obtain config from environment file. Aborting.', 'servebolt-wp'));
    }
}

/**
 * Add admin notice if we cannot read the environment file.
 *
 * @return void
 */
function envFileFailureHandling()
{
    add_action('sb_optimizer_env_file_reader_failure', function($e) {
        add_action('admin_notices', function() use ($e) {
            $adminUrl = getServeboltAdminUrl();
            ?>
            <div class="notice notice-error is-dismissable">
                <p><?php echo __('Servebolt Optimizer could not read the environment file which is necessary for the plugin to function. This file originates from Servebolt and contains information about your site.', 'servebolt-wp'); ?></p>
                <p><?php printf(__('To fix this then go to your %ssite settings%s, click "Settings" and make sure that the setting "Environment file in home folder" is <strong>not</strong> set to "None". Remember to click "Save settings" to ensure that the file is written to disk regardless of the previous state of the setting.', 'servebolt-wp'), '<a href="' . $adminUrl . '" target="_blank">', '</a>'); ?></p>
                <p><?php printf(__('%sGet in touch with our support via chat%s if you need assistance with resolving this issue.', 'servebolt-wp'), '<a href="https://admin.servebolt.com/" target="_blank">', '</a>'); ?></p>
                <?php if ($e->getCode() !== 69): ?>
                    <p>Error message: <?php echo $e->getMessage(); ?></p>
                <?php endif; ?>
            </div>
            <?php
        });
    });
}

/**
 * Display a view, Laravel style.
 *
 * @param string $templatePath
 * @param array $arguments
 * @param bool $echo
 * @return string|null
 */
function view(string $templatePath, array $arguments = [], bool $echo = true): ?string
{
    if ($filePath = resolveViewPath($templatePath)) {
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
 * Get option name by key.
 *
 * @param string $option
 *
 * @return string
 */
function getOptionName(string $option): string
{
    return 'servebolt_' . $option;
}

/**
 * Whether a string contains a valid URL.
 *
 * @param $url
 * @return bool
 */
function isUrl($url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Generate a random string.
 *
 * @param int $length
 *
 * @return string
 */
function generateRandomString(int $length): string
{
    $includeChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-@|';
    $charLength = strlen($includeChars);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $includeChars [rand(0, $charLength - 1)];
    }
    return $randomString;
}

/**
 * Display value, regardless of type.
 *
 * @param mixed $value
 * @param bool $return
 * @param bool $arrayToCsv
 * @return bool|false|string|null
 */
function displayValue($value, bool $return = true, bool $arrayToCsv = false)
{
    if (is_bool($value)) {
        $value = booleanToString($value);
    } elseif (is_string($value)) {
        $value = $value;
    } else {
        ob_start();
        var_dump($value);
        $value = ob_get_contents();
        ob_end_clean();
    }
    if ($return) {
        return $value;
    }
    echo $value;
}

/**
 * Check if current user has capability using a callable function, abort if not.
 *
 * @param $method
 * @param bool $returnResult
 * @return bool|mixed
 */
function ajaxUserAllowedByFunction($method, bool $returnResult = false)
{
    if (is_callable($method)) {
        return ajaxUserAllowed($returnResult, $method);
    }
}

/**
 * Flag cache purge origin event.
 *
 * @param string $origin
 */
function setCachePurgeOriginEvent(string $origin): void
{
    remove_all_filters('sb_optimizer_cache_purge_origin');
    add_filter('sb_optimizer_cache_purge_origin', function() use ($origin) {
        return $origin;
    });
}

/**
 * Get and clear the purge origin event flag.
 *
 * @return mixed|void|null
 */
function getCachePurgeOriginEvent()
{
    return pickupValueFromFilter('sb_optimizer_cache_purge_origin');
}

/**
 * Pick up value from a filter, then clear the filter.
 *
 * @param string $key
 * @param bool $clearAfterPickup
 * @return mixed|void|null
 */
function pickupValueFromFilter(string $key, bool $clearAfterPickup = true)
{
    if (!has_filter($key)) {
        return null;
    }
    $value = apply_filters($key, null);
    if ($clearAfterPickup) {
        remove_all_filters($key);
    }
    return $value;
}

/**
 * Check if current user has capability, abort if not.
 *
 * @param bool $returnResult
 * @param string|callable $capability
 *
 * @return bool|mixed
 */
function ajaxUserAllowed(bool $returnResult = false, $capability = 'manage_options')
{
    if (is_callable($capability)) {
        $userCan = $capability();
    } else {
        $userCan = current_user_can($capability);
    }
    $userCan = apply_filters('sb_optimizer_ajax_user_allowed', $userCan);
    if ($returnResult) {
        return $userCan;
    }
    if (!$userCan) {
        wp_die();
    }
}

/**
 * Create li-tags from array.
 *
 * @param $iterator
 * @param callable|bool $closure
 * @param bool|string $includeUlOrStyling
 *
 * @return string
 */
function createLiTagsFromArray($iterator, $closure = false, $includeUlOrStyling = true): string
{
    $markup = '';
    if ($includeUlOrStyling) {
        $markup .= '<ul' . (is_string($includeUlOrStyling) ? ' style="' . $includeUlOrStyling . '"' : '') . '>';
    }
    array_map(function($item) use (&$markup, $closure) {
        $markup .= '<li>' . (is_callable($closure) ? $closure($item) : $item) . '</li>';
    }, $iterator);
    if ($includeUlOrStyling) {
        $markup .= '</ul>';
    }
    return $markup;
}

/**
 * Create an array of paginated links based on URL and number of pages.
 *
 * @param $url
 * @param $pagesNeeded
 * @param array $args
 * @return array|string|void
 */
function paginateLinksAsArray($url, $pagesNeeded, $args = [])
{
    $url = trailingslashit($url);
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
 * Convert string from snake case to camel case.
 *
 * @param string $string
 * @param bool $capitalizeFirst
 * @return string
 */
function snakeCaseToCamelCase(string $string, bool $capitalizeFirst = false): string
{
    $string = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    if ($capitalizeFirst) {
        $string = ucfirst($string);
    }
    return $string;
}

/**
 * Get site ID, either from Env-file or from the webroot folder path.
 *
 * @return mixed|null
 */
function getSiteId(): ?string
{
    if (isHostedAtServebolt()) {
        if ($id = getSiteIdFromEnvFile()) {
            return $id;
        }
    }
    if ($id = getSiteIdFromWebrootPath(false)) {
        return $id;
    }
    return null;
}

/**
 * Get site ID from Env-file.
 *
 * @return mixed|null
 */
function getApiUrlFromEnvFile(): ?string
{
    $env = \Servebolt\Optimizer\Utils\EnvFile\Reader::getInstance();
    if ($env->api_url) {
        return $env->api_url . '/v1/';
    }
    return "https://api.servebolt.io/v1/";
}

/**
 * Get site API url from Env-file.
 *
 * @return mixed|null
 */
function getSiteIdFromEnvFile(): ?string
{
    $env = \Servebolt\Optimizer\Utils\EnvFile\Reader::getInstance();
    if ($env->id) {
        return $env->id;
    }
    return null;
}
/**
 * Get site ID from the webroot folder path.
 *
 * @param bool $attemptPathFromEnvironmentFile Whether to attempt to get webroot folder path from environment file.
 * @return string|null
 */
function getSiteIdFromWebrootPath(bool $attemptPathFromEnvironmentFile = true): ?string
{
    $legacy = '/kunder\/[a-z_0-9]+\/[a-z_]+(\d+)(|\/)/';
    $nextGen = '/(\/cust\/[0-9]\/[a-z_0-9]+\/[a-z_]+(\d+))/';
    $path = $attemptPathFromEnvironmentFile ? getWebrootPath() : getWebrootPathFromWordPress();
    $regex = $legacy;
    $match_number = 1;
    if(isNextGen($path)) {
        $regex = $nextGen;
        $match_number = 2;
    }
    if (
        preg_match($regex, $path, $matches)
        && isset($matches[$match_number])
    ) {
        return $matches[$match_number];
    }
    return null;
}

/**
 * Get the path to the webroot.
 *
 * @return string
 */
function getWebrootPath(): ?string
{
    if (isHostedAtServebolt()) {
        if ($fromEnvFile = getWebrootPathFromEnvFile()) {
            return $fromEnvFile;
        }
    }
    if ($fromWordPress = getWebrootPathFromWordPress()) {
        return $fromWordPress;
    }
    return null;
}

/**
 * Get the path to the webroot using the environment file.
 *
 * @return string|null
 */
function getWebrootPathFromEnvFile(): ?string
{
    $env = \Servebolt\Optimizer\Utils\EnvFile\Reader::getInstance();
    if ($env->public_dir) {
        return apply_filters('sb_optimizer_wp_webroot_path_from_env', apply_filters('sb_optimizer_wp_webroot_path', $env->public_dir));
    }
    return null;
}

/**
 * Get the path to the webroot using WordPress.
 *
 * @return string
 */
function getWebrootPathFromWordPress(): ?string
{
    if (!function_exists('get_home_path')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (function_exists('get_home_path')) {
        if ($path = get_home_path()) {
            return apply_filters('sb_optimizer_wp_webroot_path_from_wp', apply_filters('sb_optimizer_wp_webroot_path', $path));
        }
    }
    return null;
}

/**
 * Get a link to the Servebolt admin panel.
 *
 * @param array|string $argsOrPage Either an array of query parameter or the sub-page to redirect to.
 * @return string|null
 */
function getServeboltAdminUrl($argsOrPage = []) :? string
{
    if ($site = getSiteId()) {
        if (is_string($argsOrPage)) {
            $args = ['page' => $argsOrPage];
        } elseif (is_array($argsOrPage)) {
            $args = $argsOrPage;
        } else {
            $args = [];
        }
        $baseUrl = 'https://adminv3.servebolt.com/siteredirect/';
        if(isNextGen()) {
            $baseUrl = 'https://admin.servebolt.com/siteredirect/';
        }
        
        $queryParameters = http_build_query(array_merge($args, compact('site')));
        return $baseUrl . '?' . $queryParameters;
    }
    return null;
}

/**
 * Check if we are currently viewing a given network-screen.
 *
 * @param $screenId
 * @return bool
 */
function isNetworkScreen($screenId): bool
{
    $currentScreen = get_current_screen();
    return $screenId . '-network' == $currentScreen->id;
}

/**
 * Check if we are currently viewing a given screen.
 *
 * @param string $screenId The ID of the screen to check for.
 * @param bool $networkSupport Whether to support network
 * @param bool $strict
 * @return bool
 */
function isScreen(string $screenId, bool $networkSupport = true, bool $strict = false): bool
{
    $prefixes = $strict ? [''] : ['admin_', 'servebolt_'];
    foreach ($prefixes as $prefix) {
        $screenIdWithPrefix = $prefix . trim($screenId, '_');
        $currentScreen = get_current_screen();
        if ($screenIdWithPrefix == $currentScreen->id) {
            return true;
        }
        if ($networkSupport && isNetworkScreen($screenIdWithPrefix)) {
            return true;
        }
    }
    return false;
}

/**
 * Get instance of "FullPageCacheAuthHandling".
 *
 * @return mixed
 */
function getFullPageCacheAuthHandlingInstance()
{
    return \Servebolt\Optimizer\FullPageCache\FullPageCacheAuthHandling::getInstance();
}

/**
 * Clean the cookies we have been setting.
 */
function clearNoCacheCookie(): void
{
    (getFullPageCacheAuthHandlingInstance())->clearNoCacheCookie();
}

/**
 * Check the cookies we have been set.
 */
function cacheCookieCheck(): void
{
    (getFullPageCacheAuthHandlingInstance())->cacheCookieCheck();
}

/**
 * Get taxonomy object by term Id.
 *
 * @param int $termId
 * @return object|null
 */
function getTaxonomyFromTermId(int $termId): ?object
{
    if ($term = get_term($termId)) {
        if ($taxonomyObject = get_taxonomy($term->taxonomy)) {
            return $taxonomyObject;
        }
    }
    return null;
}

/**
 * Get for filter/actions on hook.
 *
 * @param string|null $hook
 * @return object|null
 */
function getFiltersForHook(?string $hook = null): ?object
{
    global $wp_filter;
    if (empty($hook) || !isset($wp_filter[$hook])) {
        return null;
    }
    return $wp_filter[$hook];
}

/**
 * Get taxonomy singular name by term Id.
 *
 * @param int $termId
 * @return string
 */
function getTaxonomySingularName(int $termId): string
{
    if ($taxonomyObject = getTaxonomyFromTermId($termId)) {
        if (isset($taxonomyObject->labels->singular_name) && $taxonomyObject->labels->singular_name) {
            return mb_strtolower($taxonomyObject->labels->singular_name);
        }
    }
    return 'term';
}

/**
 * Get post type singular name by post ID.
 *
 * @param int $postId
 * @return string
 */
function getPostTypeSingularName(int $postId): string
{
    if ($postType = get_post_type($postId)) {
        if ($postTypeObject = get_post_type_object($postType)) {
            if (isset($postTypeObject->labels->singular_name) && $postTypeObject->labels->singular_name) {
                return mb_strtolower($postTypeObject->labels->singular_name);
            }
        }
    }
    return 'post';
}

/**
 * Get all site option names.
 *
 * @param bool $includeMigrationOptions
 * @return string[]
 */
function getAllSiteOptionNames(bool $includeMigrationOptions = false): array
{
    $optionNames = [
        // Wipe nonce
        'ajax_nonce',

        // Cron control
        'action_scheduler_disable',
        'action_scheduler_unix_cron_active',
        'wp_unix_cron_active',

        // Wipe encryption keys
        'mcrypt_key',
        'openssl_key',
        'openssl_iv',
    ];

    if ($includeMigrationOptions) {
        $optionNames = array_merge($optionNames, [

            // Migration related
            'migration_version',

        ]);
    }

    return $optionNames;
}

/**
 * Delete plugin settings - site options.
 *
 * @param bool $includeMigrationOptions
 */
function deleteAllSiteSettings(bool $includeMigrationOptions = false): void
{
    $optionNames = getAllSiteOptionNames($includeMigrationOptions);
    foreach ($optionNames as $optionName) {
        deleteSiteOption($optionName);
    }
}

/**
 * Get all options names.
 *
 * @param bool $includeMigrationOptions Whether to delete the options related to database migrations.
 * @return string[]
 */
function getAllOptionsNames(bool $includeMigrationOptions = false): array
{
    $optionNames = [
        // General settings
        'asset_auto_version',
        'use_native_js_fallback',
        'use_cloudflare_apo',

        // Wipe nonce
        'ajax_nonce',

        // Legacy
        'record_max_num_pages_nonce',
        'sb_optimizer_record_max_num_pages',
        'cf_items_to_purge',
        'cf_cron_purge',

        // Env file reader
        'env_file_path',

        // Wipe encryption keys
        'mcrypt_key',
        'openssl_key',
        'openssl_iv',

        // CF Image resizing
        'cf_image_resizing',

        // Advanced performance optimizations
        'custom_text_domain_loader_switch',

        // Cron control
        'action_scheduler_disable',
        'action_scheduler_unix_cron_active',
        'wp_unix_cron_active',

        // Prefetching
        'prefetch_switch',
        'prefetch_file_style_switch',
        'prefetch_file_script_switch',
        'prefetch_file_menu_switch',
        'prefetch_full_url_switch',
        'prefetch_max_number_of_lines',

        // Wipe Cache purge-related options
        'cache_purge_switch',
        'cache_purge_auto',
        'cache_purge_auto_on_slug_change',
        'cache_purge_auto_on_deletion',
        'cache_purge_driver',
        'cf_switch',
        'cf_zone_id',
        'cf_auth_type',
        'cf_email',
        'cf_api_key',
        'cf_api_token',
        'queue_based_cache_purge',

        // Accelerated Domains
        'acd_switch',
        'acd_minify_switch',

        // Accelerated Domains Image Resize
        'acd_image_resize_switch',
        'acd_image_resize_half_size_switch',
        'acd_image_resize_src_tag_switch',
        'acd_image_resize_srcset_tag_switch',
        'acd_image_resize_quality',
        'acd_image_resize_metadata_optimization_level',
        'acd_image_resize_upscale',
        'acd_image_resize_size_index',

        // Accelerated Domains Image Resize (legacy)
        'acd_img_resize_switch',
        'acd_img_resize_half_size_switch',
        'acd_img_resize_src_tag_switch',
        'acd_img_resize_srcset_tag_switch',
        'acd_img_resize_quality',
        'acd_img_resize_metadata_optimization_level',
        'acd_img_resize_upscale',
        'acd_img_resize_size_index',

        // Menu Optimizer (formerly Menu cache) feature
        'menu_cache_switch',
        'menu_cache_disabled_for_authenticated_switch',
        'menu_cache_auto_cache_purge', // Legacy
        'menu_cache_auto_cache_purge_on_menu_update',
        'menu_cache_auto_cache_purge_on_front_page_settings_update',
        'menu_cache_run_timing',
        'menu_cache_simple_menu_signature',

        // HTML Cache-related options (formerly FPC / Full Page Cache)
        'cache_404_switch',
        'fast_404_switch',
        'fpc_switch',
        'fpc_settings',
        'fpc_exclude',

        // Custom cache TTL
        'custom_cache_ttl_switch',
        'cache_ttl_by_post_type',
        'cache_ttl_by_taxonomy',
        'custom_cache_ttl_by_post_type',
        'custom_cache_ttl_by_taxonomy',
    ];

    if ($includeMigrationOptions) {
        $optionNames = array_merge($optionNames, [

            // Migration related
            'migration_version',

        ]);
    }

    return $optionNames;
}

/**
 * Delete plugin settings.
 *
 * @param bool $allSites Whether to delete all settings on all sites in multisite.
 * @param bool $includeMigrationOptions Whether to delete the options related to database migrations.
 */
function deleteAllSettings(bool $allSites = true, bool $includeMigrationOptions = false): void
{
    $optionNames = getAllOptionsNames($includeMigrationOptions);
    foreach ($optionNames as $optionName) {
        if (is_multisite() && $allSites) {
            iterateSites(function ($site) use ($optionName) {
                deleteBlogOption($site->blog_id, $optionName);
            });
        } else {
            deleteOption($optionName);
        }
    }
}

/**
 * Checks if we are on NextGen or a Legacy Server.
 */
function isNextGen($path = ''): bool
{
    if($path === '') {
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            $path = trailingslashit(dirname($_SERVER['DOCUMENT_ROOT']));
        } else if (defined('ABSPATH')) {
            $path = trailingslashit(dirname(ABSPATH));
        } else {
            throw new Exception('Could not determine default environment file folder path.');
        }
    }
    return ( strpos($path, '/cust/') === 0 ) ? true : false;
}

/**
 * Check whether a variable is an instance of QueueItem.
 *
 * @param $var
 * @return bool
 */
function isQueueItem($var): bool
{
    return is_a($var, '\\Servebolt\\Optimizer\\Utils\\Queue\\QueueItem');
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
 * Check whether we should add new pages to menu(s).
 *
 * @return bool
 */
/*
function autoAddPagesToMenu(): bool
{
    $navMenuOption = (array) get_option('nav_menu_options');
    return isset($navMenuOption['auto_add']) && !empty((array) $navMenuOption['auto_add']);
}
*/

/**
 * Check if we are front-end.
 *
 * @return bool
 */
function isFrontEnd(): bool
{
    // TODO: Anything else we need to add here?
    return !is_admin()
        && !isCli()
        && !isAjax()
        && !isWpRest();
}

/**
 * Check whether we're in login context.
 *
 * @return bool
 */
function isLogin(): bool
{
    if (
        strEndsWith(arrayGet('SCRIPT_NAME', $_SERVER), 'wp-login.php')
        || strEndsWith(arrayGet('PHP_SELF', $_SERVER), 'wp-login.php')
    ) {
        return true;
    }
    return false;
}

/**
 * Check if we are running Unit tests.
 *
 * @return bool
 */
function isTesting(): bool
{
    return (defined('WP_TESTS_ARE_RUNNING') && WP_TESTS_ARE_RUNNING === true);
}

/**
 * Check if this is a WP REST API request.
 *
 * @return bool
 */
function isWpRest(): bool
{
    $restPrefix = rest_get_url_prefix();
    return substr($_SERVER['REQUEST_URI'], 1, strlen($restPrefix) ) === $restPrefix;
}

/**
 * Check if this is a WooCommerce enabled site. Must be run
 * afer hoook plugins_loaded is completed.
 *
 * @return bool
 */
function isWooCommerce(): bool
{       
    return class_exists('woocommerce');
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
 * Check whether this is an AJAX-request.
 *
 * @return bool
 */
function isAjax(): bool
{
    return (defined('DOING_AJAX') && DOING_AJAX);
}

/**
 * Get AJAX nonce.
 */
function getAjaxNonce(): string
{
  return wp_create_nonce(getAjaxNonceKey());
}

/**
 * Get ajax nonce key, generate one if it does not exist.
 *
 * @return string
 */
function getAjaxNonceKey(): string
{
    return generateRandomPermanentKey('ajax_nonce');
}

/**
 * Generate a random key stored in the database.
 *
 * @param string $name
 * @param null|int|string $blogId
 *
 * @return string
 */
function generateRandomPermanentKey(string $name, $blogId = null): string
{
    if (is_multisite() && is_numeric($blogId)) {
        $key = getBlogOption($blogId, $name);
    } elseif (is_multisite() && $blogId == 'site') {
        $key = getSiteOption($name);
    } else {
        $key = getOption($name);
    }
    if (!$key) {
        $key = generateRandomString(36);
        if (is_multisite() && is_numeric($blogId)) {
            updateBlogOption($blogId, $name, $key);
        } elseif (is_multisite() && $blogId == 'site') {
            updateSiteOption($name, $key);
        } else {
            updateOption($name, $key);
        }
    }
    return $key;
}

/**
 * Get item from array.
 *
 * @param $key
 * @param $array
 * @param mixed|false $defaultValue
 * @return false|mixed|null
 */
function arrayGet($key, $array, $defaultValue = false)
{
    if (!is_array($array)) {
        return null;
    }
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
 * @param string|int $postId
 * @param null|int $blogId
 *
 * @return string
 */
function getPostTitleByBlog($postId, ?int $blogId = null)
{
    if ($blogId) {
        switch_to_blog($blogId);
    }
    $title = get_the_title($postId);
    if ($blogId) {
        restore_current_blog();
    }
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
 * @param array $posts
 * @param null|int $blogId
 *
 * @return array
 */
function resolvePostIdsToTitleAndPostIdString($posts, ?int $blogId = null): array
{
    return array_map(function($postId) use ($blogId) {
        $title = getPostTitleByBlog($postId, $blogId);
        return $title ? $title . ' (' . $postId . ')' : $postId;
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
 * Get the blog ID of the main blog in Multisite network.
 *
 * @return int|null
 */
function getMainSiteBlogId()
{
    if (!is_multisite()) {
        return null;
    }
    return get_network()->site_id;
}

/**
 * Check whether a feature is available.
 *
 * @param string $feature
 * @return bool|null
 */
function featureIsAvailable(string $feature): ?bool
{
    switch ($feature) {
        case 'custom_text_domain_loader':
            return true;
        case 'prefetching':
            return true;
        case 'cf_image_resize':
            //return ( defined('SERVEBOLT_CF_IMAGE_RESIZE_ACTIVE') && SERVEBOLT_CF_IMAGE_RESIZE_ACTIVE === true ) || (getCloudflareImageResizeInstance())::resizingIsActive();
            return true;
    }
    return null;
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
            return getCloudflareImageResizeInstance();
        case 'asset_auto_version':
            $generalSettings = getGeneralSettingsInstance();
            return $generalSettings->assetAutoVersion();
    }
    return null;
}

/**
 * Get instance of "CloudflareImageResize".
 *
 * @return mixed
 */
function getCloudflareImageResizeInstance()
{
    return \Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize::resizingIsActive();
}

/**
 * Get instance of "GeneralSettings".
 *
 * @return mixed
 */
function getGeneralSettingsInstance()
{
    return \Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings::getInstance();
}

/**
 * Write to log.
 *
 * @param $log
 */
function writeLog($log)
{
    if (!isDebug()) {
        return;
    }
    if (is_scalar($log)) {
        error_log($log);
    } else {
        error_log(print_r($log, true));
    }
}

/**
 * Build markup for row in HTML Cache post exclude table.
 *
 * @param $postId
 * @param bool $echo
 *
 * @return false|string
 */
function htmlCacheExcludePostTableRowMarkup($postId, bool $echo = true)
{
    if (is_numeric($postId) && $post = get_post($postId)) {
        $title = get_the_title($postId);
        $url = get_permalink($postId);
        $editUrl = get_edit_post_link($postId);
        $isPost = true;
    } else {
        $title = false;
        $url = false;
        $isPost = false;
    }
    ob_start();
    ?>
    <tr class="exclude-item">
        <th scope="row" class="check-column">
            <label class="screen-reader-text" for="cb-select-<?php echo $postId; ?>">Select "<?php echo $isPost ? $title : $url; ?>"</label>
            <input type="hidden" class="exclude-item-input" value="<?php echo esc_attr($postId); ?>">
            <input id="cb-select-<?php echo $postId; ?>" type="checkbox">
        </th>
        <?php if ( $isPost ) : ?>
            <td class="column-post-id has-row-actions html-cache-exclude-item-column">
                <?php echo $postId; ?>
                <div class="row-actions">
                    <span class="trash"><a href="#" class="sb-remove-item-from-html-cache-post-exclude"><?php _e('Delete', 'servebolt-wp'); ?></a> | </span>
                    <span class="view"><a href="<?php echo esc_attr($url); ?>" target="_blank"><?php _e('View', 'servebolt-wp'); ?></a><?php if ($editUrl) echo ' | '; ?></span>
                    <?php if ($editUrl) : ?>
                        <span class="view"><a href="<?php echo $editUrl; ?>" target="_blank"><?php _e('Edit', 'servebolt-wp'); ?></a></span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="html-cache-exclude-item-column"><strong><?php echo $title; ?></strong></td>
        <?php else : ?>
            <td class="column-post-id has-row-actions html-cache-exclude-item-column" colspan="2">
                <?php echo $postId; ?> (<?php _e('Post does not exist.', 'servebolt-wp') ?>)
                <div class="row-actions">
                    <span class="trash"><a href="#" class="sb-remove-item-from-html-cache-post-exclude"><?php _e('Delete', 'servebolt-wp'); ?></a></span>
                </div>
            </td>
        <?php endif; ?>
        <td class="column-url" style="padding-left: 0;padding-top: 10px;padding-bottom: 10px;">
            <?php if ( $url ) : ?>
                <a href="<?php echo esc_attr($url); ?>" target="_blank"><?php echo $url; ?></a>
            <?php else: ?>
                <?php echo $url; ?>
            <?php endif; ?>

        </td>
    </tr>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    if ( ! $echo ) {
        return $html;
    }
    echo $html;
}

/**
 * Convert an array to a CSV-string.
 *
 * @param $array
 * @param string $glue
 *
 * @return string
 */
function formatArrayToCsv($array, string $glue = ','): string
{
    return implode($glue, $array);
}

/**
 * Check whether we are in Servebolt developer debug mode.
 *
 * @return bool
 */
function isDevDebug(): bool
{
    return apply_filters('sb_optimizer_is_dev_debug', (defined('SB_DEBUG') && SB_DEBUG === true) || (array_key_exists('debug', $_GET)));
}

/**
 * Join strings together in a natural readable way.
 *
 * @param array $list
 * @param string|null $conjunction
 * @param string $quotes
 *
 * @return string
 */
function naturalLanguageJoin(array $list, ?string $conjunction = null, ?string $quotes = '"'): string
{
    if (is_null($conjunction)) {
        $conjunction = 'and';
    }
    $last = array_pop($list);
    if ($list) {
        return $quotes . implode($quotes . ', ' . $quotes, $list) . $quotes . ' ' . $conjunction . ' ' . $quotes . $last . $quotes;
    }
    return $quotes . $last . $quotes;
}

/**
 * Whether we are in debug mode.
 *
 * @return bool
 */
function isDebug(): bool
{
    return (defined('WP_DEBUG') && WP_DEBUG === true) || array_key_exists('debug', $_GET);
}

/**
 * Get current version of the plugin.
 *
 * @param bool $ignoreBetaVersion
 * @return string
 */
function getCurrentPluginVersion(bool $ignoreBetaVersion = true): ?string
{
    static $version = null;

    if ($version === null) {
        if(!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $pluginData = get_plugin_data(SERVEBOLT_PLUGIN_FILE);
        $version = arrayGet('Version', $pluginData);
    }

    if (!$version) {
        return null;
    }
    if ($ignoreBetaVersion) {
        return preg_replace('/(.+)-(.+)/', '$1', $version);
    }
    return $version;
}

/**
 * Get current version of the plugin's database migration number.
 * 
 * Will first used the constant if there, and default back to plugin version.
 *
 * @return string/int
 */
function getCurrentDatabaseVersion()
{    
    return (defined('SERVEBOLT_PLUGIN_DB_VERSION'))? SERVEBOLT_PLUGIN_DB_VERSION : getCurrentPluginVersion(false) ;
}

/**
 * Get the version string to use for static assets in the Servebolt plugin.
 *
 * @param string $assetSrc
 *
 * @return string
 *
 * @internal This function is intended for use in the Servebolt plugin and should not be used by others.
 */
function getVersionForStaticAsset(string $assetSrc): string
{
    $pluginVersion = apply_filters('sb_optimizer_static_asset_plugin_version', getCurrentPluginVersion(false));

    // Fallback to using `filemtime` if we could not resolve the current plugin version
    if ($pluginVersion === null) {
        $filemtime = filemtime($assetSrc);

        // If even `filemtime` bails out make sure the asset is cache busted by using the current unix timestamp
        if ($filemtime === false) {
            return (string) time();
        }

        return (string) $filemtime;
    }

    return $pluginVersion;
}

/**
 * Check whether the current user is a superadmin.
 *
 * @return bool
 */
function isSuperadmin(): bool
{
    return requireSuperadmin(true);
}

/**
 * Require the user to be a super admin.
 *
 * @param bool $returnBoolean
 * @return bool
 */
function requireSuperadmin(bool $returnBoolean = false)
{
    if (!is_multisite() || !is_super_admin()) {
        if ($returnBoolean) {
            return false;
        }
        wp_die();
    }
    if ($returnBoolean) {
        return true;
    }
}

/**
 * Check if a string ends with a substring.
 *
 * @param string $haystack
 * @param string $needle
 * @param bool $php8Fallback
 * @return bool
 */
function strEndsWith(string $haystack, string $needle, bool $php8Fallback = true): bool
{
    if (function_exists('str_ends_with') && $php8Fallback) {
        return str_ends_with($haystack, $needle);
    }
    $length = mb_strlen($needle);
    if(!$length) {
        return true;
    }
    return mb_substr($haystack, -$length) === $needle;
}

/**
 * Check if a string contains substring.
 *
 * @param string $haystack
 * @param string $needle
 * @param bool $php8Fallback
 * @return bool
 */
function strContains(string $haystack, string $needle, bool $php8Fallback = true): bool
{
    if( $haystack == null || $needle == null ) return false;

    if (function_exists('str_contains') && $php8Fallback) {
        return str_contains($haystack, $needle);
    }
    return strpos($haystack, $needle) !== false;
}

/**
 * Check if current request is through Accelerated Domains.
 *
 * @return bool
 */
function isAcd(): bool
{
    $allHeaders = getallheaders();
    $isAcd = is_array($allHeaders) && arrayGet('x-acd-proxy', $allHeaders) === 'active';
    return apply_filters('sb_optimizer_is_accelerated_domains', $isAcd);
}

/**
 * Check if the site is hosted at Servebolt.
 *
 * @return bool
 */
function isHostedAtServebolt(): bool
{
    $isHostedAtServebolt = false;
    $context = null;
    if (defined('HOST_IS_SERVEBOLT_OVERRIDE') && is_bool(HOST_IS_SERVEBOLT_OVERRIDE)) {
        $isHostedAtServebolt = HOST_IS_SERVEBOLT_OVERRIDE;
        $context = 'override';
    } elseif (arrayGet('SERVER_ADMIN', $_SERVER) === 'support@servebolt.com') {
        $isHostedAtServebolt = true;
        $context = 'server_admin_check';
    } elseif (
        strEndsWith(arrayGet('HOSTNAME', $_SERVER), 'servebolt.com')
        || strEndsWith(arrayGet('HOSTNAME', $_SERVER), 'servebolt.cloud')
    ) {
        $isHostedAtServebolt = true;
        $context = 'hostname_check';
    } elseif (isCli() && file_exists('/etc/bolt-release')) {
        $isHostedAtServebolt = true;
        $context = 'file_exist_check';
    }
    return apply_filters('sb_optimizer_is_hosted_at_servebolt', $isHostedAtServebolt, $context);
}

/**
 * Get blog name.
 *
 * @param int $blogId
 *
 * @return bool|string
 */
function getBlogName($blogId)
{
    $currentBlogDetails = get_blog_details( [ 'blog_id' => $blogId ] );
    return $currentBlogDetails ? $currentBlogDetails->blogname : false;
}

/**
 * Delete blog option.
 *
 * @param $blogId
 * @param $option
 * @param bool $assertUpdate
 *
 * @return mixed
 */
function deleteBlogOption($blogId, $option, bool $assertUpdate = true)
{
    $result = delete_blog_option($blogId, getOptionName($option));
    if ($assertUpdate) {
        return is_null(get_blog_option($blogId, getOptionName($option), null));
    }
    return $result;
}

/**
 * Get all sites in multisite-network.
 *
 * @return mixed|void
 */
function getSites()
{
    if (function_exists('get_sites')) {
        return apply_filters('sb_optimizer_site_iteration', get_sites());
    }
    return null;
}

/**
 * Count sites in multisite-network.
 *
 * @return int
 */
function countSites(): int
{
    $sites = getSites();
    return is_array($sites) ? count($sites) : 0;
}

/**
 * Execute function closure for given in multisite-network.
 *
 * @param $function
 * @param $blogId
 * @return void
 */
function runForSite($function, $blogId)
{
    if (!is_multisite()) {
        return false;
    }
    switch_to_blog($blogId);
    $function();
    restore_current_blog();
    return true;
}

/**
 * Execute function closure for each site in multisite-network.
 *
 * @param $function
 * @param bool $runBlogSwitch
 *
 * @return bool
 */
function iterateSites($function, bool $runBlogSwitch = false): bool
{
    if (!is_multisite()) {
        return false;
    }
    $sites = getSites();
    if (is_array($sites)) {
        foreach ($sites as $site) {
            if ($runBlogSwitch) {
                switch_to_blog($site->blog_id);
            }
            $function($site);
            if ($runBlogSwitch) {
                restore_current_blog();
            }
        }
        return true;
    }
    return false;
}

/**
 * Update blog option.
 *
 * @param $blogId
 * @param string $optionName
 * @param mixed $value
 * @param bool $assertUpdate
 * @return bool
 */
function updateBlogOption($blogId, string $optionName, $value = '', bool $assertUpdate = true): bool
{
    $fullOptionName = getOptionName($optionName);
    $result = update_blog_option($blogId, $fullOptionName, $value);
    if ($assertUpdate && !$result) {
        $currentValue = getBlogOption($blogId, $optionName);
        return ($currentValue == $value);
    }
    return true;
}

/**
 * Get blog option.
 *
 * @param $blogId
 * @param string $optionName
 * @param mixed $default
 *
 * @return mixed
 */
function getBlogOption($blogId, string $optionName, $default = null)
{
    $fullOptionName = getOptionName($optionName);
    $value = get_blog_option($blogId, $fullOptionName, $default);
    return apply_filters('sb_optimizer_get_blog_option_' . $fullOptionName, $value, $blogId);
}

/**
 * Delete option.
 *
 * @param string $option
 * @param bool $assertUpdate
 *
 * @return bool
 */
function deleteOption(string $option, bool $assertUpdate = true)
{
    $result = delete_option(getOptionName($option));
    if ($assertUpdate) {
        return is_null(get_option(getOptionName($option), null));
    }
    return $result;
}

/**
 * Add or update a WordPress-option. The option will _not_ auto-load.
 *
 * @param string $optionName
 * @param mixed $value
 * @param bool $assertUpdate
 * @param string $autoload
 * @return bool
 */
function addOrUpdateOption(string $optionName, $value = '', bool $assertUpdate = true, string $autoload = 'no'): bool
{
    if (add_option(getOptionName($optionName), $value, '', $autoload)) {
        if ($assertUpdate) {
            $currentValue = getOption($optionName);
            return ($currentValue == $value);
        }
        return true;
    }
    return updateOption($optionName, $value, $assertUpdate);
}

/**
 * Added custom blog option add function that supports autoload-parameter.
 *
 * @param string|int $id
 * @param string $option
 * @param mixed $value
 * @param string $autoload
 * @return bool
 */
function addBlogOption($id, string $option, $value = '', string $autoload = 'no')
{
    $option = getOptionName($option);

    // From function "add_blog_option" in wp-includes/ms-blogs.php:403
    $id = (int) $id;

    if ( empty( $id ) ) {
        $id = get_current_blog_id();
    }

    if ( get_current_blog_id() == $id ) {
        return add_option( $option, $value, '', $autoload );
    }

    switch_to_blog( $id );
    $return = add_option( $option, $value, '', $autoload );
    restore_current_blog();

    return $return;
}

/**
 * Add or update blog option.
 *
 * @param int|string $blogId
 * @param string $optionName
 * @param mixed $value
 * @param bool $assertUpdate
 * @param string $autoload
 * @return bool
 */
function addOrUpdateBlogOption($blogId, string $optionName, $value = '', bool $assertUpdate = true, string $autoload = 'no'): bool
{
    $addAttempt = addBlogOption($blogId, $optionName, $value, $autoload);
    if ($addAttempt) {
        if ($assertUpdate) {
            $currentValue = getBlogOption($blogId, $optionName);
            return ($currentValue == $value);
        }
        return true;
    }
    return updateBlogOption($blogId, $optionName, $value, $assertUpdate);
}

/**
 * Update option.
 *
 * @param string $optionName
 * @param mixed $value
 * @param bool $assertUpdate
 * @return bool
 */
function updateOption(string $optionName, $value = '', bool $assertUpdate = true): bool
{
    $fullOptionName = getOptionName($optionName);
    $result = update_option($fullOptionName, $value);
    if ($assertUpdate && !$result) {
        $currentValue = getOption($optionName);
        return ($currentValue == $value);
    }
    return true;
}

/**
 * Get option.
 *
 * @param string $optionName
 * @param mixed $default
 *
 * @return mixed|void
 */
function getOption(string $optionName, $default = null)
{
    $fullOptionName = getOptionName($optionName);
    $value = get_option($fullOptionName, $default);
    return apply_filters('sb_optimizer_get_option_' . $fullOptionName, $value);
}

/**
 * Delete site option.
 *
 * @param string $option
 * @param bool $assertUpdate
 *
 * @return bool
 */
function deleteSiteOption(string $option, bool $assertUpdate = true)
{
    $result = delete_site_option(getOptionName($option));
    if ($assertUpdate) {
        return is_null(get_site_option(getOptionName($option), null));
    }
    return $result;
}

/**
 * Update site option.
 *
 * @param string $optionName
 * @param mixed $value
 * @param bool $assertUpdate
 *
 * @return bool
 */
function updateSiteOption(string $optionName, $value = '', bool $assertUpdate = true)
{
    $fullOptionName = getOptionName($optionName);
    $result = update_site_option($fullOptionName, $value);
    if ($assertUpdate && !$result) {
        $currentValue = getSiteOption($optionName);
        return ( $currentValue == $value );
    }
    return true;
}

/**
 * Get site option.
 *
 * @param string $optionName
 * @param mixed $default
 *
 * @return mixed|void
 */
function getSiteOption(string $optionName, $default = null)
{
    $fullOptionName = getOptionName($optionName);
    $value = get_site_option($fullOptionName, $default);
    return apply_filters('sb_optimizer_get_site_option_' . $fullOptionName, $value);
}

/**
 * A function that will store the option at the right place (in current blog or a specified blog).
 *
 * @param null|int $blogId
 * @param string $optionName
 * @param mixed $value
 * @param bool $assertUpdate
 * @return bool
 */
function smartAddOrUpdateOption(?int $blogId = null, string $optionName = '', $value = '', bool $assertUpdate = true): bool
{
    if (is_numeric($blogId)) {
        $result = addOrUpdateBlogOption($blogId, $optionName, $value, $assertUpdate);
    } else {
        $result = addOrUpdateOption($optionName, $value, $assertUpdate);
    }
    return $result;
}

/**
 * A function that will store the option at the right place (in current blog or a specified blog).
 *
 * @param null|int $blogId
 * @param string $optionName
 * @param mixed $value
 * @param bool $assertUpdate
 * @return bool
 */
function smartUpdateOption(?int $blogId = null, string $optionName = '', $value = '', bool $assertUpdate = true): bool
{
    if (is_numeric($blogId)) {
        $result = updateBlogOption($blogId, $optionName, $value, $assertUpdate);
    } else {
        $result = updateOption($optionName, $value, $assertUpdate);
    }
    return $result;
}

/**
 * A function that will delete the option at the right place (in current blog or a specified blog).
 *
 * @param int|null $blogId
 * @param string $optionName
 * @param bool $assertUpdate
 *
 * @return bool|mixed
 */
function smartDeleteOption(?int $blogId = null, string $optionName = '', bool $assertUpdate = true)
{
    if (is_numeric($blogId)) {
        $result = deleteBlogOption($blogId, $optionName, $assertUpdate);
    } else {
        $result = deleteOption($optionName, $assertUpdate);
    }
    return $result;
}

/**
 * Check if a table exists.
 *
 * @param string $tableName
 * @return bool
 */
function tableExists(string $tableName): bool
{
    global $wpdb;
    return in_array($tableName,
        $wpdb->get_col(
            $wpdb->prepare('SHOW TABLES LIKE %s', $tableName),
            0),
        true);
}

/**
 * Check if a table has a given index.
 *
 * @param string $tableName
 * @param string $indexName
 * @return bool
 */
function tableHasIndex(string $tableName, string $indexName): bool
{
    global $wpdb;
    $indexes  = $wpdb->get_results( "SHOW INDEX FROM {$tableName}" );
    foreach ($indexes as $index) {
        if ($index->Table == $tableName && $index->Key_name == $indexName) {
            return true;
        }
    }
    return false;
}

/**
 * Check if a table has a given index.
 *
 * @param string $tableName
 * @param string $indexName
 * @return bool
 */
function tableHasColumn(string $tableName, string $columnName): bool
{
    global $wpdb;
    $col  = $wpdb->get_results( "SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'" );

    if ( $col == null || count($col) == 0) {
        return false;
    }
    return true;
}



/**
 * A function that will get the option at the right place (in current blog or a specified blog).
 *
 * @param int|null $blogId
 * @param string $optionName
 * @param bool $default
 *
 * @return mixed|void
 */
function smartGetOption(?int $blogId = null, string $optionName = '', $default = null)
{
    if (is_numeric($blogId)) {
        $result = getBlogOption($blogId, $optionName, $default);
    } else {
        $result = getOption($optionName, $default);
    }
    return $result;
}

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function woocommerceIsActive(): bool
{
    return class_exists('WooCommerce');
}

/**
 * Check if Action Scheduler is active.
 *
 * @return bool
 */
function actionSchedulerIsActive(): bool
{
    return class_exists('ActionScheduler');
}

/**
 * Check whether plugin WP Rocket is active.
 *
 * @return bool
 */
function wpRocketIsActive(): bool
{
    return defined('WP_ROCKET_VERSION');
}

/**
 * Check whether plugin Yoast SEO Premium is active.
 *
 * @return bool
 */
function yoastSeoPremiumIsActive(): bool
{
    if (!function_exists('is_plugin_active')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    return is_plugin_active('wordpress-seo-premium/wp-seo-premium.php');
}

/**
 * Check whether plugin Easy Digital Downloads is active.
 *
 * @return bool
 */
function easyDigitalDownloadsIsActive(): bool
{
    if (!function_exists('is_plugin_active')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    return is_plugin_active('easy-digital-downloads/easy-digital-downloads.php');
}

/**
 * Check whether plugin Jetpack is active.
 *
 * @return bool
 */
function jetpackIsActive(): bool
{
    if (!function_exists('is_plugin_active')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    return is_plugin_active('jetpack/jetpack.php');
}

/**
 * Instantiate the filesystem class
 *
 * @param bool $ensureConstantsAreSet
 * @return object WP_Filesystem_Direct instance
 */
function wpDirectFilesystem(bool $ensureConstantsAreSet = true): object
{
    if ($ensureConstantsAreSet || (defined('WP_TESTS_ARE_RUNNING') && WP_TESTS_ARE_RUNNING === true)) {
        // Set the permission constants if not already set.
        if (!defined('FS_CHMOD_DIR')) {
            define('FS_CHMOD_DIR', (fileperms(ABSPATH) & 0777 | 0755));
        }
        if (!defined('FS_CHMOD_FILE')) {
            define('FS_CHMOD_FILE', (fileperms(ABSPATH . 'index.php') & 0777 | 0644));
        }
    }
    require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
    return new \WP_Filesystem_Direct(new \StdClass());
}

/**
 * Get the path of the "wp-config.php"-file.
 *
 * @return string|null
 */
function getWpConfigPath(): ?string
{
    if (file_exists(ABSPATH . 'wp-config.php')) {
        $path = ABSPATH . 'wp-config.php';
    } elseif (
        @file_exists(dirname(ABSPATH) . '/wp-config.php')
        && !@file_exists(dirname(ABSPATH) . '/wp-settings.php')
    ) {
        $path = dirname(ABSPATH) . '/wp-config.php';
    } else {
        $path = null;
    }

    return apply_filters('sb_optimizer_wp_config_path', $path);
}

/**
 * This is a clone of the function "wp_calculate_image_sizes" in the WP core-files.
 * @param $size
 * @param null $image_src
 * @param null $image_meta
 * @param int $attachment_id
 * @return mixed|void
 */
function sbCalculateImageSizes( $size, $image_src = null, $image_meta = null, $attachment_id = 0 )
{
    $width = 0;

    if (is_array($size)) {
        $width = absint($size[0]);
    } elseif (is_string($size)) {
        if (!$image_meta && $attachment_id) {
            $image_meta = wp_get_attachment_metadata($attachment_id);
        }

        if (is_array($image_meta)) {
            $size_array = _wp_get_image_size_from_meta($size, $image_meta);
            if ($size_array) {
                $width = absint($size_array[0]);
            }
        }
    }

    /**
     * Filters the output of 'sbCalculateImageSizes()'.
     *
     * @since 4.4.0
     *
     * @param int          $width         The width of the image.
     * @param string|int[] $size          Requested image size. Can be any registered image size name, or
     *                                    an array of width and height values in pixels (in that order).
     * @param string|null  $image_src     The URL to the image file or null.
     * @param array|null   $image_meta    The image meta data as returned by wp_get_attachment_metadata() or null.
     * @param int          $attachment_id Image attachment ID of the original image or 0.
     */
    return apply_filters('sb_calculate_image_sizes', $width, $size, $image_src, $image_meta, $attachment_id);
}

/**
 * Override an option value.
 *
 * @param string $optionName
 * @param mixed $overrideValue
 */
function setOptionOverride(string $optionName, $overrideValue): void
{
    if (!is_callable($overrideValue)) {
        $overrideValue = function() use ($overrideValue) {
            return $overrideValue;
        };
    }
    add_filter('pre_option_' . getOptionName($optionName), $overrideValue);
}

/**
 * Clear options value override.
 *
 * @param string $optionName
 * @param null $closureOrFunctionName
 */
function clearOptionsOverride(string $optionName, $closureOrFunctionName = null): void
{
    $key = 'pre_option_' . getOptionName($optionName);
    if ($closureOrFunctionName) {
        remove_filter($key, $closureOrFunctionName);
    } else {
        remove_all_filters($key);
    }
}

/**
 * Listen for changes to one or multiple option values.
 *
 * @param string|array $optionNameOrNames
 * @param string|callable $closureOrAction
 * @param bool $serveboltOption
 * @param bool $strictComparison
 */
function listenForOptionChange($optionNameOrNames, $closureOrAction, bool $serveboltOption = true, bool $strictComparison = true): void
{
    if (!is_array($optionNameOrNames)) {
        $optionNameOrNames = [$optionNameOrNames];
    }
    foreach ($optionNameOrNames as $optionName) {
        $hookOptionName = $serveboltOption ? getOptionName($optionName) : $optionName;
        add_filter('pre_update_option_' . $hookOptionName, function ($newValue, $oldValue) use ($closureOrAction, $optionName, $strictComparison) {
            if ($strictComparison) {
                $didChange = $newValue !== $oldValue;
            } else {
                $didChange = $newValue != $oldValue;
            }
            if ($didChange) {
                if (is_callable($closureOrAction)) {
                    $closureOrAction($newValue, $oldValue, $optionName);
                } else {
                    do_action('servebolt_' . $closureOrAction, $newValue, $oldValue, $optionName);
                }
            }
            return $newValue;
        }, 10, 2);
    }
}

/**
 * Check if we should skip reacting on event listen for certain option name.
 *
 * @param string $optionName
 * @param bool $deleteFromListOnSkip
 * @return bool
 */
function shouldSkipEventListen(string $optionName, bool $deleteFromListOnSkip = true): bool
{
    $key = 'sb_optimizer_option_event_listen_skip';
    if (array_key_exists($key, $GLOBALS)) {
        $shouldSkip = in_array($optionName, $GLOBALS[$key]);
        if ($deleteFromListOnSkip) {
            $GLOBALS[$key] = array_filter($GLOBALS[$key], function($item) use ($optionName) {
                return $item != $optionName;
            });
        }
        return $shouldSkip;
    }
    return false;
}

/**
 * Generate random integer.
 *
 * @param int $min
 * @param int $max
 * @return int
 */
function generateRandomInteger(int $min, int $max): int
{
    if (function_exists('mt_rand')) {
        return mt_rand($min, $max);
    }
    return rand($min, $max);
}

/**
 * Skip reacting on event listen for certain option name.
 *
 * @param string $optionName
 */
function skipNextListen($optionName): void
{
    $key = 'sb_optimizer_option_event_listen_skip';
    $toSkip = array_key_exists($key, $GLOBALS) ? $GLOBALS[$key] : [];
    if (!in_array($optionName, $toSkip)) {
        $toSkip[] = $optionName;
    }
    $GLOBALS[$key] = $toSkip;
}

/**
 * Listen for updates to one or multiple site options.
 *
 * @param string|array $optionNameOrNames
 * @param string|callable $closureOrActionOrFilter
 * @param string string $type
 * @return void
 */
function listenForCheckboxSiteOptionUpdates($optionNameOrNames, $closureOrActionOrFilter, string $type = 'action'): void
{
    listenForCheckboxOptionUpdates($optionNameOrNames, $closureOrActionOrFilter, $type, 'pre_update_site_option_');
}

/**
 * Listen for updates to one or multiple options.
 *
 * @param string|array $optionNameOrNames
 * @param string|callable $closureOrActionOrFilter
 * @param string $type
 * @param string $filterPrefix
 * @return void
 */
function listenForCheckboxOptionUpdates($optionNameOrNames, $closureOrActionOrFilter, string $type = 'action', string $filterPrefix = 'pre_update_option_'): void
{
    $type = $type === 'action' ? $type : 'filter';
    if (!is_array($optionNameOrNames)) {
        $optionNameOrNames = [$optionNameOrNames];
    }
    foreach ($optionNameOrNames as $optionName) {
        add_filter($filterPrefix . getOptionName($optionName), function ($newValue, $oldValue) use ($closureOrActionOrFilter, $optionName, $type) {
            if (shouldSkipEventListen($optionName)) {
                return $newValue;
            }
            $wasActive = checkboxIsChecked($oldValue);
            $isActive = checkboxIsChecked($newValue);
            $didChange = $wasActive !== $isActive;
            if (is_callable($closureOrActionOrFilter)) {
                $returnValue = $closureOrActionOrFilter($wasActive, $isActive, $didChange, $optionName);
                if ($type === 'filter') {
                    return $returnValue;
                }
            } else {
                if ($type === 'action') {
                    do_action('servebolt_' . $closureOrActionOrFilter, $wasActive, $isActive, $didChange, $optionName);
                } else {
                    return apply_filters('servebolt_' . $closureOrActionOrFilter, $wasActive, $isActive, $didChange, $optionName);
                }
            }
            return $newValue;
        }, 10, 2);
    }
}

/**
 * Listen for changes to one or multiple options.
 *
 * @param string|array $optionNameOrNames
 * @param string|callable $closureOrAction
 */
function listenForCheckboxOptionChange($optionNameOrNames, $closureOrAction): void
{
    if (!is_array($optionNameOrNames)) {
        $optionNameOrNames = [$optionNameOrNames];
    }
    foreach ($optionNameOrNames as $optionName) {
        add_filter('pre_update_option_' . getOptionName($optionName), function ($newValue, $oldValue) use ($closureOrAction, $optionName) {
            $wasActive = checkboxIsChecked($oldValue);
            $isActive = checkboxIsChecked($newValue);
            $didChange = $wasActive !== $isActive;
            if ($didChange) {
                if (is_callable($closureOrAction)) {
                    $closureOrAction($wasActive, $isActive, $optionName);
                } else {
                    do_action('servebolt_' . $closureOrAction, $wasActive, $isActive, $optionName);
                }
            }
            return $newValue;
        }, 10, 2);
    }
}

/**
 * Create a default value, both where the option is not present in the options-table, or if the value is empty.
 *
 * @param string $optionName
 * @param callable|mixed $callableOrDefaultValue
 */
function setDefaultOption(string $optionName, $callableOrDefaultValue): void
{
    if (!is_callable($callableOrDefaultValue)) {
        $callableOrDefaultValue = function() use ($callableOrDefaultValue) {
            return $callableOrDefaultValue;
        };
    }
    add_filter('default_option_' . getOptionName($optionName), $callableOrDefaultValue);
}

/**
 * Clear default value.
 *
 * @param string $optionName
 * @param null $closureOrFunctionName
 */
function clearDefaultOption(string $optionName, $closureOrFunctionName = null): void
{
    $key = 'default_option_' . getOptionName($optionName);
    if ($closureOrFunctionName) {
        remove_filter($key, $closureOrFunctionName);
    } else {
        remove_all_filters($key);
    }
}

/**
 * Redirect through JavaScript. Useful if headers are already sent.
 *
 * @param string $url
 */
function javascriptRedirect(string $url): void
{
    printf('<script> window.location = "%s"; </script>', $url);
}

/**
 * Override parent active menu item for child menu item (in WP Admin).
 *
 * @param string $childPage
 * @param string $parentPage
 */
function overrideParentMenuPage(string $childPage, string $parentPage): void
{
    add_filter('parent_file', function($parentFile) use ($childPage, $parentPage) {
        global $plugin_page;
        if ($childPage === $plugin_page) {
            $plugin_page = $parentPage;
        }
        return $parentFile;
    });
}

/**
 * Override menu title in WP Admin for given page.
 *
 * @param string $screen
 * @param string $overrideTitle
 */
function overrideMenuTitle(string $screen, string $overrideTitle): void
{
    add_filter('admin_title', function($admin_title, $title) use ($screen, $overrideTitle) {
        if (isScreen($screen)) {
            return $overrideTitle . ' ' . $admin_title;
        }
        return $admin_title;
    }, 10, 2);
}

/**
 * Get the URLs of all the sizes of an image.
 *
 * @param int $attachmentId
 * @return array|null
 */
function getAllImageSizesByImage(int $attachmentId): ?array
{
    if (!wp_attachment_is_image($attachmentId)) {
        return null;
    }
    $imageUrls = [];
    foreach (get_intermediate_image_sizes() as $size) {
        if ($imageUrl = wp_get_attachment_image_url($attachmentId, $size)) {
            $imageUrls[] = $imageUrl;
        }
    }
    $imageUrls = array_values(array_unique($imageUrls));
    return $imageUrls;
}

/**
 * Check if WP cron is disabled.
 *
 * @return bool
 */
function wpCronDisabled(): bool
{
    return apply_filters(
        'sb_optimizer_wp_cron_disabled',
        (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON)
    );
}

/**
 * Validate JSON string.
 *
 * @param string $allegedJson
 * @return bool
 */
function isValidJson(string $allegedJson): bool
{
    @json_decode($allegedJson);
    return (json_last_error() === JSON_ERROR_NONE);
}

/**
 * Convert object to array.
 *
 * @param object $object
 * @return array|mixed
 */
function convertObjectToArray(object $object)
{
    return json_decode(json_encode($object), true);
}

/**
 * Get hook for conditionals (is_[thing]()) befor sending headers. 
 * 
 * Pre 6.1 send_headers loads too early for to use conditional queries, thus wp is used.
 * send_headers pre 6.1 was not possible to use as the query object was not yet
 * created.
 * 
 * see: https://make.wordpress.org/core/2022/10/10/moving-the-send_headers-action-to-later-in-the-load/  
 * 
 * @return string
 **/
function getCondtionalHookPreHeaders()
{
    global $wp_version;
    return (version_compare($wp_version, '6.1') == -1 ) ? 'wp' : 'send_headers';
}

/**
 * Get the domain name from the current site.
 */
function getDomainNameOfWebSite()
{
    $blogId = null;
    if (is_multisite()) {
        $blogId = get_current_blog_id();
    }
    $url = get_site_url($blogId);
    return parse_url($url, PHP_URL_HOST);
}

/**
 * Double check the setup of the domain in WordPress and make sure it's properly
 * configured 
 */
function checkDomainIsSetupForServeboltCDN() : array
{
    $output = [
        'status'    => false,
        'cname'     => false,
        'a-record'  => false,
        'found'     => ''
    ];
    
    $host = parse_url( get_site_url(), PHP_URL_HOST  );
    $output['host'] = $host;
    $allowed_cnames = ['routing.serveboltcdn.com', 'routing.accelerateddomains.com'];
    $cname = dns_get_record($host, DNS_CNAME);
    if(isset($cname[0]['target']) && in_array($cname[0]['target'], $allowed_cnames)){
        $output['status'] = true;
        $output['cname'] = true;
        $output['found'] = $cname[0]['target'];
        return $output;
    }

    $allowed_ips = ['162.159.153.241', '162.159.152.23'];
    $arecord = dns_get_record($host, DNS_A);    
    if(isset($arecord[0]['ip']) && in_array($arecord[0]['ip'], $allowed_ips)){
        $output['a-record'] = true;
        $output['status'] = true;
        $output['found'] = $arecord[0]['ip'];       
    }
    return $output;
}

function convertOriginalUrlToString($originUrl)
{
    if($originUrl == '') {
        return null;
    }

    if(is_string($originUrl)) {
        return $originUrl;
    }

    if(is_array($originUrl) && !empty($originUrl['urls'])){
        return $originUrl['urls'][0];
    }

    if(is_array($originUrl) && !empty($originUrl[0])){
        return $originUrl[0];
    }

    return null;
}
/**
 * Check if a class is using a trait.
 *
 * @param $trait
 * @param $class
 * @return bool
 */
/*
function classUsesTrait($class, $trait)
{
    try {
        $traitsInUse = array_keys((new \ReflectionClass($class))->getTraits());
        return in_array($trait, $traitsInUse);
    } catch (\Exception $e) {
        return false;
    }
}
*/
