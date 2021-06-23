<?php

namespace Servebolt\Optimizer\Cli\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;
use function WP_CLI\Utils\format_items as WP_CLI_FormatItems;
use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains as AcceleratedDomainsClass;
use Servebolt\Optimizer\Cli\CliHelpers;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use function Servebolt\Optimizer\Helpers\iterateSites;

/**
 * Class AcceleratedDomains
 * @package Servebolt\Optimizer\Cli\AcceleratedDomains
 */
class AcceleratedDomains
{
    /**
     * AcceleratedDomains constructor.
     */
    public function __construct()
    {
        new AcceleratedDomainsImageResize;
        new AcceleratedDomainsImageSizeIndex;

        WP_CLI::add_command('servebolt acd status', [$this, 'statusAcd']);
        WP_CLI::add_command('servebolt acd activate', [$this, 'activateAcd']);
        WP_CLI::add_command('servebolt acd deactivate', [$this, 'deactivateAcd']);

        /*
        WP_CLI::add_command('servebolt acd html-minify status', [$this, 'statusAcdHtmlMinify']);
        WP_CLI::add_command('servebolt acd html-minify activate', [$this, 'activateAcdHtmlMinify']);
        WP_CLI::add_command('servebolt acd html-minify deactivate', [$this, 'deactivateAcdHtmlMinify']);
        */
    }

    /**
     * Display whether ACD is active or not.
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
     *     # Return whether ACD is active.
     *     wp servebolt acd status
     *
     *     # Return whether ACD is active for all sites in multisite.
     *     wp servebolt acd status --all
     *
     *     # Return whether ACD is active, in JSON-format
     *     wp servebolt acd status --format=json
     */
    public function statusAcd($args, $assocArgs): void
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            $statusArray = [];
            iterateSites(function ($site) use (&$statusArray) {
                $activeBoolean = AcceleratedDomainsClass::isActive($site->blog_id);
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
            $activeBoolean = AcceleratedDomainsClass::isActive();
            $activeState = booleanToStateString($activeBoolean);
            $message = sprintf(__('Accelerated Domains is %s.', 'servebolt-wp'), $activeState);
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
     * Activate Accelerated Domains-feature.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Activate ACD on all sites.
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
     *     # Activate ACD.
     *     wp servebolt acd activate
     *
     *     # Activate ACD for all sites in multisite.
     *     wp servebolt acd activate --all
     *
     *     # Activate ACD, get response in JSON-format.
     *     wp servebolt acd activate --format=json
     */
    public function activateAcd($args, $assocArgs): void
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            $statusArray = [];
            iterateSites(function ($site) use (&$statusArray) {
                if (AcceleratedDomainsClass::isActive($site->blog_id)) {
                    $message = sprintf(__('Accelerated Domains already active on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
                } else {
                    AcceleratedDomainsClass::toggleActive(true, $site->blog_id);
                    $message = sprintf(__('Accelerated Domains activated on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
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
            if (AcceleratedDomainsClass::isActive()) {
                $message = __('Accelerated Domains already active.', 'servebolt-wp');
            } else {
                AcceleratedDomainsClass::toggleActive(true);
                $message = __('Accelerated Domains activated.', 'servebolt-wp');
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
     * Deactivate Accelerated Domains-feature.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Deactivate ACD on all sites.
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
     *     # Deactivate ACD.
     *     wp servebolt acd deactivate
     *
     *     # Deactivate ACD for all sites in multisite.
     *     wp servebolt acd deactivate --all
     *
     *     # Deactivate ACD, get response in JSON-format.
     *     wp servebolt acd deactivate --format=json
     */
    public function deactivateAcd($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            $statusArray = [];
            iterateSites(function ($site) use (&$statusArray) {
                if (!AcceleratedDomainsClass::isActive($site->blog_id)) {
                    $message = sprintf(__('Accelerated Domains already inactive on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
                } else {
                    AcceleratedDomainsClass::toggleActive(false, $site->blog_id);
                    $message = sprintf(__('Accelerated Domains deactivated on site %s.', 'servebolt-wp'), get_site_url($site->blog_id));
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
            if (!AcceleratedDomainsClass::isActive()) {
                $message = __('Accelerated Domains already inactive.', 'servebolt-wp');
            } else {
                AcceleratedDomainsClass::toggleActive(false);
                $message = __('Accelerated Domains deactivated.', 'servebolt-wp');
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

    /**
     * Activate Accelerated Domains HTML minify-feature.
     */
    /*
    public function activateAcdHtmlMinify($args, $assocArgs)
    {
        if (AcceleratedDomainsClass::htmlMinifyIsActive()) {
            $message = __('Accelerated Domains HTML minify-feature already active.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson(compact('message'));
            } else {
                WP_CLI::success($message);
            }
        } else {
            AcceleratedDomainsClass::htmlMinifyToggleActive(true);
            $message = __('Accelerated Domains HTML minify-feature activated.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson(compact('message'));
            } else {
                WP_CLI::success($message);
            }
        }
    }
    */

    /**
     * Deactivate Accelerated Domains HTML minify-feature.
     */
    /*
    public function deactivateAcdHtmlMinify($args, $assocArgs)
    {
        if (!AcceleratedDomainsClass::htmlMinifyIsActive()) {
            $message = __('Accelerated Domains HTML minify-feature already inactive.');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson(compact('message'));
            } else {
                WP_CLI::success($message);
            }
        } else {
            AcceleratedDomainsClass::htmlMinifyToggleActive(false);
            $message = __('Accelerated Domains HTML minify-feature deactivated.');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson(compact('message'));
            } else {
                WP_CLI::success($message);
            }
        }
    }
    */
}
