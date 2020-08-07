<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__ . '/cache-purge-object-types/shared-methods.php';
require_once __DIR__ . '/cache-purge-object-types/type-post.php';
require_once __DIR__ . '/cache-purge-object-types/type-term.php';

/**
 * Class SB_CF_Cache_Purge_Object
 *
 * This class is used to build an array of various URLs related to an object (post, term etc.). This is useful when purging cache with Cloudflare to cover every place a post can be displayed (front page, various archives).
 */
class SB_CF_Cache_Purge_Object {

    /**
     * The object to be purged cache for.
     *
     * @var null
     */
    private $purge_object = null;

    /**
     * SB_CF_Cache_Purge_Object constructor.
     *
     * @param $id
     * @param string $type
     */
    public function __construct($id = false, $type = 'post') {
        if ( $id ) {
            $this->add_object($id, $type);
        }
    }

    /**
     * Check whether we could resolve the object to be purged.
     *
     * @return mixed
     */
    public function success() {
        if ( ! $this->purge_object ) return false;
        return $this->purge_object->success();
    }

    /**
     * Get all URLs generated for purge object.
     *
     * @return mixed
     */
    public function get_urls() {
        if ( ! $this->purge_object ) return false;
        return $this->purge_object->get_urls();
    }

    /**
     * Build the classname for the purge object type.
     *
     * @param $type
     * @return string
     */
    private function build_purge_object_type_classname($type) {
        return sprintf('SB_CF_Cache_Purge_%s_Object', ucfirst($type));
    }

    /**
     * Resolve the purge object for the given type.
     *
     * @param $id
     * @param $type
     * @return bool|mixed
     */
    private function resolve_purge_object($id, $type) {
        $class_name = $this->build_purge_object_type_classname($type);
        if ( ! class_exists($class_name) ) return false;
        return new $class_name($id);
    }

    /**
     * Try to find the object to be purged, and if so create an object for it.
     *
     * @param $id
     * @param string $type
     * @return bool
     */
    public function add_object($id, $type = 'post') {
        $purge_object = $this->resolve_purge_object($id, $type);
        if ( $purge_object && ! is_wp_error($purge_object) ) {
            $this->purge_object = $purge_object;
            return $this->purge_object;
        }
        return false;
    }

}
