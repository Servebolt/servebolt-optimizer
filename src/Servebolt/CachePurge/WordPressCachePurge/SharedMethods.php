<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Trait PostMethods
 * @package Servebolt\Optimizer\CachePurge\WordPressCachePurge
 */
trait SharedMethods
{

    /**
     * Check whether the driver is ACD or not.
     *
     * @param $cachePurgeDriver
     * @return bool
     */
    private static function driverIsAcd($cachePurgeDriver): bool
    {
        return is_a($cachePurgeDriver->getDriverObject(), '\\Servebolt\\Optimizer\\CachePurge\\Drivers\\Servebolt');
    }

    /**
     * Maybe reduce the amount of URLs to purge based on the driver and filters etc.
     *
     * @param array $urlsToPurge
     * @param string $context
     * @param $cachePurgeDriver
     * @return array
     */
    private static function maybeSliceUrlsToPurge(array $urlsToPurge, string $context, $cachePurgeDriver): array
    {
        if (self::driverIsAcd($cachePurgeDriver)) {
            $maxNumber = 500;
        } else {
            $maxNumber = 30;
        }
        $maxNumber = apply_filters('sb_optimizer_max_number_of_urls_to_be_purged', $maxNumber, $context, $cachePurgeDriver);
        if (is_int($maxNumber) && count($urlsToPurge) > $maxNumber) {
            $urlsToPurge = array_slice($urlsToPurge, 0, $maxNumber);
        }
        return $urlsToPurge;
    }
}
