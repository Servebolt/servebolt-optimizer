<?php

namespace Servebolt\Optimizer\Traits;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Exception;

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

    /**
     * @param $name
     * @return false|mixed
     */
    public function __get($name)
    {
        if (is_object($this->client)) {
            try {
                return $this->client->{$name};
            } catch (Exception $e) {
                trigger_error(sprintf('Call to undefined property %s', $name));
            }
        } else {
            trigger_error(sprintf('Call to undefined property %s', $name));
        }
    }
}
