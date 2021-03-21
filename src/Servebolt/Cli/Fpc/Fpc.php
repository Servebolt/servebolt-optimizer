<?php

namespace Servebolt\Optimizer\Cli\Fpc;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\formatCommaStringToArray;
use function Servebolt\Optimizer\Helpers\iterateSites;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use function Servebolt\Optimizer\Helpers\resolvePostIdsToTitleAndPostIdString;
use function Servebolt\Optimizer\Helpers\formatArrayToCsv;
use function Servebolt\Optimizer\Helpers\nginxFpc;

/**
 * Class Fpc
 * @package Servebolt\Optimizer\Cli\Fpc
 */
class Fpc extends CliHelpers
{
    /**
     * Fpc constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt fpc status', [$this, 'command_nginx_fpc_status']);
        WP_CLI::add_command('servebolt fpc activate', [$this, 'command_nginx_fpc_enable']);
        WP_CLI::add_command('servebolt fpc deactivate', [$this, 'command_nginx_fpc_disable']);

        WP_CLI::add_command('servebolt fpc post-types get', [$this, 'command_nginx_fpc_get_cache_post_types']);
        WP_CLI::add_command('servebolt fpc post-types set', [$this, 'command_nginx_fpc_set_cache_post_types']);
        WP_CLI::add_command('servebolt fpc post-types clear', [$this, 'command_nginx_fpc_clear_cache_post_types']);

        WP_CLI::add_command('servebolt fpc excluded-posts get', [$this, 'command_nginx_fpc_get_excluded_posts']);
        WP_CLI::add_command('servebolt fpc excluded-posts set', [$this, 'command_nginx_fpc_set_excluded_posts']);
        WP_CLI::add_command('servebolt fpc excluded-posts clear', [$this, 'command_nginx_fpc_clear_excluded_posts']);
    }

    /**
     * Activate Servebolt Full Page Cache.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Activate on all sites in multisite.
     *
     * [--post-types=<post_types>]
     * : Comma separated list of post types to be activated.
     *
     * [--exclude=<ids>]
     * : Comma separated list of ids to exclude for full page caching. Will be omitted when using the --all flag since post IDs are relative to each site.
     *
     * [--status]
     * : Display status after command is executed.
     *
     * ## EXAMPLES
     *
     *     # Activate Servebolt Full Page Cache, but only for pages and posts
     *     wp servebolt fpc activate --post-types=post,page
     *
     *     # Activate Servebolt Full Page Cache for all public post types on all sites in multisite-network
     *     wp servebolt fpc activate --post-types=all --all
     *
     */
    public function command_nginx_fpc_enable($args, $assoc_args)
    {
        $this->nginx_fpc_control(true, $assoc_args);
        if (in_array('status', $assoc_args)) {
            $this->get_nginx_fpc_status($assoc_args, false);
        }
    }

    /**
     * Deactivate Servebolt Full Page Cache.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Activate on all sites in multisite.
     *
     * [--status]
     * : Display status after command is executed.
     *
     * ## EXAMPLES
     *
     *     # Deactivate Servebolt Full Page Cache
     *     wp servebolt fpc deactivate
     *
     *     # Deactivate Servebolt Full Page Cache for all sites in multisite-network
     *     wp servebolt fpc deactivate --all
     *
     */
    public function command_nginx_fpc_disable($args, $assoc_args)
    {
        $this->nginx_fpc_control(false, $assoc_args);
        if (in_array('status', $assoc_args)) {
            $this->get_nginx_fpc_status($assoc_args, false);
        }
    }

    /**
     * Return status of the Servebolt Full Page Cache.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Check status on all sites in multisite-network.
     *
     * ## EXAMPLES
     *
     *     wp servebolt fpc status
     *
     */
    public function command_nginx_fpc_status($args, $assoc_args)
    {
        if ($this->affectAllSites($assoc_args)) {
            $sitesStatus = [];
            iterateSites(function ($site) use (&$sitesStatus) {
                $sitesStatus[] = $this->get_nginx_fpc_status($site->blog_id);
            });
        } else {
            $sitesStatus[] = $this->get_nginx_fpc_status();
        }
        WP_CLI\Utils\format_items('table', $sitesStatus , array_keys(current($sitesStatus)));
    }

