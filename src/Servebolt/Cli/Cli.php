<?php

namespace Servebolt\Optimizer\Cli;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Cli\CloudflareImageResize\CloudflareImageResize;
use Servebolt\Optimizer\Cli\Cache\Cache;
use Servebolt\Optimizer\Cli\Fpc\Fpc;
use Servebolt\Optimizer\Cli\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\Cli\General\General;
use Servebolt\Optimizer\Cli\GeneralSettings\GeneralSettings;
use Servebolt\Optimizer\Cli\Optimizations\Optimizations;

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
        new Fpc;
        new General;
        new GeneralSettings;
        new Optimizations;
    }

    public static function init()
    {
        new self;
    }
}
