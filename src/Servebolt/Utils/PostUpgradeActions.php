<?php

namespace Servebolt\Optimizer\Utils;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Plugin_Upgrader;
use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\iterateSites;
use function Servebolt\Optimizer\Helpers\arrayGet;

/**
 * Class PostUpgradeActions.
 * Package Servebolt\Optimizer\Utils.
 */
class PostUpgradeActions
{
    use Singleton;

    /**
     * Alias for "getInstance".
     */
    public static function init()
    {
        self::getInstance();
    }

    /**
     * PostUpgradeActions constructor.
     */
    public function __construct()
    {
        add_action('upgrader_process_complete', [$this, 'upgradeProcessComplete'], 10, 2);
    }

    /**
     * @return void
     */
    private function doPostUpgradeActions()
    {
        $this->cleanLegacyTransients();
    }

    /**
     * Clean legacy transients (transients with autoload=yes meaning that they have been set without expiry, which is undesirable).
     *
     * @return void
     */
    private function cleanLegacyTransients()
    {
        global $wpdb;
        $transientsToClean = ['sb-optimizer-text-domain-loader', 'sb-menu-cache'];
        foreach ($transientsToClean as $transientKey) {
            $fullTransientKey = '_transient_' . $transientKey;
            $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->options . ' WHERE autoload = %s AND option_name LIKE %s', 'yes', $fullTransientKey . '%'));
        }
    }

    /**
     * Check whether out plugin was part of the upgrade.
     *
     * @param $hookExtra
     * @return bool
     */
    private function ourPluginWasUpgraded($hookExtra): bool
    {
        $upgradedPlugins = arrayGet('plugins', $hookExtra);
        return in_array(SERVEBOLT_PLUGIN_BASENAME, $upgradedPlugins);
    }

    /**
     * Callback for action "upgrader_process_complete".
     *
     * @param $upgrader
     * @param $hookExtra
     * @return void
     */
    public function upgradeProcessComplete($upgrader, $hookExtra)
    {
        // Make sure we are doing plugin upgrades and that our plugin was part of that upgrade
        if (!$upgrader instanceof Plugin_Upgrader || !$this->ourPluginWasUpgraded($hookExtra)) {
            return;
        }
        if (is_multisite()) {
            iterateSites(function ($site) {
                $this->doPostUpgradeActions();
            }, true);
        } else {
            $this->doPostUpgradeActions();
        }
    }
}
