<?php

namespace Servebolt\Optimizer\Exceptions;

use Exception;
use Servebolt\Optimizer\Exceptions\MessageTranslator\ServeboltMessageTranslation;
use Servebolt\Optimizer\Exceptions\MessageTranslator\CloudflareMessageTranslation;

/**
 * Class ApiMessage
 * @package Servebolt\Optimizer\Exceptions
 */
abstract class ApiMessage extends Exception
{

    /**
     * An array of message(s).
     *
     * @var array
     */
    protected $messages;

    /**
     * The driver type that the API message originated from.
     *
     * @var string
     */
    protected $driver;

    /**
     * The response object for the driver.
     *
     * @var mixed
     */
    protected $response;

    /**
     * ApiMessage constructor.
     * @param array $messages
     * @param string $driver
     * @param mixed $response
     */
    public function __construct(array $messages, string $driver, $response)
    {
        $this->messages = $this->translateMessages($messages, $driver);
        $this->driver = $driver;
        $this->response = $response;
        $this->initializeParent();
    }

    /**
     * Translate API-messages to something that gives more sense to the end user.
     *
     * @param array $messages
     * @param string $driver
     * @return array
     */
    public function translateMessages(array $messages, string $driver): array
    {
        switch ($driver) {
            case 'acd':
                $i = new ServeboltMessageTranslation($messages);
                return $i->translate();
            case 'cloudflare':
                $i = new CloudflareMessageTranslation($messages);
                return $i->translate();
        }
        return $messages;
    }

    private function initializeParent(): void
    {
        if ($this->hasMessages()) {
            $messages = $this->getMessages();
            $message = current($messages);
            parent::__construct($message->message, $message->code);
        }
    }

    /**
     * Whether we have multiple messages.
     *
     * @return bool
     */
    public function hasMultipleMessages(): bool
    {
        return $this->hasMessages() && count($this->getMessages()) > 1;
    }

    /**
     * Whether we have message(s).
     *
     * @return bool
     */
    public function hasMessages(): bool
    {
        return !empty($this->getMessages());
    }

    /**
     * Get messages.
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Get the driver where the API originated from.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Get the response that communicated the message(s).
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }
}
