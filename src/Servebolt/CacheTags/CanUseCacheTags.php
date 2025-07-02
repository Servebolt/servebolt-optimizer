<?php

namespace Servebolt\Optimizer\CacheTags;

class CanUseCacheTags {

    static public function allowedDrivers()
    {
        return ['acd', 'serveboltcdn', 'cloudflare'];
    }

}
