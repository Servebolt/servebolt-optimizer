<?php

namespace Servebolt\Optimizer\Exceptions;

use Servebolt\Optimizer\CachePurge\CachePurge;

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
        do_action('sb_optimizer_cf_api_connection_error');
        CachePurge::setApiConnectionErrorFlag(true);
        parent::__construct($errors, 'cloudflare', $response);
    }
}
