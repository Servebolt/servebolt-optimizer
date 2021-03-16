<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods;

use Servebolt\Optimizer\Exceptions\ApiError;
use Exception;

/**
 * Trait CachePurge
 * @package Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods
 */
trait CachePurge
{

    /**
     * Purge one or more URL's in a zone.
     *
     * @param array $urls
     * @return bool
     * @throws ApiError
     */
    public function purgeUrls(array $urls)
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
        $max_number = apply_filters('sb_optimizer_max_number_of_urls_to_be_purged', false);
        if ( is_int($max_number) ) {
            $urls = array_slice($urls, 0, $max_number);
        }

        // Only keep the URL's in the cache purge queue array
        $urls = array_filter( $urls, function($url) {
            return $url !== 'all';
        } );

        // Purge all, return error if we cannot execute
        if ($shouldPurgeAll) {
            $purgeAllRequest = $this->purgeAll($zoneId);
            if ( $purgeAllRequest !== true ) {
                return $purgeAllRequest;
            }
        }

        try {
            $request = $this->request('zones/' . $zoneId . '/purge_cache', 'POST', [
                'files' => $urls,
            ]);
            if ( isset($request['json']->result->id) ) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new ApiError(
                $this->getErrorsFromRequest($request),
                $request
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
    public function purgeAll($zoneId = false)
    {
        if ( ! $zoneId ) {
            $zoneId = $this->getZoneId();
        }
        if ( ! $zoneId ) {
            return false;
        }
        try {
            $request = $this->request('zones/' . $zoneId . '/purge_cache', 'POST', [
                //'purge_everything' => true,
            ]);
            print_r($request['json']);die;
            if ( isset($request['json']->result->id) ) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new ApiError(
                $this->getErrorsFromRequest($request),
                $request
            );
        }
    }
}
