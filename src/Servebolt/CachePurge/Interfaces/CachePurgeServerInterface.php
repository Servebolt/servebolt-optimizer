<?php

namespace Servebolt\Optimizer\CachePurge\Interfaces;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

interface CachePurgeServerInterface
{
    public function purgeServer();
}
