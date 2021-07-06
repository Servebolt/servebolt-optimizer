<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

use Servebolt\Optimizer\CachePurge\CachePurge;

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
        // Skip this feature if the cache purge feature is inactive or insufficiently configured
        if (!CachePurge::featureIsAvailable()) {
            return;
        }

        ContentChangeTrigger::getInstance();
        SlugChangeTrigger::getInstance();
        DeletionCacheTrigger::getInstance();
    }
}
