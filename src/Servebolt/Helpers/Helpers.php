<?php

namespace Servebolt\Optimizer\Helpers;

use Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings;
use Servebolt\Optimizer\FullPageCache\FullPageCacheAuthHandling;

/**
 * @param $templatePath
 * @return string|null
 */
function resolveViewPath($templatePath): ?string
{
    $templatePath = str_replace('.', '/', $templatePath);
    $suffix = '.php';
    $basePath = SERVEBOLT_PLUGIN_PSR4_PATH . 'Views/';
    $filePath = $basePath . $templatePath . $suffix;
    if (file_exists($filePath) && is_readable($filePath)) {
        return $filePath;
    }
    return null;
}

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
 * @param $length
 *
 * @return string
 */
function generateRandomString($length): string
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
 * @param $value
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
 * Check if current user has capability, abort if not.
 *
 * @param bool $returnResult
 * @param string $capability
 *
 * @return mixed
 */
function ajaxUserAllowed(bool $returnResult = false, $capability = 'manage_options')
{
    $userCan = apply_filters('sb_optimizer_ajax_user_allowed', current_user_can($capability));
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
 * @param $closure
 * @param bool $includeUl
 *
 * @return string
 */
function createLiTagsFromArray($iterator, $closure = false, bool $includeUl = true): string
{
    $markup = '';
    if ($includeUl) {
        $markup .= '<ul>';
    }
    array_map(function($item) use (&$markup, $closure) {
        $markup .= '<li>' . ( is_callable($closure) ? $closure($item) : $item ) . '</li>';
    }, $iterator);
    if ($includeUl) {
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
 * Get a link to the Servebolt admin panel.
 *
 * @return string
 */
function getServeboltAdminUrl() :string
{
    if (!function_exists('get_home_path')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $webRootPath = isDevDebug() ? '/kunder/serveb_1234/custom_4321/public' : get_home_path();
    if (preg_match("@kunder/[a-z_0-9]+/[a-z_]+(\d+)/@", $webRootPath, $matches) && isset($matches[1])) {
        return 'https://admin.servebolt.com/siteredirect/?site='. $matches[1];
    }
    return false;
}

/**
 * Clean the cookies we have been setting.
 */
function clearNoCacheCookie(): void
{
    (FullPageCacheAuthHandling::getInstance())->clearNoCacheCookie();
}

/**
 * Check the cookies we have been set.
 */
function cacheCookieCheck(): void
{
    (FullPageCacheAuthHandling::getInstance())->cacheCookieCheck();
}

/**
 * Get taxonomy singular name by term.
 *
 * @param int $termId
 * @return string
 */
function getTaxonomySingularName(int $termId): string
{
    if ($term = get_term($termId)) {
        if ($taxonomyObject = get_taxonomy($term->taxonomy)) {
            if (isset($taxonomyObject->labels->singular_name) && $taxonomyObject->labels->singular_name) {
                return mb_strtolower($taxonomyObject->labels->singular_name);
            }
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
        'record_max_num_pages_nonce',

        // Legacy
        'sb_optimizer_record_max_num_pages',

        // Wipe encryption keys
        'mcrypt_key',
        'openssl_key',
        'openssl_iv',

        // CF Image resizing
        'cf_image_resizing',

        // Wipe Cache purge-related options
        'cache_purge_switch',
        'cache_purge_auto',
        'cache_purge_driver',
        'cf_switch',
        'cf_zone_id',
        'cf_auth_type',
        'cf_email',
        'cf_api_key',
        'cf_api_token',
        'cf_items_to_purge',
        'cf_cron_purge',
        'queue_based_cache_purge',

        // Accelerated Domains
        'acd_switch',
        'acd_minify_switch',

        // Wipe SB FPC-related options
        'fpc_switch',
        'fpc_settings',
        'fpc_exclude',
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
 * Check if we are running Unit tests.
 *
 * @return bool
 */
function isTesting(): bool
{
    return (defined('WP_TESTS_IS_RUNNING') && WP_TESTS_IS_RUNNING === true);
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
 * Get ajax nonce key, generate one if it does not exists.
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
 * @param bool $defaultValue
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
 * @param $postId
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
 * @param $posts
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
 * Check whether a feature is available.
 *
 * @param string $feature
 * @return bool|null
 */
function featureIsAvailable(string $feature): ?bool
{
    switch ($feature) {
        case 'cf_image_resize':
            //return ( defined('SERVEBOLT_CF_IMAGE_RESIZE_ACTIVE') && SERVEBOLT_CF_IMAGE_RESIZE_ACTIVE === true ) || ( CloudflareImageResize::getInstance() )->resizingIsActive();
            return true;
            break;
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
            return ( CloudflareImageResize::getInstance() )->resizingIsActive();
        case 'asset_auto_version':
            $generalSettings = GeneralSettings::getInstance();
            return $generalSettings->assetAutoVersion();
        /*
        case 'cf_cache':
            return true;
            break;
        */
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
    if (!isDebug()) {
        return;
    }
    if (is_array($log) || is_object($log)) {
        error_log(print_r($log, true));
    } else {
        error_log($log);
    }
}

/**
 * Build markup for row in FPC post exclude table.
 *
 * @param $postId
 * @param bool $echo
 *
 * @return false|string
 */
function fpcExcludePostTableRowMarkup($postId, $echo = true)
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
            <td class="column-post-id has-row-actions fpc-exclude-item-column">
                <?php echo $postId; ?>
                <div class="row-actions">
                    <span class="trash"><a href="#" class="sb-remove-item-from-fpc-post-exclude"><?php _e('Delete', 'servebolt-wp'); ?></a> | </span>
                    <span class="view"><a href="<?php echo esc_attr($url); ?>" target="_blank"><?php _e('View', 'servebolt-wp'); ?></a><?php if ($editUrl) echo ' | '; ?></span>
                    <?php if ($editUrl) : ?>
                        <span class="view"><a href="<?php echo $editUrl; ?>" target="_blank"><?php _e('Edit', 'servebolt-wp'); ?></a></span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="fpc-exclude-item-column"><strong><?php echo $title; ?></strong></td>
        <?php else : ?>
            <td class="column-post-id has-row-actions fpc-exclude-item-column" colspan="2">
                <?php echo $postId; ?> (<?php _e('Post does not exist.', 'servebolt-wp') ?>)
                <div class="row-actions">
                    <span class="trash"><a href="#" class="sb-remove-item-from-fpc-post-exclude"><?php _e('Delete', 'servebolt-wp'); ?></a></span>
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
function formatArrayToCsv($array, $glue = ','): string
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
    return ( defined('SB_DEBUG') && SB_DEBUG === true ) || ( array_key_exists('debug', $_GET ) );
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
    if(!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    $pluginData = get_plugin_data(SERVEBOLT_PLUGIN_FILE);
    $version = arrayGet('Version', $pluginData);
    if (!$version) {
        return null;
    }
    if ($ignoreBetaVersion) {
        return preg_replace('/(.+)-(.+)/', '$1', $version);
    }
    return $version;
}

/**
 * Require the user to be a super admin.
 */
function requireSuperadmin()
{
    if (!is_multisite() || ! is_super_admin()) {
        wp_die();
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
 * Check if the site is hosted at Servebolt.
 *
 * @return bool
 */
function isHostedAtServebolt(): bool
{
    $isHostedAtServebolt = false;
    $context = null;
    /*if (file_exists('/etc/bolt-release')) {
        $isHostedAtServebolt = true;
        $context = 'file';
    } else*/
    if (arrayGet('SERVER_ADMIN', $_SERVER) === 'support@servebolt.com') {
        $isHostedAtServebolt = true;
        $context = 'SERVER_ADMIN';
    } elseif (
        strEndsWith(arrayGet('HOSTNAME', $_SERVER), 'servebolt.com')
        || strEndsWith(arrayGet('HOSTNAME', $_SERVER), 'servebolt.cloud')
    ) {
        $isHostedAtServebolt = true;
        $context = 'HOSTNAME';
    } elseif (defined('HOST_IS_SERVEBOLT_OVERRIDE') && is_bool(HOST_IS_SERVEBOLT_OVERRIDE)) {
        $isHostedAtServebolt = HOST_IS_SERVEBOLT_OVERRIDE;
        $context = 'OVERRIDE';
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
    return apply_filters('sb_optimizer_site_iteration', get_sites());
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
 * @param $optionName
 * @param $value
 * @param bool $assertUpdate
 * @return bool
 */
function updateBlogOption($blogId, $optionName, $value, $assertUpdate = true): bool
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
 * @param $optionName
 * @param bool $default
 *
 * @return mixed
 */
function getBlogOption($blogId, $optionName, $default = null)
{
    $fullOptionName = getOptionName($optionName);
    $value = get_blog_option($blogId, $fullOptionName, $default);
    return apply_filters('sb_optimizer_get_blog_option_' . $fullOptionName, $value, $blogId);
}

/**
 * Delete option.
 *
 * @param $option
 * @param bool $assertUpdate
 *
 * @return bool
 */
function deleteOption($option, bool $assertUpdate = true)
{
    $result = delete_option(getOptionName($option));
    if ($assertUpdate) {
        return is_null(get_option(getOptionName($option), null));
    }
    return $result;
}

/**
 * Update option.
 *
 * @param $optionName
 * @param $value
 * @param bool $assertUpdate
 * @return bool
 */
function updateOption($optionName, $value, $assertUpdate = true): bool
{
    $fullOptionName = getOptionName($optionName);
    $result = update_option($fullOptionName, $value);
    if ($assertUpdate && !$result) {
        $currentValue = getOption($optionName);
        return ( $currentValue == $value );
    }
    return true;
}

/**
 * Get option.
 *
 * @param $optionName
 * @param bool $default
 *
 * @return mixed|void
 */
function getOption($optionName, $default = null)
{
    $fullOptionName = getOptionName($optionName);
    $value = get_option($fullOptionName, $default);
    return apply_filters('sb_optimizer_get_option_' . $fullOptionName, $value);
}

/**
 * Delete site option.
 *
 * @param $option
 * @param bool $assertUpdate
 *
 * @return bool
 */
function deleteSiteOption($option, bool $assertUpdate = true)
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
 * @param $optionName
 * @param $value
 * @param bool $assertUpdate
 *
 * @return bool
 */
function updateSiteOption($optionName, $value, $assertUpdate = true)
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
 * @param $optionName
 * @param bool $default
 *
 * @return mixed|void
 */
function getSiteOption($optionName, $default = null)
{
    $fullOptionName = getOptionName($optionName);
    $value = get_site_option($fullOptionName, $default);
    return apply_filters('sb_optimizer_get_site_option_' . $fullOptionName, $value);
}

/**
 * A function that will store the option at the right place (in current blog or a specified blog).
 *
 * @param null|int $blogId
 * @param $optionName
 * @param $value
 * @param bool $assertUpdate
 * @return bool
 */
function smartUpdateOption(?int $blogId = null, $optionName, $value, bool $assertUpdate = true): bool
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
 * @param $optionName
 * @param bool $assertUpdate
 *
 * @return bool|mixed
 */
function smartDeleteOption(?int $blogId = null, $optionName, bool $assertUpdate = true)
{
    if (is_numeric($blogId)) {
        $result = deleteBlogOption($blogId, $optionName, $assertUpdate);
    } else {
        $result = deleteOption($optionName, $assertUpdate);
    }
    return $result;
}

/**
 * A function that will get the option at the right place (in current blog or a specified blog).
 *
 * @param int|null $blogId
 * @param $optionName
 * @param bool $default
 *
 * @return mixed|void
 */
function smartGetOption(?int $blogId = null, $optionName, $default = null)
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
 * Check whether plugin WP Rocket is active.
 *
 * @return bool
 */
function wpRocketIsActive(): bool
{
    return defined('WP_ROCKET_VERSION');
}
