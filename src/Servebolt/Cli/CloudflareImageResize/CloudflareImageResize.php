<?php

namespace Servebolt\Optimizer\Cli\CloudflareImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize as CloudflareImageResizeAdmin;

/**
 * Class CloudflareImageResize
 * @package Servebolt\Optimizer\Cli\CloudflareImageResize
 */
class CloudflareImageResize
{
    /**
     * CloudflareImageResize constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt cf-image-resize status',     [$this, 'status']);
        WP_CLI::add_command('servebolt cf-image-resize activate',   [$this, 'enable']);
        WP_CLI::add_command('servebolt cf-image-resize deactivate', [$this, 'disable']);
    }

    /**
     * Check if the Cloudflare image resize feature is active/inactive.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Check on all sites in multisite.
     *
     * ## EXAMPLES
     *
     *     wp servebolt cf-image-resize status
     *
     */
    public function status($args, $assocArgs)
    {
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) {
                $this->imageResizeStatus($site->blog_id);
            });
        } else {
            $this->imageResizeStatus();
        }
    }

    /**
     * Activate Cloudflare image resize feature.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Activate Cloudflare image resize feature on all sites in multisite-network.
     *
     * ## EXAMPLES
     *
     *     wp servebolt cf-image-resize activate
     *
     */
    public function enable($args, $assocArgs)
    {
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) {
                $this->toggleActive(true, $site->blog_id);
            });
        } else {
            $this->toggleActive(true);
        }
    }

    /**
     * Deactivate Cloudflare image resize feature.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Deactivate Cloudflare image resize feature on all sites in multisite-network.
     *
     * ## EXAMPLES
     *
     *     wp servebolt cf-image-resize deactivate
     *
     */
    public function disable($args, $assocArgs)
    {
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) {
                $this->toggleActive(false, $site->blog_id);
            });
        } else {
            $this->toggleActive(false);
        }
    }

    /**
     * Check if Cloudflare image resize feature is active/inactive.
     *
     * @param bool|int $blogId
     */
    protected function imageResizeStatus($blogId = false)
    {
        $cloudflareImageResize = CloudflareImageResizeAdmin::getInstance();
        $currentState = $cloudflareImageResize->resizingIsActive($blogId);
        $stateString = booleanToStateString($currentState);
        if ($blogId) {
            WP_CLI::success(sprintf(__('Cloudflare image resize feature is %s for site %s', 'servebolt-wp'), $stateString, get_site_url($blogId)));
        } else {
            WP_CLI::success(sprintf(__('Cloudflare image resize feature is %s', 'servebolt-wp'), $stateString));
        }
    }

    /**
     * Activate/deactivate Cloudflare image resize feature.
     *
     * @param bool $state
     * @param bool $blogId
     */
    protected function toggleActive(bool $state, $blogId = false)
    {
        $cloudflareImageResize = CloudflareImageResizeAdmin::getInstance();
        $stateString = booleanToStateString($state);
        $isActive = $cloudflareImageResize->resizingIsActive($blogId);

        if ($isActive === $state) {
            if ($blogId) {
                WP_CLI::warning(sprintf(__('Cloudflare image resize feature is already set to %s on site %s', 'servebolt-wp'), $stateString, get_site_url($blogId)));
            } else {
                WP_CLI::warning(sprintf(__('Cloudflare image resize feature is already set to %s', 'servebolt-wp'), $stateString));
            }
            return;
        }

        if ($cloudflareImageResize->toggleActive($state, $blogId)) {
            if ($blogId) {
                WP_CLI::success(sprintf(__('Cloudflare image resize feature was set to %s on site %s', 'servebolt-wp'), $stateString, get_site_url($blogId)));
            } else {
                WP_CLI::success(sprintf(__('Cloudflare image resize feature was set to %s', 'servebolt-wp'), $stateString));
            }
        } else {
            if ($blogId) {
                WP_CLI::error(sprintf(__('Could not set Cloudflare image resize feature to %s on site %s', 'servebolt-wp'), $stateString, get_site_url($blogId)), false);
            } else {
                WP_CLI::error(sprintf(__('Could not set Cloudflare image resize feature to %s', 'servebolt-wp'), $stateString), false);
            }
        }
    }
}
