<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods;

trait Zone
{
    /**
     * List zones.
     *
     * @return bool|stdClass
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
            return sb_cf_error($e);
        }
    }

    /**
     * Check if zone exists.
     *
     * @param string $zoneId
     *
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
     *
     * @return bool|object
     */
    public function getZoneById(string $zoneId)
    {
        try {
            $request = $this->request('zones/' . $zoneId);
            return $request['json']->result;
        } catch (Exception $e) {
            return sb_cf_error($e);
        }
    }

    /**
     * Get zone from Cloudflare by given key.
     *
     * @param string $zoneName
     * @param string $key
     *
     * @return bool
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
