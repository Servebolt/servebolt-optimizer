<?php 

namespace Servebolt\Optimizer\MaintenanceTasks;

use Servebolt\Optimizer\WpCron\Events\ClearExpiredTransients;
use Servebolt\Optimizer\WpCron\Tasks\DeleteExpiredTransients;

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
        DeleteExpiredTransients::remove();
    }
    
}
