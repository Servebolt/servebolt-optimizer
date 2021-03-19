<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\booleanToStateString;
use Servebolt\Optimizer\Admin\CloudflareImageResize\CloudflareImageResize;

/**
 * Class Servebolt_CLI_Cloudflare_Image_Resize_Extra
 */
class Servebolt_CLI_Cloudflare_Image_Resize_Extra extends Servebolt_CLI_Extras {

    /**
     * Check if Cloudflare image resize feature is active/inactive.
     *
     * @param bool $blog_id
     */
    protected function cf_image_resize_status($blog_id = false) {
        $cloudflareImageResize = CloudflareImageResize::getInstance();
        $current_state = $cloudflareImageResize->resizingIsActive($blog_id);
        $state_string = booleanToStateString($current_state);
        if ( $blog_id ) {
            WP_CLI::success(sprintf(__('Cloudflare image resize feature is %s for site %s', 'servebolt-wp'), $state_string, get_site_url($blog_id)));
        } else {
            WP_CLI::success(sprintf(__('Cloudflare image resize feature is %s', 'servebolt-wp'), $state_string));
        }
    }

    /**
     * Activate/deactivate Cloudflare image resize feature.
     *
     * @param bool $state
     * @param bool $blog_id
     */
    protected function cf_image_resize_toggle_active(bool $state, $blog_id = false) {
        $cloudflareImageResize = CloudflareImageResize::getInstance();
        $state_string = booleanToStateString($state);
        $is_active = $cloudflareImageResize->resizingIsActive($blog_id);

        if ($is_active === $state) {
            if ( $blog_id ) {
                WP_CLI::warning(sprintf(__('Cloudflare image resize feature is already set to %s on site %s', 'servebolt-wp'), $state_string, get_site_url($blog_id)));
            } else {
                WP_CLI::warning(sprintf(__('Cloudflare image resize feature is already set to %s', 'servebolt-wp'), $state_string));
            }
            return;
        }

        if ($cloudflareImageResize->cfImageResizeToggleActive($state, $blog_id)) {
            if ( $blog_id ) {
                WP_CLI::success(sprintf(__('Cloudflare image resize feature was set to %s on site %s', 'servebolt-wp'), $state_string, get_site_url($blog_id)));
            } else {
                WP_CLI::success(sprintf(__('Cloudflare image resize feature was set to %s', 'servebolt-wp'), $state_string));
            }
        } else {
            if ( $blog_id ) {
                WP_CLI::error(sprintf(__('Could not set Cloudflare image resize feature to %s on site %s', 'servebolt-wp'), $state_string, get_site_url($blog_id)), false);
            } else {
                WP_CLI::error(sprintf(__('Could not set Cloudflare image resize feature to %s', 'servebolt-wp'), $state_string), false);
            }
        }
    }

}
