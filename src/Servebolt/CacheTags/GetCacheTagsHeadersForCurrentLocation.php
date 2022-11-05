<?php
namespace Servebolt\Optimizer\CacheTags;

use Exception;
use Servebolt\Optimizer\Traits\Multiton;
use Servebolt\Optimizer\CacheTags\CacheTagsBase;
use function Servebolt\Optimizer\Helpers\smartGetOption;

class GetCacheTagsHeadersForCurrentLocation extends CacheTagsBase {

    // Use the Multiton trait to allow for singleton
    use Multiton;

    public function __construct($blogId = null)
    {
        // Check if cache tags exist and work on this site.
        if( smartGetOption($blogId, $this->cache_tags_status, false) === false) return false;
        // TODO: define where we are before adding headers!

        // setup headers
        $this->getTagHeaders();
    }

    protected function getTagHeaders() : void
    {
        $this->addAuthorTag();
        $this->addTaxonomyTermIDTag();
        $this->addDateTag();
        $this->addRssTag();
        $this->addPostTypeTag();        
        $this->addHomeTag();
        $this->addWooCommerceTag();
    }

    public function returnHeaders() : array
    {
        return $this->headers;
    }
    
}
