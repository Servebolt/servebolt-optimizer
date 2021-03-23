<?php

namespace Servebolt\Optimizer\Queue;

use Servebolt\Optimizer\CronHandle\CronMinuteEvent;
if (!defined('ABSPATH')) exit; // Exit if accessed directly
/**
 * Class QueueEventHandler
 * @package Servebolt\Optimizer\Queue
 */
class QueueEventHandler
{
    /**
     * QueueHandler constructor.
     */
    public function __construct()
    {
        add_action(CronMinuteEvent::$cronKey, [$this, 'handleWpObjectQueue'], 10);
        add_action(CronMinuteEvent::$cronKey, [$this, 'handleUrlQueue'], 11);
    }

    public function handleWpObjectQueue(): void
    {
        new WpObjectQueue;
    }

    public function handleUrlQueue(): void
    {
        new UrlQueue();
    }
}
