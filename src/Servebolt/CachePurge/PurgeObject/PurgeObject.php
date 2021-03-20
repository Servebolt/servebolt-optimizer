<?php

namespace Servebolt\Optimizer\CachePurge\PurgeObject;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class PurgeObject
 *
 * This class is used to build an array of various URLs related to an object (post, term etc.). This is useful when purging cache with Cloudflare to cover every place a post can be displayed (front page, various archives).
 */
class PurgeObject
{

    /**
     * The object to be purged cache for.
     *
     * @var null
     */
    private $purge_object = null;

    /**
     * PurgeObject constructor.
     *
     * @param $id
     * @param string $type
     * @param array $args
     */
    public function __construct($id = false, $type = 'post', $args = []) {
        if ( $id ) {
            $this->addObject($id, $type, $args);
        }
    }

    /**
     * Get all the URLs to purge for this purge object.
     *
     * @return array|bool|bool[]|mixed
     */
    public function get_purge_urls() {
        if ( ! $this->purge_object ) return false;
        return $this->purge_object->get_urls();
    }

    /**
     * Get the base URL of the purge object.
     *
     * @return bool
     */
    public function get_base_url() {
        if ( ! $this->purge_object ) return false;
        return $this->purge_object->get_base_url();
    }

    /**
     * Get the edit URL of the purge object.
     *
     * @return bool
     */
    public function get_edit_url() {
        if ( ! $this->purge_object ) return false;
        return $this->purge_object->get_edit_url();
    }

    /**
     * Get the title of the purge object.
     *
     * @return bool
     */
    public function get_title() {
        if ( ! $this->purge_object ) return false;
        return $this->purge_object->get_title();
    }

    /**
     * Get the ID of the purge object.
     *
     * @return bool
     */
    public function get_id() {
        if ( ! $this->purge_object ) return false;
        return $this->purge_object->get_id();
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
    private function buildPurgeObjectTypeClassname($type): string
    {
        return '\\Servebolt\Optimizer\\CachePurge\\PurgeObject\\ObjectTypes\\' . ucfirst($type);
    }

    /**
     * Resolve the purge object for the given type.
     *
     * @param $id
     * @param $type
     * @return bool|mixed
     */
    private function resolvePurgeObject($id, $type, $args)
    {
        $className = $this->buildPurgeObjectTypeClassname($type);
        if ( ! class_exists($className) ) return false;
        return new $className($id, $args);
    }

    /**
     * Try to find the object to be purged, and if so create an object for it.
     *
     * @param $id
     * @param string $type
     * @param array $args
     * @return bool
     */
    public function addObject($id, $type = 'post', $args = [])
    {
        $purgeObject = $this->resolvePurgeObject($id, $type, $args);
        if ($purgeObject && !is_wp_error($purgeObject)) {
            $this->purge_object = $purgeObject;
            return $this->purge_object;
        }
        return false;
    }
}
