<?php

namespace Servebolt\Optimizer\CacheTags;

class CanUseCacheTags {

    static public function allowedDrivers()
    {
        return ['acd', 'servebolt-cdn'];
    }

}
