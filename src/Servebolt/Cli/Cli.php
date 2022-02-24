<?php

namespace Servebolt\Optimizer\Cli;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Cli\ActionScheduler\ActionScheduler;
use Servebolt\Optimizer\Cli\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Cli\Cache\Cache;
use Servebolt\Optimizer\Cli\WpCronControl\WpCronControl;
use Servebolt\Optimizer\Cli\HtmlCache\HtmlCache;
use Servebolt\Optimizer\Cli\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\Cli\General\General;
use Servebolt\Optimizer\Cli\GeneralSettings\GeneralSettings;
use Servebolt\Optimizer\Cli\Optimizations\Optimizations;
use function Servebolt\Optimizer\Helpers\isHostedAtServebolt;

/**
 * Class Cli
 * @package Servebolt\Optimizer\Cli
 */
class Cli
{

    /**
     * Cli constructor.
     */
    public function __construct()
    {
        new AcceleratedDomains;
        new Cache;
        new CloudflareImageResize;
        new HtmlCache;
        new General;
        new GeneralSettings;
        new Optimizations;

        if (isHostedAtServebolt()) {
            new ActionScheduler;
            new WpCronControl;
        }
    }

    public static function init()
    {
        new self;
    }
}
