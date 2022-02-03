<?php

namespace Unit\Traits;

use function Servebolt\Optimizer\Helpers\countSites;
use function Servebolt\Optimizer\Helpers\iterateSites;

/**
 * Trait MultisiteTrait
 * @package Unit
 */
trait MultisiteTrait
{
    /**
     * @param int $numberOfBlogs
     * @param $blogCreationAction
     * @return void
     */
    private function createBlogs(int $numberOfBlogs = 1, $blogCreationAction = null): array
    {
        $created = [];
        $siteCount = countSites();
        for ($i = 1; $i <= $numberOfBlogs; $i++) {
            $number = $i + $siteCount;
            $blogId = $this->factory()->blog->create([
                'domain' => 'foo-' . $number . '.com',
                'path'   => '/',
                'title'  => 'Blog ' . $number,
            ]);
            $created[] = $blogId;
            if (is_callable($blogCreationAction)) {
                $blogCreationAction($blogId);
            }
        }
        return $created;
    }

    /**
     * @param null|array $blogs
     * @return void
     */
    private function deleteBlogs($blogs = null)
    {
        if (is_array($blogs)) {
            foreach ($blogs as $blog) {
                wp_delete_site($blog);
            }
        } else {
            $mainBlogId = get_main_site_id();
            iterateSites(function($site) use ($mainBlogId) {
                if ($site->blog_id != $mainBlogId) {
                    wp_delete_site($site->blog_id);
                }
            });
        }
    }
}
