<?php

namespace Servebolt\Optimizer\Admin\AcceleratedDomainsControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\getOption;

/**
 * Class AcceleratedDomainsControl
 * @package Servebolt\Optimizer\Admin\CachePurgeControl
 */
class AcceleratedDomainsControl
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * AcceleratedDomainsControl constructor.
     */
    public function __construct()
    {
        $this->initSettings();
        $this->initSettingsActions();
    }

    /**
     * Render the options page.
     */
    public function render(): void
    {
        $settings = $this->getSettingsItemsWithValues();
        view('accelerated-domains.accelerated-domains', compact('settings'));
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
