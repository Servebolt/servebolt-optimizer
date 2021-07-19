<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\FullPageCache\FullPageCacheSettings;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\listenForCheckboxOptionChange;

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
        listenForCheckboxOptionChange('acd_switch', function($wasActive, $isActive, $optionName) {
            if ($isActive) {
                do_action('sb_optimizer_acd_enable');
            } else {
                do_action('sb_optimizer_acd_disable');
            }
        });
    }
}
