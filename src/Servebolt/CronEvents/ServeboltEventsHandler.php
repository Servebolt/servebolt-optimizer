<?php 

namespace Servebolt\Optimizer\CronEvents;

use Servebolt\Optimizer\WpCron\Events\ClearExpiredTransients;
use Servebolt\Optimizer\WpCron\Tasks\DeleteExpiredTranients;

class ServeboltEventsHandler{

    /**
     * QueueParseEventHandler constructor.
     */
    public function __construct()
    {
        add_action(ClearExpiredTransients::$hook, [$this, 'handleClearExpiredTransients'],10);
    }

    /**
     * Trigger clearing of expired transients from options tables
     */
    public function handleClearExpiredTransients() : void
    {
        DeleteExpiredTranients::remove();
    }
    
}
