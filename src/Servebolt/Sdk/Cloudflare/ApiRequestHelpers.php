<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare;

trait ApiRequestHelpers
{
    /**
     * @param $request
     * @return array
     */
    protected function getErrorsFromRequest($request): array
    {
        // TODO: Parse errors from request into array compatible with the ApiError-exception
        var_dump($request);die;
        return [];
    }
}
