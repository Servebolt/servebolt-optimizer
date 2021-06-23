<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WpObjectCachePurgeActions
 * @package Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions
 */
class WpObjectCachePurgeActions
{
    /**
     * WpObjectCachePurgeActions constructor.
     */
    public function __construct()
    {
        ContentChangeTrigger::getInstance();
        SlugChangeTrigger::getInstance();
    }
}
