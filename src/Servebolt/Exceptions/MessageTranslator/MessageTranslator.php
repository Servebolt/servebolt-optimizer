<?php

namespace Servebolt\Optimizer\Exceptions\MessageTranslator;

/**
 * Class MessageTranslator
 * @package Servebolt\Optimizer\Exceptions\MessageTranslator
 */
abstract class MessageTranslator
{

    /**
     * @var array
     */
    protected $messages;

    /**
     * MessageTranslator constructor.
     * @param $messages
     */
    public function __construct($messages)
    {
        $this->messages = $messages;
    }

    /**
     * Attempt to translate messages to something more user friendly.
     *
     * @return array
     */
    public function translate(): array
    {
        return array_map(function($message) {
            if (array_key_exists($message->code, $this->definitions)) {
                $message->original_message = $message->message;
                $message->message = $this->definitions[$message->code];
            }
            return $message;
        }, $this->messages);
    }
}
