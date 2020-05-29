<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_CF_SDK
 *
 * This class lets us communicate with the Cloudflare API using native the native WP HTTP API. Earlier we used Guzzle for this, but it turns out it created conflicts with a lot of plugins due to lack of namespace separation between Guzzle-versions.
 */
class SB_CF_SDK {

	/**
	 * Whether to debug requests to log.
	 *
	 * @var bool
	 */
	protected $request_debug = false;

	/**
	 * Cloudflare API URL.
	 *
	 * @var string
	 */
	private $base_uri = 'https://api.cloudflare.com/client/v4/';

	/**
	 * Whether to debug or not.
	 *
	 * @return bool
	 */
	private function debug() {
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
	protected function request(string $uri, string $method = 'GET', array $data = [], array $headers = [], array $additional_args = []) {
		$method = strtoupper($method);
		$request_url = $this->base_uri . $uri;
		$base_args = [
			'headers' => $headers = $this->prepare_request_headers($headers)
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
			error_log(json_encode($result));
		}

		return $result;
	}

}
