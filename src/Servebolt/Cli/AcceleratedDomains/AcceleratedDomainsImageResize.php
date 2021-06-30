<?php

namespace Servebolt\Optimizer\Cli\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use function WP_CLI\Utils\format_items as WP_CLI_FormatItems;
use Servebolt\Optimizer\AcceleratedDomains\ImageResize\AcceleratedDomainsImageResize as AcceleratedDomainsImageResizeClass;
use Servebolt\Optimizer\Cli\CliHelpers;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use function Servebolt\Optimizer\Helpers\iterateSites;

/**
 * Class AcceleratedDomainsImageResize
 * @package Servebolt\Optimizer\Cli\AcceleratedDomains
 */
class AcceleratedDomainsImageResize
{
    /**
     * AcceleratedDomainsImageResize constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt acd image-resize status', [$this, 'statusAcdImageResize']);
        WP_CLI::add_command('servebolt acd image-resize activate', [$this, 'activateAcdImageResize']);
        WP_CLI::add_command('servebolt acd image-resize deactivate', [$this, 'deactivateAcdImageResize']);
    }

    /**
     * Activate Accelerated Domains Image Resize-feature.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Activate on all sites.
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
     *     # Activate the feature.
     *     wp servebolt acd image-resize activate
     *
     *     # Activate the feature for all sites in multisite.
     *     wp servebolt acd image-resize activate --all
     *
     *     # Activate the feature, get response in JSON-format.
     *     wp servebolt acd image-resize activate --format=json
     */
    public function activateAcdImageResize($args, $assocArgs): void
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            $statusArray = [];
            iterateSites(function ($site) use (&$statusArray) {
                if (AcceleratedDomainsImageResizeClass::isActive($site->blog_id)) {
                    $message = sprintf(__('Accelerated Domains Image Resize feature already active on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
                } else {
                    AcceleratedDomainsImageResizeClass::toggleActive(true, $site->blog_id);
                    $message = sprintf(__('Accelerated Domains Image Resize feature activated on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
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
            if (AcceleratedDomainsImageResizeClass::isActive()) {
                $message = __('Accelerated Domains Image Resize feature already active.', 'servebolt-wp');
            } else {
                AcceleratedDomainsImageResizeClass::toggleActive(true);
                $message = __('Accelerated Domains Image Resize feature activated.', 'servebolt-wp');
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
     * Display whether ACD Image Resize-feature is active or not.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Get the status for all sites.
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
     *     # Return whether the feature is active.
     *     wp servebolt acd image-resize status
     *
     *     # Return whether the feature is active for all sites in multisite.
     *     wp servebolt acd image-resize status --all
     *
     *     # Return whether the feature is active, in JSON-format
     *     wp servebolt acd image-resize status --format=json
     */
    public function statusAcdImageResize($args, $assocArgs): void
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            $statusArray = [];
            iterateSites(function ($site) use (&$statusArray) {
                $activeBoolean = AcceleratedDomainsImageResizeClass::isActive($site->blog_id);
                if (CliHelpers::returnJson()) {
                    $statusArray[] = [
                        'blog_id' => $site->blog_id,
                        'active' => $activeBoolean,
                    ];
                } else {
                    $statusArray[] = [
                        'Blog' => get_site_url($site->blog_id),
                        'Active' => booleanToStateString($activeBoolean),
                    ];
                }
            });
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson($statusArray);
            } else {
                WP_CLI_FormatItems('table', $statusArray, array_keys(current($statusArray)));
            }
        } else {
            $activeBoolean = AcceleratedDomainsImageResizeClass::isActive();
            $activeState = booleanToStateString($activeBoolean);
            $message = sprintf(__('Accelerated Domains Image Resize feature is %s.', 'servebolt-wp'), $activeState);
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson([
                    'active' => $activeBoolean,
                    'message' => $message,
                ]);
            } else {
                WP_CLI::success($message);
            }
        }
    }

    /**
     * Deactivate Accelerated Domains Image Resize-feature.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Deactivate on all sites.
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
     *     # Deactivate the feature.
     *     wp servebolt acd image-resize deactivate
     *
     *     # Deactivate the feature for all sites in multisite.
     *     wp servebolt acd image-resize deactivate --all
     *
     *     # Deactivate the feature, get response in JSON-format.
     *     wp servebolt acd image-resize deactivate --format=json
     */
    public function deactivateAcdImageResize($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            $statusArray = [];
            iterateSites(function ($site) use (&$statusArray) {
                if (!AcceleratedDomainsImageResizeClass::isActive($site->blog_id)) {
                    $message = sprintf(__('Accelerated Domains Image Resize feature already inactive on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
                } else {
                    AcceleratedDomainsImageResizeClass::toggleActive(false, $site->blog_id);
                    $message = sprintf(__('Accelerated Domains Image Resize feature deactivated on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
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
            if (!AcceleratedDomainsImageResizeClass::isActive()) {
                $message = __('Accelerated Domains Image Resize feature already inactive.', 'servebolt-wp');
            } else {
                AcceleratedDomainsImageResizeClass::toggleActive(false);
                $message = __('Accelerated Domains Image Resize feature deactivated.', 'servebolt-wp');
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
