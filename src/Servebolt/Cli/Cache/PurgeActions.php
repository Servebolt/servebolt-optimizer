<?php

namespace Servebolt\Optimizer\Cli\Cache;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use WP_CLI;

/**
 * Class CacheSettings
 * @package Servebolt\Optimizer\Cli\Cache
 */
class PurgeActions
{

    /**
     * CacheSettings constructor.
     */
    public function __construct()
    {
        WP_CLI::add_command('servebolt cache purge url', [$this, 'purgeUrl']);
        WP_CLI::add_command('servebolt cache purge post', [$this, 'purgePost']);
        WP_CLI::add_command('servebolt cache purge term', [$this, 'purgeTerm']);
        WP_CLI::add_command('servebolt cache purge all', [$this, 'purgeAll']);
    }

    public function purgeUrl(): void
    {

    }

    public function purgePost(): void
    {

    }

    public function purgeTerm(): void
    {

    }

    public function purgeAll(): void
    {
        // TODO: Add multisite-support using --all-flag
    }
}
