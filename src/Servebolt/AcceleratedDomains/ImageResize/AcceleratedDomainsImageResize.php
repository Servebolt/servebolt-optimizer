<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\setDefaultOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;

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
        $this->defaultImageOptimizationLevels();
        if (self::isActive()) {
            $this->imageResize = new ImageResize();
            $this->imageResize->setImageQuality($this->getImageQuality())
                ->setMetadataOptimizationLevel($this->getMetadataOptimizationLevel())
                ->addHooks();
        }
    }

    /**
     * Make sure to return default value if no value is set.
     */
    private function defaultImageOptimizationLevels(): void
    {
        setDefaultOption('acd_image_resize_metadata_optimization_level', ImageResize::$defaultImageMetadataOptimizationLevel);
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
     * Check whether the cache purge feature is active.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'acd_img_resize_switch'));
    }
}
