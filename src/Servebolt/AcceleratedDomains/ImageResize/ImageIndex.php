<?php

namespace Servebolt\Optimizer\AcceleratedDomains\ImageResize;

use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Class ImageIndex
 * @package Servebolt\Optimizer\AcceleratedDomains\ImageResize
 */
class ImageIndex
{
    /**
     * The option name used to store the index.
     *
     * @var string
     */
    private $optionName = 'acd_image_size_index';

    /**
     * Get the option name.
     *
     * @return string
     */
    private function getOptionName(): string
    {
        return getOptionName($this->optionName);
    }

    /**
     * Get all sizes.
     *
     * @return array
     */
    public function getSizes(): array
    {
        return getOption($this->getOptionName(), []);
    }

    /**
     * Add size to index.
     *
     * @param $value
     * @param $descriptor
     * @return bool
     */
    public function addSize($value, $descriptor): bool
    {
        $sizeData = compact('value', 'descriptor');
        $sizes = $this->getSizes();
        if (in_array($sizeData, $sizes)) {
            return true;
        }
        $sizes[] = $sizeData;
        updateOption($this->getOptionName(), $sizes);
        return true;
    }

    /**
     * Remove size from index.
     *
     * @param $value
     * @param $descriptor
     * @return bool
     */
    public function removeSize($value, $descriptor): bool
    {
        $sizeData = compact('value', 'descriptor');
        $originalSizes = $this->getSizes();
        $sizes = array_filter($originalSizes, function($size) use ($sizeData) {
            return $size != $sizeData;
        });
        if (count($originalSizes) == count($sizes)) {
            return false;
        }
        updateOption($this->getOptionName(), $sizes);
        return true;
    }
}
