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
            $message = sprintf(__('Cloudflare Image Resize feature is %s', 'servebolt-wp'), $stateString);
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
                if (CloudflareImageResizeAdmin::resizingIsActive($site->blog_id)) {
                    $message = sprintf(__('Cloudflare Image Resize feature already active on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
                } else {
                    CloudflareImageResizeAdmin::toggleActive(true, $site->blog_id);
                    $message = sprintf(__('Cloudflare Image Resize feature activated on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
                }
                if (CliHelpers::returnJson()) {
                    $statusArray[] = [
                        'blog_id' => $site->blog_id,
                        'active' => true,
                        'message' => $message,
                    ];
                } else {
                    $statusArray[] = [
                        'Blog' => get_site_url($site->blog_id),
                        'Active' => booleanToStateString(true),
                        'Message' => $message,
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
                $message = __('Cloudflare Image Resize feature is already active.', 'servebolt-wp');
            } else {
                CloudflareImageResizeAdmin::toggleActive(true);
                $message = __('Cloudflare Image Resize feature is activated.', 'servebolt-wp');
            }
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'active' => true,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::success($message);
            }
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
                if (!CloudflareImageResizeAdmin::resizingIsActive($site->blog_id)) {
                    $message = sprintf(__('Cloudflare Image Resize feature already inactive on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
                } else {
                    CloudflareImageResizeAdmin::toggleActive(false, $site->blog_id);
                    $message = sprintf(__('Cloudflare Image Resize feature deactivated on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
                }
                if (CliHelpers::returnJson()) {
                    $statusArray[] = [
                        'blog_id' => $site->blog_id,
                        'active' => false,
                        'message' => $message,
                    ];
                } else {
                    $statusArray[] = [
                        'Blog' => get_site_url($site->blog_id),
                        'Active' => booleanToStateString(false),
                        'Message' => $message,
                    ];
                }
            });
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson($statusArray);
            } else {
                WP_CLI_FormatItems('table', $statusArray, array_keys(current($statusArray)));
            }
        } else {

            if (!CloudflareImageResizeAdmin::resizingIsActive()) {
                $message = __('Cloudflare Image Resize feature is already inactive.', 'servebolt-wp');
            } else {
                CloudflareImageResizeAdmin::toggleActive(false);
                $message = __('Cloudflare Image Resize feature is deactivated.', 'servebolt-wp');
            }
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'active' => false,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::success($message);
            }
        }
    }
}
