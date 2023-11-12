<?php
namespace Servebolt\Optimizer\CacheTags;

use Servebolt\Optimizer\CacheTags\CacheTagsBase;

class GetCacheTagsHeadersForTag extends CacheTagsBase {

    /**
     * Post Type or Term ID
     * @var int
     */
    protected $objectId = 0;

    /**
     * Post type name or term
     * @var string 
     */
    protected $objectType = 'term';

    /**
     * @param int $objectId
     * @param string $objectType
     */
    public function __construct(int $objectId = 0, string $objectType = 'term')
    {
        // If post ID is not correctly set, then leave early
        if($objectId == 0) return;
        $this->objectId = $objectId;
        $this->objectType = $objectType; // is this a post type or term?
        $this->setBlog();
        $this->driver = $this->getSelectedCachePurgeDriver(($this->blogId == '')?null:$this->blogId);
        $this->setupHeaders();
    }

    protected function setupHeaders() : void
    {
        
        if($this->objectType == 'term') {
            $this->add('term-'.$this->objectId);
            return;
        } 

    }
}
