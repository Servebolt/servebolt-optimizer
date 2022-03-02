<?php

namespace Servebolt\Optimizer\TextDomainLoader;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\smartGetOption;

/**
 * Class WpTextDomainLoader
 * @package Servebolt\Optimizer\TextDomainLoader
 */
class WpTextDomainLoader extends TextDomainLoader
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
     * WpTextDomainLoader constructor.
     */
    public function __construct()
    {
        if (self::isActive()) {
            add_filter('override_load_textdomain', __CLASS__ . '::aFasterLoadTextDomain', 1, 3);
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
        return checkboxIsChecked(smartGetOption($blogId, 'custom_text_domain_loader_switch'));
    }
}
