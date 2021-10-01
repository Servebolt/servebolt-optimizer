<?php

namespace Unit\Traits\EnvFile;

use Servebolt\Optimizer\Utils\EnvFile\Reader;

trait EnvFileReaderTrait
{
    /**
     * Get instance of environment file reader class.
     *
     * @param string $type
     * @param bool $useSingleton
     * @return mixed|Reader
     */
    public static function getEnvFileReader(string $type = 'auto', bool $useSingleton = true)
    {
        if ($useSingleton) {
            return Reader::getInstance(__DIR__ . '/', $type);
        } else {
            return new Reader(__DIR__ . '/', $type);
        }
    }
}
