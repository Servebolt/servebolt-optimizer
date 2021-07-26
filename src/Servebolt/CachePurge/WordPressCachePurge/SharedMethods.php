<?php

namespace Servebolt\Optimizer\CachePurge\WordPressCachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge as CachePurgeDriver;

/**
 * Trait SharedMethods
 * @package Servebolt\Optimizer\CachePurge\WordPressCachePurge
 */
trait SharedMethods
{

    /**
     * Whether we should bypass the queue and purge cache immediately.
     *
     * @var bool
     */
    private static $immediatePurge = false;

    /**
     * Whether we should
     * @var null
     */
    private static $immediatePurgeOnce = false;

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

    /**
     * Alias of "purgeImmediately".
     *
     * @param bool $once
     */
    public static function skipQueue(bool $once = false): void
    {
        self::purgeImmediately(true, $once);
    }

    /**
     * Alias of "purgeImmediately" but only purge immediately once.
     */
    public static function skipQueueOnce(): void
    {
        self::purgeImmediately();
    }

    /**
     * Set whether we should purge cache immediately or use the queue (if available).
     *
     * @param bool|null $state
     * @param bool|null $once
     */
    public static function purgeImmediately(bool $state = true, bool $once = true): void
    {
        self::$immediatePurge = $state;
        self::$immediatePurgeOnce = $once;
    }

    /**
     * Check whether we should purge cache immediately or use the queue system (if available).
     *
     * @param int|null $blogId
     * @return bool
     */
    private static function shouldPurgeByQueue(?int $blogId = null): bool
    {
        if (self::$immediatePurge) {
            if (self::$immediatePurgeOnce) {
                self::$immediatePurge = false;
                self::$immediatePurgeOnce = false;
            }
            return false;
        }
        return CachePurgeDriver::queueBasedCachePurgeIsActive($blogId);
    }
}
