<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$api_request     = new CIFA_Api_Base();
$common_callback = new CIFA_Common_Callback();
$headers         = $common_callback->get_common_header();

if (
	isset( $_POST['save'] ) && isset( $_POST['aliexpress_nonce'] )
	&& wp_verify_nonce( wp_unslash( sanitize_text_field( $_POST['aliexpress_nonce'] ) ), 'aliexpress_nonce_field' )
) {
	$chooseTitleOption = array();
	if ( ! empty( $_POST['choose_title_option'] ) ) {
		$count = 0;
		foreach ( array_map( 'sanitize_text_field', $_POST['choose_title_option'] ) as $key => $value ) {
			$count = $key + 1;
			$chooseTitleOption[ 'title_template_' . $count ] = sanitize_text_field( $value );
		}
		$chooseTitleOption['text_mappings'] = array();
		if ( ! empty( $_POST['custom_title_keyword'] ) ) {
			$titleTemplateIndex                       = 'title_template_' . ( $count + 1 );
			$chooseTitleOption[ $titleTemplateIndex ] = sanitize_text_field( $_POST['custom_title_keyword'] );
			$chooseTitleOption['text_mappings'][]     = $titleTemplateIndex;
		}
	}


	$attribute_sync = array();
	if ( ! empty( $_POST['attribute_sync_title'] ) ) {
		$attribute_sync['title'] = true;
	} else {
		$attribute_sync['title'] = false;
	}
	if ( ! empty( $_POST['attribute_sync_description'] ) ) {
		$attribute_sync['description'] = true;
	} else {
		$attribute_sync['description'] = false;
	}
	if ( ! empty( $_POST['attribute_sync_compare_at_price'] ) ) {
		$attribute_sync['compare_at_price'] = true;
	} else {
		$attribute_sync['compare_at_price'] = false;
	}
	if ( ! empty( $_POST['attribute_sync_images'] ) ) {
		$attribute_sync['images'] = true;
	} else {
		$attribute_sync['images'] = false;
	}
	$priceUpdate = array();
	if ( ! empty( $_POST['price_rule'] ) ) {
		$priceUpdate['price_template'] = sanitize_text_field( $_POST['price_rule'] );
		if ( ! empty( $_POST['price_template_value'] ) ) {
			$priceUpdate['price_template_value'] = sanitize_text_field( $_POST['price_template_value'] );
		}
	} else {
		$priceUpdate['price_template_value'] = '';
	}
	$product_auto_update = false;
	if ( ! empty( $_POST['product_auto_update'] ) ) {
		$product_auto_update = true;
	}
	$product_auto_create = false;
	if ( ! empty( $_POST['product_auto_create'] ) ) {
		$product_auto_create = true;
	}
	$product_auto_delete = false;
	if ( ! empty( $_POST['product_auto_delete'] ) ) {
		$product_auto_delete = true;
	}
	$product_auto_create_child = false;
	if ( ! empty( $_POST['product_auto_create_child'] ) ) {
		$product_auto_create_child = true;
	}
	$product_auto_create_type_change = false;
	if ( ! empty( $_POST['product_auto_create_type_change'] ) ) {
		$product_auto_create_type_change = true;
	}


	$product = array(
		'product_auto_update'             => $product_auto_update,
		'product_auto_create'             => $product_auto_create,
		'product_auto_delete'             => $product_auto_delete,
		'title_template'                  => $chooseTitleOption,
		'product_auto_create_child'       => $product_auto_create_child,
		'product_auto_create_type_change' => $product_auto_create_type_change,
		'threshold_inventory'             => ! empty( $_POST['threshold_inventory'] ) ? sanitize_text_field( $_POST['threshold_inventory'] ) : 0,
	);

	if ( ! empty( $attribute_sync ) ) {
		$product['attribute_sync'] = $attribute_sync;
	}
	if ( ! empty( $priceUpdate ) ) {
		$product['price_rule'] = $priceUpdate;
	}
	$sync_order = false;
	if ( ! empty( $_POST['sync_order'] ) ) {
		$sync_order = sanitize_text_field( $_POST['sync_order'] );
	}

	$formattedArray = array(
		'data' => array(
			array(
				'group_code' => 'product',
				'data'       => $product,
			),
			array(
				'group_code' => 'order',
				'data'       => array(
					'order_sync'                  => $sync_order,
					'woocommerce_shipping_method' => ! empty( $_POST['woocommerce_shipping_method'] ) ? sanitize_text_field( $_POST['woocommerce_shipping_method'] ) : '',
					'woocommerce_tax_class'       => ! empty( $_POST['woocommerce_tax_class'] ) ? sanitize_text_field( $_POST['woocommerce_tax_class'] ) : '',
				),
			),
		),
	);
	$params         = array(
		'headers' => $headers,
		'body'    => wp_json_encode( $formattedArray ),
	);
	$response       = $api_request->post( 'connector/config/saveConfig', $params );
	if ( empty( $response['data']['success'] ) ) {
		echo '<pre>';
		print_r( $data );
		die();
	}
	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<strong><?php echo esc_html_e( 'Your Configuration data has been saved successfully.', 'mautic-integration-for-woocommerce' ); ?></strong>
		</p>
		<button type="button" class="notice-dismiss">
			<span class="screen-reader-text">Dismiss this notice.</span>
		</button>
	</div>
	<?php
}
$url      = 'connector/config/getConfig';
$body     = array(
	'source_marketplace' => 'woocommerce',
	'group_code'         => array( 'order', 'product' ),
);
$params   = array(
	'headers'   => $headers,
	'body'      => wp_json_encode( $body ),
	'sslverify' => false,
);
$response = $api_request->post( $url, $params );

