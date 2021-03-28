<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods;

/**
 * Trait User
 * @package Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods
 */
trait User
{

    /**
     * Verify API token.
     *
     * @param false $token
     * @return bool
     */
    public function verifyApiToken($token = false)
    {
        if ( ! $token ) {
            $token = $this->getCredential('apiToken');
        }
        $response = $this->request('user/tokens/verify', 'GET', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);
        return $response['httpCode'] === 200;
    }

    /**
     * Get user ID of authenticated user.
     *
     * @return false
     */
    public function getUserId()
    {
        $user = $this->getUserDetails();
        return isset($user->id) ? $user->id : false;
    }

    /**
     * Get user details of authenticated user.
     *
     * @return mixed
     */
    public function getUserDetails()
    {
        $request = $this->request('user');
        return $request['json']->result;
    }

    /**
     * Verify that we can fetch the user.
     *
     * @return bool
     */
    public function verifyUser() : bool
    {
        return is_string($this->getUserId());
    }
}
