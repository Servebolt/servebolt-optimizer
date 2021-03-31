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
use function Servebolt\Optimizer\Helpers\fullPageCache;
use Servebolt\Optimizer\FullPageCache\FullPageCache;

/**
 * Class Fpc
 * @package Servebolt\Optimizer\Cli\Fpc
 */
class Fpc
{
    /**
     * Fpc constructor.
     */
    public function __construct()
    {
        // servebolt cache status
        // servebolt cache activate
        // servebolt cache deactivate


        // servebolt cache settings set post-types post,page,media
        // servebolt cache settings set post-types post,page,media
        // servebolt cache settings get post-types
        // servebolt cache settings clear post-types

        WP_CLI::add_command('servebolt fpc status', [$this, 'commandNginxFpcStatus']);
        WP_CLI::add_command('servebolt fpc activate', [$this, 'commandNginxFpcEnable']);
        WP_CLI::add_command('servebolt fpc deactivate', [$this, 'commandNginxFpcDisable']);

        WP_CLI::add_command('servebolt fpc post-types get', [$this, 'commandNginxFpcGetCachePostTypes']);
        WP_CLI::add_command('servebolt fpc post-types set', [$this, 'commandNginxFpcSetCachePostTypes']);
        WP_CLI::add_command('servebolt fpc post-types clear', [$this, 'commandNginxFpcClearCachePostTypes']);

        WP_CLI::add_command('servebolt fpc excluded-posts get', [$this, 'commandNginxFpcGetExcludedPosts']);
        WP_CLI::add_command('servebolt fpc excluded-posts set', [$this, 'commandNginxFpcSetExcludedPosts']);
        WP_CLI::add_command('servebolt fpc excluded-posts clear', [$this, 'commandNginxFpcClearExcludedPosts']);
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
    public function commandNginxFpcEnable($args, $assocArgs)
    {
        $this->nginxFpcControl(true, $assocArgs);
        if (in_array('status', $assocArgs)) {
            $this->getNginxFpcStatus($assocArgs, false);
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
    public function commandNginxFpcDisable($args, $assocArgs)
    {
        $this->nginxFpcControl(false, $assocArgs);
        if (in_array('status', $assocArgs)) {
            $this->getNginxFpcStatus($assocArgs, false);
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
    public function commandNginxFpcStatus($args, $assocArgs)
    {
        if (CliHelpers::affectAllSites($assocArgs)) {
            $sitesStatus = [];
            iterateSites(function ($site) use (&$sitesStatus) {
                $sitesStatus[] = $this->getNginxFpcStatus($site->blog_id);
            });
        } else {
            $sitesStatus[] = $this->getNginxFpcStatus();
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
    public function commandNginxFpcGetCachePostTypes($args, $assocArgs)
    {
        if (CliHelpers::affectAllSites($assocArgs)) {
            $sitesStatus = [];
            iterateSites(function ($site) use (&$sitesStatus) {
                $sitesStatus[] = $this->nginxFpcGetCachePostTypes($site->blog_id);
            });
        } else {
            $sitesStatus[] = $this->nginxFpcGetCachePostTypes();
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
    public function commandNginxFpcSetCachePostTypes($args, $assocArgs)
    {
        $allPostTypes = array_key_exists('all-post-types', $assocArgs);

        if ($allPostTypes) {
            $postTypes = ['all'];
        } else {
            $postTypesString = arrayGet('post-types', $assocArgs, '');
            $postTypes = formatCommaStringToArray($postTypesString);
        }

        if (empty($postTypes)) {
            WP_CLI::error(__('No post types specified', 'servebolt-wp'));
        }

        if ( CliHelpers::affectAllSites($assocArgs) ) {
            iterateSites(function ($site) use ($postTypes) {
                $this->nginxSetPostTypes($postTypes, $site->blog_id);
            });
        } else {
            $this->nginxSetPostTypes($postTypes);
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
    public function commandNginxFpcClearCachePostTypes($args, $assocArgs)
    {
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) {
                $this->nginxSetPostTypes([], $site->blog_id);
            });
        } else {
            $this->nginxSetPostTypes([]);
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
    public function commandNginxFpcGetExcludedPosts($args, $assocArgs)
    {
        $array = [];
        $extended = array_key_exists('extended', $assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site ) use (&$array, $extended) {
                $array[] = $this->nginxFpcGetExcludedPosts($site->blog_id, $extended);
            });
        } else {
            $array[] = $this->nginxFpcGetExcludedPosts(false, $extended);
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
    public function commandNginxFpcSetExcludedPosts($args, $assocArgs)
    {
        list($postIdsRaw) = $args;
        $this->nginxSetExcludeIds($postIdsRaw);
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
    public function commandNginxFpcClearExcludedPosts($args, $assocArgs)
    {
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) {
                $this->nginxSetExcludeIds(false, $site->blog_id);
            });
        } else {
            $this->nginxSetExcludeIds(false);
        }
    }

    /**
     * Display Nginx status - which post types have cache active.
     *
     * @param bool $blogId
     *
     * @return array
     */
    private function getNginxFpcStatus($blogId = false)
    {
        $status = FullPageCache::fpcIsActive($blogId) ? 'Active' : 'Inactive';
        $postTypes = fullPageCache()->getPostTypesToCache(true, true, $blogId);
        $enabledPostTypesString = $this->nginxGetActivePostTypesString($postTypes);
        $excludedPosts = fullPageCache()->getIdsToExcludeFromCache($blogId);
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
    private function nginxFpcGetCachePostTypes($blogId = false)
    {
        $postTypes = fullPageCache()->getPostTypesToCache(true, true, $blogId);
        $enabledPostTypesString = $this->nginxGetActivePostTypesString($postTypes);
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
    private function nginxGetActivePostTypesString($postTypes)
    {

        // Cache default post types
        if (!is_array($postTypes) || empty($postTypes)) {
            return sprintf(__('Default [%s]', 'servebolt-wp'), fullPageCache()->get_default_post_types_to_cache('csv'));
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
    private function nginxToggleCacheForBlog($newCacheState, $blogId = null)
    {
        $url = get_site_url($blogId);
        $cacheActiveString = booleanToStateString($newCacheState);
        if ($cacheActiveString === FullPageCache::fpcIsActive($blogId)) {
            WP_CLI::warning(sprintf( __('Full Page Cache already %s on site %s', 'servebolt-wp'), $cacheActiveString, $url ));
        } else {
            fullPageCache()->fpcToggleActive($newCacheState, $blogId);
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
    private function nginxPreparePostTypeArgument(array $args)
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
    private function nginxFpcControl(bool $cacheActive, array $args = [])
    {
        $affectAllBlogs = array_key_exists('all', $args);
        $postTypes = $this->nginxPreparePostTypeArgument($args);
        $excludeIds = arrayGet('exclude', $args);

        if (is_multisite() && $affectAllBlogs) {
            WP_CLI::line(__('Applying settings to all blogs', 'servebolt-wp'));
            iterateSites(function($site) use ($cacheActive, $postTypes) {
                $this->nginxToggleCacheForBlog($cacheActive, $site->blog_id);
                if ($postTypes) {
                    $this->nginxSetPostTypes($postTypes, $site->blog_id);
                }
            });
            if ($excludeIds) {
                WP_CLI::warning(__('Exclude ids were not set since ids are relative to each site.', 'servebolt-wp'));
            }
        } else {
            $this->nginxToggleCacheForBlog($cacheActive);
            if ($postTypes) {
                $this->nginxSetPostTypes($postTypes);
            }
            if ($excludeIds) {
                $this->nginxSetExcludeIds($excludeIds);
            }
        }
    }

    /**
     * Set post types to cache.
     *
     * @param $postTypes
     * @param bool $blogId
     */
    private function nginxSetPostTypes($postTypes, $blogId = false)
    {
        if ($postTypes === false) {
            $postTypes = [];
        }
        $allPostTypes = $this->nginxGetAllPostTypes();
        $allPostTypesKeys = array_keys($allPostTypes);
        $postTypes = array_filter($postTypes, function($postType) use ($allPostTypesKeys) {
            return in_array($postType, $allPostTypesKeys) || $postType === 'all';
        });
        fullPageCache()->set_cacheable_post_types($postTypes, $blogId);
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
    private function nginxGetAllPostTypes()
    {
        $allPostTypes = fullPageCache()->getAvailablePostTypesToCache(false);
        if (is_array($allPostTypes)) {
            return array_map(function($postType) {
                return isset($postType->name) ? $postType->name : $postType;
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
    private function nginxSetExcludeIds($idsToExclude, $blogId = false)
    {
        if (is_string($idsToExclude)) {
            $idsToExclude = formatCommaStringToArray($idsToExclude);
        }
        $alreadyExcluded = fullPageCache()->getIdsToExcludeFromCache($blogId);
        $clearAll = $idsToExclude === false;

        if ($clearAll) {
            fullPageCache()->set_ids_to_exclude_from_cache([], $blogId);
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
        fullPageCache()->set_ids_to_exclude_from_cache($alreadyExcluded, $blogId);

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
    private function nginxFpcGetExcludedPosts($blogId = false, $extended = false)
    {
        if ($blogId) {
            $array = ['Blog' => $blogId];
        } else {
            $array = [];
        }
        $alreadyExcluded = fullPageCache()->getIdsToExcludeFromCache($blogId);
        if ( $extended ) {
            $alreadyExcluded = formatArrayToCsv(resolvePostIdsToTitleAndPostIdString($alreadyExcluded), ', ');
        } else {
            $alreadyExcluded = formatArrayToCsv($alreadyExcluded);
        }
        $array['Excluded posts'] = $alreadyExcluded;
        return $array;
    }
}
