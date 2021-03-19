<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if ( ! function_exists('sb_cf_image_resize_control') ) {
	/**
	 * Get SB_Image_Resize_Control-instance.
	 *
	 * @return SB_Image_Resize_Control|null
	 */
	function sb_cf_image_resize_control() {
	    require_once SERVEBOLT_PLUGIN_DIR_PATH . 'classes/cloudflare-image-resize/sb-image-resize-control.php';
		return SB_Image_Resize_Control::get_instance();
	}
}

if ( ! function_exists('sb_nginx_fpc') ) {
  /**
   * Get Servebolt_Nginx_FPC-instance.
   *
   * @return Servebolt_Nginx_FPC|null
   */
  function sb_nginx_fpc() {
    require_once SERVEBOLT_PLUGIN_DIR_PATH . 'classes/nginx-fpc/sb-nginx-fpc.php';
    return Servebolt_Nginx_FPC::get_instance();
  }
}

if ( ! function_exists('sb_cf_cache_purge_object') ) {
    /**
     * Create a new instance of SB_CF_Cache_Purge_Object.
     *
     * @param bool $id
     * @param string $type
     * @param array $args
     * @return SB_CF_Cache_Purge_Object
     */
    function sb_cf_cache_purge_object($id = false, $type = 'post', $args = []) {
        require_once __DIR__ . '/classes/cloudflare-cache/sb-cf-cache-purge-object.php';
        return new SB_CF_Cache_Purge_Object($id, $type, $args);
    }
}
