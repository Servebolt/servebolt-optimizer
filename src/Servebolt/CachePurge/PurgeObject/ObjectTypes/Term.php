<?php

namespace Servebolt\Optimizer\CachePurge\PurgeObject\ObjectTypes;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\paginateLinksAsArray;

/**
 * Class Term
 *
 * This is a cache purge object with the type of term.
 */
class Term extends SharedMethods
{

    /**
     * Define the type of this object in WP context.
     *
     * @var string
     */
    protected $objectType = 'term';

    /**
     * Term constructor.
     * @param $termId
     * @param $args
     */
    public function __construct($termId, $args)
    {
        parent::__construct($termId, $args);
    }

    /**
     * Get the term URL.
     *
     * @return string|WP_Error
     */
    public function getBaseUrl()
    {
        return get_term_link($this->getId());
    }

    /**
     * Get the term edit URL.
     *
     * @return string|null
     */
    public function getEditUrl(): ?string
    {
        return get_edit_term_link($this->getId());
    }

    /**
     * Get the term title.
     *
     * @return string|bool
     */
    public function getTitle()
    {
        $term = get_term($this->getId());
        if (isset($term->name)) {
            return $term->name;
        }
        return false;
    }

    /**
     * Add URLs related to a term object.
     *
     * @return bool
     */
    protected function initObject(): bool
    {
        // The URL to the term archive.
        if ($this->addTermUrl()) {
            $this->success(true); // Flag that found the term
            return true;
        } else {
            return false; // Could not find the term, stop execution
        }
    }

    /**
     * Generate URLs related to the object.
     */
    protected function generateOtherUrls(): void
    {
        // The URL to the front page
        $this->addFrontPage();
    }

    /**
     * Add the term URL.
     *
     * @return bool
     */
    private function addTermUrl(): bool
    {
        $termUrl = $this->getBaseUrl();
        if ($termUrl && ! is_wp_error($termUrl)) {
            $pagesNeeded = $this->getPagesNeeded([
                'tax_query' => [
                    [
                        'taxonomy' => $this->getArgument('taxonomySlug'),
                        'field'    => 'term_id',
                        'terms'    => $this->getId(),
                    ]
                ],
            ], 'term');
            $this->addUrls(paginateLinksAsArray($termUrl, $pagesNeeded));
            return true;
        }
        return false;
    }
}
