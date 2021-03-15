<?php

namespace Servebolt\Optimizer\Api\Servebolt;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\EnvFile\Reader as EnvFileReader;
use Servebolt\Optimizer\Traits\Singleton;
use Servebolt\Optimizer\Traits\ClientMethodProxy;
use Servebolt\Sdk\Client as ServeboltSdk;
use Servebolt\Sdk\Exceptions\ServeboltInvalidOrMissingAuthDriverException;

/**
 * Class Servebolt
 *
 * This class initializes the Servebolt SDK based on the given blog Id.
 *
 * @package Servebolt\Optimizer\Api\Cloudflare
 */
class Servebolt
{
    use Singleton, ClientMethodProxy;

    /**
     * @var ServeboltSdk SDK instance.
     */
    private $client;

    /**
     * @var int Environment Id.
     */
    private $environmentId;

    /**
     * Servebolt constructor.
     * @throws ServeboltInvalidOrMissingAuthDriverException
     */
    private function __construct()
    {
        $this->setEnvironmentId();
        if ($this->hasApiToken()) {
            $this->client = new ServeboltSdk([
                'apiToken' => $this->getApiToken(),
            ]);
        }
    }

    /**
     * Check whether we have an API token.
     *
     * @return bool
     */
    private function hasApiToken(): bool
    {
        return !is_null($this->getApiToken());
    }

    /**
     * Check whether we have an environment Id.
     *
     * @return bool
     */
    private function hasEnvironmentId(): bool
    {
        return !is_null($this->getEnvironmentId());
    }

    /**
     * Check whether the API is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->hasApiToken()
            && $this->hasEnvironmentId();
    }

    private function setEnvironmentId(): void
    {
        $env = EnvFileReader::getInstance();
        $this->environmentId = $env->id;
    }

    /**
     * @return int
     */
    public function getEnvironmentId(): ?int
    {
        return $this->environmentId;
    }

    /**
     * @return string
     */
    public function getApiToken(): ?string
    {
        $env = EnvFileReader::getInstance();
        return $env->api_key;
    }
}
