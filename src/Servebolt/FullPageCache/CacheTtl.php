<?php

namespace Servebolt\Optimizer\FullPageCache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\getOption;
use function Servebolt\Optimizer\Helpers\setDefaultOption;
use function Servebolt\Optimizer\Helpers\smartGetOption;

/**
 * Class CacheTtl
 * @package Servebolt\Optimizer\FullPageCache
 */
class CacheTtl
{
    use Singleton;

    /**
     * Alias for "getInstance".
     */
    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * CacheTtl constructor.
     */
    public function __construct()
    {
        $this->defaultOptionValues();
    }

    /**
     * Set default option values.
     */
    private function defaultOptionValues(): void
    {
        setDefaultOption('cache_ttl_by_post_type', function() {
            return self::cacheTtlPresetDefaultValues('post-type');
        });
        setDefaultOption('cache_ttl_by_taxonomy', function() {
            return self::cacheTtlPresetDefaultValues('taxonomy');
        });
        setDefaultOption('custom_cache_ttl_switch', '__return_true');
    }

    /**
     * Check whether we should set custom cache TTL.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'custom_cache_ttl_switch'));
    }

    /**
     * Get TTL value by post type.
     *
     * @param string $taxonomy
     * @return int|null
     */
    public static function getTtlByTaxonomy(string $taxonomy): ?int
    {
        $ttlPresets = getOption('cache_ttl_by_taxonomy');
        if (!$ttlPreset = arrayGet($taxonomy, $ttlPresets)) {
            return null;
        }

        // Custom TTL
        if ($ttlPreset == 'custom') {
            $customTtlForTaxonomies = getOption('custom_cache_ttl_by_taxonomy');
            $customTtl = arrayGet($taxonomy, $customTtlForTaxonomies);
            if (!is_numeric($customTtl)) {
                return null;
            }
            return $customTtl;
        }

        // Preset TTL
        $ttlPresets = self::getTtlPresets();
        $ttl = arrayGet('ttl', arrayGet($ttlPreset, $ttlPresets));
        if (!is_numeric($ttl)) {
            return null;
        }
        return $ttl;
    }

    /**
     * Get TTL value by post type.
     *
     * @param string $postType
     * @return int|null
     */
    public static function getTtlByPostType(string $postType): ?int
    {
        $ttlPresets = getOption('cache_ttl_by_post_type');
        if (!$ttlPreset = arrayGet($postType, $ttlPresets)) {
            return null;
        }

        // Custom TTL
        if ($ttlPreset == 'custom') {
            $customTtlForPostTypes = getOption('custom_cache_ttl_by_post_type');
            $customTtl = arrayGet($postType, $customTtlForPostTypes);
            if (!is_numeric($customTtl)) {
                return null;
            }
            return $customTtl;
        }

        // Preset TTL
        $ttlPresets = self::getTtlPresets();
        $ttl = arrayGet('ttl', arrayGet($ttlPreset, $ttlPresets));
        if (!is_numeric($ttl)) {
            return null;
        }
        return $ttl;
    }

    /**
     * Set default cache TTL values for post types.
     *
     * @return array
     */
    public static function cacheTtlPresetDefaultValues($type): array
    {
        switch ($type) {
            case 'taxonomy':
                return array_map(function() {
                    return 'default';
                }, array_flip(array_keys(self::getTaxonomies())));
                break;
            case 'post-type':
            default:
                return array_map(function() {
                    return 'default';
                }, array_flip(array_keys(self::getPostTypes())));
        }
    }

    /**
     * Get TTL presets.
     *
     * @return array
     */
    public static function getTtlPresets(): array
    {
        return [
            'off' => [
                'label' => 'Off',
                'ttl' => 0,
            ],
            'very-short' => [
                'label' => 'Very short',
                'ttl' => 600,
            ],
            'short' => [
                'label' => 'Short',
                'ttl' => 43200,
            ],
            'default' => [
                'label' => 'Default',
                'ttl' => 86400,
            ],
            'long' => [
                'label' => 'Long',
                'ttl' => 1209600,
            ],
            'custom' => [
                'label' => 'Custom',
            ],
        ];
    }

    /**
     * Get taxonomies that we should control TTL for.
     *
     * @return array
     */
    public static function getTaxonomies(): array
    {
        $taxonomies = get_taxonomies([
            'public' => true
        ], 'objects');
        /*
        $taxonomiesToExclude = [];
        $taxonomies = array_filter($taxonomies, function($taxonomy) use ($taxonomiesToExclude) {
            return !in_array($taxonomy->name, $taxonomiesToExclude);
        });
        */
        return $taxonomies;
    }

    /**
     * Get post types that we should control TTL for.
     *
     * @return array
     */
    public static function getPostTypes(): array
    {
        $postTypes = get_post_types([
            'public' => true
        ], 'objects');
        /*
        $postTypesToExclude = ['attachment'];
        $postTypes = array_filter($postTypes, function($postType) use ($postTypesToExclude) {
            return !in_array($postType->name, $postTypesToExclude);
        });
        */
        return $postTypes;
    }
}
