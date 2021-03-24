<?php

namespace Servebolt\Optimizer\Queue;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\CronHandle\CronMinuteEvent;
use Servebolt\Optimizer\Queue\Queues\WpObjectQueue;
use Servebolt\Optimizer\Queue\Queues\UrlQueue;

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
        (WpObjectQueue::getInstance())->parseQueue();
    }

    public function handleUrlQueue(): void
    {
        (UrlQueue::getInstance())->parseQueue();
    }
}
