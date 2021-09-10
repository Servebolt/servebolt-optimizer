<?php

namespace Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;

/**
 * Class WpObjectCachePurgeActions
 * @package Servebolt\Optimizer\CachePurge\WpObjectCachePurgeActions
 */
class WpObjectCachePurgeActions
{
    public static function on(): bool
    {
        // Skip this feature if the cache purge feature is inactive or insufficiently configured
        if (!CachePurge::featureIsAvailable()) {
            return false;
        }

        ContentChangeTrigger::on();
        SlugChangeTrigger::on();
        DeletionCacheTrigger::on();
        AttachmentUpdateTrigger::on();

        return true;
    }

    public static function off(): void
    {
        ContentChangeTrigger::off();
        SlugChangeTrigger::off();
        DeletionCacheTrigger::off();
        AttachmentUpdateTrigger::off();
    }

    public static function reloadEvents(): void
    {
        ContentChangeTrigger::reload();
        SlugChangeTrigger::reload();
        DeletionCacheTrigger::reload();
        AttachmentUpdateTrigger::reload();
    }
}
