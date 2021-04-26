<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\FullPageCache\FullPageCacheSettings;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;

/**
 * Class AcceleratedDomainsSettings
 * @package Servebolt\Optimizer\AcceleratedDomains
 */
class AcceleratedDomainsSettings
{

    /**
     * AcceleratedDomainsSettings constructor.
     */
    public function __construct()
    {
        $this->initSettingsActions();
    }

    /**
     * Add listeners for ACD active state change.
     */
    private function initSettingsActions(): void
    {
        add_filter('pre_update_option_' . getOptionName('acd_switch'), [$this, 'detectAcdStateChange'], 10, 2);
    }

    /**
     * Detect when ACD gets activated/deactivated.
     *
     * @param $newValue
     * @param $oldValue
     * @return mixed
     */
    public function detectAcdStateChange($newValue, $oldValue)
    {
        $wasActive = filter_var($oldValue, FILTER_VALIDATE_BOOLEAN);
        $isActive = filter_var($newValue, FILTER_VALIDATE_BOOLEAN);
        $didChange = $wasActive !== $isActive;
        if ($didChange) {
            if ($isActive) {
                do_action('sb_optimizer_acd_enable');
            } else {
                do_action('sb_optimizer_acd_disable');
            }
        }
        return $newValue;
    }
}
