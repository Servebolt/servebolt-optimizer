<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Class ApiRequestHelpers
 * @package Servebolt\Optimizer\Sdk\Cloudflare
 */
class ApiRequestHelpers
{
    /**
     * Extract the errors from the request.
     *
     * @param $request
     * @return array
     */
    public static function getErrorsFromRequest($request): array
    {
        $response = $request['json'];
        if (isset($response->errors) && is_array($response->errors)) {
            return $response->errors;
        }
        return [];
    }

    /**
     * Extract the messages from the request.
     *
     * @param $request
     * @return array
     */
    public static function getMessagesFromRequest($request): array
    {
        $response = $request['json'];
        if (isset($response->errors) && is_array($response->errors)) {
            return $response->errors;
        }
        return [];
    }
}