    /**
     * Get the post types that should be cached with Servebolt Full Page Cache.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Get post types on all sites in multisite
     *
     *
     * ## EXAMPLES
     *
     *     # Get post types to cache on all sites
     *     wp servebolt fpc post-types get --all
     *
     */
    public function command_nginx_fpc_get_cache_post_types($args, $assoc_args) {
        if ( $this->affectAllSites($assoc_args) ) {
            $sitesStatus = [];
            iterateSites(function ($site) use (&$sitesStatus) {
                $sitesStatus[] = $this->nginx_fpc_get_cache_post_types($site->blog_id);
            });
        } else {
            $sitesStatus[] = $this->nginx_fpc_get_cache_post_types();
        }
        WP_CLI\Utils\format_items('table', $sitesStatus , array_keys(current($sitesStatus)));
    }

    /**
     * Set the post types that should be cached with Servebolt Full Page Cache.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Set post types on all sites in multisite.
     *
     * [--all-post-types]
     * : Set cache for all post types. When present then --post-types will be omitted.
     *
     * [--post-types]
     * : The post types we would like to cache.
     *
     * ## EXAMPLES
     *
     *     # Activate cache for given post types on all sites
     *     wp servebolt fpc post-types set --post-types=page,post --all
     *
     *     # Activate cache for all post types on current site
     *     wp servebolt fpc post-types set --all-post-types
     *
     */
    public function command_nginx_fpc_set_cache_post_types($args, $assoc_args) {
        $allPostTypes = array_key_exists('all-post-types', $assoc_args);

        if ($allPostTypes) {
            $postTypes = ['all'];
        } else {
            $postTypesString = arrayGet('post-types', $assoc_args, '');
            $postTypes = formatCommaStringToArray($postTypesString);
        }

        if (empty($postTypes)) {
            WP_CLI::error(__('No post types specified', 'servebolt-wp'));
        }

        if ( $this->affectAllSites($assoc_args) ) {
            iterateSites(function ($site) use ($postTypes) {
                $this->nginx_set_post_types($postTypes, $site->blog_id);
            });
        } else {
            $this->nginx_set_post_types($postTypes);
        }
    }

    /**
     * Clear post types that should be cached with Servebolt Full Page Cache.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Clear post types to cache on all sites in multisite.
     *
     * ## EXAMPLES
     *
     *     # Clear all post types to cache on all sites
     *     wp servebolt fpc post-types clear --all
     *
     *     # Clear all post types to cache
     *     wp servebolt fpc post-types clear
     *
     */
    public function command_nginx_fpc_clear_cache_post_types($args, $assoc_args) {
        if ($this->affectAllSites($assoc_args)) {
            iterateSites(function ($site) {
                $this->nginx_set_post_types([], $site->blog_id);
            });
        } else {
            $this->nginx_set_post_types([]);
        }
    }

    /**
     * Get the posts that should be excluded from the cache.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Get post to exclude from cachecache on all sites in multisite.
     *
     * [--extended]
     * : Display more details about the excluded posts.
     *
     * ## EXAMPLES
     *
     *     # Get posts to exclude from cache
     *     wp servebolt fpc excluded-posts get
     *
     *     # Get posts to exclude from cache on all sites in multisite-network.
     *     wp servebolt fpc excluded-posts get --all
     *
     */
    public function command_nginx_fpc_get_excluded_posts($args, $assoc_args)
    {
        $array = [];
        $extended = array_key_exists('extended', $assoc_args);
        if ($this->affectAllSites($assoc_args)) {
            iterateSites(function ($site ) use (&$array, $extended) {
                $array[] = $this->nginx_fpc_get_excluded_posts($site->blog_id, $extended);
            });
        } else {
            $array[] = $this->nginx_fpc_get_excluded_posts(false, $extended);
        }
        WP_CLI\Utils\format_items('table', $array, array_keys(current($array)));
    }

    /**
     * Set the posts that should be excluded from the cache.
     *
     * ## OPTIONS
     *
     * <post_ids>
     * : The posts to exclude from cache.
     *
     * ## EXAMPLES
     *
     *     # Exclude cache for posts with ID 1, 2 and 3
     *     wp servebolt fpc excluded-posts set 1,2,3
     *
     */
    public function command_nginx_fpc_set_excluded_posts($args, $assoc_args)
    {
        list($postIdsRaw) = $args;
        $this->nginx_set_exclude_ids($postIdsRaw);
    }

    /**
     * Clear the posts that was excluded from the cache.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Clear post to exclude from cachecache on all sites in multisite.
     *
     * ## EXAMPLES
     *
     *     # Clear all post types to cache on all sites
     *     wp servebolt fpc excluded-posts clear --all
     *
     *     # Clear all post types to cache
     *     wp servebolt fpc excluded-posts clear
     *
     */
    public function command_nginx_fpc_clear_excluded_posts($args, $assoc_args)
    {
        if ($this->affectAllSites($assoc_args)) {
            iterateSites(function ($site) {
                $this->nginx_set_exclude_ids(false, $site->blog_id);
            });
        } else {
            $this->nginx_set_exclude_ids(false);
        }
    }

