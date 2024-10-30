<?php

/**
 * Ced_Connector Main
 *
 * @package  Ced_Connector_Integration_For_Woocommerce
 * @version  1.0.0
 * @link     https://cedcommerce.com
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
?>
<div class="CIFA-loader">
	<img src="<?php echo esc_url( CIFA_URL . 'admin/images/ajax-loader.gif' ); ?>" width="50px" height="50px" class="ced_shopee_loading_img">
</div>
<div class="error_success_notification_display">

</div>
<?php
$common_callback   = new CIFA_Common_Callback();
$connector_get_all = $common_callback->conectorgetAll();
$step_completed    = $common_callback->getStepCompleted();
$getData           = filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
if ( ! empty( $connector_get_all['source_id'] ) ) {
	if ( get_option( 'CIFA_user_data' ) ) {
		$data                   = json_decode( get_option( 'CIFA_user_data' ), ARRAY_A );
		$data['source_shop_id'] = $connector_get_all['source_id'];
		update_option( 'CIFA_user_data', wp_json_encode( $data ) );
	} else {
		$data['source_shop_id'] = $connector_get_all['source_id'];
		update_option( 'CIFA_user_data', wp_json_encode( $data ) );
	}
}
if (
	isset( $getData['user_id'] ) &&
	'aliexpress_woo_connection' === $getData['user_id'] && ( isset( $getData['success'] ) && '0' === $getData['success'] )
) {
	$url = admin_url( 'admin.php?page=cedcommerce-integrations' );
	header( 'Location: ' . $url );
	die();
}
if (
	isset( $getData['page'] ) && 'ced_integration_aliexpress' === $getData['page']
	&& isset( $getData['shop'] ) && 'woocommerce' === $getData['shop']
) {

	$response = array(
		'token' => ! empty( $getData['user_token'] ) ? wp_unslash( $getData['user_token'] ) : false,
		'shop'  => ! empty( $getData['shop'] ) ? wp_unslash( $getData['shop'] ) : false,
	);

	update_option( 'CIFA_token_data', wp_json_encode( $response ) );
	if ( 0 == $step_completed ) {
		if ( $common_callback->setStepCompleted( 1 ) ) {
			update_option( 'CIFA_step_completed', 1 );
			$url = admin_url( 'admin.php?page=ced_integration_aliexpress' );
			header( 'Location: ' . $url );
		}
	}
	die();
}
if ( isset( $getData['user_id'] ) && 'aliexpress_woo_connection' === $getData['user_id'] ) {

	global $wp_filesystem;

	// Check if WP_Filesystem is available
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	// Initialize the WP_Filesystem
	if ( ! $wp_filesystem ) {
		WP_Filesystem();
	}



	$file         = wp_upload_dir()['basedir'] . '/CIFA_api_details.txt';
	$woo_api_data = $wp_filesystem->get_contents( $file );
	$name         = ! empty( get_bloginfo( 'name' ) ) ? get_bloginfo( 'name' ) : home_url();
	if ( ! empty( $woo_api_data ) ) {
		$admin_data                  = wp_get_current_user();
		$admin_data                  = (array) $admin_data->data;
		$user_id                     = $admin_data['ID'];
		$first_name                  = ! empty( get_user_meta( $user_id, 'first_name', true ) ) ? get_user_meta( $user_id, 'first_name', true ) : get_user_meta( $user_id, 'nickname', true );
		$last_name                   = ! empty( get_user_meta( $user_id, 'last_name', true ) ) ? get_user_meta( $user_id, 'last_name', true ) : '';
		$phone_number                = ! empty( get_user_meta( $user_id, 'billing_phone', true ) ) ? get_user_meta( $user_id, 'billing_phone', true ) : get_user_meta( $user_id, 'shipping_phone', true );
		$store_raw_country           = get_option( 'woocommerce_default_country' );
		$split_country               = explode( ':', $store_raw_country );
		$store_country               = $split_country[0];
		$store_state                 = $split_country[1];
		$user_data                   = array();
		$user_data['email']          = $admin_data['user_email'];
		$user_data['username']       = home_url();
		$user_data['domain']         = home_url();
		$user_data['first_name']     = $first_name;
		$user_data['last_name']      = $last_name;
		$user_data['name']           = $name;
		$user_data['phone']          = $phone_number;
		$user_data['address1']       = get_option( 'woocommerce_store_address' );
		$user_data['address2']       = get_option( 'woocommerce_store_address_2' );
		$user_data['city']           = get_option( 'woocommerce_store_city' );
		$user_data['zip']            = get_option( 'woocommerce_store_postcode' );
		$user_data['province']       = $store_state;
		$user_data['country']        = $store_country;
		$user_data['currency']       = get_woocommerce_currency();
		$user_data['weight_unit']    = get_option( 'woocommerce_weight_unit' );
		$user_data['dimension_unit'] = get_option( 'woocommerce_dimension_unit' );
		$decoded_data                = json_decode( $woo_api_data, true );
		$params                      = array();
		$params['domain']            = home_url();
		$params['consumer_key']      = $decoded_data['consumer_key'];
		$params['consumer_secret']   = $decoded_data['consumer_secret'];
		$params['user_detail']       = $user_data;
		$params['time']              = time();
		$state                       = array(
			'frontend_redirect_uri' => admin_url( 'admin.php?page=ced_integration_aliexpress' ),
		);
		$params['state']             = wp_json_encode( $state );
		$url                         = CIFA_AUTH_URL . 'apiconnect/request/auth?sAppId=4&' . http_build_query( $params );

		if ( wp_redirect( $url ) ) {
			exit;
		}
	}
}

$wooconnection_data = get_option( 'CIFA_token_data', '' );
if ( ( ! empty( $wooconnection_data ) ) && 0 < $step_completed && 4 > $step_completed ) {
	if ( 1 == $step_completed ) {

		$api_request        = new CIFA_Api_Base();
		$admin_data         = wp_get_current_user();
		$admin_data         = (array) $admin_data->data;
		$wooconnection_data = json_decode( $wooconnection_data, true );
		$param              = array();
		$param['username']  = home_url();
		$headers            = array();
		$headers[]          = 'Content-Type: application/json';
		$endpoint           = 'woocommercehome/request/getUserToken';
		$response           = $api_request->post(
			$endpoint,
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

		$response_code = $response['code'];
		$response_data = $response['data'];
		if ( 200 === $response_code ) {
			$data               = array(
				'token' => ! empty( $response_data['token_res'] ) ? $response_data['token_res'] : false,
				'shop'  => ! empty( $wooconnection_data['shop'] ) ?
					sanitize_text_field( wp_unslash( $wooconnection_data['shop'] ) ) : false,
			);
			$wooconnection_data = wp_json_encode( $data );
			update_option( 'CIFA_token_data', $wooconnection_data );
			set_transient( 'CIFA_token_data', $wooconnection_data, 10800 );
		}
		?>
		<div class="woocommerce-progress-form-wrapper">
			<h1 class="alignleft CIFA-bottom">AliExpress Connector for CedCommerce</h1>
			<ol class="wc-progress-steps">
				<li class="done">
					Connect WooCommerce </li>
				<li class="active">
					Connect AliExpress </li>
				<li class="">
					Default Template </li>
				<li class="">
					Default Configuration </li>
			</ol>
			<form class="wc-progress-form-content woocommerce-importer" enctype="multipart/form-data" method="POST">
				<div class="wc-progress-form-content">
					<header>
						<h2>Connect AliExpress</h2>
						<p>Click to connect With AliExpress.</p>
					</header>
					<div class="wc-actions">
						<a type="button" href="
						<?php
						echo esc_attr( CIFA_HOME_URL ) .
							'connector/get/installationForm?code=aliexpress&app_tag=aliexpress_connector&source_shop_id=' . esc_attr( $connector_get_all['source_id'] ) . '&source=' . esc_attr( $data['shop'] ) . '&bearer=' . esc_attr( $data['token'] ) . '&frontend_redirect_uri=' . esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress' ) );
						?>
						" id="" class="alignright components-button is-primary">Connect</a>

					</div>
				</div>
			</form>
		</div>

		<?php
		if (
			1 == $step_completed && ! empty( $getData['shop'] ) && 'aliexpress' === $getData['shop']
			&& ! empty( $getData['connectionStatus'] ) && 1 === (int) $getData['connectionStatus']
		) {
			$admin = new CIFA_Admin( $this->plugin_name, $this->version );
			$admin->onboarding_step2();
			$url = admin_url( 'admin.php?page=ced_integration_aliexpress' );
			header( 'Location: ' . $url );
		}
	}

	if ( 2 == $step_completed ) {

		$common_callback = new CIFA_Common_Callback();
		$api_request     = new CIFA_Api_Base();
		$headers         = $common_callback->get_common_header();
		$endpoint        = 'webapi/rest/v1/aliexpress/getTargetCategoryList';
		$args            = array(
			'headers'   => $headers,
			'sslverify' => false,
		);

		$category_response = $api_request->get( $endpoint, $args );
		if ( isset( $category_response['data'] ) && true == $category_response['data']['success'] ) {
			$target_categories = $category_response['data']['data'];
		}
		include_once CIFA_DIRPATH . 'admin/partials/form_posts/save_default_template.php';
		?>
		<form method="POST" id="default_category" class="category_template">
			<div class="woocommerce-progress-form-wrapper">
				<h1 class="alignleft CIFA-bottom">AliExpress Connector for CedCommerce</h1>
				<ol class="wc-progress-steps">
					<li class="done">
						Connect WooCommerce </li>
					<li class="done">
						Connect AliExpress </li>
					<li class="active">
						Default Template </li>
					<li class="">
						Default Configuration </li>
				</ol>
				<div class="wc-progress-form-content">
					<header>
						<h2>Create Default Template</h2>
						<p>Create a default template by selecting category & mapping attributes accordingly to upload product(s) on AliExpress.</p>
					</header>
					<header class="CIFA-paragraph-list CIFA-top-wrap">
						<h3>Select Listing Category</h3>
						<p>Choose the ‘Category’ that best defines your listing(s).</p>
						<p><strong>Note:</strong> Based on the selected category you will further map Woocommerce attributes with AliExpress attributes.</p>
						<div class="forminp forminp-select CIFA-select">
							<select name="get_target_categories" class="select2-with-checkbox" id="get_target_categories">
								<option>--Select any Category--</option>
								<?php
								if ( ! empty( $target_categories ) ) {
									foreach ( $target_categories as $key => $value ) {
										?>
										<option value="<?php echo esc_attr( $value['category_id'] ); ?>"><?php echo esc_attr( $value['category_path_en'] ); ?></option>
										<?php
									}
								}
								?>
							</select>
							<div class="CIFA_selected_category_section" style="display:none">
								<p class="CIFA_selected_category"><span class="CIFA_selected_category_name"></span><button type="button" class="CIFA_unselect_category"><span class="dashicons dashicons-no-alt"></span></button></p>
							</div>
							<input type="hidden" name="aliexpress_default_category_name" id="aliexpress_default_category_name">
						</div>
					</header>
					<div class="aliexpress_attribute_mapping_default_profiling">
						<header class="CIFA-paragraph-list CIFA-top-wrap">
							<div class="woocommerce-task-header__contents">
								<h3>Select Attribute Mapping</h3>
								<p>Through attribute mapping you can enhance your listing catalog with additional information.</p>
							</div>
							<div class="woocommerce-task-header__contents">
								<p><strong>Product Attributes:</strong> Based on the selected category you will further map Woocommerce attributes with AliExpress attributes.</p>
							</div>
							<div class="woocommerce-task-header__contents">
								<p><strong>Variation Attribute:</strong>These are the mandatory attributes that must be selected if you have variants for your listings.</p>
							</div>
						</header>
						<div class="components-card is-size-medium pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0">
							<div class="CIFA-padding2">
								<div class="aliexpress_attribute_section_mapping">

								</div>
							</div>
						</div>
					</div>
					<?php wp_nonce_field( 'aliexpress_nonce_field', 'aliexpress_nonce' ); ?>

					<div class="wc-actions">
						<input type="submit" class="alignright components-button is-primary" name="aliexpress_save_default_template" value="create" id="aliexpress_save_default_template">
					</div>
				</div>

			</div>

		</form>
		<?php
	}
	if ( 3 == $step_completed ) {
		$common_callback = new CIFA_Common_Callback();
		$api_request     = new CIFA_Api_Base();
		$headers         = $common_callback->get_common_header();
		$endpoint        = 'webapi/rest/v1/woocommerce/category/getTitleRule';
		$args            = array(
			'headers'   => $headers,
			'sslverify' => false,
		);

		$get_title_rule = $api_request->get( $endpoint, $args );
		if ( isset( $get_title_rule['data'] ) && true == $get_title_rule['data']['success'] ) {
			$title_rule_values = $get_title_rule['data']['data'];
		}
		?>
		<div class="woocommerce-progress-form-wrapper">
			<form method="POST">
				<h1 class="alignleft CIFA-bottom">AliExpress Connector for CedCommerce</h1>
				<ol class="wc-progress-steps">
					<li class="done">
						Connect WooCommerce </li>
					<li class="done">
						Connect AliExpress </li>
					<li class="done">
						Default Template </li>
					<li class="active">
						Default Configuration </li>
				</ol>
				<div class="wc-progress-form-content">
					<header>
						<h2>Default Configuration</h2>
						<p>Create a default template by selecting category & mapping attributes accordingly to upload product(s) on AliExpress.</p>
					</header>
					<div class="CIFA-padding">
						<h4>Title Optimization</h4>
						<div class="wc-progress-form-content woocommerce-importer">

							<table class="form-table">
								<tbody>
									<tr>
										<td scope="row" class="titledesc">
											<label for="woocommerce_currency">
												Custom Title Option
												<?php
												$attribute_description = __( 'Choose relevant product details here to customize product titles on AliExpress.', 'cedcommerce-integration-for-aliexpress' );
												echo wc_help_tip( esc_html( $attribute_description ) );
												?>
											</label>
										</td>
										<td class="forminp forminp-select">
											<select style="width: 100%;margin-bottom:15px;" class="select2-with-checkbox choose_title_option" multiple="multiple" name="aliexpress_title_rule[]" id="aliexpress_title_rule_multiselect" data-fieldid="">
												<option value="">--Select--</option>
												<?php
												if ( ! empty( $title_rule_values ) ) {
													foreach ( $title_rule_values as $key => $values ) {
														if ( empty( $key ) ) {
															continue;
														} else {
															?>
															<option value="<?php echo esc_attr( $key ); ?>" 
																						<?php
																						if ( 'title' == esc_attr( $key ) ) {
																							?>
																											selected 
																											<?php
																						}
																						?>
																														><?php echo esc_attr( $values ); ?></option>
															<?php
														}
													}
												}
												?>
											</select>

										</td>
									</tr>
									<tr>
										<td scope="row" class="titledesc">
											<label for="woocommerce_currency">
												Custom Title Keyword
												<?php
												$attribute_description = __( 'Provide relevant keyword(s) here to customize product titles on AliExpress.', 'cedcommerce-integration-for-aliexpress' );
												echo wc_help_tip( esc_html( $attribute_description ) );
												?>
											</label>
										</td>
										<td class="forminp forminp-select"> <input type="text" name="custom_title_keyword" placeholder="Enter Custom Title Keyword">

										</td>

									</tr>
								</tbody>
							</table>
						</div>
					</div>
					<div class="CIFA-padding">
						<div class="wc-progress-form-content woocommerce-importer">

							<table class="form-table CIFA-input">
								<tbody>
									<tr>
										<td scope="row" class="titledesc">
											<label for="woocommerce_currency">
												Custom Price Rule
												<?php
												$attribute_description = __( 'Customize (Increase or decrease) product prices on AliExpress by setting a custom price rule.', 'cedcommerce-integration-for-aliexpress' );
												echo wc_help_tip( esc_html( $attribute_description ) );
												?>
											</label>
										</td>
										<td class="forminp forminp-select">
											<select style="width: 100%;margin-bottom: 15px;" name="price_rule" id="default_temp_custom_price_rule_type" data-fieldid="">
												<option value="">--Select--</option>
												<option value="fixed_increment">Fixed Increment</option>
												<option value="fixed_decrement">Fixed Decrement</option>
												<option value="multiply">Multiply</option>
												<option value="percent_increment">Percent Increment</option>
												<option value="percent_decrement">Percent Decrement</option>
											</select>
											<input type="text" name="default_temp_custom_price_rule_value" placeholder="Enter Value" class="custom_price_rule CIFA-number-input" id="default_temp_custom_price_rule_value" min="1" value="1">
										</td>
									</tr>
									<tr>
										<td scope="row" class="titledesc">
											<label for="woocommerce_currency">
												Product Auto Update
												<?php
												$attribute_description = __( ' Enable to allow automatic syncing between WooCommerce and AliExpress; any product update on WooCommerce will also be reflected on AliExpress in real time.', 'cedcommerce-integration-for-aliexpress' );
												echo wc_help_tip( esc_html( $attribute_description ) );
												?>
											</label>
										</td>
										<td class="forminp forminp-select">
											<input name="product_auto_update" class="CIFA-checked-button" id="product_auto_update" type="checkbox" checked>
											<label class="" for="product_auto_update"></label>
										</td>
									</tr>
									<tr>
										<td scope="row" class="titledesc">
											<label for="woocommerce_currency">
												Product Auto Upload Variations
												<?php
												$attribute_description = __( 'Enable to upload new product variants on AliExpress once created in WooCommerce so that existing variant product on AliExpress stays updated.', 'cedcommerce-integration-for-aliexpress' );
												echo wc_help_tip( esc_html( $attribute_description ) );
												?>
											</label>
										</td>
										<td class="forminp forminp-select">
											<input name="product_auto_create_child" class="CIFA-checked-button" id="product_auto_create_child" type="checkbox" checked>
											<label class="" for="product_auto_create_child"></label>
										</td>
									</tr>
									<tr>
										<td scope="row" class="titledesc">
											<label for="woocommerce_currency">
												Product Auto Upload If Type Changed
												<?php
												$attribute_description = __( ' Enable to automatically upload product on AliExpress when a Live simple product is changed to a variation product on the plugin. Please note that changing product type will lead to the deletion of that “Previous Simple product” from AliExpress.', 'cedcommerce-integration-for-aliexpress' );
												echo wc_help_tip( esc_html( $attribute_description ) );
												?>
											</label>
										</td>
										<td class="forminp forminp-select">
											<input name="product_auto_create_type_change" class="CIFA-checked-button" id="product_auto_create_type_change" type="checkbox" checked>
											<label class="" for="product_auto_create_type_change"></label>
										</td>
									</tr>
									<tr>
										<td scope="row" class="titledesc">
											<label for="woocommerce_currency">
												Product Auto Create
												<?php
												$attribute_description = __( 'Enable to automatically create product(s) on AliExpress when new product(s) are added to the WooCommerce store.', 'cedcommerce-integration-for-aliexpress' );
												echo wc_help_tip( esc_html( $attribute_description ) );
												?>
											</label>
										</td>
										<td class="forminp forminp-select">
											<input name="product_auto_create" class="CIFA-checked-button" id="product_auto_create" type="checkbox" checked>
											<label class="" for="product_auto_create"></label>
										</td>
									</tr>
									<tr>
										<td scope="row" class="titledesc">
											<label for="woocommerce_currency">
												Product Auto Delete
												<?php
												$attribute_description = __( 'Enable to automatically delete products on AliExpress when the product(s) are deleted in the WooCommerce store.', 'cedcommerce-integration-for-aliexpress' );
												echo wc_help_tip( esc_html( $attribute_description ) );
												?>
											</label>
										</td>
										<td class="forminp forminp-select">
											<input name="product_auto_delete" class="CIFA-checked-button" id="product_auto_delete" type="checkbox" checked>
											<label class="" for="product_auto_delete"></label>
										</td>
									</tr>
									<tr>
										<td scope="row" class="titledesc">
											<label for="woocommerce_currency">
												Threshold Inventory
												<?php
												$attribute_description = __( 'Specify a minimum inventory limit, which, when reached, marks the product as "Sold Out" on AliExpress, thus preventing you from overselling.', 'cedcommerce-integration-for-aliexpress' );
												echo wc_help_tip( esc_html( $attribute_description ) );
												?>
											</label>
										</td>
										<td class="forminp forminp-select">
											<input type="text" name="threshold_inventory" class="CIFA-number-input threshold_inventory" placeholder="Enter Inventory Value" value="0" min="0">
										</td>

									</tr>
								</tbody>
							</table>
						</div>
					</div>
					<div class="wc-actions">
						<button type="button" class="alignright components-button is-primary" id="aliexpress_onboarding_step4_save_config" name="aliexpress_onboarding_step4_save_config">Save</button>
					</div>
				</div>

			</form>
		</div>
		<?php
	}
} else {
	?>
	<div class="woocommerce-progress-form-wrapper">
		<h1 class="alignleft CIFA-bottom">AliExpress Integration Onboarding</h1>
		<ol class="wc-progress-steps">
			<li class="active">
				Connect WooCommerce </li>
			<li class="">
				Connect AliExpress </li>
			<li class="">
				Default Template </li>
			<li class="">
				Default Configuration </li>
		</ol>
		<div class="wc-progress-form-content">
			<?php
			$terms_and_condition = get_option( 'CIFA_terms_and_condition' );
			if ( 'true' != $terms_and_condition ) {
				?>

				<header class="ced-CIFA-listing-container">
					<h2>Connect WooCommerce</h2>
					<p>Click to connect With WooCommerce.</p>
					<ul>
						<li>To facilitate your transactions and improve our services, we require your consent to collect and process necessary personal data.</li>
						<li>For order processing and delivery, your consent is essential for collecting and utilizing order-related information.</li>
						<li>By proceeding with your order, you provide consent for the collection and utilization of necessary personal data, including product details, to fulfill and process your purchase.</li>
					</ul>
				</header>
				<div class="wc-actions">
					<label for="connector_dev" class="<?php echo 'true' == $terms_and_condition ? 'CIFA-readonly' : ''; ?>" style="background-color:#fff !important">
						<input type="checkbox" id="connector_dev" <?php echo 'true' == $terms_and_condition ? 'checked' : ''; ?> class="CIFA-terms-conditions <?php echo 'true' == $terms_and_condition ? 'CIFA-readonly' : ''; ?>">
						<span> Accept </span>
					</label>
					<a href="https://cedcommerce.com/privacy-policy" target="_blank">Terms & Conditions</a>
					<button type="button" class="alignright components-button is-primary CIFA_connect_button <?php echo 'true' == $terms_and_condition ? '' : 'CIFA-readonly'; ?>">Connect</button>
				</div>
			<?php } else { ?>
				<header class="ced-listing-common">
					<div class="CIFA-try-again-wrapper">
						<div class="CIFA-try-content">
							<h4>There is an error in Automatic Account Connection, Please <a class="CIFA-try-link CIFA_connect_button" href="javascript:void(0)">try again</a> or use Manual Account Connection given below.</h4>
						</div>
					</div>
					<h4>Instruction</h4>
					<ul>
						<li>Go to woocommerce->settings->advanced>REST API & click on add key button</li>
						<li>Fill up the description , select user & provide Read/Write permission & then click on generate API key button.</li>
						<li>Copy consumer key & secret and paste above.</li>
					</ul>
					<div class="CIFA-form-field CIFA-filed">
						<label for="ced_connector_consumer_key">Consumer Key</label>
						<input required name="tag-name" id="ced_connector_consumer_key" type="text" class="ced_connector_consumer_key" value="" size="40" aria-required="true" aria-describedby="name-description" placeholder="Enter Here">
					</div>
					<div class="CIFA-form-field CIFA-filed">
						<label for="ced_connector_consumer_secret">Consumer Secret</label>
						<input required name="tag-name" id="ced_connector_consumer_secret" type="text" value="" size="40" class="ced_connector_consumer_secret" aria-required="true" aria-describedby="name-description" placeholder="Enter Here">

					</div>
					<div class="CIFA-instruction-holder">
						<div class="CIFA-form-instruction">
							<ul>
								<li>To facilitate your transactions and improve our services, we require your consent to collect and process necessary personal data.</li>
								<li>For order processing and delivery, your consent is essential for collecting and utilizing order-related information.</li>
								<li>By proceeding with your order, you provide consent for the collection and utilization of necessary personal data, including product details, to fulfill and process your purchase.</li>
							</ul>
						</div>
					</div>
				</header>
				<div class="wc-actions">
					<label for="connector_dev" class="<?php echo 'true' == $terms_and_condition ? 'CIFA-readonly' : ''; ?>" style="background-color:#fff !important">
						<input type="checkbox" id="connector_dev" <?php echo 'true' == $terms_and_condition ? 'checked' : ''; ?> class="CIFA-terms-conditions <?php echo 'true' == $terms_and_condition ? 'CIFA-readonly' : ''; ?>">
						<span> Accept </span>
					</label>
					<a href="https://cedcommerce.com/privacy-policy" target="_blank">Terms & Conditions</a>
					<button type="button" class="alignright components-button is-primary CIFA_manual_connect_button <?php echo 'true' == $terms_and_condition ? '' : 'CIFA-readonly'; ?>">Validate</button>
				</div>
			<?php } ?>
		</div>

	</div>
	<?php
}
?>