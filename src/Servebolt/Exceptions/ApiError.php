<?php

namespace Servebolt\Optimizer\Exceptions;

use Exception;
use Servebolt\Optimizer\Exceptions\ErrorTranslator\ServeboltErrorTranslation;
use Servebolt\Optimizer\Exceptions\ErrorTranslator\CloudflareErrorTranslation;

/**
 * Class ApiError
 * @package Servebolt\Optimizer\Exceptions
 */
abstract class ApiError extends Exception
{

    /**
     * An array of error(s).
     *
     * @var array
     */
    protected $errors;

    /**
     * The driver type that the API error originated from.
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
     * ApiError constructor.
     * @param array $errors
     * @param string $driver
     * @param mixed $response
     */
    public function __construct(array $errors, string $driver, $response)
    {
        $this->errors = $this->translateErrors($errors, $driver);
        $this->driver = $driver;
        $this->response = $response;
        $this->initializeParent();
    }

    /**
     * Translate API-errors to something that gives more sense to the end user.
     *
     * @param array $errors
     * @param string $driver
     * @return array
     */
    public function translateErrors(array $errors, string $driver): array
    {
        switch ($driver) {
            case 'acd':
                $i = new ServeboltErrorTranslation($errors);
                return $i->translate();
            case 'cloudflare':
                $i = new CloudflareErrorTranslation($errors);
                return $i->translate();
        }
        return $errors;
    }

    private function initializeParent(): void
    {
        if ($this->hasErrors()) {
            $errors = $this->getErrors();
            $error = current($errors);
            parent::__construct($error->message, $error->code);
        }
    }

    /**
     * Whether we have multiple error messages.
     *
     * @return bool
     */
    public function hasMultipleErrors(): bool
    {
        return $this->hasErrors() && count($this->getErrors()) > 1;
    }

    /**
     * Whether we have error message(s).
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->getErrors());
    }

    /**
     * Get errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
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
     * Get the response that communicated the error(s).
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }
}
