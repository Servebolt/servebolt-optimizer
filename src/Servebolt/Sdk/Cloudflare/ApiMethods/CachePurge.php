<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use Servebolt\Optimizer\Sdk\Cloudflare\Exceptions\ApiError;
use Servebolt\Optimizer\Sdk\Cloudflare\ApiRequestHelpers;

/**
 * Trait CachePurge
 * @package Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods
 */
trait CachePurge
{

    /**
     * Purge one or more URL's in a zone.
     *
     * @param string $url
     * @return bool
     * @throws ApiError
     */
    public function purgeUrl(string $url): bool
    {
        return $this->purgeUrls([$url]);
    }

    /**
     * Purge one or more URL's in a zone.
     *
     * @param array $urls
     * @return bool
     * @throws ApiError
     */
    public function purgeUrls(array $urls): bool
    {

        $zoneId = $this->getZoneId();
        if ( ! $zoneId ) {
            return false;
        }

        // Check whether we should purge all
        $shouldPurgeAll = in_array('all', $urls);

        // Maybe alter URL's before sending to CF?
        $urls = apply_filters('sb_optimizer_urls_to_be_purged', $urls);

        // A hacky way of limiting so that we don't get an error from the Cloudflare API about too many URLs in purge request.
        // Future solution will be to queue up all URLs and purge them in chunks the size of 30 each.
        $maxNumber = apply_filters('sb_optimizer_max_number_of_urls_to_be_purged', false);
        if ( is_int($maxNumber) ) {
            $urls = array_slice($urls, 0, $maxNumber);
        }

        // Only keep the URL's in the cache purge queue array
        $urls = array_filter($urls, function($url) {
            return $url !== 'all';
        });

        // Purge all, return error if we cannot execute
        if ($shouldPurgeAll) {
            $purgeAllRequest = $this->purgeAll($zoneId);
            if ( $purgeAllRequest !== true ) {
                return $purgeAllRequest;
            }
        }

        $response = $this->request('zones/' . $zoneId . '/purge_cache', 'POST', [
            'files' => $urls,
        ]);
        if ($this->wasSuccessful($response)) {
            return true;
        } else {
            throw new ApiError(
                ApiRequestHelpers::getErrorsFromRequest($response),
                $response
            );
        }
    }

    /**
     * Purge all URL's in a zone.
     *
     * @param false $zoneId
     * @return bool
     * @throws ApiError
     */
    public function purgeAll($zoneId = false): bool
    {
        if ( ! $zoneId ) {
            $zoneId = $this->getZoneId();
        }
        if ( ! $zoneId ) {
            return false;
        }

        $response = $this->request(
            'zones/' . $zoneId . '/purge_cache',
            'POST',
            [
                'purge_everything' => true,
            ]
        );
        if ($this->wasSuccessful($response)) {
            return true;
        } else {
            throw new ApiError(
                ApiRequestHelpers::getErrorsFromRequest($response),
                $response
            );
        }
    }

    /**
     * Check whether the request was succesful.
     *
     * @param $response
     * @return bool
     */
    private function wasSuccessful($response): bool
    {
        if (!array_key_exists('json', $response)) {
            return false;
        }
        $jsonResponse = $response['json'];
        return isset($jsonResponse->success)
            && $jsonResponse->success;
    }
}
