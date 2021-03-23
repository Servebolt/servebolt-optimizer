<?php

namespace Servebolt\Optimizer\CronHandle;

use Servebolt\Optimizer\CachePurge\CachePurge;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class CronEvents
 * @package Servebolt\Optimizer\CronHandle
 */
class CronEvents
{
    /**
     * CronEvent constructor.
     */
    public function __construct()
    {
        new CronMinuteEvent;
    }
}
