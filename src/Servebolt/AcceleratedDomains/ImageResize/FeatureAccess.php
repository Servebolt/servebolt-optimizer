<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\EnvironmentConfig;

/**
 * Class FeatureAccess
 * @package Servebolt\Optimizer\AcceleratedDomains\ImageResize
 */
class FeatureAccess
{

    /**
     * Check if current site has access to the Accelerated Domains Image Resize-feature.
     *
     * @param bool $purgeCache
     * @return bool
     */
    public static function hasAccess(bool $purgeCache = false): bool
    {
        $config = EnvironmentConfig::getInstance();
        return (bool) $config->get('sb_acd_image_resize', $purgeCache);
    }
}
