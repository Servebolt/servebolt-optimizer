<?php

namespace Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl\Ajax\ImageSizeIndex;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\AcceleratedDomains\ImageResize\ImageSizeIndexModel;
use function Servebolt\Optimizer\Helpers\getVersionForStaticAsset;
use function Servebolt\Optimizer\Helpers\isScreen;

/**
 * Class AcceleratedDomainsImageSizeIndexControl
 * @package Servebolt\Optimizer\Admin\AcceleratedDomainsImageControl
 */
class AcceleratedDomainsImageSizeIndexControl
{
    use Singleton;

    /**
     * AcceleratedDomainsImageSizeIndexControl constructor.
     */
    public function __construct()
    {
        $this->initAjax();
        $this->initAssets();
    }

    private function initAjax(): void
    {
        new ImageSizeIndex;
    }

    private function initAssets(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function enqueueScripts(): void
    {
        if (!isScreen('admin_page_servebolt-acd-image-resize')) {
            return;
        }
        wp_enqueue_script(
            'servebolt-optimizer-acd-image-size-index',
            SERVEBOLT_PLUGIN_DIR_URL . 'assets/dist/js/acd-image-size-index.js',
            ['servebolt-optimizer-scripts'],
            getVersionForStaticAsset(SERVEBOLT_PLUGIN_DIR_PATH . 'assets/dist/js/acd-image-size-index.js'),
            true
        );
        wp_localize_script('servebolt-optimizer-acd-image-size-index', 'sb_ajax_object_acd_image_size', [
            'image_size_regex_pattern' => ImageSizeIndexModel::getValidationRegexPattern(false),
        ]);
    }
}