$data = $response['data'];
if ( empty( $data['success'] ) ) {
	echo '<pre>';
	echo 'data not fetch';
	print_r( $data );
	die();
	return array(
		'success' => false,
		'message' => 'Something wents wrong in wordpress',
	);
}
$groups = array();
foreach ( $data['data'] as $value ) {
	$groups[ $value['group_code'] ] = $value;
}
$product              = ! empty( $groups['product']['value'] ) ? $groups['product']['value'] : array();
$CIFA_order           = ! empty( $groups['order']['value'] ) ? $groups['order']['value'] : array();
$new_title_rule_array = array();
$count                = 0;
if ( ! empty( $product['title_template'] ) ) {
	foreach ( $product['title_template'] as $k => $v ) {
		$new_title_rule_array[ $count ] = $v;
		++$count;
	}
}

$productData = array(
	'product_auto_update'             => ( isset( $product['product_auto_update'] ) &&
		$product['product_auto_update'] ) ? 'checked' : '',
	'product_auto_create'             => ( isset( $product['product_auto_create'] ) &&
		$product['product_auto_create'] ) ? 'checked' : '',
	'product_auto_delete'             => ( isset( $product['product_auto_delete'] ) &&
		$product['product_auto_delete'] ) ? 'checked' : '',
	'product_auto_create_child'       => ( isset( $product['product_auto_create_child'] ) &&
		$product['product_auto_create_child'] ) ? 'checked' : '',
	'product_auto_create_type_change' => ( isset( $product['product_auto_create_type_change'] )
		&& $product['product_auto_create_type_change'] ) ? 'checked' : '',
	'title_template'                  => ! empty( $new_title_rule_array ) ? $new_title_rule_array : array(),
	'attribute_sync_title'            => ( isset( $product['attribute_sync']['title'] ) &&
		$product['attribute_sync']['title'] ) ? 'checked' : '',
	'attribute_sync_description'      => ( isset( $product['attribute_sync']['description'] ) &&
		$product['attribute_sync']['description'] ) ? 'checked' : '',
	'attribute_sync_compare_at_price' => ( isset( $product['attribute_sync']['compare_at_price'] ) &&
		$product['attribute_sync']['compare_at_price'] ) ? 'checked' : '',
	'attribute_sync_images'           => ( isset( $product['attribute_sync']['images'] ) &&
		$product['attribute_sync']['images'] ) ? 'checked' : '',
	'price_template'                  => isset( $product['price_rule']['price_template'] ) ?
		$product['price_rule']['price_template'] : '',
	'price_template_value'            => isset( $product['price_rule']['price_template_value'] ) ?
		$product['price_rule']['price_template_value'] : '',
	'threshold_inventory'             => ! empty( $product['threshold_inventory'] )
		? $product['threshold_inventory'] : '',

);
$orderData = array(
	'order_sync'                  => ! empty( $CIFA_order['order_sync'] ) ? 'checked' : '',
	'woocommerce_shipping_method' => ! empty( $CIFA_order['woocommerce_shipping_method'] ) ? $CIFA_order['woocommerce_shipping_method'] : '',
	'woocommerce_tax_class'       => ! empty( $CIFA_order['woocommerce_tax_class'] ) ? $CIFA_order['woocommerce_tax_class'] : '',
);

