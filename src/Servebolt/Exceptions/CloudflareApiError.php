<?php

namespace Servebolt\Optimizer\Exceptions;

/**
 * Class CloudflareApiError
 * @package Servebolt\Optimizer\Exceptions
 */
class CloudflareApiError extends ApiError
{

    /**
     * ApiError constructor.
     * @param array $errors
     * @param mixed $response
     */
    public function __construct(array $errors, $response)
    {
        parent::__construct($errors, 'cloudflare', $response);
    }
}
