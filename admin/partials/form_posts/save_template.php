<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_REQUEST['aliexpress_nonce'] ) && ! wp_verify_nonce(
	sanitize_text_field( wp_unslash( $_REQUEST['aliexpress_nonce'] ) ),
	'aliexpress_nonce_field'
) ) {
	wp_die( 'Unauthorized Access' );
}
$sanitized_array      = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$api_request          = new CIFA_Api_Base();
$common_callback      = new CIFA_Common_Callback();
$headers              = $common_callback->get_common_header();
$template_name        = '';
$price_rule           = '';
$price_rule_value     = '';
$custom_title_keyword = '';
$override_listing     = '';
$prod_count_check     = 0;
$query                = '';
if ( array_key_exists( 'prod_count_run_query', $sanitized_array ) ) {
	$prod_count_check = $sanitized_array['prod_count_run_query'];
}
if ( array_key_exists( 'CIFA_template_name', $sanitized_array ) ) {
	$template_name = $sanitized_array['CIFA_template_name'];
}
if ( array_key_exists( 'woocommerce_override_listing', $sanitized_array ) ) {
	$override_listing = $sanitized_array['woocommerce_override_listing'];
}
if ( array_key_exists( 'CIFA-condition', $sanitized_array ) ) {
	$rule_grp_condition = $sanitized_array['CIFA-condition'];
}
if ( array_key_exists( 'get_target_categories', $sanitized_array ) ) {
	$category_id = (int) $sanitized_array['get_target_categories'];
}
if ( array_key_exists( 'aliexpress_default_category_name', $sanitized_array ) ) {
	$category_name = $sanitized_array['aliexpress_default_category_name'];
}
if ( array_key_exists( 'custom_title_keyword', $sanitized_array ) ) {
	$custom_title_keyword = $sanitized_array['custom_title_keyword'];
}
if ( array_key_exists( 'price_rule', $sanitized_array ) ) {
	$price_rule = $sanitized_array['price_rule'];
}
if (
	array_key_exists(
		'default_temp_custom_price_rule_value',
		$sanitized_array
	)
) {
	$price_rule_value =
		$sanitized_array['default_temp_custom_price_rule_value'];
}
if ( array_key_exists( 'CIFA_template_id', $sanitized_array ) ) {
	$temp_id = $sanitized_array['CIFA_template_id'];
}
if ( array_key_exists( 'CIFA_user_id', $sanitized_array ) ) {
	$user_id = $sanitized_array['CIFA_user_id'];
}
if ( array_key_exists( 'rule_group_condition', $sanitized_array ) ) {
	$query = $sanitized_array['rule_group_condition'];
}

$overwrite_list = false;
if ( ! empty( $override_listing ) ) {
	$overwrite_list = true;
} else {
	$overwrite_list = false;
}

$title_keyword_array = array();
foreach ( $sanitized_array as $key3 => $value3 ) {
	if ( str_contains( $key3, 'title_rule' ) ) {
		$title_keyword_array = $value3;
	}
}

$title_rules['text_mappings'][0] = 'title_template_1';
$title_rules['title_template_1'] = $custom_title_keyword;
$count                           = 2;
foreach ( $title_keyword_array as $key4 => $value4 ) {
	$title_rules[ 'title_template_' . $count ] = $value4;
	++$count;
}

