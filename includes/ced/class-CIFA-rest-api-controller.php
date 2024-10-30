<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://cedcommerce.com
 * @since      1.0.0
 *
 * @package    CIFA
 * @subpackage CIFA/includes/ced
 */

/**
 * Will handle custom api for ced connector.
 *
 * @since 1.0.2
 */

class CIFA_REST_CIFA_Api_Controller {

	/**
	 * Namespace to be used.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Base url to be used for endpoint.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected $rest_base = '/ced';

	/**
	 * Will update product quantity.
	 *
	 * @param object $request current request object.
	 * @since 1.0.0
	 * @return mixed
	 */
	public function ced_update_product_quantity( $request ) {
		$params = rest_sanitize_object( $request->get_params() );
		if ( empty( $params['skus'] ) ) {
			return new WP_Error( 'ced_rest_missing_required_param', __( 'Required param missing.', 'cedcommerce-integration-for-aliexpress' ), array( 'status' => 406 ) );
		}
		$skus = $params['skus'];
		if ( ! rest_is_object( $skus ) ) {
			return new WP_Error( 'ced_rest_invalid_data_format', __( 'Invalid data format.', 'cedcommerce-integration-for-aliexpress' ), array( 'status' => 406 ) );
		}
		$success_skus = array();
		$err_skus     = array();
		foreach ( $skus as $sku ) {
			$product_id = wc_get_product_id_by_sku( $sku );
			if ( ! $product_id ) {
				$err_skus[] = $sku;
				continue;
			}
			$success_skus[ $sku ] = wc_update_product_stock( wc_get_product( $product_id ), 1, 'decrease' );
		}
		return rest_ensure_response(
			array(
				'success'    => true,
				'successkus' => $success_skus,
				'errorskus'  => $err_skus,
			)
		);
	}

	/**
	 * Will register API route.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/product_quantity',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'ced_update_product_quantity' ),
				'permission_callback' => array( $this, 'update_product_permission_cb' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-meta_attr',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'ced_receive_callback_clientdata' ),
				'permission_callback' => array( $this, 'get_post_meta_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update_shipping_carrier',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ced_update_source_shipping_carrier' ),
				'permission_callback' => array( $this, 'update_post_permission_cb' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get_order_status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'CIFA_get_order_status' ),
				'permission_callback' => array( $this, 'get_post_meta_permission' ),
			)
		);
	}

	/**
	 * Will check if customer is able to update product data.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public function update_product_permission_cb() {
		if ( ! wc_rest_check_post_permissions( 'product', 'batch' ) ) {
			return new WP_Error( 'ced_rest_cannot_edit', __( 'Sorry, you are not allowed to update resources.', 'cedcommerce-integration-for-aliexpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}
	/**
	 * Will check if customer is able to update product data.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_post_meta_permission() {
		if ( ! wc_rest_check_post_permissions( 'post', 'read' ) ) {
			return new WP_Error( 'ced_rest_cannot_edit', __( 'Sorry, you are not allowed to access resources.', 'cedcommerce-integration-for-aliexpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}
	/**
	 * Will check if customer is able to update product data.
	 *
	 * @since 1.0.0
	 * @return mixed
	 */
	public function update_post_permission_cb() {
		if ( ! wc_rest_check_post_permissions( 'post', 'batch' ) ) {
			return new WP_Error( 'ced_rest_cannot_edit', __( 'Sorry, you are not allowed to update resources.', 'cedcommerce-integration-for-aliexpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}


	/**
	 * Function to get order status
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function CIFA_get_order_status() {
		$order_statuses = wc_get_order_statuses();
		return $order_statuses;
	}

	/**
	 * Function to update shipping carriers
	 *
	 * @param object $request contains request params.
	 * @return bool
	 */
	public function ced_update_source_shipping_carrier( $request ) {

		$params        = wp_parse_args( $request->get_params() );
		$data          = $params['data'];
		$check_updated = update_option( 'CIFA_shipping_carriers', $data );
		if ( $check_updated ) {
			return true;
		}
		return false;
	}

	/**
	 * Undocumented function
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function ced_receive_callback_clientdata() {
		global $wpdb;
		$post_meta_keys = array();
		$results        = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta", 'ARRAY_A' );
		foreach ( $results as $key => $meta_key ) {
			$post_meta_keys[] = 'meta-' . $meta_key['meta_key'];
		}
		$attributes = wc_get_attribute_taxonomies();
		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attributes_object ) {
				$post_meta_keys[] = 'attribute-' . $attributes_object->attribute_label;
			}
		}
		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$dimention_unit = get_option( 'woocommerce_dimension_unit' );
		$currency_unit  = get_option( 'woocommerce_currency' );
		$encoded        = implode( ',', $post_meta_keys );
		$required       = array( 'gtin', 'brand', 'weightunit', 'dimentionunit', 'length', 'width', 'height', 'weight' );
		$required       = implode( ',', $required );
		$unit_data      = array( 'weight-' . $weight_unit, 'dimension-' . $dimention_unit, 'currency-' . $currency_unit );
		$unit_data      = implode( ',', $unit_data );
		return array(
			'meta_data' => base64_encode( $encoded ),
			'req_data'  => base64_encode( $required ),
			'unit_data' => base64_encode( $unit_data ),
		);
	}
}
