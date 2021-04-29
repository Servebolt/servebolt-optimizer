<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class Cache
 * @package Servebolt\Optimizer\Cli\Cache
 */
class Cache
{
    /**
     * Cache constructor.
     */
    public function __construct()
    {
        new CfSetup;
        new PurgeActions;
        new CacheSettings;
        new Queue;
    }
}
