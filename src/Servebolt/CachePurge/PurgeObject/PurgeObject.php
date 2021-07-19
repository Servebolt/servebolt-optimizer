<?php

namespace Servebolt\Optimizer\CachePurge\PurgeObject;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\PurgeObject\ObjectTypes\Post;
use Servebolt\Optimizer\CachePurge\PurgeObject\ObjectTypes\Term;

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
    private $purgeObject = null;

    /**
     * PurgeObject constructor.
     *
     * @param int|null $objectId
     * @param string $objectType
     * @param array $args
     */
    public function __construct(?int $objectId = null, string $objectType = 'post', array $args = [])
    {
        if ($objectId) {
            $this->addObject(
                $objectId,
                $objectType,
                $args
            );
        }
    }

    /**
     * Proxy function calls to purge object.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $forwardedMethods = [
            'success',
            'getUrls',
            'getPurgeUrls',
            'getEditUrl',
            'getBaseUrl',
            'getTitle',
            'getId',
        ];
        if (in_array($name, $forwardedMethods) && $this->purgeObject && method_exists($this->purgeObject, $name)) {
            return $this->purgeObject->{$name}();
        }
        trigger_error(sprintf('Call to undefined method %s', $name));
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
     * @param $args
     * @return bool|Term|Post
     */
    private function resolvePurgeObject($id, $type, $args)
    {
        $className = $this->buildPurgeObjectTypeClassname($type);
        if (!class_exists($className)) {
            return false;
        }
        return new $className($id, $args);
    }

    /**
     * Try to find the object to be purged, and if so create an object for it.
     *
     * @param $id
     * @param string $type
     * @param array $args
     * @return bool|Post|Term
     */
    public function addObject($id, string $type = 'post', array $args = [])
    {
        $purgeObject = $this->resolvePurgeObject($id, $type, $args);
        if ($purgeObject && !is_wp_error($purgeObject)) {
            $this->purgeObject = $purgeObject;
            return $this->purgeObject;
        }
        return false;
    }
}