$url      = 'webapi/rest/v1/woocommerce/category/getTitleRule';
$params   = array(
	'headers' => $headers,
);
$response = $api_request->get( $url, $params );

$titleOptions        = ! empty( $response['data']['data'] ) ? $response['data']['data'] : array();
$selectedTitleValues = array();
foreach ( $productData['title_template'] as $key => $value ) {
	if ( is_string( $value ) ) {
		$selectedTitleValues[] = $value;
	}
}
$productData['custom_title_keyword'] =
	! empty( $product['title_template'][ $product['title_template']['text_mappings'][0] ] ) ?
	$product['title_template'][ $product['title_template']['text_mappings'][0] ] : '';
$getShippingMethods                  = $api_request->get( 'webapi/rest/v1/woocommerce/order/getShippingMethod', $params );
$shippingMethods                     = ! empty( $getShippingMethods['data']['data'] ) ? $getShippingMethods['data']['data'] : array();

$getTaxClasses = $api_request->get( 'webapi/rest/v1/woocommerce/order/getTaxClass', $params );
$taxClasses    = ! empty( $getTaxClasses['data']['data'] ) ? $getTaxClasses['data']['data'] : array();

$getTaxRate = $api_request->get( add_query_arg( array( 'refresh' => true ), 'webapi/rest/v1/woocommerce/order/fetchTaxRate' ), $params );
$taxRate    = ! empty( $getTaxRate['data']['data'] ) ? $getTaxRate['data']['data'] : array();

