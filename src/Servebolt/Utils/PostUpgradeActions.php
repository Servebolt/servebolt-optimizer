<?php

namespace Servebolt\Optimizer\Utils;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Plugin_Upgrader;
use function Servebolt\Optimizer\Helpers\arrayGet;
use function Servebolt\Optimizer\Helpers\writeLog;

/**
 * Class PostUpgradeActions.
 * Package Servebolt\Optimizer\Utils.
 */
class PostUpgradeActions
{
    /**
     * PostUpgradeActions constructor.
     */
    public function __construct()
    {
        add_action('upgrader_process_complete', [$this, 'upgradeProcessComplete'], 10, 2);
    }

    private function doPostUpgradeActions()
    {
        // TODO: Cleanup old shit
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
     * @param $upgrader
     * @param $hookExtra
     * @return void
     */
    public function upgradeProcessComplete($upgrader, $hookExtra)
    {
        // Make sure we we're doing plugin upgrades and that our plugin was part of that upgrade
        if (!$upgrader instanceof Plugin_Upgrader || !$this->ourPluginWasUpgraded($hookExtra)) {
            return;
        }
        $this->doPostUpgradeActions();
    }
}
