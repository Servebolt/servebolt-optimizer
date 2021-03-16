<?php

namespace Servebolt\Optimizer\Exceptions;

/**
 * Class ServeboltApiError
 * @package Servebolt\Optimizer\Exceptions
 */
class ServeboltApiError extends ApiError
{

    /**
     * ApiError constructor.
     * @param array $errors
     * @param mixed $response
     */
    public function __construct(array $errors, $response)
    {
        parent::__construct($errors, 'acd', $response);
    }
}
