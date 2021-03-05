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
     * @param null|int $blogId
     * @return mixed|void
     */
    private function getApiToken($blogId = null) : string
    {
        if ( is_numeric($blogId) ) {
            return sb_get_blog_option($blogId, 'sb_api_token');
        } else {
            return sb_get_option('sb_api_token');
        }
    }

    /**
     * @param null|int $blogId
     */
    private function setEnvironmentId($blogId = null) : void
    {
        if (is_numeric($blogId)) {
            $this->environmentId = sb_get_blog_option($blogId, 'sb_api_environment_id');
        } else {
            $this->environmentId = sb_get_option('sb_api_environment_id');
        }
    }

    /**
     * @return int
     */
    public function getEnvironmentId() : int
    {
        return $this->environmentId;
    }
}
