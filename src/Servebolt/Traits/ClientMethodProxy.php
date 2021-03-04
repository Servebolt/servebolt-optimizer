<?php

namespace Servebolt\Optimizer\Traits;

trait ClientMethodProxy
{
    /**
     * @param $name
     * @param $arguments
     * @return false|mixed
     */
    public function __call($name, $arguments)
    {
        if (is_object($this->client) && is_callable([$this->client, $name])) {
            return call_user_func_array([$this->client, $name], $arguments);
        } else {
            trigger_error(sprintf('Call to undefined method %s', $name));
        }
    }
}
