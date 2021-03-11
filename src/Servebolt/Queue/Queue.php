<?php

namespace Servebolt\Optimizer\Queue;

use Servebolt\Optimizer\Traits\Multiton;

class Queue
{

    use Multiton;

    private string $queueName;

    public function __construct($queueName)
    {
        $this->queueName = $queueName;
    }
}
