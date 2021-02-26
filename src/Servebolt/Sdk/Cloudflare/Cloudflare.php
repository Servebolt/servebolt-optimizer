<?php

namespace Servebolt\Optimizer\Api\Sdk\Cloudflare;

class Cloudflare
{

    /**
     * Singleton instance.
     *
     * @var null
     */
    private static $instance = null;

    /**
     * Cloudflare API credentials.
     *
     * @var array
     */
    private $credentials = null;

    /**
     * Cloudflare API authentication type.
     *
     * @var null
     */
    private $auth_type = null;

    /**
     * Cloudflare Zone Id.
     *
     * @var null
     */
    private $zone_id = null;

    /**
     * Whether to debug requests to log.
     *
     * @var bool
     */
    private $request_debug = false;

    /**
     * Cloudflare API URL.
     *
     * @var string
     */
    private $base_uri = 'https://api.cloudflare.com/client/v4/';

    /**
     * Instantiate class.
     *
     * @return Cloudflare|null
     */
    public static function get_instance() {
        if ( self::$instance == null ) {
            self::$instance = new SB_CF_SDK();
        }
        return self::$instance;
    }

    /**
     * Whether to debug the requests or not.
     *
     * @return bool
     */
    private function debug() {
        if ( defined('SB_CF_REQUEST_DEBUG') && is_bool(SB_CF_REQUEST_DEBUG) ) {
            return SB_CF_REQUEST_DEBUG;
        }
        return (bool) apply_filters('sb_optimizer_cf_api_request_debug', $this->request_debug);
    }

    /**
     * Prepare request headers.
     *
     * @param array $additional_headers
     *
     * @return array
     */
    private function prepare_request_headers (array $additional_headers = []): array {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
        switch ( $this->auth_type ) {
            case 'api_token':
                $headers['Authorization'] = 'Bearer ' . $this->get_credential('api_token');
                break;
            case 'api_key':
                $headers['X-Auth-Email'] = $this->get_credential('email');
                $headers['X-Auth-Key'] = $this->get_credential('api_key');
                break;
        }
        return array_merge($headers, $additional_headers);
    }

