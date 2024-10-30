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
 * The non-core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    CIFA
 * @subpackage CIFA/includes
 */
class CIFA_Common_Callback {


	public function get_common_header() {

		$woo_response_source_id = '';
		$woo_response_target_id = '';
		$tok                    = get_option( 'CIFA_token_data' ) ? json_decode( get_option( 'CIFA_token_data' ) )->token : ( isset( $_GET['user_token'] ) ? sanitize_text_field( wp_unslash( $_GET['user_token'] ) ) : '' );

		$url      = CIFA_HOME_URL . 'connector/get/all';
		$app_code = wp_json_encode(
			array(
				'aliexpress'  => 'aliexpress',
				'woocommerce' => 'woocommerce',
				'shopify'     => 'shopify',
			)
		);
		$app_code = base64_encode( $app_code );
		$headers  = array(
			'Content-type'  => 'json',
			'appcode'       => $app_code,
			'apptag'        => 'aliexpress_connector',
			'Authorization' => 'Bearer ' . $tok,
		);
		$args     = array(
			'headers'   => $headers,
			'sslverify' => false,
		);
		$response = wp_remote_get( $url, $args );

		$code     = (int) wp_remote_retrieve_response_code( $response );
		$message  = wp_remote_retrieve_response_message( $response );
		$body     = wp_remote_retrieve_body( $response );
		$data     = json_decode( $body, ARRAY_A );
		$response = compact( 'code', 'message', 'data' );
		if ( isset( $response['data'] ) && true == $response['data']['success'] ) {
			if ( isset( $response['data']['data']['woocommerce'] ) ) {
				if ( isset( $response['data']['data']['woocommerce']['installed'][0] ) ) {
					$woo_response_source_id = $response['data']['data']['woocommerce']['installed'][0]['_id'];
				} else {
					$woo_response_source_id = '';
				}
			} else {
				$woo_response_source_id = '';
			}
			if ( isset( $response['data']['data']['aliexpress'] ) ) {
				if ( isset( $response['data']['data']['aliexpress']['installed'][0] ) ) {
					$woo_response_target_id = $response['data']['data']['aliexpress']['installed'][0]['_id'];
				} else {
					$woo_response_target_id = '';
				}
			} else {
				$woo_response_target_id = '';
			}
		}

		return array(
			'Content-type'    => 'application/json',
			'Appcode'         => $app_code,
			'Apptag'          => 'aliexpress_connector',
			'Ced-Source-Id'   => $woo_response_source_id,
			'Ced-Source-Name' => 'woocommerce',
			'Ced-Target-Id'   => $woo_response_target_id,
			'Ced-Target-Name' => 'aliexpress',
			'Authorization'   => 'Bearer ' . $tok,
		);
	}

	public function setStepCompleted( $step ) {
		$url      = CIFA_HOME_URL . 'webapi/rest/v1/aliexpress/step';
		$headers  = $this->get_common_header();
		$args     = array(
			'headers'   => $headers,
			'sslverify' => false,
			'body'      => wp_json_encode(
				array(
					'step_completed' => $step,
				)
			),
		);
		$response = wp_remote_post( $url, $args );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		$message  = wp_remote_retrieve_response_message( $response );
		$body     = wp_remote_retrieve_body( $response );
		$data     = json_decode( $body, ARRAY_A );
		$response = compact( 'code', 'message', 'data' );
		if ( isset( $response['data'] ) && true == $response['data']['success'] ) {
			return true;
		} else {
			return false;
		}
	}

	public function getStepCompleted() {
		$url      = CIFA_HOME_URL . 'webapi/rest/v1/aliexpress/step';
		$headers  = $this->get_common_header();
		$args     = array(
			'headers'   => $headers,
			'sslverify' => false,
		);
		$response = wp_remote_get( $url, $args );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		$message  = wp_remote_retrieve_response_message( $response );
		$body     = wp_remote_retrieve_body( $response );
		$data     = json_decode( $body, ARRAY_A );
		$response = compact( 'code', 'message', 'data' );
		if ( isset( $response['data'] ) && true == $response['data']['success'] ) {
			if ( ! empty( $response['data']['data']['step_completed'] ) ) {
				return $response['data']['data']['step_completed'];
			} else {
				return 0;
			}
		} else {
			return false;
		}
	}

	public function conectorgetAll() {

		if ( get_option( 'CIFA_token_data' ) ) {
			$woo_response_source_id = '';
			$woo_response_target_id = '';
			$tok                    = json_decode( get_option( 'CIFA_token_data' ) )->token;
			$url                    = CIFA_HOME_URL . 'connector/get/all';
			$app_code               = wp_json_encode(
				array(
					'aliexpress'  => 'aliexpress',
					'woocommerce' => 'woocommerce',
					'shopify'     => 'shopify',
				)
			);
			$app_code               = base64_encode( $app_code );
			$headers                = array(
				'Content-type'  => 'json',
				'appcode'       => $app_code,
				'apptag'        => 'aliexpress_connector',
				'Authorization' => 'Bearer ' . $tok,
			);
			$args                   = array(
				'headers'   => $headers,
				'sslverify' => false,
			);
			$response               = wp_remote_get( $url, $args );

			$code     = (int) wp_remote_retrieve_response_code( $response );
			$message  = wp_remote_retrieve_response_message( $response );
			$body     = wp_remote_retrieve_body( $response );
			$data     = json_decode( $body, ARRAY_A );
			$response = compact( 'code', 'message', 'data' );
			if ( isset( $response['data'] ) && true == $response['data']['success'] ) {
				if ( isset( $response['data']['data']['woocommerce'] ) ) {
					if ( get_option( 'CIFA_user_data' ) ) {
						$data1                = json_decode( get_option( 'CIFA_user_data' ), ARRAY_A );
						$data1['source_name'] = 'woocommerce';
						update_option( 'CIFA_user_data', wp_json_encode( $data1 ) );
					} else {
						$data1['source_name'] = 'woocommerce';
						update_option( 'CIFA_user_data', wp_json_encode( $data1 ) );
					}
					if ( isset( $response['data']['data']['woocommerce']['installed'][0] ) ) {
						$woo_response_source_id = $response['data']['data']['woocommerce']['installed'][0]['_id'];
					} else {
						$woo_response_source_id = '';
					}
				} else {
					$woo_response_source_id = '';
				}
				if ( isset( $response['data']['data']['aliexpress'] ) ) {
					if ( get_option( 'CIFA_user_data' ) ) {
						$data1                = json_decode( get_option( 'CIFA_user_data' ), ARRAY_A );
						$data1['target_name'] = 'aliexpress';
						update_option( 'CIFA_user_data', wp_json_encode( $data1 ) );
					} else {
						$data1['target_name'] = 'aliexpress';
						update_option( 'CIFA_user_data', wp_json_encode( $data1 ) );
					}
					if ( isset( $response['data']['data']['aliexpress']['installed'][0] ) ) {
						$woo_response_target_id = $response['data']['data']['aliexpress']['installed'][0]['_id'];
					} else {
						$woo_response_target_id = '';
					}
				} else {
					$woo_response_target_id = '';
				}
			}
			return array(
				'source_id' => $woo_response_source_id,
				'target_id' => $woo_response_target_id,
			);
		}
	}
}
