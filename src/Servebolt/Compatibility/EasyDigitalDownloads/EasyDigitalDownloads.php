<?php

namespace Servebolt\Optimizer\Compatibility\EasyDigitalDownloads;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\easyDigitalDownloadsIsActive;

/**
 * Class EasyDigitalDownloads
 * @package Servebolt\Optimizer\Compatibility\EasyDigitalDownloads
 */
class EasyDigitalDownloads
{
    /**
     * EasyDigitalDownloads constructor.
     */
    public function __construct()
    {
        if (!apply_filters('sb_optimizer_easy_digital_downloads_compatibility', true)) {
            return;
        }
        if (!easyDigitalDownloadsIsActive()) {
            return;
        }
        new CacheExceptionRules;
    }
}
