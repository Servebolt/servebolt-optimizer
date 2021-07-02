<?php

namespace Servebolt\Optimizer\MenuCache;

use Servebolt\Optimizer\Traits\Singleton;
use function Servebolt\Optimizer\Helpers\checkboxIsChecked;
use function Servebolt\Optimizer\Helpers\smartGetOption;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class MenuCache
 * @package Servebolt\Optimizer\MenuCache
 */
class WpMenuCache
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
     * MenuCache constructor.
     */
    public function __construct()
    {
        MenuCache::init();
    }

    /**
     * @param int|null $blogId
     * @return bool
     */
    public static function isActive(?int $blogId = null): bool
    {
        return checkboxIsChecked(smartGetOption($blogId, 'menu_cache'));
    }
}
