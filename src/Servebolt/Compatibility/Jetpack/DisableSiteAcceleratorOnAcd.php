<?php

namespace Servebolt\Optimizer\Compatibility\Jetpack;

use Servebolt\Optimizer\AcceleratedDomains\AcceleratedDomains;
use Servebolt\Optimizer\AcceleratedDomains\ImageResize\AcceleratedDomainsImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class DisableSiteAcceleratorOnAcd
 * @package Servebolt\Optimizer\Compatibility\Jetpack
 */
class DisableSiteAcceleratorOnAcd
{
    /**
     * DisableSiteAcceleratorOnAcd constructor.
     */
    public function __construct()
    {
        if (AcceleratedDomains::isActive() || AcceleratedDomainsImageResize::isActive()) {
            add_filter('option_jetpack_active_modules', [$this, 'disablePhotonAkaSiteAccelerator']);
        }
    }

    /**
     * Force Jetpack Site Accelerator disabled.
     *
     * @param $activeModules
     * @return mixed|array
     */
    function disablePhotonAkaSiteAccelerator($activeModules)
    {
        $forcedDisabledModules = ['photon', 'photon-cdn'];
        if (is_array($activeModules)) {
            $activeModules = array_filter($activeModules, function($activeModule) use ($forcedDisabledModules) {
                return !in_array($activeModule, $forcedDisabledModules);
            });
        }
        return $activeModules;
    }
}
