<?php

namespace Servebolt\Optimizer\Sdk\Cloudflare\ApiMethods;

trait User
{
    /**
     * Verify API token.
     *
     * @param bool $token
     *
     * @return bool
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
            return sb_cf_error($e);
        }
    }

    /**
     * Get user ID of authenticated user.
     *
     * @return mixed
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
        try {
            $request = $this->request('user');
            return $request['json']->result;
        } catch (Exception $e) {
            return sb_cf_error($e);
        }
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
