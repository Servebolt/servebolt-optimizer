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
     * @var bool Whether to return JSON in CLI.
     */
    private static $returnJson = false;

    /**
     * Cli constructor.
     */
    public function __construct()
    {
        self::returnJsonInitState();
        new AcceleratedDomains;
        new Cache;
        new CloudflareImageResize;
        new Fpc;
        new General;
        new GeneralSettings;
        new Optimizations;
    }

    /**
     * Set initial JSON return state.
     */
    private static function returnJsonInitState(): void
    {
        $instance = \Servebolt\Optimizer\Admin\GeneralSettings\GeneralSettings::getInstance();
        self::$returnJson = $instance->returnJsonInCli();
    }

    /**
     * Whether to return JSON in CLI.
     *
     * @return bool
     */
    public static function returnJson(): bool
    {
        return self::$returnJson;
    }

    public static function init()
    {
        new self;
    }
}
