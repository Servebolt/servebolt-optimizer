<?php

namespace Unit\Traits;

use function Servebolt\Optimizer\Helpers\countSites;

/**
 * Trait MultisiteTrait
 * @package Unit
 */
trait MultisiteTrait
{
    /**
     * @param int $numberOfBlogs
     * @param null $blogCreationAction
     */
    private function createBlogs(int $numberOfBlogs = 1, $blogCreationAction = null): void
    {
        $siteCount = countSites();
        for ($i = 1; $i <= $numberOfBlogs; $i++) {
            $number = $i + $siteCount;
            $blogId = $this->factory()->blog->create( [ 'domain' => 'foo-' . $number , 'path' => '/', 'title' => 'Blog ' . $number ] );
            if (is_callable($blogCreationAction)) {
                $blogCreationAction($blogId);
            }
        }
    }
}
