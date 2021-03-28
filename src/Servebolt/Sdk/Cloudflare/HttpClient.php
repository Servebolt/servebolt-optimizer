<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare;

abstract class HttpClient
{
    /**
     * Whether to debug requests to log.
     *
     * @var bool
     */
    private $requestDebug = false;

    /**
     * Whether to debug the requests or not.
     *
     * @return bool
     */
    private function debug() : bool
    {
        if (defined('SB_CF_REQUEST_DEBUG') && is_bool(SB_CF_REQUEST_DEBUG)) {
            return SB_CF_REQUEST_DEBUG;
        }
        return (bool) apply_filters('sb_optimizer_cf_api_request_debug', $this->requestDebug);
    }

    /**
     * Prepare request headers.
     *
     * @param array $additionalHeaders
     *
     * @return array
     */
    private function prepareRequestHeaders(array $additionalHeaders = []) : array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
        switch ($this->authType) {
            case 'api_token':
                $headers['Authorization'] = 'Bearer ' . $this->getCredential('apiToken');
                break;
            case 'api_key':
                $headers['X-Auth-Email'] = $this->getCredential('email');
                $headers['X-Auth-Key'] = $this->getCredential('apiKey');
                break;
        }
        return array_merge($headers, $additionalHeaders);
    }

    /**
     * Send request.
     *
     * @param string $uri
     * @param string $method
     * @param array $data
     * @param array $headers
     * @param array $additionalArgs
     *
     * @return array|bool
     */
    protected function request
    (
        string $uri,
        string $method = 'GET',
        array $data = [],
        array $headers = [],
        array $additionalArgs = []
    )
    {
        $method = strtoupper($method);
        $requestUrl = $this->baseUri . $uri;
        $baseArgs = [
            'headers' => $this->prepareRequestHeaders($headers)
        ];

        // Convert data-parameters to JSON for selected request methods
        if ( in_array($method, ['POST']) ) {
            $data = json_encode($data);
        }

        // Add request data only if present
        if ( ! empty($data) ) {
            $baseArgs['body'] = $data;
        }

        $args = array_merge($baseArgs, $additionalArgs);

        switch ( $method ) {
            case 'GET':
                $response = wp_remote_get($requestUrl, $args);
                break;
            case 'POST':
                $response = wp_remote_post($requestUrl, $args);
                break;
            /*
            case 'DELETE':
                $args['method'] = 'DELETE';
                $response = wp_remote_request($requestUrl, $args);
                break;
            */
            default:
                return false;
        }

        $httpCode = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body);

        $result = compact('httpCode', 'response', 'body', 'json');

        if ( $this->debug() ) {
            error_log(json_encode(array_merge($result, compact('requestUrl', 'args'))));
        }

        return $result;
    }
}
