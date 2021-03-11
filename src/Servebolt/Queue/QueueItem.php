<?php

namespace Servebolt\Optimizer\Queue;

class QueueItem
{

    public function __construct($queueItemData)
    {
        $this->registerItemData($queueItemData);
    }

    private function registerItemData($queueItemData)
    {

    }

}
