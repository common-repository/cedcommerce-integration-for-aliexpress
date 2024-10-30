<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$admin           = new CIFA_Admin( $this->plugin_name, $this->version );
$api_request     = new CIFA_Api_Base();
$common_callback = new CIFA_Common_Callback();
$headers         = $common_callback->get_common_header();
$profile_data    = array();
$category_name   = '';
if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] && ! empty( $_GET['id'] ) ) {

	$templt_id   = sanitize_text_field( $_GET['id'] );
	$endpoint    = 'connector/profile/getProfileData?activePage=1&count=10';
	$args        = array(
		'headers'   => $headers,
		'sslverify' => false,
		'body'      => wp_json_encode(
			array(
				'id' => $templt_id,
			)
		),
	);
	$get_profile = $api_request->post( $endpoint, $args );
	if ( ! empty( $get_profile['data']['success'] ) ) {
		$new_prof       = $get_profile['data']['data']['rows'];
		$profile_filter = array_filter(
			$new_prof,
			function ( $profile ) use ( $templt_id ) {
				if ( $templt_id == $profile['_id']['$oid'] ) {
					return $profile;
				}
			}
		);
		$profile_data   = ! ( empty( $profile_filter ) ) ? array_values( $profile_filter )[0] : array();
		foreach ( $profile_data['data'] as $value ) {
			if ( 'attribute_data' == $value['data_type'] ) {
				$attributes_mapping = array();
				$attributes_mapping = $value['data']['product_attributes'] +
					$value['data']['variation_attributes'];
			} elseif ( 'title_optimize' == $value['data_type'] ) {
				$title_rule = $value['data'];
			} elseif ( 'price_rule' == $value['data_type'] ) {
				$price_rule = $value['data'];
			}
		}
		$category_name = ! empty( $profile_data['category_id']['label'] ) ? $profile_data['category_id']['label'] : '';
	}
}
$endpoint             = 'webapi/rest/v1/woocommerce/category/getRuleGroup';
$args                 = array(
	'headers'   => $headers,
	'sslverify' => false,
	'body'      => array(
		'marketplace' => 'aliexpress',
	),
);
$rule_groups          = array();
$rule_groups_response = $api_request->get( $endpoint, $args );
if ( ! empty( $rule_groups_response['data']['success'] ) ) {
	$rule_groups = $rule_groups_response['data']['data'];
}

$endpoint1     = 'webapi/rest/v1/aliexpress/getTargetCategoryList';
$selected      = '';
$attr_selected = '';
$args1         = array(
	'headers' => $headers,
);
if ( ! empty( $profile_data['category_id']['value'] ) ) {
	$endpoint1 = add_query_arg(
		array( 'existing_category_id' => $profile_data['category_id']['value'] ),
		$endpoint1
	);
}

$category_response = $api_request->get( $endpoint1, $args1 );
if ( ! empty( $category_response['data']['success'] ) ) {
	$target_categories = $category_response['data']['data'];
}


$endpoint2 = 'webapi/rest/v1/woocommerce/category/getTitleRule';
$args2     = array(
	'headers' => $headers,
);

$get_title_rule = $api_request->get( $endpoint2, $args2 );
if ( ! empty( $get_title_rule['data']['success'] ) ) {
	$title_rule_values = $get_title_rule['data']['data'];
}

if (
	! empty( $_POST ) && isset( $_REQUEST['aliexpress_nonce'] )
	&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['aliexpress_nonce'] ) ), 'aliexpress_nonce_field' )
) {
	include_once CIFA_DIRPATH . 'admin/partials/form_posts/save_template.php';
}
?>

<div class="CIFA-return-button-wrap CIFA-category-header-wrap">
	<h1><a href="admin.php?page=ced_integration_aliexpress&section=category-template"><span class="dashicons dashicons-arrow-left-alt"></span></a>
		<?php echo empty( $profile_data['name'] ) ? 'Create' : 'Edit'; ?> Category Template</h1>