    /**
     * Display Nginx status - which post types have cache active.
     *
     * @param bool $blogId
     *
     * @return array
     */
    private function get_nginx_fpc_status($blogId = false)
    {
        $status = nginxFpc()->fpc_is_active($blogId) ? 'Active' : 'Inactive';
        $postTypes = nginxFpc()->get_post_types_to_cache(true, true, $blogId);
        $enabledPostTypesString = $this->nginx_get_active_post_types_string($postTypes);
        $excludedPosts = nginxFpc()->get_ids_to_exclude_from_cache($blogId);
        $array = [];
        if ($blogId) {
            $array['URL'] = get_site_url($blogId);
        }
        return array_merge($array, [
            'Status' => $status,
            'Active post types' => $enabledPostTypesString,
            'Posts to exclude' => formatArrayToCsv($excludedPosts),
        ]);
    }

    /**
     * Get post types to cache with FPC.
     *
     * @param bool $blogId
     *
     * @return array
     */
    private function nginx_fpc_get_cache_post_types($blogId = false)
    {
        $postTypes = nginxFpc()->get_post_types_to_cache(true, true, $blogId);
        $enabledPostTypesString = $this->nginx_get_active_post_types_string($postTypes);
        $array = [];
        if ($blogId) {
            $array['URL'] = get_site_url($blogId);
        }
        return array_merge($array, [
            'Active post types' => $enabledPostTypesString,
        ]);
    }

    /**
     * Get the string displaying which post types are active in regards to Nginx caching.
     *
     * @param $postTypes
     *
     * @return string|void
     */
    private function nginx_get_active_post_types_string($postTypes)
    {

        // Cache default post types
        if (!is_array($postTypes) || empty($postTypes)) {
            return sprintf(__('Default [%s]', 'servebolt-wp'), nginxFpc()->get_default_post_types_to_cache('csv'));
        }

        // Cache all post types
        if (array_key_exists('all', $postTypes)) {
            return __('All', 'servebolt-wp');
        }
        return formatArrayToCsv($postTypes);
    }

    /**
     * Toggle cache active/inactive for site.
     *
     * @param $newCacheState
     * @param null $blogId
     */
    private function nginx_toggle_cache_for_blog($newCacheState, $blogId = null)
    {
        $url = get_site_url($blogId);
        $cacheActiveString = booleanToStateString($newCacheState);
        if ($cacheActiveString === nginxFpc()->fpc_is_active($blogId)) {
            WP_CLI::warning(sprintf( __('Full Page Cache already %s on site %s', 'servebolt-wp'), $cacheActiveString, $url ));
        } else {
            nginxFpc()->fpc_toggle_active($newCacheState, $blogId);
            WP_CLI::success(sprintf( __('Full Page Cache %s on site %s', 'servebolt-wp'), $cacheActiveString, $url ));
        }
    }

    /**
     * Prepare post types from argument if present. To be used when toggling Nginx FPC active/inactive.
     *
     * @param array $args
     *
     * @return array|bool
     */
    private function nginx_prepare_post_type_argument(array $args)
    {
        if (array_key_exists('post-types', $args)) {
            $postTypes = formatCommaStringToArray($args['post-types']);
            $postTypes = array_filter($postTypes, function ($postType) {
                return post_type_exists($postType);
            });
            if (!empty($postTypes)) {
                return $postTypes;
            }
        }
        return false;
    }

    /**
     * Enable/disable Nginx cache headers.
     *
     * @param bool $cacheActive
     * @param array $args
     */
    private function nginx_fpc_control(bool $cacheActive, array $args = [])
    {
        $affectAllBlogs = array_key_exists('all', $args);
        $postTypes = $this->nginx_prepare_post_type_argument($args);
        $excludeIds = arrayGet('exclude', $args);

        if (is_multisite() && $affectAllBlogs) {
            WP_CLI::line(__('Applying settings to all blogs', 'servebolt-wp'));
            iterateSites(function($site) use ($cacheActive, $postTypes) {
                $this->nginx_toggle_cache_for_blog($cacheActive, $site->blog_id);
                if ($postTypes) {
                    $this->nginx_set_post_types($postTypes, $site->blog_id);
                }
            });
            if ($excludeIds) {
                WP_CLI::warning(__('Exclude ids were not set since ids are relative to each site.', 'servebolt-wp'));
            }
        } else {
            $this->nginx_toggle_cache_for_blog($cacheActive);
            if ($postTypes) {
                $this->nginx_set_post_types($postTypes);
            }
            if ($excludeIds) {
                $this->nginx_set_exclude_ids($excludeIds);
            }
        }
    }

