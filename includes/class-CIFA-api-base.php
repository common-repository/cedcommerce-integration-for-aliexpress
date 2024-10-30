<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://cedcommerce.com
 * @since      1.0.0
 *
 * @package    CIFA
 * @subpackage CIFA/includes
 */

/**
 * Base Api Class.
 *
 * This class defines all code necessary api communication.
 *
 * @since      1.0.0
 * @package    CIFA
 * @subpackage CIFA/includes
 */
class CIFA_Api_Base {



	/**
	 * Get Request.
	 *
	 * @param string $endpoint Api endpoint of mautic.
	 * @param array  $args     Header and Request data.
	 */
	public function get( $endpoint, $args = array() ) {
		return $this->request( 'GET', $endpoint, $args );
	}

	/**
	 * Post Request.
	 *
	 * @param string $endpoint Api endpoint of mautic.
	 * @param array  $args     Header and Request data.
	 */
	public function post( $endpoint, $args = array() ) {
		return $this->request( 'POST', $endpoint, $args );
	}

	/**
	 * Send api request
	 *
	 * @param string $method   HTTP method.
	 * @param string $endpoint Api endpoint.
	 * @param array  $args     Header and Request data.
	 */
	public function request( $method, $endpoint, $args = array() ) {

		$method            = strtoupper( trim( $method ) );
		$url               = CIFA_HOME_URL . $endpoint;
		$args['sslverify'] = false;
		$response          = ( 'GET' === $method ) ? wp_remote_get( $url, $args ) :
			wp_remote_post( $url, $args );

		$code     = (int) wp_remote_retrieve_response_code( $response );
		$message  = wp_remote_retrieve_response_message( $response );
		$body     = wp_remote_retrieve_body( $response );
		$data     = json_decode( $body, ARRAY_A );
		$response = compact( 'code', 'message', 'data' );
		if ( ! empty( $response['data']['code'] ) && 'token_expired' === $response['data']['code'] ) {
			$token_data                       = $this->update_user_token();
			$args['headers']['Authorization'] = 'Bearer ' . $token_data['token_res'];
			$this->request( $method, $endpoint, $args );
		}
		return $response;
	}
	public function update_user_token() {
		$wooconnection_data = get_option( 'CIFA_token_data', '' );
		$admin_data         = wp_get_current_user();
		$admin_data         = (array) $admin_data->data;
		$wooconnection_data = json_decode( $wooconnection_data, true );
		$param              = array();
		$param['username']  = home_url();
		$headers            = array();
		$headers[]          = 'Content-Type: application/json';
		$endpoint           = 'woocommercehome/request/getUserToken';
		$response           = wp_remote_post(
			CIFA_HOME_URL . $endpoint,
			array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 10,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'headers'     => $headers,
				'body'        => $param,
			)
		);
		$response_code      = (int) wp_remote_retrieve_response_code( $response );
		$body               = wp_remote_retrieve_body( $response );
		$response_data      = json_decode( $body, ARRAY_A );
		if ( 200 === $response_code && ! empty( $response_data['token_res'] ) ) {
			$data               = array(
				'token' => ! empty( $response_data['token_res'] ) ? $response_data['token_res'] : false,
				'shop'  => ! empty( $wooconnection_data['shop'] ) ? sanitize_text_field( wp_unslash( $wooconnection_data['shop'] ) ) : false,
			);
			$wooconnection_data = wp_json_encode( $data );
			update_option( 'CIFA_token_data', $wooconnection_data );
			set_transient( 'CIFA_token_data', $wooconnection_data, 10800 );
		}
		return $response_data;
	}
}