$selectValues = array(
	'fixed_increment'   => 'Fixed Increment',
	'fixed_decrement'   => 'Fixed Decrement',
	'multiply'          => 'Multiply',
	'percent_decrement' => 'Percent Decrement',
	'percent_increment' => 'Percent Increment',
);
$getAll       = $api_request->get( 'connector/get/all', $params );
$getAllShops  = $getAll['data']['data'];
$config_tabs  = ! empty( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'product_settings';
echo '<div class="sub-header"><ul class="subsubsub">
    <li><a href="' . esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress&section=configuration&tab=product_settings' ) ) . '" class="' . ( 'product_settings' == $config_tabs ? ' current ' : '' ) . 'product_settings_button">Product Settings</a> | </li>
    <li><a href="' . esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress&section=configuration&tab=title_optimization' ) ) . '" class="' . ( 'title_optimization' == $config_tabs ? ' current ' : '' ) . 'title_optimization_button">Title Optimization</a> | </li>
    <li><a href="' . esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress&section=configuration&tab=order_management' ) ) . '" class="' . ( 'order_management' == $config_tabs ? ' current ' : '' ) . 'order_management_button">Order Management</a> | </li>
    <li><a href="' . esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress&section=configuration&tab=account' ) ) . '" class="' . ( 'account' == $config_tabs ? ' current ' : '' ) . 'account_button">Account</a></li></ul></div>';
echo '<form method="POST">
<div class="wc-progress-form-content CIFA-background">';
wp_nonce_field(
	'aliexpress_nonce_field',
	'aliexpress_nonce'
);
$product_settings      = '<div class="product_settings CIFA-product-setting-top" style="' . ( 'product_settings' !== $config_tabs ? 'display:none;' : '' ) . '">
	<header class="CIFA-padding2">
		<h2>Product Settings</h2>
		<p>Set your preferences for default settings to upload products on AliExpress</p>			
	</header>
	<div class="CIFA-padding CIFA-border-bottom-wrap">
		<div class="wc-progress-form-content woocommerce-importer">
			<table class="form-table CIFA-table-content-width">
				<tbody>
					<tr>
						<td scope="row" class="row-title">
							<label for="woocommerce_currency">Custom Price Rule';
$attribute_description = __(
	'Customize (Increase or decrease) product prices on AliExpress by setting a custom price rule.
',
	'cedcommerce-integration-for-aliexpress'
);
$product_settings     .= wc_help_tip( esc_html( $attribute_description ) );
$product_settings     .= '</label>
						</td>
						<td class="forminp forminp-select">
							<select style="width: 100%; margin-bottom:15px;" name="price_rule" id="price_rule" data-fieldid="">
								<option value="">None</option>';

foreach ( $selectValues as $key => $value ) {
	if ( $key === $productData['price_template'] ) {
		$product_settings .= '<option value="' . esc_attr( $key ) . '" selected>' . esc_attr( $value ) . '</option>';
	} else {
		$product_settings .= '<option value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</option>';
	}
}
$product_settings     .= '
							</select><br>
                            <input type="text" id="price_template_value" class="CIFA-number-input price_template_value custom_price_rule" style="' . ( empty( $productData['price_template_value'] ) ? 'display:none' : '' ) . '" name="price_template_value" min="1" value="' . esc_attr( $productData['price_template_value'] ) . '" >
						</td>
					</tr><tr>
						<td scope="row" class="row-title">
							<label for="product_auto_update">Product Auto Update';
$attribute_description = __( 'Enable to allow automatic syncing between WooCommerce and AliExpress; any product update on WooCommerce will also be reflected on AliExpress in real time.', 'cedcommerce-integration-for-aliexpress' );
$product_settings     .= wc_help_tip( esc_html( $attribute_description ) );
$product_settings     .= '</label>
						</td>
						<td class="forminp forminp-select">
							<input name="product_auto_update" class="CIFA-checked-button" id="product_auto_update" type="checkbox" ' . esc_attr( $productData['product_auto_update'] ) . '>
							<label class="" for="product_auto_update"></label>
						</td>
					</tr>
					<tr>
						<td scope="row" class="row-title">
							<label for="product_auto_create_child">Product Auto Upload Variations';
$attribute_description = __( 'Enable to upload new product variants on AliExpress once created in WooCommerce so that existing variant product on AliExpress stays updated.', 'cedcommerce-integration-for-aliexpress' );
$product_settings     .= wc_help_tip( esc_html( $attribute_description ) );
$product_settings     .= '</label>
						</td>
						<td class="forminp forminp-select">
							<input name="product_auto_create_child" class="CIFA-checked-button" id="product_auto_create_child" type="checkbox" ' . esc_attr( $productData['product_auto_create_child'] ) . '>
							<label class="" for="product_auto_create_child"></label>
						</td>
					</tr><tr>
						<td scope="row" class="row-title">
							<label for="product_auto_create_type_change">Product Auto Upload If Type Changed';
$attribute_description = __( 'Enable to automatically upload product on AliExpress when a Live simple product is changed to a variation product on the plugin. Please note that changing product type will lead to the deletion of that “Previous Simple product” from AliExpress.', 'cedcommerce-integration-for-aliexpress' );
$product_settings     .= wc_help_tip( esc_html( $attribute_description ) );
$product_settings     .= '</label>
						</td>
						<td class="forminp forminp-select">
							<input name="product_auto_create_type_change" class="CIFA-checked-button" id="product_auto_create_type_change" type="checkbox" ' . esc_attr( $productData['product_auto_create_type_change'] ) . '>
							<label class="" for="product_auto_create_type_change"></label>
						</td>
					</tr><tr>
						<td scope="row" class="row-title">
							<label for="product_auto_create">Product Auto Create';
$attribute_description = __( 'Enable to automatically create product(s) on AliExpress when new product(s) are added to the WooCommerce store.', 'cedcommerce-integration-for-aliexpress' );
$product_settings     .= wc_help_tip( esc_html( $attribute_description ) );
$product_settings     .= '</label>
						</td>
						<td class="forminp forminp-select">
							<input name="product_auto_create" class="CIFA-checked-button" id="product_auto_create" type="checkbox" ' . esc_attr( $productData['product_auto_create'] ) . '>
							<label class="" for="product_auto_create"></label>
						</td>
					</tr>
                    <tr>
						<td scope="row" class="row-title">
							<label for="product_auto_delete">Product Auto Delete';
$attribute_description = __( 'Enable to automatically delete products on AliExpress when the product(s) are deleted in the WooCommerce store.', 'cedcommerce-integration-for-aliexpress' );
$product_settings     .= wc_help_tip( esc_html( $attribute_description ) );
$product_settings     .= '</label>
						</td>
						<td class="forminp forminp-select">
							<input name="product_auto_delete" class="CIFA-checked-button" id="product_auto_delete" type="checkbox" ' . esc_attr( $productData['product_auto_delete'] ) . '>
							<label class="" for="product_auto_delete"></label>
						</td>
					</tr>
                    
                    </tbody>
				</table>
			</div>
		</div><header class="CIFA-padding2">
			<h2>Inventory Rules</h2>
			<p>Provide your preferred inventory settings for AliExpress.</p>			
		</header><div class="CIFA-padding CIFA-border-bottom-wrap">
			<div class="wc-progress-form-content woocommerce-importer">
				<table class="form-table">
					<tbody>
						<tr>
							<td scope="row" class="row-title">
								<label for="woocommerce_currency">Threshold Inventory';
$attribute_description = __( 'Specify a minimum inventory limit, which, when reached, marks the product as "Sold Out" on AliExpress, thus preventing you from overselling.', 'cedcommerce-integration-for-aliexpress' );
$product_settings     .= wc_help_tip( esc_html( $attribute_description ) );
$product_settings     .= '</label>
							</td>
							<td class="forminp forminp-select">
                            <input type="text" name="threshold_inventory" class="CIFA-number-input threshold_inventory" value="' . esc_attr( $productData['threshold_inventory'] ) . '" placeholder="Enter Inventory Value" min="0">
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
        <header class="CIFA-padding2">
            <h2>Data Sync with AliExpress</h2>
			<p>This setting will help in syncing price, inventory, and product details from the app to AliExpress. If you uncheck or disable any option,
             then the corresponding product information will not be synced to AliExpress via the app.</p>
        </header>
        <div class="CIFA-padding CIFA-border-bottom-wrap">
			<div class="wc-progress-form-content woocommerce-importer">
				
                <div class="CIFA-display-flex">
                    <div>
                    <label for="ctitle">Title</label>
                    <input type="checkbox" name="attribute_sync_title" id="ctitle" ' . $productData['attribute_sync_title'] . '>
                    </div>
                    <div>
                    <label for="cdescription">Description</label>
                    <input type="checkbox" name="attribute_sync_description" id="cdescription" ' . $productData['attribute_sync_description'] . '>
                    </div>
                    <div>
                    <label for="cprice">Sale Price</label>
                    <input type="checkbox" name="attribute_sync_compare_at_price" id="cprice" ' . $productData['attribute_sync_compare_at_price'] . '>
                    </div>
                    <div>
                    <label for="cimage">Image</label>
                    <input type="checkbox" name="attribute_sync_images" id="cimage" ' . $productData['attribute_sync_images'] . '>							</td>
                    </div>
                </div>
                        
			</div>
		</div>
        </div>
		';
$title_optimization    = '
    <div class="title_optimization CIFA-product-setting-top" style="' . ( 'title_optimization' !== $config_tabs ? 'display:none;' : '' ) . '">
        <header class="CIFA-padding2">
            <h2>Title Optimization</h2>
            <p>Customize the product title to improve SEO and increase visibility. Add relevant keywords to ensure customers can easily identify the product on AliExpress.</p>			
        </header>
        <div class="CIFA-padding CIFA-product-setting-top">
            <div class="wc-progress-form-content woocommerce-importer">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <td scope="row" class="row-title">
                                <label for="woocommerce_currency">Custom Title Option';
$attribute_description = __( 'Choose relevant product details here to customize product titles on AliExpress.', 'cedcommerce-integration-for-aliexpress' );
$title_optimization   .= wc_help_tip( esc_html( $attribute_description ) );
$title_optimization   .= '</label>
                            </td>
                            <td class="forminp forminp-select CIFA-select-to-width">
                                <select name="choose_title_option[]" class="select2-with-checkbox choose_title_option" multiple="multiple" data-fieldid="">
                                    ';
$custom_title_keyword  = $productData['custom_title_keyword'];
$selectedTitleValues   = array_filter(
	$selectedTitleValues,
	function ( $s ) use ( $custom_title_keyword ) {
		if ( $s != $custom_title_keyword ) {
			return $s;
		}
	}
);
foreach ( $titleOptions as $key => $value ) {
	if ( in_array( $key, $selectedTitleValues ) ) {
		$title_optimization .= '<option value="' . esc_attr( $key ) . '" selected>' . esc_attr( $value ) . '</option>';
	} elseif ( count( $selectedTitleValues ) >= 5 ) {
		$title_optimization .= '<option value="' . esc_attr( $key ) . '" disabled>' . esc_attr( $value ) . '</option>';
	} else {
		$title_optimization .= '<option value="' . esc_attr( $key ) . '" >' . esc_attr( $value ) . '</option>';
	}
}
$title_optimization   .= '</select>';
$title_optimization   .= '
                            </td>
                        </tr><tr>
                            <td scope="row" class="row-title">
                                <label for="woocommerce_currency">Custom Title Keyword';
$attribute_description = __( 'Provide relevant keyword(s) here to customize product titles on AliExpress.', 'cedcommerce-integration-for-aliexpress' );
$title_optimization   .= wc_help_tip( esc_html( $attribute_description ) );
$title_optimization   .= '</label>
                            </td>
                            <td class="forminp forminp-select">
                                <input name="custom_title_keyword" value="' . esc_attr( $productData['custom_title_keyword'] ) . '" type="text" placeholder="type">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>';

$order_management      = '<div class="order_management CIFA-product-setting-top" style="' . ( 'order_management' !== $config_tabs ? 'display:none;' : '' ) . '">
	<header class="CIFA-padding2">
		<h2>Order Management</h2>
		<p>Set your preferences for default settings of order management</p>			
	</header>
	<div class="CIFA-padding">

		<div class="wc-progress-form-content woocommerce-importer">

			<table class="form-table CIFA-table-content-width">
				<tbody>

					<tr>
						<td scope="row" class="row-title">
							<label for="woocommerce_currency">Sync Order';
$attribute_description = __( 'If enabled, the plugin will create AliExpress orders automatically on WooCommerce.', 'cedcommerce-integration-for-aliexpress' );
$order_management     .= wc_help_tip( esc_html( $attribute_description ) );
$order_management     .= '</label>
						</td>
						<td class="forminp forminp-select">
                        <input name="sync_order" class="CIFA-checked-button" id="sync_order" type="checkbox" ' . esc_attr( $orderData['order_sync'] ) . '>
                        <label class="" for="sync_order"></label>
						</td>
					</tr>
                    <tr>
						<td scope="row" class="row-title">
							<label for="shipping_method">Choose Shipping Method';
$attribute_description = __( 'Choose a relevant shipping method to create orders in WooCommerce.', 'cedcommerce-integration-for-aliexpress' );
$order_management     .= wc_help_tip( esc_html( $attribute_description ) );
$order_management     .= '</label>
						</td>
						<td class="forminp forminp-select">
                            <select style="width: 100%;" name="woocommerce_shipping_method" id="shipping_method" data-fieldid="">
                                ';

foreach ( $shippingMethods as $key => $value ) {
	if ( ! ( empty( $CIFA_order['woocommerce_shipping_method'] ) ) && $key === $CIFA_order['woocommerce_shipping_method'] ) {
		$order_management .= '<option value=' . esc_attr( $key ) . ' selected>' . esc_attr( $value ) . '</option>';
	} else {
		$order_management .= '<option value=' . esc_attr( $key ) . '>' . esc_attr( $value ) . '</option>';
	}
}
$order_management     .= '
                            </select>
							<label class="" for="shipping_method"></label>
						</td>
					</tr>
                    <tr>
						<td scope="row" class="row-title">
							<label for="tax_class">Choose Tax Class';
$attribute_description = __( 'Choose a relevant tax class to create orders in WooCommerce.', 'cedcommerce-integration-for-aliexpress' );
$order_management     .= wc_help_tip( esc_html( $attribute_description ) );
$order_management     .= '</label>
						</td>
						<td class="forminp forminp-select">
                            <select style="width: 100%;" name="woocommerce_tax_class" id="tax_class" data-fieldid="">
                                ';

foreach ( $taxClasses as $key => $value ) {
	if ( ! ( empty( $CIFA_order['woocommerce_tax_class'] ) ) && $key === $CIFA_order['woocommerce_tax_class'] ) {
		$order_management .= '<option value=' . esc_attr( $key ) . ' selected>' . esc_attr( $value ) . '</option>';
	} else {
		$order_management .= '<option value=' . esc_attr( $key ) . '>' . esc_attr( $value ) . '</option>';
	}
}
$order_management .= '
                            </select>
							<label class="" for="tax_class"></label>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>';

$account    = '
        <div class="account" style="' . ( 'account' !== $config_tabs ? 'display:none;' : '' ) . '">
        <header class="CIFA-padding2 CIFA-product-setting-top">
        <h2>Account Management</h2>
        <p>Here you will find important details about your connected WooCommerce Store & AliExpress seller account.</p>
        </header>
        <div class="error_success_notification_display"></div>
            <div class="CIFA-account-wrapper account">
                <div class="CIFA-account-common-wrap CIFA-padding2">
                    <div class="CIFA-account-text-holder">
                        <div class="CIFA-account-detail-holder">
                            <div class="CIFA-store-account">
                                <div class="CIFA-detail-common-container">
                                    <h5 class="row-title">Store URL:</h5>
                                    <p>' . $getAllShops['woocommerce']['installed'][0]['domain'] . '</p>
                                </div>
                                <div class="CIFA-detail-common-container">
                                    <h5 class="row-title">Email:</h5>
                                    <p>' . $getAllShops['woocommerce']['installed'][0]['email'] . '</p>
                                </div>
                            </div>
                            <div class="CIFA-store-account">
                                <div class="CIFA-detail-common-container">
                                    <h5 class="row-title">Created At:</h5>
                                    <p>';
$created_at = $getAllShops['woocommerce']['installed'][0]['created_at']['$date']['$numberLong'] / 1000;
$account   .= gmdate( 'Y-m-d H:i:s', (int) $created_at );

$account .= '</p>
                                </div>
                                <div class="CIFA-detail-common-container">
                                    <h5 class="row-title">Business Name:</h5>
                                    <p>' . $getAllShops['woocommerce']['installed'][0]['name'] . '</p>
                                </div>
                            </div>
                        </div>
                        <div class="CIFA-account-card-wrapper">
                            <div class="CIFA-account-card">
                                <div class="CIFA-account-logo-container">
                                    <div class="CIFA-icon-account"><img src="' . CIFA_URL . 'admin/images/aliexpress_connection.png"><h5>AliExpress Account</h5></div><img src="' . CIFA_URL . 'admin/images/connected.png">
                                </div>
                                <div class="CIFA-account-list-common-wrap-holder">
                                    <div class="CIFA-details-holder">
                                        <div><h5 class="row-title">Supplier ID:</h5></div>
                                        <div><p>' . $getAllShops['aliexpress']['installed'][0]['seller_id'] . '</p></div>
                                    </div>
                                    <div class="CIFA-details-holder">
                                        <div>
                                            <div><h5 class="row-title">Current account status:</h5></div>
                                        <div><p>' . ucfirst( ! empty( $getAllShops['aliexpress']['installed'][0]['shop_status'] ) ? $getAllShops['aliexpress']['installed'][0]['shop_status'] : '' ) . '</p></div>
                                    </div>
                                    </div>
                                    
                                </div>
								<div class="CIFA-reauthorization-button">
                                        <a class="components-button is-secondary" id="reauthorize" href="javascript:void(0)">Reauthorize</a>

                                        
                                        <div id="CIFA-myPopup" class="CIFA-modal-wrap reauthorize-modal">
                                            <div class="CIFA-content">
                                                <span class="CIFA-close-button"><span class="dashicons dashicons-no-alt"></span></span>
                                                <div class="CIFA-title-holder">
                                                    <h3>Reauthorize</h3>
                                                </div>
                                                <div class="CIFA-popup-content-wrapper">
                                                    <div class="CIFA-popup-content">
                                                    <div class="woocommerce-progress-form-wrapper">
                                                    <form method="POST">
                                                    
                                                    <div class="wc-progress-form-content">
                                                    <header><h2>Are you sure, you want to reauthorize?</h2></header>
                                                                <div class="wc-actions">
                                                                    <a type="button" href="' . CIFA_HOME_URL .
	'connector/get/installationForm?code=aliexpress&app_tag=aliexpress_connector&source_shop_id='
	. $getAllShops['woocommerce']['installed'][0]['_id'] . '&source=' . $getAllShops['woocommerce']['code'] . '&bearer=' . json_decode( get_option( 'CIFA_token_data' ) )->token .
	'&frontend_redirect_uri=' . admin_url( 'admin.php?page=ced_integration_aliexpress&section=configuration&tab=account' ) . '" id=""
                                                                        class="alignright components-button is-primary">Reauthorize</a>
                                                                </div>
                                                            </div>
                                                    </form> 
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
print_r( $product_settings . $title_optimization . $order_management . $account );
echo '<div class="wc-actions CIFA-button-save-wrap CIFA-border-top" >
<button type="submit" name="save" class="components-button is-primary" style="' . ( 'account' === $config_tabs ? 'display:none;' : '' ) . '">Save</button>
</div>
</div></form>';