    /**
     * Set post types to cache.
     *
     * @param $postTypes
     * @param bool $blogId
     */
    private function nginx_set_post_types($postTypes, $blogId = false) {
        if ($postTypes === false) {
            $postTypes = [];
        }
        $allPostTypes = $this->nginx_get_all_post_types();
        $allPostTypesKeys = array_keys($allPostTypes);
        $postTypes = array_filter($postTypes, function($postType) use ($allPostTypesKeys) {
            return in_array($postType, $allPostTypesKeys) || $postType === 'all';
        });
        nginxFpc()->set_cacheable_post_types($postTypes, $blogId);
        if (empty($postTypes)) {
            if ($blogId) {
                WP_CLI::success(sprintf(__('Cache post type(s) cleared on site %s'), get_site_url($blogId)));
            } else {
                WP_CLI::success(sprintf(__('Cache post type(s) cleared'), get_site_url($blogId)));
            }
        } else {
            if ($blogId) {
                WP_CLI::success(sprintf(__('Cache post type(s) set to %s on site %s'), formatArrayToCsv($postTypes), get_site_url($blogId)));
            } else {
                WP_CLI::success(sprintf(__('Cache post type(s) set to %s'), formatArrayToCsv($postTypes)));
            }
        }
    }

    /**
     * Get all post types to be used in Nginx FPC context.
     *
     * @return array|bool
     */
    private function nginx_get_all_post_types()
    {
        $allPostTypes = nginxFpc()->get_available_post_types_to_cache(false);
        if (is_array($allPostTypes)) {
            return array_map(function($postType) {
                return isset($post_type->name) ? $post_type->name : $postType;
            }, $allPostTypes);
        }
        return false;
    }

    /**
     * Set posts to be excluded from the Nginx FPC.
     *
     * @param $idsToExclude
     * @param bool|int $blogId
     */
    private function nginx_set_exclude_ids($idsToExclude, $blogId = false)
    {
        if (is_string($idsToExclude)) {
            $idsToExclude = formatCommaStringToArray($idsToExclude);
        }
        $alreadyExcluded = nginxFpc()->get_ids_to_exclude_from_cache($blogId);
        $clearAll = $idsToExclude === false;

        if ($clearAll) {
            nginxFpc()->set_ids_to_exclude_from_cache([], $blogId);
            WP_CLI::success(__('All excluded posts were cleared.', 'servebolt-wp'));
            return;
        } elseif (is_array($idsToExclude) && empty($idsToExclude)) {
            WP_CLI::warning(__('No ids were specified.', 'servebolt-wp'));
            return;
        }

        $alreadyAdded = [];
        $wasExcluded = [];
        $invalidId = [];
        foreach ($idsToExclude as $id) {
            if (get_post_status($id) === false) {
                $invalidId[] = $id;
            } elseif (!in_array($id, $alreadyExcluded)) {
                $wasExcluded[] = $id;
                $alreadyAdded[] = $id;
            } else {
                $alreadyAdded[] = $id;
            }
        }
        nginxFpc()->set_ids_to_exclude_from_cache($alreadyExcluded, $blogId);

        if (!empty($alreadyAdded)) {
            WP_CLI::warning(sprintf(__('The following ids were already excluded: %s', 'servebolt-wp'), formatArrayToCsv($alreadyAdded)));
        }

        if (!empty($invalidId)) {
            WP_CLI::warning(sprintf(__('The following ids were invalid: %s', 'servebolt-wp'), formatArrayToCsv($invalidId)));
        }

        if (!empty($wasExcluded)) {
            WP_CLI::success(sprintf(__('Added %s to the list of excluded ids', 'servebolt-wp'), formatArrayToCsv($wasExcluded)));
        } else {
            WP_CLI::warning(__('No action was made.', 'servebolt-wp'));
        }
    }

    /**
     * Get posts excluded from FPC for specified site.
     *
     * @param bool|int $blogId
     * @param bool $extended
     *
     * @return array
     */
    private function nginx_fpc_get_excluded_posts($blogId = false, $extended = false) {
        if ($blogId) {
            $array = ['Blog' => $blogId];
        } else {
            $array = [];
        }
        $alreadyExcluded = nginxFpc()->get_ids_to_exclude_from_cache($blogId);
        if ( $extended ) {
            $alreadyExcluded = formatArrayToCsv(resolvePostIdsToTitleAndPostIdString($alreadyExcluded), ', ');
        } else {
            $alreadyExcluded = formatArrayToCsv($alreadyExcluded);
        }
        $array['Excluded posts'] = $alreadyExcluded;
        return $array;
    }
}
