<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods;

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
     */
    public function listZones()
    {
        $query = [
            'page'     => 1,
            'per_page' => 20,
            'match'    => 'all'
        ];
        $request = $this->request('zones', 'GET', $query);
        return $request['json']->result;
    }

    /**
     * Check if zone exists.
     *
     * @param string $zoneId
     * @return bool
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
     */
    public function getZoneById(string $zoneId)
    {
        $request = $this->request('zones/' . $zoneId);
        return $request['json']->result;
    }

    /**
     * Get zone from Cloudflare by given key.
     *
     * @param string $zoneName
     * @param string $key
     * @return bool|mixed
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
