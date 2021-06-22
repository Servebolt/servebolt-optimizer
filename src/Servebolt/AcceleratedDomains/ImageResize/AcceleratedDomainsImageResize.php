<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Utils\ImageUpscale;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\setDefaultOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;
use function Servebolt\Optimizer\Helpers\smartUpdateOption;

/**
 * Class AcceleratedDomainsImageResize
 * @package Servebolt\Optimizer\AcceleratedDomains\ImageResize
 */
class AcceleratedDomainsImageResize
{

    /**
     * @var ImageResize
     */
    private $imageResize;

    /**
     * AcceleratedDomainsImageResize constructor.
     */
    public function __construct()
    {
        $this->defaultOptionValues();
        if (self::isActive()) {
            new WpImageIndex;

            $this->imageResize = new WpImageResize;
            if ($imageQuality = $this->getImageQuality()) {
                $this->imageResize->setImageQuality($imageQuality);
            }
            if ($metadataOptimizationLevel = $this->getMetadataOptimizationLevel()) {
                $this->imageResize->setMetadataOptimizationLevel($metadataOptimizationLevel);
            }

            if (self::doHalfSizes()) {
                $this->imageResize->addHalfSizesToSrcsetHook();
            }
            //$this->imageResize->addOverrideImageSizeCreationHook();
            if (self::srcAlteringIsActive()) {
                $this->imageResize->addSingleImageUrlHook();
            }
            if (self::srcsetAlteringIsActive()) {
                $this->imageResize->addSrcsetImageUrlsHook();
            }
            if (self::doImageUpscale()) {
                ImageUpscale::getInstance();
            }
        }
    }

    /**
     * Check if user has access to resize feature.
     *
     * @return bool
     */
    public static function hasAccessToFeature(): bool
    {
        // TODO: Check if the user has access to this feature.
        return true;
    }

    /**
     * Set default option values.
     */
    private function defaultOptionValues(): void
    {
        setDefaultOption('acd_image_resize_metadata_optimization_level', ImageResize::$defaultImageMetadataOptimizationLevel);
        setDefaultOption('acd_image_resize_upscale', '__return_true');
        setDefaultOption('acd_image_resize_half_size_switch', '__return_true');
        setDefaultOption('acd_image_resize_src_tag_switch', '__return_true');
        setDefaultOption('acd_image_resize_srcset_tag_switch', '__return_true');
    }

    /**
     * Get the image metadata optimization level.
     *
     * @param int|null $blogId
     * @return bool|int
     */
    public static function getMetadataOptimizationLevel(?int $blogId = null)
    {
        return smartGetOption($blogId, 'acd_image_resize_metadata_optimization_level');
    }

    /**
     * Get the image quality.
     *
     * @param int|null $blogId
     * @return bool|int
     */
    public static function getImageQuality(?int $blogId = null)
    {
        return smartGetOption($blogId, 'acd_image_resize_quality');
    }

    /**
     * Check whether we should alter the srcset-tag for images.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function srcsetAlteringIsActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'acd_image_resize_srcset_tag_switch'));
    }

    /**
     * Check whether we should alter the src-tag for images.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function srcAlteringIsActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'acd_image_resize_src_tag_switch'));
    }

    /**
     * Check whether we should resize images.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'acd_image_resize_switch'));
    }

    /**
     * Check whether we should create half sizes.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function doHalfSizes(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'acd_image_resize_half_size_switch'));
    }

    /**
     * Check whether we should resize images.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function doImageUpscale(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'acd_image_resize_upscale'));
    }

    /**
     * Toggle image resize feature active/inactive.
     *
     * @param bool $state
     * @param int|null $blogId
     */
    public static function toggleActive(bool $state, ?int $blogId = null): void
    {
        smartUpdateOption($blogId, 'acd_image_resize_switch', $state);
    }
}
