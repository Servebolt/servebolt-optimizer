<?php

namespace Servebolt\Optimizer\Admin\CloudflareImageResizing;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\getOptionName;

/**
 * Class CloudflareImageResizing
 *
 * This class initiates the admin GUI for the Cloudflare Image Resize feature.
 */
class CloudflareImageResizing
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * CF_Image_Resizing constructor.
     */
    private function __construct() {
        $this->initSettings();
    }

    /**
     * Initialize settings.
     */
    private function initSettings() {
        add_action( 'admin_init', [$this, 'registerSettings'] );
    }

    /**
     * Register custom option.
     */
    public function registerSettings() {
        foreach(['cf_image_resizing'] as $key) {
            register_setting('sb-cf-image-resizing-options-page', getOptionName($key));
        }
    }

    /**
     * Display view.
     */
    public function render() {
        view('cf-image-resizing.cf-image-resizing');
    }
}
