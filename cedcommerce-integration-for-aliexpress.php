<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://cedcommerce.com
 * @since             1.0.0
 * @package           CEDCOMMERCE_INTEGRATION_FOR_ALIEXPRESS
 *
 * @wordpress-plugin
 * Plugin Name:       CedCommerce Integration for AliExpress
 * Plugin URI:        https://cedcommerce.com
 * Description:       CedCommerce Integration for AliExpress allows merchants to list their products on AliExpress marketplaces and manage all orders from their WooCommerce store.
 * Version:           1.0.1
 * Author:            CedCommerce
 * Author URI:        https://cedcommerce.com
 * License:           GPLv3 or later
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       cedcommerce-integration-for-aliexpress
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.1 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CIFA_VERSION', '1.0.1' );
define( 'CIFA_DIRPATH', plugin_dir_path( __FILE__ ) );
define( 'CIFA_URL', plugin_dir_url( __FILE__ ) );
define( 'CIFA_ABSPATH', untrailingslashit( plugin_dir_path( __DIR__ ) ) );
define( 'CIFA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CIFA_PLUGIN_NAME', 'cedcommerce-integration-for-aliexpress' );


define( 'CIFA_AUTH_URL', 'https://aliexpress-api-backend.cifapps.com/' );
define( 'CIFA_HOME_URL', 'https://aliexpress-app-backend.cifapps.com/' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-CIFA-activator.php
 */
function CIFA_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-CIFA-activator.php';
	CIFA_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-CIFA-deactivator.php
 */
function CIFA_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-CIFA-deactivator.php';
	CIFA_Deactivator::deactivate();
}

register_deactivation_hook( __FILE__, 'CIFA_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-CIFA.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function CIFA_run() {

	$plugin = new CIFA();
	$plugin->run();
}

/**
 * Woocommerce active plugins hook
 *
 * @since 1.0.0
 */
$activatedPlugins = get_option( 'active_plugins' );
if ( $activatedPlugins && is_array( $activatedPlugins ) && in_array( 'woocommerce/woocommerce.php', $activatedPlugins ) ) {
	CIFA_run();
	register_activation_hook( __FILE__, 'CIFA_activate' );
} else {
	add_action( 'admin_init', 'CIFA_deactivate_woo_missing' );
}

/**
 * Function to show notice while woocommerce plugin is deactivated.
 *
 * @return void
 */
function CIFA_deactivate_woo_missing() {
	deactivate_plugins( CIFA_PLUGIN_BASENAME );
	add_action( 'admin_notices', 'CIFA_woo_missing_notice' );
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} 
);

/**
 * Function to show error message.
 *
 * @return void
 */
function CIFA_woo_missing_notice() {
	// translators: %s: search term !!
	echo '<div class="notice notice-error is-dismissible"><p>' .
	sprintf(
		esc_html(
			__(
				'CedCommerce Integration for AliExpress requires WooCommerce to be installed and active. You can download %s from here.',
				'cedcommerce-integration-for-aliexpress'
			)
		),
		'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>'
	) . '</p></div>';
}
