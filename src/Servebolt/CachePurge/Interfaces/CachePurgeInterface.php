<?php

namespace Servebolt\Optimizer\CachePurge\Interfaces;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Interface CachePurgeInterface
 * @package Servebolt\Optimizer\CachePurge\Interfaces
 */
interface CachePurgeInterface
{
    public function purgeByUrl(string $url);
    public function purgeByUrls(array $urls);
    public function purgeAll();
}
