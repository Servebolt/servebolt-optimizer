<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
        $current_state = sb_cf_image_resize_control()->resizing_is_active($blog_id);
        $state_string = sb_boolean_to_state_string($current_state);
        if ( $blog_id ) {
            WP_CLI::success(sprintf(sb__('Cloudflare image resize feature is %s for site %s'), $state_string, get_site_url($blog_id)));
        } else {
            WP_CLI::success(sprintf(sb__('Cloudflare image resize feature is %s'), $state_string));
        }
    }

    /**
     * Activate/deactivate Cloudflare image resize feature.
     *
     * @param bool $state
     * @param bool $blog_id
     */
    protected function cf_image_resize_toggle_active(bool $state, $blog_id = false) {
        $state_string = sb_boolean_to_state_string($state);
        $is_active = sb_cf_image_resize_control()->resizing_is_active($blog_id);

        if ( $is_active === $state ) {
            if ( $blog_id ) {
                WP_CLI::warning(sprintf(sb__('Cloudflare image resize feature is already set to %s on site %s'), $state_string, get_site_url($blog_id)));
            } else {
                WP_CLI::warning(sprintf(sb__('Cloudflare image resize feature is already set to %s'), $state_string));
            }
            return;
        }

        if ( sb_cf_image_resize_control()->cf_image_resize_toggle_active($state, $blog_id) ) {
            if ( $blog_id ) {
                WP_CLI::success(sprintf(sb__('Cloudflare image resize feature was set to %s on site %s'), $state_string, get_site_url($blog_id)));
            } else {
                WP_CLI::success(sprintf(sb__('Cloudflare image resize feature was set to %s'), $state_string));
            }
        } else {
            if ( $blog_id ) {
                WP_CLI::error(sprintf(sb__('Could not set Cloudflare image resize feature to %s on site %s'), $state_string, get_site_url($blog_id)), false);
            } else {
                WP_CLI::error(sprintf(sb__('Could not set Cloudflare image resize feature to %s'), $state_string), false);
            }
        }
    }

}