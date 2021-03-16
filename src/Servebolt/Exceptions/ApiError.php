<?php

namespace Servebolt\Optimizer\Exceptions;

use Exception;
use Throwable;

/**
 * Class ApiError
 * @package Servebolt\Optimizer\Exceptions
 */
class ApiError extends Exception
{

    /**
     * ApiError constructor.
     * @param array $errors
     * @param $response
     * @param Throwable|null $previous
     */
    public function __construct(array $errors, $response, Throwable $previous = null)
    {
        $this->errors = $errors;
        $this->response = $response;
        parent::__construct('', '', $previous);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }
}
