<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if (
	isset( $_POST ) && isset( $_REQUEST['aliexpress_nonce'] ) &&
	wp_verify_nonce(
		sanitize_text_field( wp_unslash( $_REQUEST['aliexpress_nonce'] ) ),
		'aliexpress_nonce_field'
	)
) {
	$sanitized_array    = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	$req_data           = array();
	$product_attributes = array();
	if ( ! empty( $sanitized_array['product_attributes'] ) ) {
		foreach ( $sanitized_array['product_attributes'] as $key => $value ) {
			$attributes                = array();
			$attributes['displayName'] = ! empty( $value['displayName'] ) ? $value['displayName'] : '';
			$attributes['id']          = ! empty( $value['id'] ) ? $value['id'] : '';
			$attributes['customised']  = ! empty( $value['customised'] ) && '1' === $value['customised'] ? true : false;
			if ( ! empty( $value['required'] ) && '1' === $value['required'] ) {
				$attributes['required'] = true;
				if ( empty( $value['name'] ) && empty( $value['value'] ) ) {
					$req_data[] = $value['displayName'];
				}
			} else {
				$attributes['required'] = false;
			}
			$attributes['variation'] = ! empty( $value['variation'] ) && '1' === $value['variation'] ? true : false;
			$attributes['value']     = ! empty( $value['value'] ) || 0 == $value['value'] ? $value['value'] : '';
			if ( 'select_woocommerce_attr' == $value['select_source_target_attr_type'] ) {
				$attributes['name'] = ! empty( $value['name'] ) ? $value['name']['source'] : '';
				$attributes['type'] = 'attribute';
			} elseif ( 'select_aliexpress_attr' == $value['select_source_target_attr_type'] ) {
				$attributes['type'] = 'predefined';
				$attributes['name'] = ! empty( $value['name'] ) ? $value['name']['target'] : '';
			} elseif ( 'select_custom_value' == $value['select_source_target_attr_type'] ) {
				$attributes['type'] = 'fixed';
				if ( empty( $attributes['name'] ) ) {
					$attributes['name'] = $attributes['value'];
				}
			}
			$attributes['name']         = ! empty( $attributes['name'] ) ? stripslashes( $attributes['name'] ) : '';
			$product_attributes[ $key ] = $attributes;
		}
	}

	$variation_attributes = array();
	if ( ! empty( $sanitized_array['variation_attributes'] ) ) {
		foreach ( $sanitized_array['variation_attributes'] as $key => $value ) {
			$attributes                = array();
			$attributes['displayName'] = ! empty( $value['displayName'] ) ? $value['displayName'] : '';
			$attributes['id']          = ! empty( $value['id'] ) ? $value['id'] : '';
			$attributes['customised']  = ! empty( $value['customised'] ) && '1' === $value['customised'] ? true : false;
			if ( ! empty( $value['required'] ) && '1' === $value['required'] ) {
				$attributes['required'] = true;
				if ( empty( $value['name'] ) && empty( $value['value'] ) ) {
					$req_data[] = $value['displayName'];
				}
			} else {
				$attributes['required'] = false;
			}
			$attributes['variation'] = ! empty( $value['variation'] ) && '1' === $value['variation'] ? true : false;

			$attributes['value'] = ( strpos( $value['value'], ',' ) !== false ) ?
				array_filter(
					( explode( ',', $value['value'] ) ),
					function ( $v ) {
						if ( ! empty( $v ) ) {
							return $v;
						}
					}
				) : $value['value'];
			if ( 'select_woocommerce_attr' == $value['select_source_target_attr_type'] ) {
				$attributes['type'] = 'attribute';
				$attributes['name'] = ! empty( $value['name'] ) ? $value['name']['source'] : '';
			} elseif ( 'select_aliexpress_attr' == $value['select_source_target_attr_type'] ) {
				$attributes['type'] = 'predefined';
				$attributes['name'] = ! empty( $value['name'] ) ? $value['name']['target'] : '';
			} elseif ( 'select_custom_value' == $value['select_source_target_attr_type'] ) {
				$attributes['type'] = 'fixed';
				if ( empty( $attributes['name'] ) ) {
					$attributes['name'] = $attributes['value'];
				}
			}
			$attributes['name']           = ! empty( $attributes['name'] ) ? $attributes['name'] : '';
			$variation_attributes[ $key ] = $attributes;
		}
	}
	$category_name = $sanitized_array['aliexpress_default_category_name'];
	$category_id   = (int) $sanitized_array['get_target_categories'];

	$attr_mapping = $product_attributes + $variation_attributes;

	$endpoint = 'connector/config/saveConfig';

	$args = array(
		'headers'     => $headers,
		'method'      => 'POST',
		'data_format' => 'body',
		'sslverify'   => false,
		'body'        => wp_json_encode(
			array(
				'data' => array(
					array(
						'group_code' => 'default_profile',
						'data'       => array(
							'default_profile' => array(
								'name'               => 'Default',
								'query'              => 'default',
								'category_id'        => array(
									'label' => $category_name,
									'value' => (string) $category_id,
								),
								'data'               => array(
									array(
										'data_type' => 'attribute_data',
										'id'        => 1,
										'data'      => array(
											'product_attributes' => $product_attributes,
											'variation_attributes' => $variation_attributes,
										),
									),
									array(
										'data_type' => 'price_rule',
										'id'        => 2,
										'data'      => array(),
									),
									array(
										'data_type' => 'title_optimize',
										'id'        => 3,
										'data'      => array(
											'title_template' => array(
												'text_mappings' => array(),
											),
										),
									),
								),
								'attributes_mapping' => $attr_mapping,
								'targets'            => array(
									array(
										'target_marketplace' => 'aliexpress',
										'attributes_mapping' => $attr_mapping,
										'shops' => array(
											array(
												'shop_id' => (string) json_decode( get_option( 'CIFA_user_data' ), ARRAY_A )['target_shop_id'],
												'active'  => 1,
												'warehouses' => array(
													array(
														'warehouse_id' => 1111,
														'active'       => 1,
														'attributes_mapping' => $attr_mapping,
														'sources' => array(
															array(
																'source_marketplace' => 'woocommerce',
																'attributes_mapping' => $attr_mapping,
																'shops' => array(
																	array(
																		'shop_id' => (string) json_decode( get_option( 'CIFA_user_data' ), ARRAY_A )['source_shop_id'],
																		'active'  => 1,
																		'warehouses' => array(
																			array(
																				'active' => 1,
																				'attributes_mapping' => $attr_mapping,
																			),
																		),
																	),
																),
															),
														),
													),
												),
											),
										),
									),
								),
							),
						),
					),
				),
			)
		),
	);
	if ( ! empty( $category_name ) ) {
		if ( empty( $req_data ) ) {
			$response = $api_request->post( $endpoint, $args );
			if ( isset( $response['data'] ) && true == $response['data']['success'] ) {
				if ( ! empty( $_POST['option_mapping'] ) ) {
					$option_mapping = array(
						'category_id'    => $category_id,
						'option_mapping' => array(),
					);
					foreach ( $sanitized_array['option_mapping'] as $key => $value ) {
						$option_mapping['option_mapping'][] = $value;
					}

					$api_request->post(
						'webapi/rest/v1/aliexpress/product/saveVariantAttributesValueMappings',
						array(
							'headers' => $headers,
							'body'    => wp_json_encode( $option_mapping ),
						)
					);
				}
				if ( ! empty( $step_completed ) ) {
					$shipping_carrier  = '';
					$shipping_endpoint = 'webapi/rest/v1/aliexpress/getShippingCarrier';
					$shipping_args     = array(
						'headers'   => $headers,
						'sslverify' => false,
					);
					$shipping_response = $api_request->get( $shipping_endpoint, $shipping_args );
					if ( isset( $shipping_response['data'] ) && true == $shipping_response['data']['success'] ) {
						$shipping_carrier = $shipping_response['data']['data'];
						update_option( 'CIFA_shipping_carriers', $shipping_carrier );
					}
					if ( 2 == $step_completed && 4 >= $step_completed ) {
						if ( $common_callback->setStepCompleted( 3 ) ) {
							update_option( 'CIFA_step_completed', 3 );
							if ( ! empty( $shipping_carrier ) ) {
								$ship_endpoint = 'webapi/rest/v1/woocommerce/order/updateShippingCarrier';
								$ship_args     = array(
									'headers'     => $headers,
									'sslverify'   => false,
									'marketplace' => 'aliexpress',
									'data'        => $shipping_carrier,
								);
								$api_request->post( $ship_endpoint, $ship_args );
							}
							wp_redirect( esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress' ) ) );
						}
					}
				} else {
					echo '<div class="notice notice-success is-dismissible"> 		
                    <p>
                        <strong >Default template saved successfully.</strong>          
                    </p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                    </div>';
				}
			}
		} else {
			echo '<div class="notice notice-error is-dismissible"> 		
                <p>
                    <strong >' . wp_json_encode( $req_data ) . ' Fields mapping is required, kindly map and then save.</strong>          
                </p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
                </div>';
		}
	} else {
		echo '<div class="notice notice-error is-dismissible"> 		
        <p>
            <strong >Please select a category first to create Default template.</strong>          
        </p>
        <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Dismiss this notice.</span>
        </button>
        </div>';
	}
}
