<?php
namespace Servebolt\Optimizer\HttpHeaders;

defined( 'ABSPATH' ) || die();

use Servebolt\Optimizer\HttpHeaders\CacheHeaders;

use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\getOptionName;

class Static404 {

    public function __construct() {
        add_action( $this->generateHookPoint(), [ $this, 'serve404' ],1 );

    }

    protected function generateHookPoint() {
        if ( ! did_action( 'muplugins_loaded' ) ) {
            return 'muplugins_loaded';
        }
        return 'plugins_loaded';
    }

    function serve404() {
        error_log("request uri is " . $_SERVER['REQUEST_URI']);
        // must have a path to check for extension
        if ( ! isset( $_SERVER['REQUEST_URI'] ) || $_SERVER['REQUEST_URI'] === '/' ) {
            return;
        }
    
        // Exit early so that WordPress will process the 404.
        if ( $this->wordpressShouldProcessRequest() ) {
            return;
        }
    
        // make sure that we have a valid extension of a static file.
        $req_ext = $this->get_request_extension();
        if ( ! $req_ext || ! in_array( $req_ext, $this->get_extensions(), true ) ) {
            return;
        }
        
        // add the 404 response code.
        http_response_code( apply_filters( 'sb_optimizer_static_404_response_code', 404 ) );
        // add Cache-Control header
        if($this->useIntelligentCacheHeaders()) {
            CacheHeaders::byExtension($req_ext);
        } else {
            CacheHeaders::byTimeout(0,0);
        }

        // send the reponse and stop processing.
        die( $this->get_message() );
    }
    
    /**
     * Determine the earliest `loaded` action we can use. If we're in an mu-plugin,
     * then fire it on mu-plugins_loaded, otherwise we need to wait until plugins_loaded.
     * 
     * If servebolt optimizer is loaded using MU plugins, then we need to use `muplugins_loaded` action,
     * as that will give a earlier boot loader hook. Thus making the response faster.
     *
     * @return string Action to use.
     */
    function get_early_action_to_use() {
        if ( ! did_action( 'muplugins_loaded' ) ) {
            return 'muplugins_loaded';
        }
        return 'plugins_loaded';
    }
    
    /**
     * Get the extension of the request.
     *
     * @return string The extension of the request.
     */
    function get_request_extension() : string
    {
        $path = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
        if(strpos($path, '.') === false) {
            return false;
        }
        $request = wp_check_filetype( $path );    
        return isset( $request['ext'] ) ? $request['ext'] : '';
    }
    
    /**
     * Get the list of file extensions that should be checked.
     * filter `static_404_file_extensions` to modify.
     *
     * @return array Array of file extensions that could be static-404ed.
     */
    function get_extensions() : array 
    {
        $wp_ext_types = wp_get_ext_types();
        $extensions   = [];
    
        // Flatten the array from [ 'image', 'audio', ... ] to one array.
        foreach ( array_keys( $wp_ext_types ) as $ext_type ) {
            $extensions = array_merge( $extensions, $wp_ext_types[ $ext_type ] );
        }
    
        // Unset file types that are not static files.
        unset( $extensions['html'] );
        unset( $extensions['htm'] );
        unset( $extensions['php'] );
    
        // Add / remove known file extensions with this filter
        return apply_filters( 'sb_optimizer_static_404_extensions', $extensions );
    }
    
    /**
     * Get the error message. Default is '404 - Not Found'.
     *
     * @return string 404 error message.
     */
    function get_message() : string
    {
        return apply_filters( 'sb_optimizer_static_404_message', '404 - File ' . get_status_header_desc( 404 ) );
    }
    
    /**
     * Allow filters to determine if we should process the request via WordPress.
     *
     * @return bool Default is false unless filtered.
     */
    function wordpressShouldProcessRequest() : bool 
    {
        // defaults to false, meaning that WordPress SHOULD NOT process the request.
        $default = (smartGetOption(null, 'fast_404_switch', true))?false:true;
        return apply_filters( 'sb_optimizer_static_404_wordpress_to_process_request', $default, $_SERVER['REQUEST_URI'] );
    }

    /**
     * Check if we should use intelligent cache headers.
     *
     * @return bool
     */
    function useIntelligentCacheHeaders() : bool
    {
        // defaults to true, only disable if explicitly set to false.
        $default = (smartGetOption(null, 'cache_404_switch', true))?true:false;
        return apply_filters( 'sb_optimizer_static_404_use_intelligent_cache_headers', $default );
    }
};