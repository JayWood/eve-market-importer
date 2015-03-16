<?php

class Eve_Crest_Request {
	protected $protocol, $url, $con;

	public $error;

	public function __construct( $connect_params = array() ) {
		$this->con = $this->make_con( $connect_params );
	}

	public function make_con( $connect_params ) {

		$defaults = array(
			'url_params' => array(),
			'endpoint'   => 'market',
			'protocol'   => 'http',
			'url'        => 'public-crest.eveonline.com',
			'timeout'    => 5,
		);

		$connect_params             = wp_parse_args( $connect_params, $defaults );
		$connect_params['endpoint'] = $this->sanitize_endpoint( $connect_params['endpoint'] );
		$connect_params['protocol'] = in_array( $connect_params['protocol'], array( 'http', 'https') ) ? $connect_params['protocol'] : 'http';

		$url_base = sprintf( '%s://%s/%s', $connect_params['protocol'], $connect_params['url'], $connect_params['endpoint'] );
		if( ! empty( $connect_params['url_params'] ) && is_array( $connect_params['url_params'] ) ){
			$url_base = add_query_arg( $connect_params['url_params'], $url_base );
		}

		$http_args = apply_filters( 'eve_get_http_args', array( 'timeout' => intval( $connect_params['timeout'] ) ), $connect_params );
		$response = wp_remote_get( $url_base, $http_args );

		if( ! is_wp_error( $response ) ){
			return $response;
		} else { error_log( $response->get_error_message() ); }


		return false;
	}

	public function get() {
		if ( ! is_wp_error( $this->con ) ) {
			return $this->con;
		}

		return false;
	}

	public function get_body() {
		if ( ! is_wp_error( $this->con ) && isset( $this->con['body'] ) ) {
			return $this->con['body'];
		}

		return false;
	}

	public function get_headers() {
		if ( ! is_wp_error( $this->con ) && isset( $this->con['headers'] ) ) {
			return $this->con['headers'];
		}

		return false;
	}

	public function get_decoded_body() {
		$body = $this->get_body();
		if ( $body ) {
			return json_decode( $body );
		}

		return false;
	}

	public function sanitize_endpoint( $endpoint ) {
		return trailingslashit( $endpoint );
	}
}