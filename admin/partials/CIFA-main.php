<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	wp_die();
}
?>
<div class="CIFA-loader">
	<img src="<?php echo esc_url( CIFA_URL . 'admin/images/ajax-loader.gif' ); ?>" width="50px" height="50px" class="ced_shopee_loading_img">
</div>
<?php
$api_request     = new CIFA_Api_Base();
$common_callback = new CIFA_Common_Callback();
$headers         = $common_callback->get_common_header();
$params          = array( 'headers' => $headers );
$getAll          = $api_request->get( 'connector/get/all', $params );
$getAllShops     = $getAll['data']['data'];
$token_expire    = $getAllShops['aliexpress']['installed'][0]['refresh_token_expires_time_gmt'];
$notices         = ! empty( $getAllShops['aliexpress']['installed'][0]['notices'] ) ?
	$getAllShops['aliexpress']['installed'][0]['notices'] : array();
$redirect_url    = CIFA_HOME_URL .
	'connector/get/installationForm?code=aliexpress&app_tag=aliexpress_connector&source_shop_id='
	. $getAllShops['woocommerce']['installed'][0]['_id'] . '&source=' . $getAllShops['woocommerce']['code'] . '&bearer=' . json_decode( get_option( 'CIFA_token_data' ) )->token .
	'&frontend_redirect_uri=' . admin_url( 'admin.php?page=ced_integration_aliexpress&section=configuration&tab=account' );
?>
<div class="CIFA-reauthorize-notice notice notice-success is-dismissible">
	<p>
		Your account token will expire on <?php echo esc_attr( $token_expire ); ?> (UTC), Please
		<a href="<?php echo esc_url( $redirect_url ); ?>" class="reauthorize-app">reauthorize</a>
		the app to continue using it.
	</p>
	<?php
	foreach ( $notices as $k => $notice ) {
		if ( 'upgrade_notice' == $k ) {
			continue;
		}
		echo '<p>';
		print_r( $notice['notice'] );
		echo '</p>';
	}
	?>
</div>
<?php
$error_message   = get_transient( 'CIFA_error_message' );
$success_message = get_transient( 'CIFA_success_message' );
if ( ! empty( $error_message ) ) {
	echo '<div class="error_success_notification_display notice notice-error is-dismissible" style="display:block"><p><strong >' . esc_attr( $error_message ) . '
    </strong></p></div>';
	delete_transient( 'error_message' );
} elseif ( ! empty( $success_message ) ) {
	echo '<div class="error_success_notification_display notice notice-success is-dismissible" style="display:block"><p><strong >' . esc_attr( $success_message ) . '
    </strong></p></div>';
	delete_transient( 'success_message' );
} else {
	echo '<div class="error_success_notification_display" ></div>';
}
?>

<?php
$postData = isset( $_POST['aliexpress_nonce'] ) && wp_verify_nonce(
	sanitize_text_field( wp_unslash( $_POST['aliexpress_nonce'] ) ),
	'aliexpress_nonce_field'
) ? filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
if ( 'yes' == get_option( 'CIFA_onboarding_completed', 'no' ) ) {
	if ( isset( $postData['go_to_landing_page'] ) ) {
		update_option( 'CIFA_onboarding_completed', 'done' );
		header( 'Location: admin.php?page=ced_integration_aliexpress' );
	}
	?>
	<div class="woocommerce-progress-form-wrapper">
		<h2 style="text-align: left;">AliExpress Connector for CedCommerce</h2>
		<ol class="wc-progress-steps">
			<li class="done">
				Connect WooCommerce </li>
			<li class="done">
				Connect AliExpress </li>
			<li class="done">
				Default Template </li>
			<li class="done">
				Default Configuration </li>
		</ol>
		<div class="wc-progress-form-content woocommerce-importer">
			<header style="text-align: center;">
				<img style="width: 15%;" src="<?php echo esc_url( CIFA_URL . 'admin/images/' ); ?>success.jpg" alt="">
				<p><strong>Onboarding successfully completed!</strong></p>
			</header>
			<div class="wc-actions">
				<form method="POST">
					<?php
					wp_nonce_field(
						'aliexpress_nonce_field',
						'aliexpress_nonce'
					);
					?>
					<input type="submit" style="float:right;" type="button" value="Go to Landing Page" class="components-button is-primary" name="go_to_landing_page" id="go_to_landing_page">
				</form>
			</div>
		</div>
	</div>
	<?php
} else {
	$CIFA_tabs = array(
		'category-template' => __( 'Category Template', 'cedcommerce-integration-for-aliexpress' ),
		'products'          => __( 'Products', 'cedcommerce-integration-for-aliexpress' ),
		'orders'            => __( 'Orders', 'cedcommerce-integration-for-aliexpress' ),
		'configuration'     => __( 'Configuration', 'cedcommerce-integration-for-aliexpress' ),
		'activities'        => __( 'Activities', 'cedcommerce-integration-for-aliexpress' ),
	);
	?>

	<?php if ( ! empty( $CIFA_tabs ) && is_array( $CIFA_tabs ) ) { ?>
		<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php foreach ( $CIFA_tabs as $key => $label ) { ?>

				<?php
				if ( isset( $_GET['section'] ) ) {
					$is_active = $_GET['section'] === $key ? 'nav-tab-active' : '';
				} else {
					$is_active = 'category-template' === $key ? 'nav-tab-active' : '';
				}
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress&section=' . $key ) ); ?>" class="nav-tab <?php echo esc_html( $is_active ); ?>"><?php echo esc_html( $label ); ?></a>

			<?php } ?>
		</nav>

		<?php
	}
	if ( isset( $_GET['page'] ) && 'ced_integration_aliexpress' === $_GET['page'] && isset( $_GET['section'] ) && isset( $_GET['sub-section'] ) ) {
		include_once CIFA_DIRPATH . 'admin/partials/templates/' . sanitize_text_field( wp_unslash( $_GET['sub-section'] ) ) . '.php';
	} elseif ( isset( $_GET['page'] ) && 'ced_integration_aliexpress' === $_GET['page'] && isset( $_GET['section'] ) ) {
		include_once CIFA_DIRPATH . 'admin/partials/CIFA-' . sanitize_text_field( wp_unslash( $_GET['section'] ) ) . '.php';
	} elseif ( isset( $_GET['page'] ) && 'ced_integration_aliexpress' === $_GET['page'] ) {
		include_once CIFA_DIRPATH . 'admin/partials/CIFA-category-template.php';
	}
}

?>