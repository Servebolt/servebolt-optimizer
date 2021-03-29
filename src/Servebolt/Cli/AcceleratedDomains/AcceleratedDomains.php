<?php

namespace Servebolt\Optimizer\Cli\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains as AcceleratedDomainsClass;
use WP_CLI;

/**
 * Class AcceleratedDomains
 * @package Servebolt\Optimizer\Cli\AcceleratedDomains
 */
class AcceleratedDomains
{
    public function __construct()
    {
        WP_CLI::add_command('servebolt acd activate', [$this, 'activateAcd']);
        WP_CLI::add_command('servebolt acd deactivate', [$this, 'deactivateAcd']);
    }

    /**
     * Activate Accelerated Domains-feature.
     */
    public function activateAcd($args, $assocArgs)
    {
        if (AcceleratedDomainsClass::isActive()) {
            WP_CLI::success('Accelerated Domains already active.');
        } else {
            AcceleratedDomainsClass::toggleActive(true);
            WP_CLI::success('Accelerated Domains activated.');
        }
    }

    /**
     * Deactivate Accelerated Domains-feature.
     */
    public function deactivateAcd($args, $assocArgs)
    {
        if (!AcceleratedDomainsClass::isActive()) {
            WP_CLI::success('Accelerated Domains already inactive.');
        } else {
            AcceleratedDomainsClass::toggleActive(false);
            WP_CLI::success('Accelerated Domains deactivated.');
        }
    }
}
