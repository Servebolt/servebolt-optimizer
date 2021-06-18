<?php

namespace Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\view;

/**
 * Class AcceleratedDomainsImageResizeControl
 * @package Servebolt\Optimizer\Admin\AcceleratedDomainsImageResizeControl
 */
class AcceleratedDomainsImageResizeControl
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * AcceleratedDomainsImageResizeControl constructor.
     */
    public function __construct()
    {
        AcceleratedDomainsImageIndexControl::getInstance();
        $this->initSettings();
    }

    /**
     * Render the options page.
     */
    public function render(): void
    {
        $settings = $this->getSettingsItemsWithValues();
        view('accelerated-domains.image-resize.image-resize', compact('settings'));
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
            register_setting('sb-accelerated-domains-image-resize-options-page', getOptionName($key));
        }
    }

    /**
     * Settings items for Accelerated Domains Image Resize.
     *
     * @return array
     */
    private function getSettingsItems(): array
    {
        return [
            'acd_img_resize_switch',
            'acd_img_resize_half_size_switch',
            'acd_img_resize_src_tag_switch',
            'acd_img_resize_srcset_tag_switch',
            'acd_image_resize_quality',
            'acd_image_resize_metadata_optimization_level',
            'acd_img_resize_upscale',
        ];
    }
}
