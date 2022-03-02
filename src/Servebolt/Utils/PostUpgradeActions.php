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
        add_action('upgrader_process_complete', [$this, 'upgradeProcessCompleteCallback'], 10, 2);
    }

    /**
     * Post upgrade actions.
     *
     * @return void
     */
    private function doPostUpgradeActions()
    {
        // This function can be utilized from version >3.5.1
    }

    /**
     * Check whether out plugin was part of the upgrade.
     *
     * @param $hookExtra
     * @return bool
     */
    private function ourPluginWasUpgraded($hookExtra): bool
    {
        if (!$hookExtra) {
            return false;
        }
        $upgradedPlugins = arrayGet('plugins', $hookExtra);
        if (!is_array($upgradedPlugins)) {
            return false;
        }
        return in_array(SERVEBOLT_PLUGIN_BASENAME, $upgradedPlugins);
    }

    /**
     * Callback for action "upgrader_process_complete".
     *
     * @param $upgrader
     * @param $hookExtra
     * @return void
     */
    public function upgradeProcessCompleteCallback($upgrader, $hookExtra)
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
