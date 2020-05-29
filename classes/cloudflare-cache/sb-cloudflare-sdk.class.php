<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Class SB_CF_SDK
 *
 * This class lets us communicate with the Cloudflare API using native the native WP HTTP API. Earlier we used Guzzle for this, but it turns out it created conflicts with a lot of plugins due to lack of namespace separation between Guzzle-versions.
 */
class SB_CF_SDK {

	/**
	 * Cloudflare API URL.
	 *
	 * @var string
	 */
	private $base_uri = 'https://api.cloudflare.com/client/v4/';

	/**
	 * Prepare request headers.
	 *
	 * @param $additional_headers
	 *
	 * @return array
	 */
	private function prepare_request_headers ($additional_headers) {
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
	 * @param $uri
	 * @param string $method
	 * @param array $data
	 * @param array $headers
	 * @param array $additional_args
	 *
	 * @return array|bool
	 */
	protected function request($uri, $method = 'GET', $data = [], $headers = [], $additional_args = []) {

		$headers = $this->prepare_request_headers($headers);
		$request_url = $this->base_uri . $uri;

		$args = array_merge([
			'headers'     => $headers = $this->prepare_request_headers($headers),
			'body'        => json_encode($data),
		], $additional_args);

		switch (strtoupper($method)) {
			case 'GET':
				$response = wp_remote_get($request_url, $args);
				break;
			case 'POST':
				$response = wp_remote_post($request_url, $args);
				break;
			case 'DELETE':
				$args['method'] = 'DELETE';
				$response = wp_remote_request($request_url, $args);
				break;
			default:
				return false;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = wp_remote_retrieve_body($response);
		$json      = json_decode($body);

		return compact('http_code', 'response', 'body', 'json');
	}

}