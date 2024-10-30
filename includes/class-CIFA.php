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
 * The core plugin class.
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
class CIFA {


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @var      CIFA_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		if ( defined( 'CIFA_VERSION' ) ) {
			$this->version = CIFA_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = CIFA_PLUGIN_NAME;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - CIFA_Loader. Orchestrates the hooks of the plugin.
	 * - CIFA_I18n. Defines internationalization functionality.
	 * - CIFA_Admin. Defines all hooks for the admin area.
	 * - CIFA_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-CIFA-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-CIFA-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-CIFA-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-CIFA-public.php';

		/**
		 * The class responsible for defining all actions that commonly called
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-CIFA-common-callback.php';

		/**
		 * The class responsible for defining all actions that commonly called
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-CIFA-api-base.php';

		$this->loader = new CIFA_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the CIFA_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function set_locale() {

		$plugin_i18n = new CIFA_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks() {

		$plugin_admin = new CIFA_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'CIFA_add_menus', 25 );
		$this->loader->add_filter( 'ced_add_marketplace_menus_array', $plugin_admin, 'CIFA_add_marketplace_menus_to_array', 13 );
		$this->loader->add_action( 'rest_api_init', $plugin_admin, 'CIFA_add_callback_url_endpoint_authorization', 10 );
		$this->loader->add_filter( 'woocommerce_rest_api_get_rest_namespaces', $plugin_admin, 'CIFA_woocommerce_rest_api_set_rest_namespaces' );
		$this->loader->add_filter( 'woocommerce_api_permissions_in_scope', $plugin_admin, 'CIFA_change_app_permission', 10 );
		$this->loader->add_action( 'woocommerce_variation_options_pricing', $plugin_admin, 'CIFA_add_custom_field_to_variations', 10, 3 );
		$this->loader->add_action( 'save_post', $plugin_admin, 'CIFA_save_metadata', 24, 2 );
		$this->loader->add_action( 'woocommerce_update_order', $plugin_admin, 'CIFA_save_metadata_hpos', 24, 2 );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'CIFA_add_order_metabox', 24, 2 );
		$this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'CIFA_add_column_order_section', 20 );
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'CIFA_column_order_section_callback', 20, 2 );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'init_process_of_admin', 20 );

		/* Product data tabs hooks & actions*/
		if ( ! empty( $_GET['post'] ) && isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
			$this->loader->add_filter( 'woocommerce_product_data_tabs', $plugin_admin, 'CIFA_add_product_data_custom_tab', 20 );
			$this->loader->add_action( 'woocommerce_product_data_panels', $plugin_admin, 'CIFA_product_data_panels' );
		}
		$this->loader->add_action( 'woocommerce_product_after_variable_attributes', $plugin_admin, 'CIFA_render_product_fields', 10, 3 );
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'CIFA_save_product_fields_variation' );
		$this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'CIFA_save_product_fields_variation', 10, 2 );
		$this->loader->add_action( 'save_post', $plugin_admin, 'CIFA_save_data_on_simple_product', 10, 2 );

		// ajax calls
		$this->loader->add_action( 'wp_ajax_CIFA_connect_account', $plugin_admin, 'CIFA_connect_account' );
		$this->loader->add_action( 'wp_ajax_CIFA_manual_connect_account', $plugin_admin, 'CIFA_manual_connect_account' );
		$this->loader->add_action( 'wp_ajax_CIFA_onboarding_step2', $plugin_admin, 'onboarding_step2' );
		$this->loader->add_action( 'wp_ajax_CIFA_get_values_for_attr_mapping_default_template', $plugin_admin, 'get_values_for_attr_mapping_default_template' );
		$this->loader->add_action( 'wp_ajax_CIFA_save_default_temp_data_aliexpress', $plugin_admin, 'save_default_temp_data_aliexpress' );
		$this->loader->add_action( 'wp_ajax_CIFA_save_configuration_data_onboarding', $plugin_admin, 'save_configuration_data_onboarding' );
		$this->loader->add_action( 'wp_ajax_CIFA_edit_default_temp_data_aliexpress', $plugin_admin, 'edit_default_temp_data_aliexpress' );
		$this->loader->add_action( 'wp_ajax_CIFA_add_price_rule', $plugin_admin, 'add_price_rule' );
		$this->loader->add_action( 'wp_ajax_CIFA_display_values_on_click_of_rule_group_title', $plugin_admin, 'display_values_on_click_of_rule_group_title' );
		$this->loader->add_action( 'wp_ajax_CIFA_run_query_rule_grp', $plugin_admin, 'run_query_rule_grp' );
		$this->loader->add_action( 'wp_ajax_CIFA_save_new_template_data', $plugin_admin, 'save_new_template_data' );
		$this->loader->add_action( 'wp_ajax_CIFA_edit_template_data', $plugin_admin, 'edit_template_data' );
		$this->loader->add_action( 'wp_ajax_CIFA_refresh_activities', $plugin_admin, 'refresh_activities' );
		$this->loader->add_action( 'wp_ajax_CIFA_reauthorize', $plugin_admin, 'reauthorize' );
		$this->loader->add_action( 'wp_ajax_CIFA_getVariantOptions', $plugin_admin, 'getVariantOptions' );
		$this->loader->add_action( 'wp_ajax_CIFA_validate_profile_name', $plugin_admin, 'validate_profile_name' );
		$this->loader->add_action( 'wp_ajax_CIFA_validate_category_template', $plugin_admin, 'validate_category_template' );
		$this->loader->add_action( 'wp_ajax_CIFA_get_all_categories', $plugin_admin, 'get_all_categories' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_public_hooks() {

		$plugin_public = new CIFA_Public( $this->get_plugin_name(), $this->get_version() );
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    CIFA_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
