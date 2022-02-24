<?php

namespace Servebolt\Optimizer\CachePurge\Interfaces;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Interface CachePurgeUrlInterface
 * @package Servebolt\Optimizer\CachePurge\Interfaces
 */
interface CachePurgeUrlInterface
{
    public function purgeByUrl(string $url);
    public function purgeByUrls(array $urls);
}
