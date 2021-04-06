<?php

namespace Servebolt\Optimizer\Cli\CloudflareImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use function WP_CLI\Utils\format_items as WP_CLI_FormatItems;
use Servebolt\Optimizer\Cli\CliHelpers;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize as CloudflareImageResizeAdmin;
use function Servebolt\Optimizer\Helpers\iterateSites;

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
        WP_CLI::add_command('servebolt cf-image-resize activate',   [$this, 'activate']);
        WP_CLI::add_command('servebolt cf-image-resize deactivate', [$this, 'deactivate']);
    }

    /**
     * Check if the Cloudflare image resize feature is active/inactive.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Check on all sites in multisite.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp servebolt cf-image-resize status
     *
     */
    public function status($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            $statusArray = [];
            iterateSites(function ($site) use (&$statusArray) {
                $currentState = CloudflareImageResizeAdmin::resizingIsActive($site->blog_id);
                if (CliHelpers::returnJson()) {
                    $statusArray[] = [
                        'blog_id' => $site->blog_id,
                        'active' => $currentState,
                    ];
                } else {
                    $statusArray[] = [
                        'Blog' => get_site_url($site->blog_id),
                        'Active' => booleanToStateString($currentState),
                    ];
                }
            });
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson($statusArray);
            } else {
                WP_CLI_FormatItems('table', $statusArray, array_keys(current($statusArray)));
            }
        } else {
            $currentState = CloudflareImageResizeAdmin::resizingIsActive();
            $stateString = booleanToStateString($currentState);
            $message = sprintf(__('Cloudflare image resize feature is %s', 'servebolt-wp'), $stateString);
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'active' => $currentState,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::success($message);
            }
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
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp servebolt cf-image-resize activate
     *
     */
    public function activate($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            $statusArray = [];
            iterateSites(function ($site) use (&$statusArray) {
                $result = CloudflareImageResizeAdmin::toggleActive(true, $site->blog_id);
                $stateString = booleanToStateString($result);
                if (CliHelpers::returnJson()) {
                    $statusArray[] = [
                        'blog_id' => $site->blog_id,
                        'active' => $result,
                    ];
                } else {
                    $statusArray[] = [
                        'Blog' => get_site_url($site->blog_id),
                        'Active' => booleanToStateString($stateString),
                    ];
                }
            });
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson($statusArray);
            } else {
                WP_CLI_FormatItems('table', $statusArray, array_keys(current($statusArray)));
            }
        } else {
            if (CloudflareImageResizeAdmin::resizingIsActive()) {
                $message = __('Cloudflare image resize feature is already active.', 'servebolt-wp');
            } else {
                CloudflareImageResizeAdmin::toggleActive(true);
                $message = __('Cloudflare image resize feature is activated.', 'servebolt-wp');
            }
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'active' => CloudflareImageResizeAdmin::resizingIsActive(),
                    'message' => $message,
                ]);
            } else {
                WP_CLI::success($message);
            }
        }
        /*
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) {
                $this->toggleActive(true, $site->blog_id);
            });
        } else {
            $this->toggleActive(true);
        }
        */
    }

    /**
     * Deactivate Cloudflare image resize feature.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Deactivate Cloudflare image resize feature on all sites in multisite-network.
     *
     * [--format=<format>]
     * : Return format.
     * ---
     * default: text
     * options:
     *   - text
     *   - json
     * ---
     *
     * ## EXAMPLES
     *
     *     wp servebolt cf-image-resize deactivate
     *
     */
    public function deactivate($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            $statusArray = [];
            iterateSites(function ($site) use (&$statusArray) {
                $result = CloudflareImageResizeAdmin::toggleActive(false, $site->blog_id);
                $stateString = booleanToStateString($result);
                if (CliHelpers::returnJson()) {
                    $statusArray[] = [
                        'blog_id' => $site->blog_id,
                        'active' => $result,
                    ];
                } else {
                    $statusArray[] = [
                        'Blog' => get_site_url($site->blog_id),
                        'Active' => booleanToStateString($stateString),
                    ];
                }
            });
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson($statusArray);
            } else {
                WP_CLI_FormatItems('table', $statusArray, array_keys(current($statusArray)));
            }
        } else {
            $result = CloudflareImageResizeAdmin::toggleActive(false);
            var_dump($result);
            die;
            $stateString = booleanToStateString($result);
            $message = sprintf(__('Cloudflare image resize feature is %s', 'servebolt-wp'), $stateString);
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'active' => $result,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::success($message);
            }
        }
        /*
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) {
                $this->toggleActive(false, $site->blog_id);
            });
        } else {
            $this->toggleActive(false);
        }
        */
    }

    /**
     * Activate/deactivate Cloudflare image resize feature.
     *
     * @param bool $state
     * @param int|null $blogId
     */
    protected function toggleActive(bool $state, ?int $blogId = null)
    {
        $stateString = booleanToStateString($state);
        $isActive = CloudflareImageResizeAdmin::resizingIsActive($blogId);

        if ($isActive === $state) {
            if ($blogId) {
                WP_CLI::warning(sprintf(__('Cloudflare image resize feature is already set to %s on site %s', 'servebolt-wp'), $stateString, get_site_url($blogId)));
            } else {
                WP_CLI::warning(sprintf(__('Cloudflare image resize feature is already set to %s', 'servebolt-wp'), $stateString));
            }
            return;
        }

        if (CloudflareImageResizeAdmin::toggleActive($state, $blogId)) {
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
