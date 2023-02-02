<?php 

namespace Servebolt\Optimizer\CronEvents;

use Servebolt\Optimizer\WpCron\Events\QueueClearExpiredTransients;
use Servebolt\Optimizer\WpCron\Tasks\DeleteExpiredTranients;

class ServeboltEventsHandler{

    /**
     * QueueParseEventHandler constructor.
     */
    public function __construct()
    {       
        add_action(QueueClearExpiredTransients::$hook, [$this, 'handleQueueClearExpiredTransients'],10);
    }

    /**
     * Trigger clearing of expired transients from options tables
     */
    public function handleQueueClearExpiredTransients() : void
    {
        DeleteExpiredTranients::remove();
    }
    
}
