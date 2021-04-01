<?php

namespace Servebolt\Optimizer\Cli\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains as AcceleratedDomainsClass;
use WP_CLI;
use Servebolt\Optimizer\Cli\CliHelpers;
use function Servebolt\Optimizer\Helpers\booleanToStateString;
use function Servebolt\Optimizer\Helpers\iterateSites;

/**
 * Class AcceleratedDomains
 * @package Servebolt\Optimizer\Cli\AcceleratedDomains
 */
class AcceleratedDomains
{
    public function __construct()
    {
        WP_CLI::add_command('servebolt acd status', [$this, 'statusAcd']);
        WP_CLI::add_command('servebolt acd activate', [$this, 'activateAcd']);
        WP_CLI::add_command('servebolt acd deactivate', [$this, 'deactivateAcd']);

        /*
        WP_CLI::add_command('servebolt acd html-minify activate', [$this, 'activateAcdHtmlMinify']);
        WP_CLI::add_command('servebolt acd html-minify deactivate', [$this, 'deactivateAcdHtmlMinify']);
        */
    }

    /**
     * Display whether ACD is active or not.
     *
     * ## OPTIONS
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
     *     wp servebolt acd status
     */
    public function statusAcd($args, $assocArgs): void
    {
        CliHelpers::setReturnJson($assocArgs);
        if (CliHelpers::affectAllSites($assocArgs)) {
            iterateSites(function ($site) {

            });
        } else {
        }
        $activeState = booleanToStateString(AcceleratedDomainsClass::isActive());
        $message = sprintf(__('Accelerated Domains is %s.', 'servebolt-wp'), $activeState);
        if (CliHelpers::returnJson()) {
            CliHelpers::printJson(compact('message'));
        } else {
            WP_CLI::success($message);
        }
    }

    /**
     * Activate Accelerated Domains-feature.
     */
    public function activateAcd($args, $assocArgs): void
    {
        CliHelpers::setReturnJson($assocArgs);
        if (AcceleratedDomainsClass::isActive()) {
            $message = __('Accelerated Domains already active.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson(compact('message'));
            } else {
                WP_CLI::success($message);
            }
        } else {
            AcceleratedDomainsClass::toggleActive(true);
            $message = __('Accelerated Domains activated.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson(compact('message'));
            } else {
                WP_CLI::success($message);
            }
        }
    }

    /**
     * Deactivate Accelerated Domains-feature.
     */
    public function deactivateAcd($args, $assocArgs)
    {
        CliHelpers::setReturnJson($assocArgs);
        if (!AcceleratedDomainsClass::isActive()) {
            $message = __('Accelerated Domains already inactive.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson(compact('message'));
            } else {
                WP_CLI::success($message);
            }
        } else {
            AcceleratedDomainsClass::toggleActive(false);
            $message = __('Accelerated Domains deactivated.', 'servebolt-wp');
            if (CliHelpers::returnJson()) {
                CliHelpers::printJson(compact('message'));
            } else {
                WP_CLI::success($message);
            }
        }
    }

    /**
     * Activate Accelerated Domains HTML minify-feature.
     */
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

    /**
     * Deactivate Accelerated Domains HTML minify-feature.
     */
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
}
