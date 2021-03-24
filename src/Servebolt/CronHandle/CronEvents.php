<?php

namespace Servebolt\Optimizer\CronHandle;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CronHandle\Events\CronMinuteEvent;

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
