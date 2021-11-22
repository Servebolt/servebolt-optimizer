<?php

namespace Servebolt\Optimizer\CachePurge\Interfaces;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

interface CachePurgeAllInterface
{
    public function purgeAll();
}
