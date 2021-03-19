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
     * @param array|null $filterColumns
     * @return mixed
     */
    public function listZones(?array $filterColumns = null): ?array
    {
        $query = [
            'page'     => 1,
            'per_page' => 20,
            'match'    => 'all'
        ];
        $request = $this->request('zones', 'GET', $query);
        $zones = $request['json']->result;
        if (is_array($zones)) {
            if ($filterColumns) {
                $zones = array_map(function($zone) use($filterColumns) {
                    $filteredZone = [];
                    foreach($filterColumns as $column) {
                        $filteredZone[$column] = isset($zone->{$column}) ? $zone->{$column} : null;
                    }
                    return (object) $filteredZone;
                }, $zones);
            }
            return $zones;
        }
        return null;
    }

    /**
     * Check if zone exists.
     *
     * @param string $zoneId
     * @return bool
     */
    protected function zoneExists(string $zoneId): bool
    {
        return is_object($this->getZoneByKey($zoneId, 'id'));
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
     * @return bool|object
     */
    public function getZoneByKey(string $zoneName, string $key = 'name'): ?object
    {
        $zones = $this->listZones();
        if (is_array($zones)) {
            foreach ($zones as $zone)
            {
                if ( isset($zone->{ $key }) && $zone->{ $key } === $zoneName ) {
                    return $zone;
                }
            }
        }
        return null;
    }
}
