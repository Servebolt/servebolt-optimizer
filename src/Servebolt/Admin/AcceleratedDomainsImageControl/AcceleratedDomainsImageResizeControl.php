<?php

namespace Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\AcceleratedDomains\ImageResize\ImageSizeIndexModel;
use Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl\Ajax\DisableFeature;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\overrideMenuTitle;
use function Servebolt\Optimizer\Helpers\overrideParentMenuPage;
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
        AcceleratedDomainsImageSizeIndexControl::getInstance();
        $this->initAjax();
        $this->initSettings();
        $this->rewriteHighlightedMenuItem();
    }

    private function initAjax(): void
    {
        new DisableFeature;
    }

    /**
     * Render the options page.
     */
    public function render(): void
    {
        $settings = $this->getSettingsItemsWithValues();
        $extraSizes = ImageSizeIndexModel::getSizes();
        view('accelerated-domains.image-resize.image-resize', compact('settings', 'extraSizes'));
    }

    private function initSettings(): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Flag "Performance Optimizer"-page as active when on Prefetching-page.
     */
    private function rewriteHighlightedMenuItem(): void
    {
        overrideParentMenuPage('servebolt-acd-image-resize', 'servebolt-acd');
        overrideMenuTitle('admin_page_servebolt-acd-image-resize', __('Image Resizing (beta)', 'servebolt-wp'));
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
            'acd_image_resize_switch',
            'acd_image_resize_half_size_switch',
            'acd_image_resize_src_tag_switch',
            'acd_image_resize_srcset_tag_switch',
            'acd_image_resize_quality',
            'acd_image_resize_metadata_optimization_level',
            'acd_image_resize_upscale',
        ];
    }
}
