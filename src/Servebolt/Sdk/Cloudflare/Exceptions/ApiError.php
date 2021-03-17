<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare\Exceptions;

use Exception;

/**
 * Class ApiError
 * @package Servebolt\Optimizer\Exceptions
 */
class ApiError extends Exception
{

    /**
     * An array of error(s).
     *
     * @var array
     */
    protected $errors;

    /**
     * The response object for the driver.
     *
     * @var mixed
     */
    protected $response;

    /**
     * ApiError constructor.
     * @param array $errors
     * @param mixed $response
     */
    public function __construct(array $errors, $response)
    {
        $this->errors = $errors;
        $this->response = $response;
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
     * Get the response that communicated the error(s).
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }
}
