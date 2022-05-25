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
        add_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        if ($useSingleton) {
            $instance = Reader::getInstance(__DIR__ . '/', $type);
        } else {
            $instance = new Reader(__DIR__ . '/', $type);
        }
        remove_filter('sb_optimizer_is_hosted_at_servebolt', '__return_true');
        return $instance;
    }
}
