<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Class ImageSizeIndexModel
 * @package Servebolt\Optimizer\AcceleratedDomains\ImageResize
 */
class ImageSizeIndexModel
{
    /**
     * The option name used to store the index.
     *
     * @var string
     */
    private static $optionName = 'acd_image_resize_size_index';

    /**
     * Get the option name.
     *
     * @return string
     */
    private static function getOptionName(): string
    {
        return getOptionName(self::$optionName);
    }

    /**
     * Get all sizes.
     *
     * @return array
     */
    public static function getSizes(): array
    {
        return getOption(self::getOptionName(), []);
    }

    /**
     * Add size to index.
     *
     * @param $value
     * @param $descriptor
     * @return bool
     */
    public static function addSize($value, $descriptor): bool
    {
        $sizeData = compact('value', 'descriptor');
        $sizes = self::getSizes();
        if (in_array($sizeData, $sizes)) {
            return true;
        }
        $sizes[] = $sizeData;
        updateOption(self::getOptionName(), $sizes);
        return true;
    }

    /**
     * Remove size from index.
     *
     * @param $value
     * @param $descriptor
     * @return bool
     */
    public static function removeSize($value, $descriptor): bool
    {
        $sizeData = compact('value', 'descriptor');
        $originalSizes = self::getSizes();
        $sizes = array_filter($originalSizes, function($size) use ($sizeData) {
            return $size != $sizeData;
        });
        if (count($originalSizes) == count($sizes)) {
            return false;
        }
        updateOption(self::getOptionName(), $sizes);
        return true;
    }

    /**
     * Check if size exists in index.
     *
     * @param $value
     * @param $descriptor
     * @return bool
     */
    public static function sizeExists($value, $descriptor): bool
    {
        $sizeData = compact('value', 'descriptor');
        $sizes = self::getSizes();
        foreach ($sizes as $size) {
            if ($size === $sizeData) {
                return true;
            }
        }
        return false;
    }
}
