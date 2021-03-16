<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods;

use Servebolt\Optimizer\Exceptions\ApiError;
use Exception;

/**
 * Trait Zone
 * @package Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods
 */
trait Zone
{

    /**
     * List zones.
     *
     * @return mixed
     * @throws ApiError
     */
    public function listZones()
    {
        $query = [
            'page'     => 1,
            'per_page' => 20,
            'match'    => 'all'
        ];
        try {
            $request = $this->request('zones', 'GET', $query);
            return $request['json']->result;
        } catch (Exception $e) {
            throw new ApiError(
                $this->getErrorsFromRequest($request),
                $request
            );
        }
    }

    /**
     * Check if zone exists.
     *
     * @param string $zoneId
     * @return bool
     * @throws ApiError
     */
    protected function zoneExists(string $zoneId) : bool
    {
        return $this->getZoneByKey($zoneId, 'id') !== false;
    }

    /**
     * Get zone by Id.
     *
     * @param string $zoneId
     * @return mixed
     * @throws ApiError
     */
    public function getZoneById(string $zoneId)
    {
        try {
            $request = $this->request('zones/' . $zoneId);
            return $request['json']->result;
        } catch (Exception $e) {
            throw new ApiError(
                $this->getErrorsFromRequest($request),
                $request
            );
        }
    }

    /**
     * Get zone from Cloudflare by given key.
     *
     * @param string $zoneName
     * @param string $key
     * @return bool|mixed
     * @throws ApiError
     */
    public function getZoneByKey(string $zoneName, string $key = 'name')
    {
        foreach ( $this->listZones() as $zone ) {
            if ( isset($zone->{ $key }) && $zone->{ $key } === $zoneName ) {
                return $zone;
            }
        }
        return true;
    }
}
