<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods;

trait CachePurge
{
    /**
     * Purge one or more URL's in a zone.
     **
     * @param array $urls
     *
     * @return bool|void
     */
    public function purgeUrls(array $urls)
    {

        $zoneId = $this->getZoneId();
        if ( ! $zoneId ) {
            return false;
        }

        // Check whether we should purge all
        $should_purge_all = in_array('all', $urls);

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
        if ( $should_purge_all ) {
            $purge_all_request = $this->purgeAll($zoneId);
            if ( $purge_all_request !== true ) {
                return $purge_all_request;
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
            return sb_cf_error($e);
        }
    }

    /**
     * Purge all URL's in a zone.
     *
     * @param bool $zoneId
     *
     * @return bool
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
                'purge_everything' => true,
            ]);
            if ( isset($request['json']->result->id) ) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            return sb_cf_error($e);
        }
    }
}
