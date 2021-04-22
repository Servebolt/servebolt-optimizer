<?php

namespace Servebolt\Optimizer\Admin\AcceleratedDomainsControl;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\view;
use function Servebolt\Optimizer\Helpers\getOptionName;
use function Servebolt\Optimizer\Helpers\getOption;

/**
 * Class AcceleratedDomainsControl
 * @package Servebolt\Optimizer\Admin\CachePurgeControl
 */
class AcceleratedDomainsControl
{
    use Singleton;

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * Render the options page.
     */
    public function render(): void
    {
        $settings = $this->getSettingsItemsWithValues();
        view('accelerated-domains.accelerated-domains', compact('settings'));
    }
}