    /**
     * Send request.
     *
     * @param string $uri
     * @param string $method
     * @param array $data
     * @param array $headers
     * @param array $additional_args
     *
     * @return array|bool
     */
    private function request(string $uri, string $method = 'GET', array $data = [], array $headers = [], array $additional_args = []) {
        $method = strtoupper($method);
        $request_url = $this->base_uri . $uri;
        $base_args = [
            'headers' => $this->prepare_request_headers($headers)
        ];

        // Convert data-parameters to JSON for selected request methods
        if ( in_array($method, ['POST']) ) {
            $data = json_encode($data);
        }

        // Add request data only if present
        if ( ! empty($data) ) {
            $base_args['body'] = $data;
        }

        $args = array_merge($base_args, $additional_args);

        switch ( $method ) {
            case 'GET':
                $response = wp_remote_get($request_url, $args);
                break;
            case 'POST':
                $response = wp_remote_post($request_url, $args);
                break;
            /*
            case 'DELETE':
                $args['method'] = 'DELETE';
                $response = wp_remote_request($request_url, $args);
                break;
            */
            default:
                return false;
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        $body      = wp_remote_retrieve_body($response);
        $json      = json_decode($body);

        $result = compact('http_code', 'response', 'body', 'json');

        if ( $this->debug() ) {
            error_log(json_encode(array_merge($result, compact('request_url', 'args'))));
        }

        return $result;
    }

    /**
     * Get user details of authenticated user.
     *
     * @return mixed
     */
    public function get_user_details() {
        try {
            $request = $this->request('user');
            return $request['json']->result;
        } catch (Exception $e) {
            return sb_cf_error($e);
        }
    }

    /**
     * Get user ID of authenticated user.
     *
     * @return mixed
     */
    public function get_user_id() {
        $user = $this->get_user_details();
        return isset($user->id) ? $user->id : false;
    }

    /**
     * Verify API token.
     *
     * @param bool $token
     *
     * @return bool
     */
    public function verify_token($token = false) {
        if ( ! $token ) {
            $token = $this->get_credential('api_token');
        }
        try {
            $request = $this->request('user/tokens/verify', 'GET', [], [
                'Authorization' => 'Bearer ' . $token,
            ]);
            return $request['http_code'] === 200;
        } catch (Exception $e) {
            return sb_cf_error($e);
        }
    }

    /**
     * Verify that we can fetch the user.
     *
     * @return bool
     */
    public function verify_user(): bool {
        return is_string($this->get_user_id());
    }

    /**
     * Purge one or more URL's in a zone.
     **
     * @param array $urls
     *
     * @return bool|void
     */
    public function purge_urls(array $urls) {

        $zone_id = $this->get_zone_id();
        if ( ! $zone_id || empty($zone_id) ) return false;

        // Check whether we should purge all
        $should_purge_all = in_array('all', $urls);

        // Maybe alter URL's before sending to CF?
        $urls = apply_filters('sb_optimizer_urls_to_be_purged', $urls);

        // A hacky way of limiting so that we don't get an error from the Cloudflare API about too many URLs in purge request.
        // Future solution will be to queue up all URLs and purge them in chunks the size of 30 each.
        $max_number = apply_filters('sb_optimizer_max_number_of_urls_to_be_purged', false);
        if ( is_int($max_number) ) {
            $urls = array_slice($urls, 0, $max_number);
        }

        // Only keep the URL's in the cache purge queue array
        $urls = array_filter( $urls, function($url) {
            return $url !== 'all';
        } );

        // Purge all, return error if we cannot execute
        if ( $should_purge_all ) {
            $purge_all_request = $this->purge_all($zone_id);
            if ( $purge_all_request !== true ) {
                return $purge_all_request;
            }
        }

        try {
            $request = $this->request('zones/' . $zone_id . '/purge_cache', 'POST', [
                'files' => $urls,
            ]);
            if ( isset($request['json']->result->id) ) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            return sb_cf_error($e);
        }
    }

    /**
     * Purge all URL's in a zone.
     *
     * @param bool $zone_id
     *
     * @return bool
     */
    public function purge_all($zone_id = false) {
        if ( ! $zone_id ) {
            $zone_id = $this->get_zone_id();
        }
        if ( ! $zone_id || empty($zone_id) ) return false;
        try {
            $request = $this->request('zones/' . $zone_id . '/purge_cache', 'POST', [
                'purge_everything' => true,
            ]);
            if ( isset($request['json']->result->id) ) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            return sb_cf_error($e);
        }
    }

    /**
     * Set credentials. Can handle both API key-setup and API token-setup.
     *
     * @param string $auth_type
     * @param array $credentials
     */
    public function set_credentials(string $auth_type, array $credentials) {
        $this->auth_type = $auth_type;
        $this->credentials = $credentials;
    }

    /**
     * Get credentials.
     *
     * @return array
     */
    private function get_credentials() {
        return $this->credentials;
    }

    /**
     * Get specific credential from credentials.
     *
     * @param string $key
     *
     * @return array|bool|mixed
     */
    public function get_credential(string $key) {
        $credentials = $this->get_credentials();
        if ( $credentials && array_key_exists($key, $credentials) ) {
            return $credentials[$key];
        }
        return false;
    }

    /**
     * List zones.
     *
     * @return bool|stdClass
     */
    public function list_zones() {

        // Function arguments
        /*
        string $name = '',
        string $status = '',
        int $page = 1,
        int $perPage = 20,
        string $order = '',
        string $direction = '',
        string $match = 'all'

        $query = [
            'page' => $page,
            'per_page' => $perPage,
            'match' => $match
        ];

        if (!empty($name)) {
            $query['name'] = $name;
        }

        if (!empty($status)) {
            $query['status'] = $status;
        }

        if (!empty($order)) {
            $query['order'] = $order;
        }

        if (!empty($direction)) {
            $query['direction'] = $direction;
        }
        */

        $query = [
            'page'     => 1,
            'per_page' => 20,
            'match'    => 'all'
        ];

        try {
            $request = $this->request('zones', 'GET', $query);
            return $request['json']->result;
        } catch (Exception $e) {
            return sb_cf_error($e);
        }
    }

    /**
     * Check if zone exists.
     *
     * @param string $zone_id
     *
     * @return bool
     */
    private function zone_exists(string $zone_id): bool {
        return $this->get_zone_by_key($zone_id, 'id') !== false;
    }

    /**
     * Get zone by Id.
     *
     * @param string $zone_id
     *
     * @return bool|object
     */
    public function get_zone_by_id(string $zone_id) {
        try {
            $request = $this->request('zones/' . $zone_id);
            return $request['json']->result;
        } catch (Exception $e) {
            return sb_cf_error($e);
        }
    }

    /**
     * Get zone from Cloudflare by given key.
     *
     * @param string $zone_name
     * @param string $key
     *
     * @return bool
     */
    public function get_zone_by_key(string $zone_name, string $key = 'name') {
        foreach ( $this->list_zones() as $zone ) {
            if ( isset($zone->{ $key }) && $zone->{ $key } === $zone_name ) {
                return $zone;
            }
        }
        return true;
    }

    /**
     * Set zone Id (to be used when purging cache).
     *
     * @param string $zone_id
     * @param bool $do_zone_check
     *
     * @return bool
     */
    public function set_zone_id(string $zone_id, bool $do_zone_check = true): bool {
        if ( ! $zone_id || ( $do_zone_check && ! $this->zone_exists($zone_id) ) ) return false;
        $this->zone_id = $zone_id;
        return true;
    }

    /**
     * Get zone Id.
     *
     * @return null
     */
    private function get_zone_id() {
        return $this->zone_id;
    }

}
