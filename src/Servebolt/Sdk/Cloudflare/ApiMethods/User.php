<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods;

use Servebolt\Optimizer\Exceptions\ApiError;
use Exception;

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
     * @throws ApiError
     */
    public function verifyApiToken($token = false) {
        if ( ! $token ) {
            $token = $this->getCredential('api_token');
        }
        try {
            $request = $this->request('user/tokens/verify', 'GET', [], [
                'Authorization' => 'Bearer ' . $token,
            ]);
            return $request['httpCode'] === 200;
        } catch (Exception $e) {
            throw new ApiError(
                $this->getErrorsFromRequest($request),
                $request
            );
        }
    }

    /**
     * Get user ID of authenticated user.
     *
     * @return false
     * @throws ApiError
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
     * @throws ApiError
     */
    public function getUserDetails()
    {
        try {
            $request = $this->request('user');
            return $request['json']->result;
        } catch (Exception $e) {
            throw new ApiError(
                $this->getErrorsFromRequest($request),
                $request
            );
        }
    }

    /**
     * Verify that we can fetch the user.
     *
     * @return bool
     * @throws ApiError
     */
    public function verifyUser() : bool
    {
        return is_string($this->getUserId());
    }
}