</div>
<div class="wc-progress-form-content woocommerce-importer components-panel CIFA-padding2">
	<form method="POST" id="create_template" class="category_template" action=<?php echo esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress&section=category-template&sub-section=create_edit_template' ) ); ?>>

		<?php
		if ( ! empty( $templt_id ) ) {
			echo '<input type="hidden" id="CIFA_template_id" name="CIFA_template_id" value="' . esc_attr( $templt_id ) . '">';
		}
		?>
		<table class="form-table css-off1bd CIFA-table-common">
			<tbody>
				<tr>
					<td scope="row" class="row-title">
						<label for="Category Template Name">
							Category Template Name
							<?php
							$attribute_description = __( 'Enter a unique name for the category template you wish to create not exceeding 80 characters.', 'cedcommerce-integration-for-aliexpress' );
							echo wc_help_tip( esc_html( $attribute_description ) );
							?>
						</label>
					</td>
					<td class="forminp forminp-select">
						<input type="text" required maxlength="30" placeholder="Enter Template Name" <?php echo ! empty( $profile_data['name'] ) ? 'readonly' : ''; ?> value="<?php echo ! empty( $profile_data['name'] ) ? esc_attr( $profile_data['name'] ) : ''; ?>" id="CIFA_template_name" name="CIFA_template_name">
					</td>
				</tr>
			</tbody>
		</table>
		<table class="form-table css-off1bd CIFA-table-common">
			<tbody>
				<tr>
					<td scope="row" class="row-title">
						<label for="Category Template Name">
							Override Listing
							<?php
							$attribute_description = __( 'Select this option if you wish to override products with templates already assigned.', 'cedcommerce-integration-for-aliexpress' );
							echo wc_help_tip( esc_html( $attribute_description ) );
							?>
						</label>
					</td>
					<td class="forminp forminp-select">
						<input name="woocommerce_override_listing" id="woocommerce_override_listing" type="checkbox" class="CIFA-checked-button" <?php echo ! empty( $profile_data['overWriteExistingProducts'] ) ? 'checked' : ''; ?>>

						<label class="" for="woocommerce_override_listing"></label>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="css-10klw3m e19lxcc00">
			<div class="wooocommerce-task-card__header-container">
				<div class="wooocommerce-task-card__header">
					<div class="woocommerce-task-header__contents-container">
						<div class="woocommerce-task-header__contents">
							<div class="CIFA-section-head CIFA-margin-top CIFA-content-border-container">
								<p class="row-title">Rule Group
									<?php
									$attribute_description = __( 'Create rule group(s) to fetch a particular set of the product(s) to assign in the current category template. Click on Run Query to fetch the product(s) based on the rule group(s) created.', 'cedcommerce-integration-for-aliexpress' );
									echo wc_help_tip( esc_html( $attribute_description ) );
									?>
								</p>

								<div class="CIFA-product-list">
									<p>Product Must Match</p>
									<div class="CIFA-flex-wrap">
										<input type="radio" name="CIFA-condition" class="CIFA-condition" id="any_condition" value="any_condition" <?php echo ! isset( $profile_data['query'] ) || strpos( $profile_data['query'], '||' ) !== false || strpos( $profile_data['query'], '&&' ) !== true ? 'checked' : ''; ?>><label for="any_condition">Any Condition</label>
										<input type="radio" name="CIFA-condition" class="CIFA-condition" id="all_condition" value="all_condition" <?php echo isset( $profile_data['query'] ) && strpos( $profile_data['query'], '&&' ) !== false ? 'checked' : ''; ?>><label for="all_condition">All Conditions</label>
									</div>
									<!-- <form id="run_query_form"> -->
									<table class="form-table css-off1bd CIFA-select-wrap CIFA-repeat-title-rule">
										<thead></thead>
										<?php
										$requestData = array( 'rule_count' => 1 );
										if ( ! empty( $profile_data['query'] ) ) {
											$requestData['query'] = $admin->extract_price_rule_query( $profile_data['query'] );
										}
										?>
										<tbody id="rule_groups" data-row="<?php echo ! empty( $requestData['query'] ) ? count( $requestData['query'] ) : 1; ?>">
											<?php
											print_r( $admin->add_price_rule( $requestData ) );
											?>
											<input type="hidden" name="rule_group_condition" id="rule_group_condition" value="">
											<input type="hidden" name="prod_count_run_query" id="prod_count_run_query" value="0">
										</tbody>
									</table>
									<div class="woocommerce-inbox-message__actions">
										<a id="run_rule_group_query" class="components-button is-secondary alignright">Run Query</a>
										<a class="components-button woocommerce-admin-dismiss-notification alignright" id="add_new_rule_create_template">Add New Rule</a>
									</div>
									<div id="display_run_query_message">

									</div>
									<!-- </form> -->
								</div>
							</div>
							<table class="form-table css-off1bd CIFA-table-common CIFA-margin-top">
								<tbody>
									<tr>
										<td scope="row" class="row-title">
											<label for="Category Template Name">
												Select Aliexpress Product Category
												<?php
												$attribute_description = __( 'Select a category that best defines your products. ', 'cedcommerce-integration-for-aliexpress' );
												echo wc_help_tip( esc_html( $attribute_description ) );
												?>
											</label>
										</td>
										<td class="forminp forminp-select">
											<select name="get_target_categories" class="select2-with-checkbox" id="get_target_categories">
												<option>--Select any Category--</option>
												<?php
												if ( ! empty( $target_categories ) ) {
													foreach ( $target_categories as $cat_key => $cat_value ) {
														?>
														<option value="<?php echo esc_attr( $cat_value['category_id'] ); ?>" 
																					<?php
																																echo ! empty( $profile_data['category_id']['value'] )
																																	&& $profile_data['category_id']['value'] == $cat_value['category_id'] ? 'selected' : '';
																					?>
																																>
															<?php echo esc_attr( $cat_value['category_path_en'] ); ?></option>
														<?php
													}
												}
												?>
											</select>
											<div class="CIFA_selected_category_section" style="<?php echo empty( $category_name ) ? 'display:none' : ''; ?>">
												<p class="CIFA_selected_category"><span class="CIFA_selected_category_name"><?php echo esc_attr( $category_name ); ?></span><button type="button" class="CIFA_unselect_category"><span class="dashicons dashicons-no-alt"></span></button></p>
												<input type="hidden" name="aliexpress_default_category_name" id="aliexpress_default_category_name" value="<?php echo esc_attr( $category_name ); ?>">

											</div>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

		</div>
		<div class="components-card is-size-medium pinterest-for-woocommerce-landing-page__faq-section css-1xs3c37-CardUI e1q7k77g0">
			<div class="CIFA-paddi">
				<div class="aliexpress_attribute_mapping_default_profiling" style="<?php echo ! empty( $attributes_mapping ) ? 'display:block' : ''; ?>">
					<div class="aliexpress_attribute_section_mapping">
						<?php
						$admin->get_values_for_attr_mapping_default_template(
							array(
								'category_id'        => $profile_data['category_id']['value'],
								'attributes_mapping' => $attributes_mapping,
							)
						);
						?>
					</div>
				</div>
				<div class="wooocommerce-task-card__header-container CIFA-seo-optimization-wrap">
					<div class="wooocommerce-task-card__header">
						<div class="woocommerce-task-header__contents-container">
							<div class="woocommerce-task-header__contents">
								<h3>Title SEO Optimization </h3>
								<p>The title will be set on AliExpress according to the below rules to improve the rank of the product on the AliExpress marketplace. If nothing is selected, the Plugin product title will be used.</p>
							</div>
						</div>
						<table class="form-table css-off1bd CIFA-table-common">
							<tbody>
								<tr>
									<td scope="row" class="row-title">
										<label for="Choose Title Option">
											Choose Title Option
											<?php
											$attribute_description = __( 'Choose relevant product details here to customize product titles on AliExpress.', 'cedcommerce-integration-for-aliexpress' );
											echo wc_help_tip( esc_html( $attribute_description ) );
											?>
										</label>
									</td>
									<td class="forminp forminp-select">
										<select name="aliexpress_title_rule[]" class="select2-with-checkbox choose_title_option" multiple="multiple" id="aliexpress_title_rule_multiselect" data-fieldid="">
											<option value="">--Select--</option>
											<?php

											if ( ! empty( $title_rule_values ) ) {
												$title_template_values = ! empty( $title_rule['title_template'] ) ? array_values( $title_rule['title_template'] ) : array();
												foreach ( $title_rule_values as $key => $value ) {
													if ( empty( $key ) ) {
														continue;
													} elseif ( empty( $title_rule ) ) {
														echo '<option ' . ( 'title' == $key ? 'selected' : '' ) . '  value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</option>';
													} else {
														echo '<option ' . ( in_array( $key, $title_template_values ) ? 'selected' : '' ) . ' value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</option>';
													}
												}
											}
											?>
										</select>
									</td>
								</tr>
								<tr>
									<td scope="row" class="row-title">
										<label for="Custom Title Keyword">
											Custom Title Keyword
											<?php
											$attribute_description = __( 'Provide relevant keyword(s) here to customize product titles on AliExpress.', 'cedcommerce-integration-for-aliexpress' );
											echo wc_help_tip( esc_html( $attribute_description ) );
											?>
										</label>
									</td>
									<td class="forminp forminp-select">
										<input type="text" value="<?php echo ! empty( $title_rule['title_template']['text_mappings'] ) ? esc_attr( $title_rule['title_template'][ $title_rule['title_template']['text_mappings'][0] ] ) : ''; ?>" placeholder="Enter Custom Title Keyword" name="custom_title_keyword">
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="wooocommerce-task-card__header">
					<div class="woocommerce-task-header__contents-container">
						<table class="form-table css-off1bd CIFA-table-common">
							<tbody>
								<tr>
									<td scope="row" class="row-title">
										<label for="Choose Title Option">
											Custom Price Rule
											<?php
											$attribute_description = __( 'Customize (Increase or decrease) product prices on AliExpress by setting a custom price rule.', 'cedcommerce-integration-for-aliexpress' );
											echo wc_help_tip( esc_html( $attribute_description ) );
											?>
										</label>
									</td>
									<td class="forminp forminp-select">
										<select name="price_rule" id="default_temp_custom_price_rule_type">
											<option value="">--Select--</option>
											<option value="fixed_increment" <?php echo ! empty( $price_rule['price_template'] ) && 'fixed_increment' == $price_rule['price_template'] ? 'selected' : 'fixed_decrement'; ?>>Fixed Increment</option>
											<option <?php echo ! empty( $prifixed_decrementce_rule['price_template'] ) && 'fixed_decrement' == $price_rule['price_template'] ? 'selected' : ''; ?> value="fixed_decrement">Fixed Decrement</option>
											<option <?php echo ! empty( $price_rule['price_template'] ) && 'multiply' == $price_rule['price_template'] ? 'selected' : ''; ?> value="multiply">Multiply</option>
											<option <?php echo ! empty( $price_rule['price_template'] ) && 'percent_increment' == $price_rule['price_template'] ? 'selected' : ''; ?> value="percent_increment">Percent Increment</option>
											<option <?php echo ! empty( $price_rule['price_template'] ) && 'percent_decrement' == $price_rule['price_template'] ? 'selected' : ''; ?> value="percent_decrement">Percent Decrement</option>
										</select>
										<input style="margin-top:15px;<?php echo ! empty( $price_rule['price_template_value'] ) ? 'display:inline-block' : ''; ?>" type="number" name="default_temp_custom_price_rule_value" class="custom_price_rule" value="<?php echo ! empty( $price_rule['price_template_value'] ) ? esc_attr( $price_rule['price_template_value'] ) : ''; ?>" placeholder="Enter Value" id="default_temp_custom_price_rule_value">
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<?php wp_nonce_field( 'aliexpress_nonce_field', 'aliexpress_nonce' ); ?>
			<div class="wc-actions CIFA-bottom-save-button-category">
				<input type="submit" class="components-button is-primary" id="create_new_template" name="save_template" value="Save">
			</div>
		</div>

	</form>
</div>
</div>
</div>
</div>

</form>
</div>