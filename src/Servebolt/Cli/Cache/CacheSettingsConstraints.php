<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CachePurge\CachePurge;
use Servebolt\Optimizer\FullPageCache\FullPageCache;

/**
 * Class CacheSettingsConstraints
 * @package Servebolt\Optimizer\Cli\Cache
 */
class CacheSettingsConstraints
{

    /**
     * CacheSettingsConstraints constructor.
     */
    public function __construct()
    {
        $this->fpcSettingsConstraint();
        $this->getValueOverrides();
    }

    /**
     * Add value overrides.
     */
    private function getValueOverrides(): void
    {
        // Override value for setting "cache_purge_auto"
        add_filter('sb_optimizer_key_value_storage_get_value_cache_purge_auto', function(?int $blogId = null) {
            return CachePurge::automaticCachePurgeOnContentUpdateIsActive($blogId);
        }, 10, 1);
    }

    /**
     * Get available values for setting "fpc_settings".
     *
     * @return array
     */
    private function fpcSettingsValues(): array
    {
        return array_keys(FullPageCache::getAvailablePostTypesToCache(true));
    }

    /**
     * Validate and constrain the possible values for setting "fpc_settings".
     */
    private function fpcSettingsConstraint(): void
    {
        add_filter('sb_optimizer_key_value_storage_multi_value_constraints_for_fpc_settings', '__return_true');
        add_filter('sb_optimizer_key_value_storage_set_multi_value_fpc_settings', function($value) {
            return array_map(function() {
                return 1;
            }, array_flip($value));
        });
        add_filter('sb_optimizer_key_value_storage_constraints_for_fpc_settings', function()  {
            return $this->fpcSettingsValues();
        }, 10, 0);
        add_filter('sb_optimizer_key_value_storage_set_validate_fpc_settings', function($valid, $value, $itemName, $blogId, $itemType) {
            $values = array_map('trim', explode(',', $value));
            foreach ($values as $value) {
                if (!in_array($value, $this->fpcSettingsValues())) {
                    return false;
                }
            }
            return true;
        }, 10, 5);
    }
}