$req_data           = array();
$product_attributes = array();
if ( ! empty( $sanitized_array['product_attributes'] ) ) {
	foreach ( $sanitized_array['product_attributes'] as $key => $value ) {
		$attributes                = array();
		$attributes['displayName'] = ! empty( $value['displayName'] )
			? $value['displayName']
			: '';
		$attributes['id']          = ! empty( $value['id'] ) ? $value['id'] : '';
		$attributes['customised']  =
			! empty( $value['customised'] ) && '1' === $value['customised']
			? true
			: false;
		if ( ! empty( $value['required'] ) && '1' === $value['required'] ) {
			$attributes['required'] = true;
			if ( empty( $value['name'] ) && empty( $value['value'] ) ) {
				$req_data[] = $value['displayName'];
			}
		} else {
			$attributes['required'] = false;
		}
		$attributes['variation'] =
			! empty( $value['variation'] ) && '1' === $value['variation']
			? true
			: false;
		$attributes['value']     =
			! empty( $value['value'] ) || 0 == $value['value']
			? $value['value']
			: '';
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
		$attributes['displayName'] = ! empty( $value['displayName'] )
			? $value['displayName']
			: '';
		$attributes['id']          = ! empty( $value['id'] ) ? $value['id'] : '';
		$attributes['customised']  =
			! empty( $value['customised'] ) && '1' === $value['customised']
			? true
			: false;
		if ( ! empty( $value['required'] ) && '1' === $value['required'] ) {
			$attributes['required'] = true;
			if ( empty( $value['name'] ) && empty( $value['value'] ) ) {
				$req_data[] = $value['displayName'];
			}
		} else {
			$attributes['required'] = false;
		}
		$attributes['variation'] =
			! empty( $value['variation'] ) && '1' === $value['variation']
			? true
			: false;

		$attributes['value'] =
			strpos( $value['value'], ',' ) !== false
			? explode( ',', $value['value'] )
			: $value['value'];
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
ksort( $product_attributes );
ksort( $variation_attributes );

$attr_mapping = $product_attributes + $variation_attributes;

$endpoint  = 'connector/profile/saveProfile';
$user_data = json_decode( get_option( 'CIFA_user_data' ), ARRAY_A );

$body = array(
	'useRefinProduct' => true,
	'data'            => array(
		'name'                      => $template_name,
		'query'                     => $query,
		'category_id'               => array(
			'label' => $category_name,
			'value' => $category_id,
		),
		'user_id'                   => $user_data['user_id'],
		'overWriteExistingProducts' => $overwrite_list,
		'data'                      => array(
			array(
				'data_type' => 'attribute_data',
				'id'        => 1,
				'data'      => array(
					'product_attributes'   => $product_attributes,
					'variation_attributes' => $variation_attributes,
				),
			),
			array(
				'data_type' => 'price_rule',
				'id'        => 2,
				'data'      => array(
					'price_template'       => $price_rule,
					'price_template_value' => $price_rule_value,
				),
			),
			array(
				'data_type' => 'title_optimize',
				'id'        => 3,
				'data'      => array(
					'title_template' => $title_rules,
				),
			),
		),
		'attributes_mapping'        => $attr_mapping,
		'targets'                   => array(
			array(
				'target_marketplace' => 'aliexpress',
				'attributes_mapping' => $attr_mapping,
				'shops'              => array(
					array(
						'shop_id'    =>
						(string) $user_data['target_shop_id'],
						'active'     => 1,
						'warehouses' => array(
							array(
								'warehouse_id'       => 1111,
								'active'             => 1,
								'attributes_mapping' => $attr_mapping,
								'sources'            => array(
									array(
										'source_marketplace' =>
										'woocommerce',
										'attributes_mapping' => $attr_mapping,
										'shops' => array(
											array(
												'shop_id' =>
												(string) $user_data['source_shop_id'],
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
);
if ( ! empty( $temp_id ) ) {
	$body['data']['_id']                 = array(
		'$oid' => $temp_id,
	);
	$body['data']['useForceQueryUpdate'] = true;
}

$args     = array(
	'headers' => $headers,
	'body'    => wp_json_encode( $body ),
);
$redirect = false;
if ( empty( $req_data ) ) {
	$response = $api_request->post(
		$endpoint,
		$args
	);
	if (
		isset( $response['data'] ) &&
		$response['data']['success']
	) {
		set_transient(
			'CIFA_success_message',
			$temp_id ? 'Category Template Edited Successfully' :
			'Category Template Created Successfully'
		);
		if ( ! empty( $sanitized_array ) ) {
			$option_mapping = array(
				'category_id'    => $category_id,
				'option_mapping' => array(),
			);
			foreach ( $sanitized_array as $key => $value ) {
				$option_mapping['option_mapping'][] = $value;
			}
			$api_request->post(
				'webapi/rest/v1/aliexpress/product/saveVariantAttributesValueMappings',
				array(
					'headers' => $headers,
					'body'    => wp_json_encode(
						$option_mapping
					),
				)
			);
		}
		$redirect = esc_url(
			admin_url(
				'admin.php?page=ced_integration_aliexpress&section=category-template'
			)
		);
	} else {
		echo '<div class="notice notice-error is-dismissible"> 		
        <p>
            <strong >' . esc_attr( $response['data']['message'] ) .
			' Fields mapping is required, kindly map and then save.</strong>          
        </p>
        <button type="button" class="notice-dismiss">
            <span class="screen-reader-text">Dismiss this notice.</span>
        </button>
        </div>';
	}
} else {
	echo '<div class="notice notice-error is-dismissible"> 		
                                <p>
                                    <strong >' .
		wp_json_encode( $req_data ) .
		' Fields mapping is required, kindly map and then save.</strong>          
                                </p>
                                <button type="button" class="notice-dismiss">
                                    <span class="screen-reader-text">Dismiss this notice.</span>
                                </button>
                                </div>';
}
if ( ! empty( $temp_id ) ) {
	$redirect = wp_specialchars_decode(
		esc_url(
			admin_url(
				'admin.php?page=ced_integration_aliexpress&section=category-template&sub-section=create_edit_template&action=edit&id=' . $temp_id
			)
		)
	);
}
if ( $redirect ) {

	wp_redirect( $redirect );
	exit;
}
