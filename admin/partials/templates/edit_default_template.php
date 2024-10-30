<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$common_callback = new CIFA_Common_Callback();
$api_request     = new CIFA_Api_Base();
$admin           = new CIFA_Admin( $this->plugin_name, $this->version );
$headers         = $common_callback->get_common_header();

require_once CIFA_DIRPATH . 'admin/partials/form_posts/save_default_template.php';

$endpoint1 = 'connector/config/getConfig';
$args1     = array(
	'headers'   => $headers,
	'sslverify' => false,
	'body'      => wp_json_encode(
		array(
			'group_code' => array( 'default_profile' ),
		)
	),
);

$get_config = $api_request->post( $endpoint1, $args1 );

if ( ! empty( $get_config['data']['success'] ) ) {
	$default_config_data = $get_config['data']['data'];
	foreach ( $default_config_data as $config_key => $config_value ) {
		if ( 'default_profile' == $config_value['group_code'] ) {
			$default_config_data        = $config_value['value']['default_profile'];
			$default_temp_name          = $default_config_data['name'];
			$default_temp_category      = $default_config_data['category_id']['value'];
			$default_temp_category_name = $default_config_data['category_id']['label'];
			$attribute_mapping          = $default_config_data['attributes_mapping'];
		}
	}
}

$endpoint      = 'webapi/rest/v1/aliexpress/getTargetCategoryList';
$selected      = '';
$attr_selected = '';
$args          = array(
	'headers'   => $headers,
	'sslverify' => false,
);
if ( ! empty( $default_temp_category ) ) {
	$endpoint = add_query_arg(
		array( 'existing_category_id' => $default_temp_category ),
		$endpoint
	);
}

$category_response = $api_request->get( $endpoint, $args );
if ( ! empty( $category_response['data']['success'] ) ) {
	$target_categories = $category_response['data']['data'];
}
?>

<div class="CIFA-return-button-wrap CIFA-category-header-wrap">
	<h1><a href="admin.php?page=ced_integration_aliexpress&section=category-template"><span class="dashicons dashicons-arrow-left-alt"></span></a> Edit Default Category Template</h1>
</div>
<div class="wc-progress-form-content woocommerce-importer components-panel CIFA-padding2">
	<form id="edit_default_template_form" class="category_template" method="POST">
		<table class="form-table css-off1bd CIFA-table-common">
			<tbody>
				<tr>
					<td scope="row" class="titledesc CIFA-table-head row-title">
						<label for="Category Template Name">
							Category Template Name 
							<?php
													$attribute_description = __( 'Enter a unique name for the category template you wish to create not exceeding 80 characters.', 'cedcommerce-integration-for-aliexpress' );
													echo wc_help_tip( esc_html( $attribute_description ) );
							?>
													</label>
					</td>
					<td class="forminp forminp-select">
						<input type="text" placeholder="Enter Template Name" value="
						<?php
						if ( ! empty( $default_temp_name ) ) {
							echo esc_attr( $default_temp_name );
						}
						?>
																					" disabled>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="CIFA-section-head CIFA-margin-top CIFA-content-border-container">
			<div class="css-10klw3m e19lxcc00">
				<div class="wooocommerce-task-card__header-container">
					<div class="wooocommerce-task-card__header">
						<div class="woocommerce-task-header__contents-container">
							<div class="woocommerce-task-header__contents">
								<table class="form-table css-off1bd CIFA-table-common">
									<tbody>
										<tr>
											<td scope="row" class="titledesc CIFA-table-head row-title">
												<label for="Category Template Name">
													Select AliExpress Product Category 
													<?php
														$attribute_description = __(
															'Select a category that best defines your products. ',
															'cedcommerce-integration-for-aliexpress'
														);
														echo wc_help_tip( esc_html( $attribute_description ) );
														?>
												</label>
											</td>
											<td class="forminp forminp-select">
												<select name="get_target_categories" class="select2-with-checkbox" id="get_target_categories">
													<option>--Select any Category--</option>
													<?php
													if ( ! empty( $target_categories ) ) {
														foreach ( $target_categories as $key => $value ) {
															if ( $default_temp_category == $value['category_id'] ) {
																?>
																<option value="<?php echo esc_attr( $value['category_id'] ); ?>" selected><?php echo esc_attr( $value['category_path_en'] ); ?></option>
																<?php
															} else {
																?>
																<option value="<?php echo esc_attr( $value['category_id'] ); ?>"><?php echo esc_attr( $value['category_path_en'] ); ?></option>
																<?php
															}
														}
													}
													?>
												</select>
												<div class="CIFA_selected_category_section" style="<?php echo empty( $default_temp_category_name ) ? 'display:none' : ''; ?>"><p class="CIFA_selected_category"><span class="CIFA_selected_category_name"><?php echo esc_attr( $default_temp_category_name ); ?></span><button type="button" class="CIFA_unselect_category"><span class="dashicons dashicons-no-alt"></span></button></p></div>
												<input type="hidden" value="<?php echo esc_attr( $default_temp_category_name ); ?>" name="aliexpress_default_category_name" id="aliexpress_default_category_name">
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
					<div class="">
						<div class="aliexpress_attribute_section_mapping">
							<?php
							$admin->get_values_for_attr_mapping_default_template(
								array(
									'category_id'        => $default_temp_category,
									'attributes_mapping' => $attribute_mapping,
								)
							);
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		wp_nonce_field(
			'aliexpress_nonce_field',
			'aliexpress_nonce'
		);
		?>
		<div class="product-footer CIFA-background">
			<input type="submit" value="Save" class="alignright components-button is-primary" id="aliexpress_edit_default_template" name="aliexpress_save_default_template" disabled>
		</div>
	</form>
</div>