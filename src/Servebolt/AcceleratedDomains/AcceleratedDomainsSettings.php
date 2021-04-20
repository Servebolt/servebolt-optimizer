<?php

namespace Servebolt\Optimizer\AcceleratedDomains;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\FullPageCache\FullPageCacheHeaders;
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
        $this->initSettings();
        $this->initSettingsActions();
    }

    /**
     * Add listeners for ACD active state change.
     */
    private function initSettingsActions(): void
    {
        add_filter('pre_update_option_' . getOptionName('acd_switch'), [$this, 'detectAcdActivation'], 10, 2);
    }

    /**
     * Detect when ACD gets activated/deactivated.
     *
     * @param $newValue
     * @param $oldValue
     * @return mixed
     */
    public function detectAcdActivation($newValue, $oldValue)
    {
        $wasActive = filter_var($oldValue, FILTER_VALIDATE_BOOLEAN);
        $isActive = filter_var($newValue, FILTER_VALIDATE_BOOLEAN);
        $didChange = $wasActive !== $isActive;
        if ($didChange) {
            if ($isActive) {
                do_action('sb_optimizer_acd_enable');
                if (!FullPageCacheHeaders::fpcIsActive()) {
                    do_action('sb_optimizer_fpc_enable');
                }
            } else {
                do_action('sb_optimizer_acd_disable');
                if (!FullPageCacheHeaders::fpcIsActive()) {
                    do_action('sb_optimizer_fpc_disable');
                }
            }
        }
        return $newValue;
    }

    private function initSettings(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Get all plugin settings in array.
     *
     * @return array
     */
    public function getSettingsItemsWithValues(): array
    {
        $items = $this->getSettingsItems();
        $itemsWithValues = [];
        foreach ($items as $item) {
            switch ($item) {
                default:
                    $itemsWithValues[$item] = getOption($item);
                    break;
            }
        }
        return $itemsWithValues;
    }

    public function registerSettings(): void
    {
        foreach ($this->getSettingsItems() as $key) {
            register_setting('sb-accelerated-domains-options-page', getOptionName($key));
        }
    }

    /**
     * Settings items for Accelerated Domains.
     *
     * @return array
     */
    private function getSettingsItems(): array
    {
        return [
            'acd_switch',
            'acd_minify_switch',
        ];
    }
}
