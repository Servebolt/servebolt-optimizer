<?php

namespace Servebolt\Optimizer\TextDomainLoader;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\smartGetOption;

/**
 * Class WpTextDomainLoader
 * @package Servebolt\Optimizer\TextDomainLoader
 */
class WpTextDomainLoader extends TextDomainLoader
{

    public static function init(): void
    {
        new self;
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
