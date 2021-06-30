<?php

namespace Servebolt\Optimizer\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Class ManifestFilesModel
 * @package Servebolt\Optimizer\Prefetching
 */
class ManifestFilesModel
{

    /**
     * Key used to store manifest file content.
     *
     * @var string
     */
    private static $optionName = 'manifest_available_files';

    /**
     * Store manifest files data in options.
     *
     * @param array $data
     */
    public static function store(array $data): void
    {
        updateOption(self::$optionName, $data);
    }

    /**
     * Remove item from model.
     *
     * @param string $itemToRemove
     */
    public static function remove(string $itemToRemove): void
    {
        $items = self::get();
        $items = array_filter($items, function($itemType) use ($itemToRemove) {
            return $itemType !== $itemToRemove;
        });
        self::store($items);
    }

    /**
     * Delete manifest files data in options.
     */
    public static function clear(): void
    {
        deleteOption(self::$optionName);
    }

    /**
     * Get manifest files data from options.
     *
     * @return array
     */
    public static function get(): array
    {
        return getOption(self::$optionName, []);
    }
}
