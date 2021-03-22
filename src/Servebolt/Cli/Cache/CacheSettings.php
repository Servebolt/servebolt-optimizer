<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;

/**
 * Class CacheSettings
 * @package Servebolt\Optimizer\Cli\Cache
 */
class CacheSettings
{

    /**
     * CacheSettings constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt cache settings list', [$this, 'list']);
        WP_CLI::add_command('servebolt cache settings set', [$this, 'set']);
        WP_CLI::add_command('servebolt cache settings get', [$this, 'get']);
    }

    public function list()
    {

    }

    public function set()
    {

    }

    public function get()
    {

    }
}
