<?php

namespace Servebolt\Optimizer\Compatibility\EasyDigitalDownloads;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Trait PageConditionsTrait
 * @package Servebolt\Optimizer\Compatibility\EasyDigitalDownloads
 */
trait PageConditionsTrait
{
    /**
     * @var string The post type slug used when registering the download-post type.
     */
    private $cptSlug = 'download';

    /**
     * Check if we're currently looking at a product.
     *
     * @return bool
     */
    private function isProduct(): bool
    {
        return is_singular($this->cptSlug);
    }

    /**
     * Check if we're currently looking at the shop page.
     *
     * @return bool
     */
    private function isShop(): bool
    {
        return is_post_type_archive($this->cptSlug);
    }

    /**
     * Check if we're currently looking at a product category.
     *
     * @return bool
     */
    private function isProductCategory(): bool
    {
        return is_tax('download_category');
    }

    /**
     * Check if we're currently looking at a download tag.
     * @return bool
     */
    private function isProductTag(): bool
    {
        return is_tax('download_tag');
    }
}
