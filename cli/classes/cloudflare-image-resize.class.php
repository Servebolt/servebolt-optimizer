<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once __DIR__ . '/cloudflare-image-resize-extra.class.php';

use function Servebolt\Options\Helpers\iterateSites;

/**
 * Class Servebolt_CLI_Cloudflare_Image_Resize
 */
class Servebolt_CLI_Cloudflare_Image_Resize extends Servebolt_CLI_Cloudflare_Image_Resize_Extra {

    /**
     * Check if the Cloudflare image resize feature is active/inactive.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Check on all sites in multisite.
     *
     * ## EXAMPLES
     *
     *     wp servebolt cf-image-resize status
     *
     */
    public function command_cf_image_resize_status($args, $assoc_args) {
        if ( $this->affect_all_sites( $assoc_args ) ) {
            iterateSites(function ( $site ) {
                $this->cf_image_resize_status($site->blog_id);
            });
        } else {
            $this->cf_image_resize_status();
        }
    }

    /**
     * Activate Cloudflare image resize feature.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Activate Cloudflare image resize feature on all sites in multisite-network.
     *
     * ## EXAMPLES
     *
     *     wp servebolt cf-image-resize activate
     *
     */
    public function command_cf_image_resize_enable($args, $assoc_args) {
        if ( $this->affect_all_sites( $assoc_args ) ) {
            iterateSites(function ( $site ) {
                $this->cf_image_resize_toggle_active(true, $site->blog_id);
            });
        } else {
            $this->cf_image_resize_toggle_active(true);
        }
    }

    /**
     * Deactivate Cloudflare image resize feature.
     *
     * ## OPTIONS
     *
     * [--all]
     * : Deactivate Cloudflare image resize feature on all sites in multisite-network.
     *
     * ## EXAMPLES
     *
     *     wp servebolt cf-image-resize deactivate
     *
     */
    public function command_cf_image_resize_disable($args, $assoc_args) {
        if ( $this->affect_all_sites( $assoc_args ) ) {
            iterateSites(function ( $site ) {
                $this->cf_image_resize_toggle_active(false, $site->blog_id);
            });
        } else {
            $this->cf_image_resize_toggle_active(false);
        }
    }

}
