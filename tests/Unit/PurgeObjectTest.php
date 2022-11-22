<?php

namespace Unit;

use Servebolt\Optimizer\CachePurge\PurgeObject\PurgeObject;
use ServeboltWPUnitTestCase;

/**
 * Class QueueTest
 * @package Unit\Queue
 */
class PurgeObjectTest extends ServeboltWPUnitTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->set_permalink_structure('/%postname%/');
    }

    public function testThatPurgeObjectResolvesUrls()
    {
        $taxonomy = 'test-taxonomy';
        $termName = 'test-term';

        register_taxonomy($taxonomy, null);
        $term = wp_insert_term($termName, $taxonomy);
        $termId = $term['term_id'];

        $postCount = 50;

        $editorUser = $this->factory()->user->create(['role' => 'editor']);
        $this->assertTrue(user_can($editorUser,'edit_posts'));

        wp_set_current_user($editorUser);

        $postIds = array_map(function ($n) use ($taxonomy, $termName) {
            return wp_insert_post([
                'post_title' => 'Test post ' . $n,
                'post_status' => 'publish',
                'tax_input' => [$taxonomy => $termName]
            ]);
        }, range(1, $postCount));

        $taxQuery = [
            'fields' => 'ids',
            'nopaging' => true,
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy,
                    'terms' => $termName,
                    'field' => 'slug'
                ]
            ]
        ];

        $postsInTaxonomy = get_posts($taxQuery);
        $this->assertCount($postCount, $postsInTaxonomy);

        shuffle($postIds);
        $postId = current($postIds);
        $purgeObject = new PurgeObject($postId);
        $urlsToPurge = $purgeObject->getUrls();
        $this->assertIsArray($urlsToPurge);
        $this->assertContains(trailingslashit(get_site_url()), $urlsToPurge);
        $this->assertContains(trailingslashit(get_permalink($postId)), $urlsToPurge);

        $purgeObject = new PurgeObject($termId, 'term');
        $urlsToPurge = $purgeObject->getUrls();
        $this->assertIsArray($urlsToPurge);
        $this->assertContains(trailingslashit(get_term_link($termId)), $urlsToPurge);

        $purgeObject = new PurgeObject($postId, 'cachetag');
        $tagsToPurge = $purgeObject->getCacheTags();
        $this->assertIsArray($tagsToPurge);
        $this->assertContains('home', $tagsToPurge);
        $this->assertContains('posttype-post', $tagsToPurge);
        $this->assertContains('feed', $tagsToPurge);
        $this->assertContains('year-'.date("Y"), $tagsToPurge);   
    }
}
