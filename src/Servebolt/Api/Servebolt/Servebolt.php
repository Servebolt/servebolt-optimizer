<?php

namespace Servebolt\Optimizer\Api\Servebolt;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Servebolt\Optimizer\Traits\Multiton;
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
    use Multiton, ClientMethodProxy;

    /**
     * @var ServeboltSdk Sdk instance.
     */
    private $client;

    /**
     * @var int Environment Id.
     */
    private $environmentId;

    /**
     * Servebolt constructor.
     * @param null|int $blogId
     * @throws ServeboltInvalidOrMissingAuthDriverException
     */
    private function __construct($blogId = null)
    {
        $this->setEnvironmentId($blogId);
        $this->client = new ServeboltSdk([
            'apiToken' => $this->getApiToken($blogId)
        ]);
    }

    /**
     * @return string
     */
    private function getApiToken(): string
    {
        $env = Servebolt\Optimizer\EnvFile\Reader::getInstance();
        return $env->api_key;
        /*
        $key = 'sb_api_token';
        if ( is_numeric($blogId) ) {
            return sb_get_blog_option($blogId, $key);
        } else {
            return sb_get_option($key);
        }
        */
    }

    private function setEnvironmentId(): void
    {
        $env = Servebolt\Optimizer\EnvFile\Reader::getInstance();
        $this->environmentId = $env->id;
        /*
        $key = 'sb_environment_id';
        if (is_numeric($blogId)) {
            $this->environmentId = sb_get_blog_option($blogId, $key);
        } else {
            $this->environmentId = sb_get_option($key);
        }
        */
    }

    /**
     * @return int
     */
    public function getEnvironmentId(): int
    {
        return $this->environmentId;
    }
}
