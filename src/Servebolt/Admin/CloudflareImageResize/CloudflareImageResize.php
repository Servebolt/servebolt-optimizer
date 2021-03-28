<?php

namespace Servebolt\Optimizer\Admin\CloudflareImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\getBlogOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\updateBlogOption;
use function Servebolt\Optimizer\Helpers\updateOption;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\getOptionName;

/**
 * Class CloudflareImageResize
 *
 * This class initiates the admin GUI for the Cloudflare Image Resize feature.
 */
class CloudflareImageResize
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * CloudflareImageResize constructor.
     */
    private function __construct()
    {
        $this->initSettings();
    }

    /**
     * Initialize settings.
     */
    private function initSettings()
    {
        add_action( 'admin_init', [$this, 'registerSettings'] );
    }

    /**
     * Register custom option.
     */
    public function registerSettings()
    {
        foreach(['cf_image_resizing'] as $key) {
            register_setting('sb-cf-image-resizing-options-page', getOptionName($key));
        }
    }

    /**
     * Display view.
     */
    public function render(): void
    {
        view('cloudflare-image-resize.configration');
    }

    /**
     * The option name/key we use to store the active state for the Cloudflare image resize feature.
     *
     * @return string
     */
    private static function cfResizingActiveOptionKey()
    {
        return 'cf_image_resizing';
    }

    /**
     * Check if Cloudflare image resize feature is active.
     *
     * @param bool $state
     * @param bool|int $blogId
     *
     * @return bool
     */
    public function toggleActive(bool $state, $blogId = false)
    {
        if ( is_numeric($blogId) ) {
            return updateBlogOption($blogId, $this->cfResizingActiveOptionKey(), $state);
        } else {
            return updateOption($this->cfResizingActiveOptionKey(), $state);
        }
    }

    /**
     * Check if Cloudflare image resize feature is active.
     *
     * @param bool|int $blogId
     *
     * @return bool
     */
    public function resizingIsActive($blogId = false): bool
    {
        if (is_numeric($blogId)) {
            return checkboxIsChecked(getBlogOption($blogId, $this->cfResizingActiveOptionKey()));
        } else {
            return checkboxIsChecked(getOption($this->cfResizingActiveOptionKey()));
        }
    }
}
