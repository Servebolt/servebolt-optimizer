<?php

namespace Servebolt\Optimizer\TextDomainLoader;

use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\smartGetOption;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class WpTextDomainLoader
 * @package Servebolt\Optimizer\TextDomainLoader
 */
class WpTextDomainLoader extends TextDomainLoader
{

    /**
     * WpTextDomainLoader constructor.
     */
    public function __construct()
    {
        if (self::isActive()) {
            add_filter('override_load_textdomain', __CLASS__ . '::aFasterLoadTextdomain', 1, 3);
        }
    }

    /**
     * Check whether the prefetching feature is active.
     *
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'custom_textdomain_loader_switch'));
    }
}
