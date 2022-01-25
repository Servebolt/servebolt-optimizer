<?php

namespace Servebolt\Optimizer\AcceleratedDomains\Prefetching;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\deleteOption;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\updateOption;

/**
 * Class ManifestDataModel
 * @package Servebolt\Optimizer\AcceleratedDomains\Prefetching
 */
class ManifestDataModel
{

    /**
     * Key used to store manifest file content.
     *
     * @var string
     */
    private static $optionName = 'manifest_file_content';

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
     * Delete manifest data (prefetch item data) in options.
     *
     * @param string|null $itemType
     * @return void
     */
    public static function clear($itemType = null): void
    {
        if ($itemType && $data = self::get()) {
            if (array_key_exists($itemType, $data)) {
                unset($data[$itemType]);
                self::store($data);
            }
        } else {
            deleteOption(self::$optionName);
        }
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
