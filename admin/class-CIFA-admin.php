<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://cedcommerce.com
 * @since      1.0.0
 *
 * @package    CIFA
 * @subpackage CIFA/admin
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    CIFA
 * @subpackage CIFA/admin
 */
class CIFA_Admin {





	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	public $apiRequest;
	public $commonCallback;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name       The name of this plugin.
	 * @param    string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name    = $plugin_name;
		$this->version        = $version;
		$this->apiRequest     = new CIFA_Api_Base();
		$this->commonCallback = new CIFA_Common_Callback();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in CIFA_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The CIFA_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( ! empty( $_GET['page'] ) && ( 'ced_integration_aliexpress' == sanitize_text_field( $_GET['page'] )
			|| 'cedcommerce-integrations' === sanitize_text_field( $_GET['page'] ) ) ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/CIFA-admin.css', array(), $this->version . time(), 'all' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( WC_ADMIN_APP );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in CIFA_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The CIFA_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( ! empty( $_GET['page'] ) && 'ced_integration_aliexpress' == sanitize_text_field( $_GET['page'] ) ) {

			$suffix = '';
			wp_register_script(
				'woocommerce_admin',
				WC()->plugin_url() . '/assets/js/admin/woocommerce_admin' . $suffix . '.js',
				array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ),
				time()
			);
			wp_register_script(
				'jquery-tiptip',
				WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js',
				array( 'jquery' ),
				time(),
				true
			);
			$params = array(
				'strings' => array(
					'import_products' => __( 'Import', 'woocommerce' ),
					'export_products' => __( 'Export', 'woocommerce' ),
				),
				'urls'    => array(
					'import_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ),
					'export_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
				),
			);
			wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $params );
			wp_enqueue_script( 'woocommerce_admin' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'selectWoo' );
			wp_enqueue_script( 'jquery-ui-spinner' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui-css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui-min.css', array(), '1.12.1', 'all' );
			wp_enqueue_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/CIFA-admin.js',
				array( 'jquery', 'selectWoo' ),
				$this->version,
				array( 'in_footer' => true )
			);
			$ajax_nonce     = wp_create_nonce( 'ced-aliexpress-ajax-seurity-string' );
			$localize_array = array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => $ajax_nonce,

			);
			wp_localize_script( $this->plugin_name, 'CIFA_obj', $localize_array );
			wc_enqueue_js( "$('.select2-with-checkbox').selectWoo();" );
		} elseif ( 'product' == get_current_screen()->id ) {
			wp_enqueue_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/CIFA-product.js',
				array( 'jquery' ),
				$this->version,
				array( 'in_footer' => true )
			);
			$ajax_nonce     = wp_create_nonce( 'ced-aliexpress-ajax-seurity-string' );
			$localize_array = array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => $ajax_nonce,

			);
			wp_localize_script( $this->plugin_name, 'CIFA_obj', $localize_array );
		}
	}


	/**
	 * Function to add menu in woocommerce
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function CIFA_add_menus() {
		global $submenu;
		if ( empty( $GLOBALS['admin_page_hooks']['cedcommerce-integrations'] ) ) {
			add_menu_page( __( 'CedCommerce', 'cedcommerce-integration-for-aliexpress' ), __( 'CedCommerce', 'cedcommerce-integration-for-aliexpress' ), 'manage_woocommerce', 'cedcommerce-integrations', array( $this, 'ced_marketplace_listing_page' ), CIFA_URL . 'admin/images/logo.png', 12 );
			/**
			 * Filter to add more marketplace indexes of cedcommerce
			 *
			 * @since 1.0.0
			 */
			$menus = apply_filters( 'ced_add_marketplace_menus_array', array() );
			if ( is_array( $menus ) && ! empty( $menus ) ) {
				foreach ( $menus as $key => $value ) {
					add_submenu_page( 'cedcommerce-integrations', $value['name'], $value['name'], 'manage_woocommerce', $value['menu_link'], array( $value['instance'], $value['function'] ) );
				}
			}
		}
	}

	/**
	 * Function to add listing page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ced_marketplace_listing_page() {
		/**
		 * Filter to add more marketplace indexes of cedcommerce
		 *
		 * @since 1.0.0
		 */
		$active_marketplaces = apply_filters( 'ced_add_marketplace_menus_array', array() );
		if ( is_array( $active_marketplaces ) && ! empty( $active_marketplaces ) ) {
			require CIFA_DIRPATH . 'admin/partials/CIFA-marketplaces.php';
		}
	}

	/**
	 * Function to add marketplace Menu.
	 *
	 * @param array $menus menus list array.
	 * @return array
	 */
	public function CIFA_add_marketplace_menus_to_array( $menus = array() ) {
		$menus[] = array(
			'name'            => 'AliExpress',
			'slug'            => 'cedcommerce-integration-for-aliexpress',
			'menu_link'       => 'ced_integration_aliexpress',
			'instance'        => $this,
			'function'        => 'CIFA_configuration_page',
			'card_image_link' => CIFA_URL . 'admin/images/aliexpress.png',
		);
		return $menus;
	}

	/**
	 * Function to include file to the menu array.
	 *
	 * @return void
	 */
	public function CIFA_configuration_page() {
		$step = get_option( 'CIFA_step_completed', 0 );
		$page = ! empty( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		if ( 'ced_integration_aliexpress' === $page && 4 > $step ) {
			include_once CIFA_DIRPATH . 'admin/partials/CIFA-onboarding.php';
		} elseif ( 'ced_integration_aliexpress' === $page && 4 == $step ) {
			include_once CIFA_DIRPATH . 'admin/partials/CIFA-main.php';
		}
	}

	/**
	 * Function to add custom endpoints
	 *
	 * @return void
	 */
	public function CIFA_add_callback_url_endpoint_authorization() {
		// register custom endpoint class.
		require_once CIFA_DIRPATH . 'includes/ced/class-CIFA-rest-api-controller.php';
	}

	/**
	 * WIll register custom rest api namespace to woocommerce.
	 *
	 * @param array $controllers array containing the existsing controllers.
	 * @since 1.0.0
	 * @return array
	 */
	public function CIFA_woocommerce_rest_api_set_rest_namespaces( $controllers ) {
		$controllers['wc/v3']['ced'] = 'CIFA_REST_CIFA_Api_Controller';
		return $controllers;
	}

	/**
	 * Function to change app permission
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function CIFA_change_app_permission() {
		$permissions[] = __( 'Create webhooks', 'cedcommerce-integration-for-aliexpress' );
		$permissions[] = __( 'View and manage orders and sales reports', 'cedcommerce-integration-for-aliexpress' );
		$permissions[] = __( 'View and manage products', 'cedcommerce-integration-for-aliexpress' );
		return $permissions;
	}


	/**
	 * ***********************************************************
	 * CIFA product field table on the simple product level .
	 * ***********************************************************
	 *
	 * @since 2.0.0
	 */
	public function CIFA_add_product_data_custom_tab( $tabs ) {
		$tabs['CIFA_product_tab'] = array(
			'label'  => __( 'AliExpress', 'cedcommerce-integration-for-aliexpress' ),
			'target' => 'CIFA_product_tab',
			'class'  => array( 'show_if_simple', 'show_if_variable' ),
		);
		return $tabs;
	}

	/**
	 * ******************************************************************
	 * ******************************************************************
	 *
	 * @since 2.0.0
	 */
	public function CIFA_product_data_panels() {

		global $post;

		?>
		<div id='CIFA_product_tab' class='panel woocommerce_options_panel'>
			<div class='options_group'>
				<form>
					<?php wp_nonce_field( 'ced_product_settings', 'ced_product_settings_submit' ); ?>
				</form>
				<?php
				echo "<div class='CIFA_simple_product_level_wrap'>";
				echo "<div class='' style='display:flex;align-items: baseline;'>";
				echo "<h2 class='aliexpress-cool'>AliExpress Product Data";
				echo '</h2>';
				echo '<p>( This data will be used to send product details on aliexpress )</p>';
				echo '</div>';
				echo "<div class='CIFA_simple_product_content' style='max-height: 350px;min-height: 350px;
			overflow: scroll;'>";
				$this->CIFA_render_fields( $post->ID, true );
				echo '</div>';
				echo '</div>';
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * ******************************************************************
	 * ******************************************************************
	 *
	 * @since 2.0.0
	 */
	public function CIFA_render_product_fields( $loop, $variation_data, $variation ) {
		if ( ! empty( $variation_data ) ) {
			?>
			<div id='CIFA_product_tab_variable' class='panel woocommerce_options_panel'>
				<div class='options_group'>
					<form>
						<?php wp_nonce_field( 'ced_product_settings', 'ced_product_settings_submit' ); ?>
					</form>
					<?php
					echo "<div class='CIFA_variation_product_level_wrap'>";
					echo "<div class='CIFA_parent_element' style='display:flex;align-items: baseline;'>";
					echo "<h2 class='aliexpress-cool'> AliExpress Product Data";
					echo "<span class='dashicons dashicons-arrow-down-alt2 CIFA_instruction_icon'></span>";
					echo '</h2>';
					echo '<p>( This data will be used to send product details on aliexpress )</p>';
					echo '</div>';
					echo "<div class='CIFA_variation_product_content CIFA_child_element'>";
					$this->CIFA_render_fields( $variation->ID, false );
					echo '</div>';
					echo '</div>';
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * *****************************************************************
	 * *****************************************************************
	 *
	 * @since 2.0.0
	 */
	public function CIFA_save_product_fields_variation( $post_id = '', $i = '' ) {

		if ( empty( $post_id ) ) {
			return;
		}
		$postData = ! empty( $_POST ) ? filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
		if ( ! isset( $postData['ced_product_settings_submit'] ) || ! wp_verify_nonce( wp_unslash( $postData['ced_product_settings_submit'] ), 'ced_product_settings' ) ) {
			return;
		}

		if ( isset( $postData['ced_connector_barcode'] ) && is_array( $postData['ced_connector_barcode'] ) ) {
			foreach ( map_deep( $postData['ced_connector_barcode'], 'sanitize_text_field' ) as $m_id => $m_val ) {
				update_post_meta( $m_id, 'ced_connector_barcode', $m_val );
			}
		}
		$prod           = wc_get_product( $post_id );
		$parent_prod_id = $prod->get_parent_id();
		if ( 0 != $parent_prod_id ) {
			$post_id = $parent_prod_id;
		}

		if ( ! empty( $postData ) ) {
			$headers   = $this->commonCallback->get_common_header();
			$user_data = json_decode( get_option( 'CIFA_user_data' ), ARRAY_A );
			$data      = array();
			$count     = 1;
			if ( ! empty( $postData['CIFA_data'] ) ) {
				foreach ( $postData['CIFA_data'] as $id => $value ) {
					if ( $id == $post_id ) {
						$prod = wc_get_product( $post_id );
						if ( 'variable' == $prod->get_type() ) {
							update_post_meta(
								$post_id,
								'data_sync_enable',
								! empty( $value['data_sync_enable'] ) ? $value['data_sync_enable'] : 'no'
							);
							update_post_meta(
								$post_id,
								'ced_title',
								! empty( $value['ced_title'] ) ? $value['ced_title'] : 'no'
							);
							update_post_meta(
								$post_id,
								'ced_description',
								! empty( $value['ced_description'] ) ? $value['ced_description'] : 'no'
							);

							update_post_meta(
								$post_id,
								'ced_regular_price',
								! empty( $value['ced_regular_price'] ) ? $value['ced_regular_price'] : 'no'
							);

							update_post_meta(
								$post_id,
								'ced_sale_price',
								! empty( $value['ced_sale_price'] ) ? $value['ced_sale_price'] : 'no'
							);

							$data[0]['unset'] = array();
							foreach ( $value as $meta_key => $meta_val ) {
								update_post_meta( $id, $meta_key, $meta_val );
								$attribute_sync_global = false;
								if ( 'yes' == get_post_meta( $post_id, 'data_sync_enable', true ) ) {
									$attribute_sync_global = true;
								}

								if ( ! $attribute_sync_global ) {

									if ( 'ced_connector_sales_price' == $meta_key ) {
										if ( 'yes' == get_post_meta( $post_id, 'ced_sale_price', true ) ) {
											$data[0]['attribute_sync']['compare_at_price'] = true;
										} else {
											$data[0]['attribute_sync']['compare_at_price'] = false;
										}
									}
								}

								if ( 'ced_connector_product_name' == $meta_key ) {
									if ( ! empty( $meta_val ) ) {
										if ( ! $attribute_sync_global ) {
											if ( 'yes' == get_post_meta( $post_id, 'ced_title', true ) ) {
												$data[0]['attribute_sync']['title'] = true;
											} else {
												$data[0]['attribute_sync']['title'] = false;
											}
										}
										$data[0]['sync_setting']['title'] = true;
										$data[0]['title']                 = $meta_val;
									} else {
										if ( ! $attribute_sync_global ) {
											if ( 'yes' == get_post_meta( $post_id, 'ced_title', true ) ) {
												$data[0]['attribute_sync']['title'] = true;
											} else {
												$data[0]['attribute_sync']['title'] = false;
											}
										}
										$data[0]['sync_setting']['title'] = false;
										$data[0]['unset']['title']        = true;
									}
								} elseif ( 'ced_connector_description' == $meta_key ) {
									if ( ! empty( $meta_val ) ) {
										if ( ! $attribute_sync_global ) {
											if ( 'yes' == get_post_meta( $post_id, 'ced_description', true ) ) {
												$data[0]['attribute_sync']['description'] = true;
											} else {
												$data[0]['attribute_sync']['description'] = false;
											}
										}
										$data[0]['sync_setting']['description'] = true;
										$data[0]['description']                 = $meta_val;
									} else {
										if ( ! $attribute_sync_global ) {
											if ( 'yes' == get_post_meta( $post_id, 'ced_description', true ) ) {
												$data[0]['attribute_sync']['description'] = true;
											} else {
												$data[0]['attribute_sync']['description'] = false;
											}
										}
										$data[0]['sync_setting']['description'] = false;
										$data[0]['unset']['description']        = true;
									}
								}
							}
							$data[0]['container_id']          = (string) $post_id;
							$data[0]['target_marketplace']    = (string) $user_data['target_name'];
							$data[0]['source_marketplace']    = (string) $user_data['source_name'];
							$data[0]['shop_id']               = (string) $user_data['target_shop_id'];
							$data[0]['source_product_id']     = (string) $post_id;
							$data[0]['source_shop_id']        = (string) $user_data['source_shop_id'];
							$data[0]['user_id']               = (string) $user_data['user_id'];
							$data[0]['attribute_sync_global'] = $attribute_sync_global;
						}
					} else {
						$prod = wc_get_product( $id );
						if ( 'variation' == $prod->get_type() ) {

							update_post_meta(
								$post_id,
								'ced_regular_price',
								! empty( $value['ced_regular_price'] ) ? $value['ced_regular_price'] : 'no'
							);

							update_post_meta(
								$post_id,
								'ced_sale_price',
								! empty( $value['ced_sale_price'] ) ? $value['ced_sale_price'] : 'no'
							);
							$data[ $count ]['container_id']       = (string) $post_id;
							$data[ $count ]['target_marketplace'] = (string) $user_data['target_name'];
							$data[ $count ]['source_marketplace'] = (string) $user_data['source_name'];
							$data[ $count ]['shop_id']            = (string) $user_data['target_shop_id'];
							$data[ $count ]['source_product_id']  = (string) $id;
							$data[ $count ]['source_shop_id']     = (string) $user_data['source_shop_id'];
							$data[ $count ]['user_id']            = (string) $user_data['user_id'];
							foreach ( $value as $meta_key => $meta_val ) {
								update_post_meta( $id, $meta_key, $meta_val );
								$attribute_sync_global = false;
								if ( 'yes' == get_post_meta( $id, 'data_sync_enable', true ) ) {
									$attribute_sync_global = true;
								}

								if ( 'ced_connector_regular_price' == $meta_key ) {
									if ( ! empty( $meta_val ) ) {
										$data[ $count ]['sync_setting']['price'] = true;
										$data[ $count ]['price']                 = (int) $meta_val;
									} else {
										$data[ $count ]['sync_setting']['price'] = false;
									}
								} elseif ( 'ced_connector_sales_price' == $meta_key ) {
									if ( ! empty( $meta_val ) ) {
										$data[ $count ]['sync_setting']['compare_at_price'] = true;
										$data[ $count ]['compare_at_price']                 = (int) $meta_val;
									} else {
										$data[ $count ]['sync_setting']['compare_at_price'] = false;
									}
								} elseif ( 'ced_connector_weight' == $meta_key ) {
									if ( ! empty( $meta_val ) ) {
										$data[ $count ]['sync_setting']['weight'] = true;
										$data[ $count ]['weight']                 = $meta_val;
									} else {
										$data[ $count ]['sync_setting']['weight'] = false;
									}
								}
							}
						}
						++$count;
					}
				}
			}
			ksort( $data );
			if ( ! empty( $data ) ) {
				$args     = array(
					'headers'   => $headers,
					'sslverify' => false,
					'body'      => wp_json_encode(
						(object) $data
					),
				);
				$endpoint = 'connector/product/saveProduct';

				$this->apiRequest->post( $endpoint, $args );
			}
		}
	}

	/**
	 * **************************************************************
	 * CIFA save meta data for simple product
	 *
	 * @since 1.0.0
	 */
	public function CIFA_save_data_on_simple_product( $post_id = '' ) {

		if ( empty( $post_id ) ) {
			return;
		}
		$postData = ! empty( $_POST ) ? filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
		if ( ! isset( $postData['ced_product_settings_submit'] ) || ! wp_verify_nonce(
			wp_unslash( $postData['ced_product_settings_submit'] ),
			'ced_product_settings'
		) ) {
			return;
		}

		if ( isset( $postData['ced_connector_barcode'] ) ) {
			if ( ! is_array( $postData['ced_connector_barcode'] ) ) {
				update_post_meta( $post_id, 'ced_connector_barcode', sanitize_text_field( $postData['ced_connector_barcode'] ) );
			}
		}

		if ( ! empty( $postData ) ) {
			$data                  = array();
			$title_condition       = false;
			$price_condition       = false;
			$weight_condition      = false;
			$description_condition = false;
			$sale_price_condition  = false;
			$attribute_sync_global = false;
			$attribute_sync        = array();

			if ( ! empty( $postData['CIFA_data'] ) ) {
				foreach ( $postData['CIFA_data'] as $id => $value ) {
					if ( $id != $post_id ) {
						continue;
					}
					$prod = wc_get_product( $post_id );
					if ( 'simple' == $prod->get_type() ) {
						if ( isset( $value['data_sync_enable'] ) && ! empty( $value['data_sync_enable'] ) ) {
							update_post_meta( $post_id, 'data_sync_enable', $value['data_sync_enable'] );
						} else {
							update_post_meta( $post_id, 'data_sync_enable', 'no' );
						}
						if ( isset( $value['ced_title'] ) && ! empty( $value['ced_title'] ) ) {
							update_post_meta( $post_id, 'ced_title', $value['ced_title'] );
						} else {
							update_post_meta( $post_id, 'ced_title', 'no' );
						}
						if ( isset( $value['ced_description'] ) && ! empty( $value['ced_description'] ) ) {
							update_post_meta( $post_id, 'ced_description', $value['ced_description'] );
						} else {
							update_post_meta( $post_id, 'ced_description', 'no' );
						}
						if ( isset( $value['ced_regular_price'] ) && ! empty( $value['ced_regular_price'] ) ) {
							update_post_meta( $post_id, 'ced_regular_price', $value['ced_regular_price'] );
						} else {
							update_post_meta( $post_id, 'ced_regular_price', 'no' );
						}
						if ( isset( $value['ced_sale_price'] ) && ! empty( $value['ced_sale_price'] ) ) {
							update_post_meta( $post_id, 'ced_sale_price', $value['ced_sale_price'] );
						} else {
							update_post_meta( $post_id, 'ced_sale_price', 'no' );
						}
						if ( isset( $value['ced_weight'] ) && ! empty( $value['ced_weight'] ) ) {
							update_post_meta( $post_id, 'ced_weight', $value['ced_weight'] );
						} else {
							update_post_meta( $post_id, 'ced_weight', 'no' );
						}
						$unset = array();
						foreach ( $value as $meta_key => $meta_val ) {
							update_post_meta( $id, $meta_key, $meta_val );
							if ( 'yes' == get_post_meta( $post_id, 'data_sync_enable', true ) ) {
								$attribute_sync_global = true;
							}

							if ( 'ced_connector_product_name' == $meta_key ) {
								if ( ! empty( $meta_val ) ) {
									$title_condition = true;
									$data['title']   = $meta_val;
								} else {
									$title_condition = false;
									$unset['title']  = true;
								}
								if ( ! $attribute_sync_global ) {
									if ( 'yes' == get_post_meta( $post_id, 'ced_title', true ) ) {
										$attribute_sync['title'] = true;
									} else {
										$attribute_sync['title'] = false;
									}
								}
							} elseif ( 'ced_connector_regular_price' == $meta_key ) {
								if ( ! empty( $meta_val ) ) {
									$price_condition       = true;
									$data['regular_price'] = $meta_val;
								} else {
									$price_condition = false;
									$unset['price']  = true;
								}
							} elseif ( 'ced_connector_sales_price' == $meta_key ) {
								if ( ! empty( $meta_val ) ) {
									$sale_price_condition = true;
									$data['sale_price']   = $meta_val;
								} else {
									$sale_price_condition      = false;
									$unset['compare_at_price'] = true;
								}
								if ( ! $attribute_sync_global ) {
									if ( 'yes' == get_post_meta( $post_id, 'ced_sale_price', true ) ) {
										$attribute_sync['compare_at_price'] = true;
									} else {
										$attribute_sync['compare_at_price'] = false;
									}
								}
							} elseif ( 'ced_connector_weight' == $meta_key ) {
								if ( ! empty( $meta_val ) ) {
									$weight_condition = true;
									$data['weight']   = $meta_val;
								} else {
									$weight_condition = false;
								}
							} elseif ( 'ced_connector_description' == $meta_key ) {
								if ( ! empty( $meta_val ) ) {
									$description_condition = true;
									$data['description']   = $meta_val;
								} else {
									$description_condition = false;
									$unset['description']  = true;
								}
								if ( ! $attribute_sync_global ) {
									if ( 'yes' == get_post_meta( $post_id, 'ced_description', true ) ) {
										$attribute_sync['description'] = true;
									} else {
										$attribute_sync['description'] = false;
									}
								}
							}
						}
					}
				}
			}
			$headers   = $this->commonCallback->get_common_header();
			$user_data = json_decode( get_option( 'CIFA_user_data' ), ARRAY_A );
			if ( ! empty( $data ) ) {
				$new_data = array(
					'container_id'          => (string) $post_id,
					'target_marketplace'    => (string) $user_data['target_name'],
					'shop_id'               => (string) $user_data['target_shop_id'],
					'source_product_id'     => (string) $post_id,
					'source_shop_id'        => (string) $user_data['source_shop_id'],
					'user_id'               => (string) $user_data['user_id'],
					'sync_setting'          => array(
						'title'            => $title_condition,
						'description'      => $description_condition,
						'weight'           => $weight_condition,
						'price'            => $price_condition,
						'compare_at_price' => $sale_price_condition,
					),
					'attribute_sync_global' => $attribute_sync_global,
					'unset'                 => (object) $unset,
				);
				if ( ! empty( $data['sale_price'] ) && empty( $new_data['sale_price'] ) ) {
					$new_data['compare_at_price'] = (int) $data['sale_price'];
				}
				if ( ! empty( $data['description'] ) && empty( $new_data['description'] ) ) {
					$new_data['description'] = '<p>' . $data['description'] . '</p>';
				}
				if ( ! empty( $data['title'] ) && empty( $new_data['title'] ) ) {
					$new_data['title'] = $data['title'];
				}
				if ( ! empty( $data['price'] ) && empty( $new_data['price'] ) ) {
					$new_data['price'] = (int) $data['regular_price'];
				}
				if ( ! empty( $data['weight'] ) && empty( $new_data['weight'] ) ) {
					$new_data['weight'] = $data['weight'];
				}
				if ( ! $attribute_sync_global ) {
					$new_data['attribute_sync'] = $attribute_sync;
				}

				$args = array(
					'headers'   => $headers,
					'sslverify' => false,
					'body'      => wp_json_encode(
						(object) array( $new_data )
					),
				);

				$endpoint = 'connector/product/saveProduct';
				$response = $this->apiRequest->post( $endpoint, $args );
			}
		}
	}

	/**
	 * ********************************************************
	 * CREATE FIELDS AT EACH VARIATIONS LEVEL FOR ENTER PRICE
	 * ********************************************************
	 *
	 * @since 2.0.0
	 */
	public function CIFA_render_fields( $product_id = '', $simple_product = '' ) {
		$prod           = wc_get_product( $product_id );
		$variation      = 'variation' == $prod->get_type() ? true : false;
		$main_product   = 'variable' == $prod->get_type() ? true : false;
		$product_fields = array(
			array(
				'type'   => '_checkbox_input',
				'id'     => 'data_sync_enable',
				'fields' => array(
					'id'          => 'data_sync_enable',
					'label'       => __( 'Data Sync with AliExpress', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'Enable this to sync data with AliExpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'checkbox',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',

				),
			),
			array(
				'type'   => '_checkbox_input',
				'id'     => 'ced_title',
				'fields' => array(
					'id'          => 'ced_title',
					'label'       => __( 'Title', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'Enable this to sync Title to AliExpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'checkbox',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',

				),
			),
			array(
				'type'   => '_checkbox_input',
				'id'     => 'ced_description',
				'fields' => array(
					'id'          => 'ced_description',
					'label'       => __( 'Description', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'Enable this to sync Description to AliExpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'checkbox',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',

				),
			),
			array(
				'type'   => '_checkbox_input',
				'id'     => 'ced_regular_price',
				'fields' => array(
					'id'          => 'ced_regular_price',
					'label'       => __( 'Regular Price', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'Enable this to sync Regular Price to AliExpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'checkbox',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',

				),
			),
			array(
				'type'   => '_checkbox_input',
				'id'     => 'ced_sale_price',
				'fields' => array(
					'id'          => 'ced_sale_price',
					'label'       => __( 'Sale Price', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'Enable this to sync Sale Price to AliExpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'checkbox',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',

				),
			),
			array(
				'type'   => '_text_input',
				'id'     => 'ced_connector_product_name',
				'fields' => array(
					'id'          => 'ced_connector_product_name',
					'label'       => __( 'Product name', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'product name to be sent on aliexpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'text',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',
				),
			),
			array(
				'type'   => '_text_input',
				'id'     => 'ced_connector_regular_price',
				'fields' => array(
					'id'          => 'ced_connector_regular_price',
					'label'       => __( 'Regular Price', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'Product Regular Price to be sent on aliexpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'text',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',
				),
			),
			array(
				'type'   => '_text_input',
				'id'     => 'ced_connector_sales_price',
				'fields' => array(
					'id'          => 'ced_connector_sales_price',
					'label'       => __( 'Sales Price', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'Product Sales Price to be sent on aliexpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'text',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',
				),
			),
			array(
				'type'   => '_text_input',
				'id'     => 'ced_connector_weight',
				'fields' => array(
					'id'          => 'ced_connector_weight',
					'label'       => __( 'Weight', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'Product Weight to be sent on aliexpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'text',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',
				),
			),
			array(
				'type'   => '_textarea_input',
				'id'     => 'ced_connector_description',
				'fields' => array(
					'id'          => 'ced_connector_description',
					'label'       => __( 'Description', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'Product Description to be sent on aliexpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'text',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',
				),
			),
			array(
				'type'   => '_text_input',
				'id'     => 'ced_connector_barcode',
				'fields' => array(
					'id'          => 'ced_connector_barcode',
					'label'       => __( 'Barcode', 'cedcommerce-integration-for-aliexpress' ),
					'desc_tip'    => true,
					'description' => __( 'Barcode to be sent on aliexpress', 'cedcommerce-integration-for-aliexpress' ),
					'type'        => 'number',
					'is_required' => false,
					'class'       => 'wc_input_price',
					'default'     => '',

				),
			),
		);

		if ( ! empty( $product_fields ) ) {
			$number_fields = array(
				'ced_connector_sales_price',
				'ced_connector_regular_price',
				'ced_connector_barcode',
				'ced_connector_weight',
				'ced_connector_barcode',
			);

			foreach ( $product_fields as $key => $value ) {
				$variation_fields = array( 'ced_connector_sales_price', 'ced_connector_regular_price', 'ced_connector_weight' );
				if ( ( $variation && ! in_array( $value['fields']['id'], $variation_fields ) ) ||
					$main_product && in_array( $value['fields']['id'], $variation_fields )
				) {
					continue;
				}

				$label    = isset( $value['fields']['label'] ) ? $value['fields']['label'] : '';
				$field_id = isset( $value['fields']['id'] ) ? $value['fields']['id'] : '';

				$id             = 'CIFA_data[' . $product_id . '][' . $field_id . ']';
				$selected_value = get_post_meta( $product_id, $field_id, true );

				if ( '_select' == $value['type'] ) {
					$option_array     = array();
					$option_array[''] = '--select--';
					foreach ( $value['fields']['options'] as $option_key => $option ) {
						$option_array[ $option_key ] = $option;
					}
					woocommerce_wp_select(
						array(
							'id'          => $id,
							'label'       => $value['fields']['label'],
							'options'     => $option_array,
							'value'       => $selected_value,
							'desc_tip'    => 'true',
							'description' => $value['fields']['description'],
							'class'       => 'CIFA_product_select',
						)
					);
				} elseif ( '_text_input' == $value['type'] ) {
					woocommerce_wp_text_input(
						array(
							'id'          => $id,
							'label'       => $value['fields']['label'],
							'desc_tip'    => 'true',
							'description' => $value['fields']['description'],
							'type'        => 'text',
							'value'       => $selected_value,
							'class'       => ( ! empty( $value['id'] ) && in_array( $value['id'], $number_fields ) ? ' CIFA-number-input' : '' ),
						)
					);
				} elseif ( '_number_input' == $value['type'] ) {
					woocommerce_wp_text_input(
						array(
							'id'          => $id,
							'label'       => $value['fields']['label'],
							'desc_tip'    => 'true',
							'description' => $value['fields']['description'],
							'type'        => 'number',
							'value'       => $selected_value,
							'min'         => 0,
						)
					);
				} elseif ( '_textarea_input' == $value['type'] ) {
					woocommerce_wp_textarea_input(
						array(
							'id'          => $id,
							'label'       => $value['fields']['label'],
							'desc_tip'    => 'true',
							'description' => $value['fields']['description'],
							'type'        => 'textarea',
							'value'       => $selected_value,
							'class'       => '',
						)
					);
				} elseif ( '_checkbox_input' == $value['type'] ) {
					woocommerce_wp_checkbox(
						array(
							'id'          => $id,
							'label'       => $value['fields']['label'],
							'desc_tip'    => 'true',
							'description' => $value['fields']['description'],
							'type'        => 'text',
							'value'       => $selected_value,
							'class'       => ( ! empty( $value['id'] ) ? $value['id'] : '' ) .
								( ! empty( $value['id'] ) && 'data_sync_enable' == $value['id'] ? '' : ' product_edit_fields' ),
						)
					);
				}
			}
		}
	}


	/**
	 * Function to add custom fields at variation product level
	 *
	 * @param string $loop an iteration count of a variation.
	 * @param array  $variation_data  an array with all variation fields settings.
	 * @param object $variation object of this specific variation.
	 * @return void
	 */
	public function CIFA_add_custom_field_to_variations( $loop, $variation_data, $variation ) {
		$connector_barcode = get_post_meta( $variation->ID, 'ced_connector_barcode', true );
		?>
		<p class="CIFA-form-field variable_regular_price_0_field form-row form-row-first">
			<label for="ced_connector_barcode"> <?php esc_html_e( 'Ced Connector Barcode', 'cedcommerce-integration-for-aliexpress' ); ?> : </label><input type="number" min="0" class="short " name="ced_connector_barcode[<?php echo esc_attr( $variation->ID ); ?>]" id="ced_connector_barcode" value="<?php echo esc_attr( $connector_barcode ); ?>" placeholder="<?php esc_html_e( 'Enter Barcode', 'cedcommerce-integration-for-aliexpress' ); ?>"></input>
		</p>
		<?php
		wp_nonce_field( 'CIFA_product_nonce', 'CIFA_product_nonce' );
	}
	/**
	 * Check if HPOS is enabled
	 *
	 * @return void
	 */
	public function CIFA_is_hpos_order_enable() {
		return method_exists( CustomOrdersTableController::class, 'custom_orders_table_usage_is_enabled' )
			&& OrderUtil::custom_orders_table_usage_is_enabled();
	}
	/**
	 * Function to add order metabox.
	 *
	 * @return void
	 */
	public function CIFA_add_order_metabox( $post_id, $post ) {
		$order       = ( $post instanceof WP_Post ) ? wc_get_order( $post->ID ) : $post;
		$screen      = $this->CIFA_is_hpos_order_enable() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		$marketplace = 'shop_order' == $screen ? get_post_meta( $post->ID, '_ced_marketplace', true ) : $order->get_meta( '_ced_marketplace' );
		if ( ( 'aliexpress' === $marketplace ) ) {
			add_meta_box(
				'CIFA_manage_orders_metabox',
				__( 'Manage AliExpress Orders', 'cedcommerce-integration-for-aliexpress' ) . wc_help_tip( esc_attr( __( 'Please save tracking information of order.', 'cedcommerce-integration-for-aliexpress' ) ) ),
				array( $this, 'CIFA_render_order_metabox' ),
				$screen,
				'advanced',
				'high'
			);
		}
	}

	/**
	 *  Function CIFA_render_order_metabox.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function CIFA_render_order_metabox( $post ) {
		$order_details = ( $post instanceof WP_Post ) ? wc_get_order( $post->ID ) : $post;
		if ( ! empty( $order_details ) ) {
			$order_id = $order_details->get_id();
			if ( ! is_null( $order_id ) ) {
				if ( $this->CIFA_is_hpos_order_enable() ) {
					$tracking_company = $order_details->get_meta( '_trackingCompany' );
					$tracking_number  = $order_details->get_meta( '_trackingNumber' );
					$tracking_errors  = $order_details->get_meta( 'tracking_errors' );
					$marketplace      = $order_details->get_meta( '_ced_marketplace' );
				} else {
					$tracking_company = get_post_meta( $order_id, '_trackingCompany', true );
					$tracking_number  = get_post_meta( $order_id, '_trackingNumber', true );
					$tracking_errors  = get_post_meta( $order_id, 'tracking_errors', true );
					$marketplace      = get_post_meta( $order_id, '_ced_marketplace', true );
				}
				if ( 'aliexpress' === $marketplace ) {
					$tracking_company = ! empty( $tracking_company ) ? $tracking_company : '';
					$tracking_number  = ! empty( $tracking_number ) ? $tracking_number : '';
					$provider         = get_option( 'CIFA_shipping_carriers' );
					?>
					<table class="ced_bol_submit_shipment">
						<?php
						if ( ! empty( $tracking_errors ) ) {
							echo '<tr><td>There are following errors while saving the tracking details :<ul style="list-style:square">';
							foreach ( $tracking_errors as $k => $v ) {
								echo '<li><span style="color:red;">' . esc_html( $v ) . '</span></li>';
							}
							echo '</ul></td></tr>';
						}
						?>
						<?php wp_nonce_field( 'CIFA_order_nonce', 'CIFA_order_nonce' ); ?>
						<tr>
							<td style="color:#444 !important; padding-bottom: 10px;"> <?php esc_html_e( 'Shipping Provider', 'cedcommerce-integration-for-aliexpress' ); ?></td>
							<td style="color:#444 !important; padding-bottom: 10px;">
								<select name="trackingCompany" required="required" style="width: 100%;">
									<option value=""><?php esc_html_e( 'Select', 'cedcommerce-integration-for-aliexpress' ); ?></option>
									<?php
									foreach ( $provider as $key => $value ) {
										if ( (string) $key === (string) $tracking_company ) {
											$ship_class = 'selected';
										} else {
											$ship_class = '';
										}
										echo "<option value='" . esc_attr( $key ) . "' " . esc_attr( $ship_class ) . '>' . esc_attr( $value ) . '</option>';
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td style="color:#444 !important"><?php esc_html_e( 'Tracking Number', 'cedcommerce-integration-for-aliexpress' ); ?></td>
							<td style="color:#444 !important"><input type='text' style="width: 100%;" name='trackingNumber' id='trackingNumber' required="required" value='<?php echo esc_html( $tracking_number ); ?>'></td>
						</tr>
					</table>
					<?php
				}
			}
		}
	}
	/**
	 * Will save order meta data.
	 *
	 * @param string $post_id current order ID.
	 * @param object $post current post object.
	 * @since 1.0.0
	 * @return void
	 */
	public function CIFA_save_metadata( $post_id, $post ) {
		if ( ! $post_id || ! isset( $post->post_type ) || 'shop_order' !== $post->post_type ) {
			return;
		}
		$postData = ! empty( $_POST ) ? filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
		if ( ! empty( $postData['CIFA_order_nonce'] ) ) {
			$CIFA_order_nonce = wp_unslash( $postData['CIFA_order_nonce'] );
			if ( wp_verify_nonce( $CIFA_order_nonce, 'CIFA_order_nonce' ) && ! $this->CIFA_is_hpos_order_enable() ) {
				if ( $post_id ) {
					if ( ! empty( $postData['trackingCompany'] ) && ! empty( $postData['trackingNumber'] ) ) {
						if ( ! empty( $postData['trackingCompany'] ) ) {
							update_post_meta( $post_id, '_trackingCompany', wp_unslash( $postData['trackingCompany'] ) );
						}
						if ( ! empty( $postData['trackingNumber'] ) ) {
							update_post_meta( $post_id, '_trackingNumber', wp_unslash( $postData['trackingNumber'] ) );
						}
						delete_post_meta( $post_id, 'tracking_errors' );
					} else {
						$errors = array();
						if ( isset( $postData['_ced_marketplace'] ) && 'aliexpress' === $postData['_ced_marketplace'] ) {
							if ( empty( $postData['trackingCompany'] ) ) {
								$errors[] = 'Carrier Name Can not be empty.';
							}
							if ( empty( $postData['trackingNumber'] ) ) {
								$errors[] = 'Tracking Number Can not be empty.';
							}
							update_post_meta( $post_id, 'tracking_errors', $errors );
						}
					}
				}
			}
		}
	}

	/**
	 * Will save order meta data in case of HPOS.
	 *
	 * @param string $post_id current order ID.
	 * @param object $post current post object.
	 * @since 1.0.0
	 * @return void
	 */
	public function CIFA_save_metadata_hpos( $post_id, $post ) {

		if ( ! $post_id ) {
			return;
		}
		$order_details = ( $post instanceof WP_Post ) ? wc_get_order( $post_id ) : $post;
		if ( ! $order_details ) {
			return;
		}
		if ( ! empty( $_POST['CIFA_order_nonce'] ) ) {
			$CIFA_order_nonce = sanitize_text_field( wp_unslash( $_POST['CIFA_order_nonce'] ) );
			if ( wp_verify_nonce( $CIFA_order_nonce, 'CIFA_order_nonce' ) && $this->CIFA_is_hpos_order_enable() ) {
				if ( ! empty( $_POST['trackingCompany'] ) ) {
					$order_details->update_meta_data( '_trackingCompany', sanitize_text_field( wp_unslash( $_POST['trackingCompany'] ) ) );
				}
				if ( ! empty( $_POST['trackingNumber'] ) ) {
					$order_details->update_meta_data( '_trackingNumber', sanitize_text_field( wp_unslash( $_POST['trackingNumber'] ) ) );
				}
			}
		}
	}


	/**
	 * Function to add column in order section woocommerce
	 *
	 * @param array $columns woocommerce order section columns.
	 * @return array
	 */
	public function CIFA_add_column_order_section( $columns ) {
		$reordered_columns = array();

		// Inserting columns to a specific location.
		foreach ( $columns as $key => $column ) {
			$reordered_columns[ $key ] = $column;
			if ( 'order_status' === $key ) {
				$reordered_columns['order_type']  = __( 'Order Type', 'cedcommerce-integration-for-aliexpress' );
				$reordered_columns['marketplace'] = __( 'Marketplace', 'cedcommerce-integration-for-aliexpress' );
			}
		}
		return $reordered_columns;
	}

	/**
	 * Function to show values in order scetion custom columns
	 *
	 * @param string $column column name.
	 * @param string $post_id post id.
	 * @return void
	 */
	public function CIFA_column_order_section_callback( $column, $post_id ) {
		if ( 'marketplace' == $column ) {
			// Get custom post meta data.
			if ( $this->CIFA_is_hpos_order_enable() ) {
				$order_details = ( $post_id instanceof WP_Post ) ? wc_get_order( $post_id->ID ) : $post_id;
				$marketplace   = $order_details->get_meta( '_ced_marketplace' );
			} else {
				$marketplace = get_post_meta( $post_id, '_ced_marketplace', true );
			}
			if ( $marketplace && 'aliexpress' === $marketplace ) {
				echo esc_html( 'AliExpress' );
			} else {
				echo '-';
			}
		}
	}

	public function CIFA_connect_account() {

		$check_ajax = check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' );

		if (
			$check_ajax && isset( $_POST['terms_and_conditions'] ) &&
			'true' == sanitize_text_field( $_POST['terms_and_conditions'] )
		) {
			update_option( 'CIFA_terms_and_condition', sanitize_text_field( $_POST['terms_and_conditions'] ) );
			$domain_url   = home_url();
			$redirect_url = admin_url( 'admin.php?page=ced_integration_aliexpress' );
			$callback_url = wp_upload_dir()['baseurl'] . '/CIFA_woo_callback.php';
			$auth_url     = $domain_url . '/wc-auth/v1/authorize?app_name=AliExpress Integration by CedCommerce&scope=read_write&user_id=aliexpress_woo_connection&return_url=' . $redirect_url . '&callback_url=' . $callback_url;
			echo wp_json_encode(
				array(
					'status'   => 200,
					'auth_url' => $auth_url,
				)
			);
			wp_die();
		}
	}

	public function CIFA_manual_connect_account() {

		$check_ajax = check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' );
		$postData   = ! empty( $_POST ) ? filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
		if ( $check_ajax ) {
			$consumer_key    = isset( $postData['consumer_key'] ) ? $postData['consumer_key'] : '';
			$consumer_secret = isset( $postData['consumer_secret'] ) ? $postData['consumer_secret'] : '';

			if ( ! empty( $consumer_key ) && ! empty( $consumer_secret ) ) {

				$data['consumer_key']     = $consumer_key;
				$data['consumer_secret']  = $consumer_secret;
				$data['user_id']          = 'aliexpress_woo_connection';
				$site_url                 = get_site_url() . '/wp-json/wc/v3/products';
				$headers                  = array();
				$headers['Authorization'] = 'Basic ' . base64_encode( $consumer_key . ':' . $consumer_secret );

				$response      = wp_remote_get(
					$site_url,
					array(
						'method'      => 'GET',
						'timeout'     => 45,
						'redirection' => 10,
						'httpversion' => '1.0',
						'sslverify'   => false,
						'headers'     => $headers,
					)
				);
				$response_code = wp_remote_retrieve_response_code( $response );
				$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( ! isset( $response_data['message'] ) && ! empty( $response_data ) && is_array( $response_data ) ) {
					$api_keys = wp_json_encode( $data );
					$file     = wp_upload_dir()['basedir'] . '/CIFA_api_details.txt';
					file_put_contents( $file, $api_keys );
					$auth_url = admin_url( 'admin.php?page=ced_integration_aliexpress&user_id=aliexpress_woo_connection' );
					echo wp_json_encode(
						array(
							'status'   => 200,
							'auth_url' => $auth_url,
						)
					);
					wp_die();
				} else {
					$message = ! empty( $response_data['message'] ) ? $response_data['message'] : 'Auth credentials are not valid';
					echo wp_json_encode(
						array(
							'status'  => 204,
							'message' => $message,
						)
					);
					wp_die();
				}
				$auth_url = admin_url( 'admin.php?page=ced_integration_aliexpress&user_id=aliexpress_woo_connection' );
				echo wp_json_encode(
					array(
						'status'   => 200,
						'auth_url' => $auth_url,
					)
				);
				wp_die();
			} else {
				echo wp_json_encode(
					array(
						'status'  => 204,
						'message' => "Required fields can't be blank ",
					)
				);
				wp_die();
			}
		}
	}

	public function onboarding_step2() {
		$headers           = $this->commonCallback->get_common_header();
		$connector_get_all = $this->commonCallback->conectorgetAll();
		$tok               = json_decode( get_option( 'CIFA_token_data' ) )->token;
		if ( ! empty( $connector_get_all['target_id'] ) ) {
			$tokenParts   = explode( '.', $tok );
			$tokenPayload = base64_decode( $tokenParts[1] );
			$jwtPayload   = json_decode( $tokenPayload );
			$user_id      = $jwtPayload->user_id;

			$data                   = json_decode( get_option( 'CIFA_user_data' ), ARRAY_A );
			$data['target_shop_id'] = $connector_get_all['target_id'];
			update_option( 'CIFA_user_data', wp_json_encode( $data ) );

			$step_completed = $this->commonCallback->getStepCompleted();
			if ( 1 == $step_completed && $this->commonCallback->setStepCompleted( 2 ) ) {

				$this->apiRequest->post(
					'connector/product/import',
					array(
						'headers' => $headers,
					)
				);

				$this->apiRequest->post(
					'woocommercehome/product/categoryImport',
					array(
						'headers' => $headers,
					)
				);

				if ( get_option( 'CIFA_user_data' ) ) {
					$data            = json_decode( get_option( 'CIFA_user_data' ), ARRAY_A );
					$data['user_id'] = $user_id;
					update_option( 'CIFA_user_data', wp_json_encode( $data ) );
				} else {
					$data['user_id'] = $user_id;
					update_option( 'CIFA_user_data', wp_json_encode( $data ) );
				}
				update_option( 'CIFA_step_completed', 2 );
				echo esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress' ) );
			}
		} else {
			return array( 'error_message' => 'There is some technical issue, please contact support.' );
		}
	}
	public function init_process_of_admin() {

		$token_data = get_transient( 'CIFA_token_data' );
		if ( false === $token_data ) {
			$wooconnection_data = get_option( 'CIFA_token_data', '' );

			$admin_data         = wp_get_current_user();
			$admin_data         = (array) $admin_data->data;
			$wooconnection_data = json_decode( $wooconnection_data, true );
			$param              = array();
			$param['username']  = home_url();
			$headers            = array();
			$headers[]          = 'Content-Type: application/json';
			$endpoint           = 'woocommercehome/request/getUserToken';
			$response           = $this->apiRequest->post(
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
			if ( 200 === $response_code && ! empty( $response_data['token_res'] ) ) {
				$data               = array(
					'token' => ! empty( $response_data['token_res'] ) ? $response_data['token_res'] : false,
					'shop'  => ! empty( $wooconnection_data['shop'] ) ? sanitize_text_field( wp_unslash( $wooconnection_data['shop'] ) ) : false,
				);
				$wooconnection_data = wp_json_encode( $data );
				update_option( 'CIFA_token_data', $wooconnection_data );
				set_transient( 'CIFA_token_data', $wooconnection_data, 10800 );
			}
		}
	}

	public function get_values_for_attr_mapping_default_template( $requestData = array() ) {
		if ( ! empty( $requestData ) || check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' ) ) {
			$categoryId        = ! empty( $_POST['category_id'] ) ? sanitize_text_field( $_POST['category_id'] ) : $requestData['category_id'];
			$attributesMapping = ! empty( $requestData['attributes_mapping'] ) ? $requestData['attributes_mapping'] : array();

			$category_id        = sanitize_text_field( $categoryId );
			$headers            = $this->commonCallback->get_common_header();
			$endpoint2          = 'webapi/rest/v1/aliexpress/getAttributeOptions';
			$args2              = array(
				'headers'   => $headers,
				'sslverify' => false,
				'body'      => array(
					'category_id'        => $category_id,
					'marketplace'        => 'aliexpress',
					'source_marketplace' => 'woocommerce',
				),
			);
			$attribute_response = $this->apiRequest->get( $endpoint2, $args2 );
			if ( ! empty( $attribute_response['data']['success'] ) ) {
				$target_attributes = $attribute_response['data']['data'];
			}

			$endpoint3 = 'webapi/rest/v1/woocommerce/category/getAttributesOptions';
			$args3     = array(
				'headers'   => $headers,
				'sslverify' => false,
				'body'      => array(
					'marketplace' => 'woocommerce',
				),
			);

			$woo_attribute_response = $this->apiRequest->get( $endpoint3, $args3 );
			if ( isset( $woo_attribute_response['data'] ) && true == $woo_attribute_response['data']['success'] ) {
				$woo_target_attributes = $woo_attribute_response['data']['data'];
			}
			$html                  = '';
			$count_attr            = 0;
			$count_variation_attr  = 0;
			$selectedVariantValues = array();
			if ( ! empty( $attributesMapping ) ) {
				foreach ( $attributesMapping as $attr ) {
					if ( ! empty( $attr['value'] ) && is_array( $attr['value'] ) ) {
						$selectedVariantValues = array_merge( $selectedVariantValues, $attr['value'] );
					}
				}
			}
			$html1 = '<div class="CIFA-faq-wrapper CIFA-bottom">
			<input class="CIFA-faq-trigger" id="CIFA-faq-wrapper-one" type="checkbox" />
			<label class="CIFA-faq-title" for="CIFA-faq-wrapper-one">Variation Attribute</label>
			<div class="CIFA-faq-content-wrap">
				<div class="CIFA-faq-content-holder">
					<div class="CIFA-form-accordian-wrap">
						<div class="">
							<table class="form-table CIFA-form-wrap"><tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_currency">
					AliExpress Attributes 
					</label>
				</th>
				<th scope="row" class="titledesc">
					<label for="woocommerce_currency">
						Mapping Attributes
					</label>
				</th>
			</tr>';
			$html2 = '<div class="CIFA-faq-wrapper CIFA-bottom">
			<input class="CIFA-faq-trigger" id="faq-wrapper-two" type="checkbox" />
				<label class="CIFA-faq-title" for="faq-wrapper-two">Product Attribute</label>
			<div class="CIFA-faq-content-wrap">
				<div class="CIFA-faq-content-holder">
					<div class="CIFA-form-accordian-wrap">
						<div class="">
							<table class="form-table CIFA-form-wrap"><tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="woocommerce_currency">
					AliExpress Attributes 
					</label>
				</th>
				<th scope="row" class="titledesc">
					<label for="woocommerce_currency">
						Mapping Attributes
					</label>
				</th>
			</tr>';
			if ( ! empty( $target_attributes ) ) {
				foreach ( $target_attributes as $key => $value ) {
					$attribute = ! empty( $attributesMapping[ $value['id'] ] ) ? $attributesMapping[ $value['id'] ] : array();

					if ( ! empty( $value['sku'] ) ) {
						++$count_variation_attr;
						$html1 .= '<tr><td scope="row" class="titledesc"><label for="billing_first_name" class="">'
							. $value['name'] . '&nbsp;';
						if ( ! empty( $value['required'] ) ) {
							$html1 .= '<abbr class="required attribute_required_' . $value['id'] . '" 
							data-attribute-required="' . $value['required'] . '" title="required">*</abbr>';
						}
						$html1 .= '
						<input type="hidden" name="variation_attributes[' . $value['id'] . '][displayName]" 
						value="' . $value['name'] . '"><input type="hidden" name="variation_attributes
						[' . $value['id'] . '][id]" value="' . $value['id'] . '"><input type="hidden" 
						name="variation_attributes[' . $value['id'] . '][customised]" 
						value="' . $value['customized_name'] . '"><input type="hidden" 
						name="variation_attributes[' . $value['id'] . '][required]" 
						value="' . $value['required'] . '"><input type="hidden" 
						name="variation_attributes[' . $value['id'] . '][variation]" 
						value="' . $value['sku'] . '"></label></td><td class="forminp forminp-select">';
						if ( empty( $value['customized_name'] ) ) {
							$html1 .= '
							<select class="CIFA-readonly select_source_target_attr_type variation_attribute attribute_type CIFA-select"
							 name="variation_attributes[' . $value['id'] . '][select_source_target_attr_type]" 
							 data-attribute-id="' . $value['id'] . '" style="width: 100%;" id="bulk-action-selector-top">
							<option value="select_woocommerce_attr" selected>Select WooCommerce Attribute</option>
							</select>';
						} else {
							$html1 .= '
							<select data-attribute-id="' . $value['id'] . '" 
							class="select_source_target_attr_type variation_attribute attribute_type CIFA-select"
							 name="variation_attributes[' . $value['id'] . '][select_source_target_attr_type]" 
							 style="width: 100%;" id="bulk-action-selector-top">
								<option value="select_woocommerce_attr" ' .
								( empty( $attribute['type'] ) || 'attribute' == $attribute['type'] ? 'selected' : '' ) .
								'>Select WooCommerce Attribute</option>
								<option value="select_custom_value" ' .
								( ! empty( $attribute['type'] ) && 'fixed' == $attribute['type'] ? 'selected' : '' ) .
								'>Custom Value</option>
								</select>';
						}
						$html1 .= '<select ' . ( ( empty( $attribute['type'] ) || 'attribute' == $attribute['type'] )
							&& ! empty( $value['required'] ) ? 'required' : '' ) . ' 
							class="listing_of_atttribute_values select_attribute_values_select2 select2-with-checkbox
							 variant_attribute option_value_mapping_select_' . $value['id'] . ' attribute_value_' .
							$value['id'] . ' attribute_woocommerce_' . $value['id'] . '" multiple="multiple" 
							 name="variation_attributes[' . $value['id'] . '][name][source][]" 
							 style="width: 100%; ' . ( ! empty( $attribute['value'] ) && 'attribute' == $attribute['type'] ? '' : 'display:none' ) . '" data-select-id="' . $value['id'] . '" id="bulk-action-selector-top">';
						$html1 .= '<option>--Select--</option>';
						if ( ! empty( $woo_target_attributes ) ) {
							foreach ( $woo_target_attributes as $woo_attr_key => $woo_attr_key_data ) {
								foreach ( $woo_attr_key_data as $attribute_woo_key => $attribute_woo_data ) {
									$html1 .= '<option data-value="' . $attribute_woo_data['value'] . '" 
										value="' . $attribute_woo_data['value'] . '" ' . ( ! empty( $attribute['value'] ) &&
										'attribute' == $attribute['type'] && in_array( $attribute_woo_data['value'], $attribute['value'] ) ? 'selected' : '' ) .
										' ' . ( ! empty( $selectedVariantValues ) && in_array(
											$attribute_woo_data['value'],
											$selectedVariantValues
										) ? 'disabled' : '' ) . '>' . $attribute_woo_data['label'] .
										'</option>';
								}
							}
						}
						$html1 .= '</select>';
						$html1 .= '<input type="text" ' . ( ! empty( $attribute['type'] ) && ! empty( $value['required'] ) &&
							'fixed' == $attribute['type'] ? 'required' : '' ) . ' id="select-' . $value['id'] . '" 
							placeholder="Enter Value" value="' . ( ! empty( $attribute['value'] ) ? ( is_array( $attribute['value'] )
							? implode( ',', $attribute['value'] ) . ',' : $attribute['value'] ) : '' ) . '" class="custom_value_for_attribute attribute_value_'
							. $value['id'] . ' attribute_custom_' . $value['id'] . '" 
							name="variation_attributes[' . $value['id'] . '][value]" style="' . ( ! empty( $attribute['value'] ) &&
								'fixed' == $attribute['type'] ? '' : 'display:none' ) . '">
							<div id="target_select_' . $value['id'] . '" style="display:none"><select> ';
						if ( ! empty( $value['values'] ) ) {
							foreach ( $value['values'] as $attr_key => $attr_key_data ) {
								$html1 .= '<option data-value="' . $attr_key_data['id'] . '" 
								value="' . $attr_key_data['name'] . '">' . $attr_key_data['name'] . '</option>';
							}
						}
						$html1 .= '</select></div>';
						$html1 .= '<a class="option_value_mapping option_value_mapping_' . $value['id'] . '" 
						data-option-id="' . $value['id'] . '" style="' . ( ! empty( $attribute['value'] ) &&
							'attribute' == $attribute['type'] ? '' : 'display:none' ) .
							'" href="javascript:void(0)">Option Value Mapping</a>';
						$html1 .= '<div id="CIFA-myPopup" class="CIFA-modal-wrap CIFA-advance-popup-modal option-value-mapping-form option_form_' . $value['id'] . '">
							<div class="CIFA-content">
								<span class="CIFA-close-button"><span class="dashicons dashicons-no-alt"></span></span>
								<div class="CIFA-title-holder">
									<h3>Attribute Mapping</h3>
								</div>
								<div class="CIFA-popup-content-wrapper">
									<div class="CIFA-popup-content">
										<div class="woocommerce-progress-form-wrapper">
			
											<div class="wc-progress-form-content">
												<header>
													<h3>Your Selected Woocommerce Attributes</h3>
													<p>Kindly map your Woocommerce Attribute with AliExpress Attribute</p>
													<a href="javascript:void(0)" data-category-id="' . $category_id . '" 
													data-attributes="' . ( ! empty( $attribute['value'] ) && is_array( $attribute['value'] ) ?
							implode( ',', $attribute['value'] ) : '' ) . '" 
													data-attribute-id="' . $value['id'] . '" data-attribute-values="' .
							htmlspecialchars( wp_json_encode( $value['values'] ) ) . '" class="button refresh_variant_options">
													<span style="margin-top: 2px;color: #fff;font-size: 21px;margin-left: -2px;" class="dashicons dashicons-update-alt"></span></a>
												</header>
												<header>
													<div class="CIFA-form-field CIFA-filed option_value_body_' . $value['id'] . '">
														' . ( ! empty( $attribute['value'] ) && is_array( $attribute['value'] ) ? $this->getVariantOptions(
														array(
															'category_id' => $category_id,
															'attributes'  => $attribute['value'],
															'values'      => $value['values'],
															'id'          => $value['id'],
														)
													) : '' ) . '
													</div>
												</header>
												<div class="wc-actions">
													<button type="button" name="" id="option_value_submit" 
													class="alignright components-button is-primary">Done</button>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>';
						$html1 .= '</td></tr>';
					} else {

						++$count_attr;
						$html2 .= '<tr><td scope="row" class="titledesc"><label for="billing_first_name" class="">'
							. $value['name'] . '&nbsp;';
						if ( ! empty( $value['required'] ) ) {
							$html2 .= '<abbr class="required attribute_required_' . $value['id'] . '" 
							data-attribute-required="' . $value['required'] . '" title="required">*</abbr>';
						}
						$html2 .= '
						<input type="hidden" name="product_attributes[' . $value['id'] . '][displayName]" 
						value="' . $value['name'] . '"><input type="hidden" name="product_attributes[' . $value['id'] .
							'][id]" value="' . $value['id'] . '"><input type="hidden" name="product_attributes[' .
							$value['id'] . '][customised]" value="' . ( empty( $value['values'] ) ||
								$value['customized_name'] ? true : $value['customized_name'] ) . '"><input type="hidden" 
								name="product_attributes[' . $value['id'] . '][required]" value="' . $value['required'] .
							'"><input type="hidden" name="product_attributes[' . $value['id'] . '][variation]" 
							value="' . $value['sku'] . '"></label></td><td class="forminp forminp-select">';
						if ( empty( $value['customized_name'] ) ) {

							if ( empty( $value['values'] ) ) {
								$html2 .= '<select class="CIFA-readonly select_source_target_attr_type attribute_type CIFA-select" 
								name="product_attributes[' . $value['id'] . '][select_source_target_attr_type]" 
								data-attribute-id="' . $value['id'] . '" style="width: 100%;" id="bulk-action-selector-top">
								<option value="select_custom_value" selected>Select Custom Value</option>
								</select><input ' . ( ! empty( $value['required'] ) ? 'required' : '' ) . ' type="text"  
								id="select-' . $value['id'] . '" placeholder="Enter Value" class="custom_value_for_attribute" 
								value="' . ( ! empty( $attribute['value'] ) ? $attribute['value'] : '' ) . '" 
								name="product_attributes[' . $value['id'] . '][value]">';
							} else {
								$html2 .= '<select class="CIFA-readonly select_source_target_attr_type attribute_type CIFA-select" 
								name="product_attributes[' . $value['id'] . '][select_source_target_attr_type]" 
								data-attribute-id="' . $value['id'] . '" style="width: 100%;" id="bulk-action-selector-top" >
								<option value="select_aliexpress_attr" selected>Select AliExpress Attribute</option>
								</select><select ' . ( ! empty( $value['required'] ) ? 'required' : '' ) . ' 
								class="listing_of_atttribute_values select_attribute_values CIFA-select" 
								name="product_attributes[' . $value['id'] . '][name][target]" style="width: 100%;" 
								data-select-id="' . $value['id'] . '" id="bulk-action-selector-top">
								<option value="">--Select--</option>';
								foreach ( $value['values'] as $attr_key => $attr_key_data ) {
									if ( ! empty( $attr_key_data['name'] ) ) {
										$html2 .= '<option data-value="' . ( empty( $attr_key_data['id'] ) || 0 == $attr_key_data['id'] ? '0' : $attr_key_data['id'] ) . '" value="' . $attr_key_data['name'] .
											'" ' . ( ! empty( $attribute['name'] ) && $attr_key_data['name'] == $attribute['name'] ? 'selected' : '' ) .
											'>' . $attr_key_data['name'] . '</option>';
									}
								}
								$html2 .= '</select><input type="text" id="select-' . $value['id'] . '" 
								placeholder="Enter Value" class="custom_value_for_attribute" 
								name="product_attributes[' . $value['id'] . '][value]" 
								value="' . ( ! empty( $attribute['value'] ) ? $attribute['value'] : '' ) . '" style="display:none">';
							}
						} else {
							if ( empty( $value['values'] ) ) {

								$html2 .= '<select data-attribute-id="' . $value['id'] . '" 
								class="select_source_target_attr_type attribute_type CIFA-select" 
								name="product_attributes[' . $value['id'] . '][select_source_target_attr_type]" 
								style="width: 100%;" id="bulk-action-selector-top">
								<option value="select_woocommerce_attr" ' . ( empty( $attribute['type'] ) || 'attribute' == $attribute['type'] ?
									'selected' : '' ) . '>Select WooCommerce Attribute</option>
								<option value="select_custom_value" ' . ( ! empty( $attribute['type'] ) && 'fixed' == $attribute['type'] ?
									'selected' : '' ) . '>Custom Value</option></select><select ' . ( ( empty( $attribute['type'] ) ||
									'attribute' == $attribute['type'] ) && ! empty( $value['required'] ) ? 'required' : '' ) . ' 
									class="listing_of_atttribute_values select_attribute_values_customized CIFA-select attribute_value_' .
									$value['id'] . ' attribute_woocommerce_' . $value['id'] . '" 
									name="product_attributes[' . $value['id'] . '][name][source]" 
									style="width: 100%;' . ( ! empty( $attribute['type'] ) && 'fixed' == $attribute['type'] ?
										'display:none' : '' ) . '" data-select-id="' . $value['id'] . '" id="bulk-action-selector-top">
										<option value="">--Select--</option>';
								if ( ! empty( $woo_target_attributes ) ) {
									foreach ( $woo_target_attributes as $woo_attr_key => $woo_attr_key_data ) {
										if ( 'attr' != $woo_attr_key ) {
											continue;
										}
										foreach ( $woo_attr_key_data as $attribute_woo_key => $attribute_woo_data ) {
											$html2 .= '
											<option data-value="' . $attribute_woo_data['value'] . '" value="' . $attribute_woo_data['label'] . '" ' .
												( ! empty( $attribute['name'] ) && $attribute_woo_data['label'] == $attribute['name'] ? 'selected' : '' ) . '>' .
												$attribute_woo_data['label'] . '</option>';
										}
									}
								}
								$html2 .= '</select>';
							} else {
								$html2 .= '<select data-attribute-id="' . $value['id'] . '" 
								class="select_source_target_attr_type attribute_type CIFA-select" 
								name="product_attributes[' . $value['id'] . '][select_source_target_attr_type]" 
								style="width: 100%;" id="bulk-action-selector-top">
										<option value="select_woocommerce_attr" ' . ( empty( $attribute['type'] ) || 'attribute' == $attribute['type'] ?
									'selected' : '' ) . '>Select WooCommerce Attribute</option><option 
									value="select_aliexpress_attr" ' . ( ! empty( $attribute['type'] ) && 'predefined' == $attribute['type'] ?
									'selected' : '' ) . '>Select AliExpress Attribute</option></select><select ' . ( ( empty( $attribute['type'] ) ||
									'attribute' == $attribute['type'] ) && ! empty( $value['required'] ) ? 'required' : '' ) . ' 
									class="listing_of_atttribute_values select_attribute_values_customized CIFA-select attribute_value_' .
									$value['id'] . ' attribute_woocommerce_' . $value['id'] . '" 
									name="product_attributes[' . $value['id'] . '][name][source]" 
									style="width: 100%;' . ( ! empty( $attribute['type'] ) && 'attribute' !==
										$attribute['type'] ? 'display:none' : '' ) . '" data-select-id="' . $value['id'] . '"
										 id="bulk-action-selector-top"><option value="">--Select--</option>';
								if ( ! empty( $woo_target_attributes ) ) {
									foreach ( $woo_target_attributes as $woo_attr_key => $woo_attr_key_data ) {
										if ( 'attr' != $woo_attr_key ) {
											continue;
										}
										foreach ( $woo_attr_key_data as $attribute_woo_key => $attribute_woo_data ) {
											$html2 .= '<option data-value="' . $attribute_woo_data['value'] . '" 
											value="' . $attribute_woo_data['label'] . '" ' . ( ! empty( $attribute['name'] ) && $attribute_woo_data['label']
												== $attribute['name'] ? 'selected' : '' ) . '>' . $attribute_woo_data['label'] . '</option>';
										}
									}
								}
								$html2 .= '
								</select><select class="listing_of_atttribute_values select_attribute_values CIFA-select attribute_value_' .
									$value['id'] . ' attribute_aliexpress_' . $value['id'] . '" 
									name="product_attributes[' . $value['id'] . '][name][target]" 
									style="width: 100%;' . ( ! empty( $attribute['type'] ) && 'predefined' ==
										$attribute['type'] ? '' : 'display:none' ) . '" data-select-id="' . $value['id'] . '" 
										id="bulk-action-selector-top"> ' . ( ( ! empty( $attribute['type'] ) && 'predefined' == $attribute['type'] )
										&& ! empty( $value['required'] ) ?
										'required' : '' ) . '<option value="">--Select--</option>';
								foreach ( $value['values'] as $attr_key => $attr_key_data ) {
									if ( ! empty( $attr_key_data['name'] ) ) {
										$html2 .= '<option data-value="' . ( empty( $attr_key_data['id'] ) || 0 == $attr_key_data['id'] ? '0' : $attr_key_data['id'] ) . '" value="' . $attr_key_data['name'] . '" ' .
											( ! empty( $attribute['name'] ) && $attr_key_data['name'] == $attribute['name'] ? 'selected' : '' ) . '>'
											. $attr_key_data['name'] . '</option>';
									}
								}
								$html2 .= '</select>';
							}
							$html2 .= '<input type="text" id="select-' . $value['id'] . '" placeholder="Enter Value" 
							' . ( ( ! empty( $attribute['type'] ) && 'fixed' == $attribute['type'] ) && ! empty( $value['required'] ) ?
								'required' : '' ) . ' class="custom_value_for_attribute attribute_value_' . $value['id'] . ' attribute_custom_' .
								$value['id'] . '" name="product_attributes[' . $value['id'] . '][value]" value="' .
								( ! empty( $attribute['value'] ) ? $attribute['value'] : '' ) . '" style="' . ( ! empty( $attribute['type'] ) &&
									'fixed' == $attribute['type'] ? '' : 'display:none' ) . '">';
						}
						$html2 .= '</td></tr>';
					}
				}
			}
			$html1 .= '</tbody></table></div></div></div></div></div>';
			$html2 .= '</tbody></table></div></div></div></div></div>';

			if ( $count_attr > 0 ) {
				$html .= $html2;
			}

			if ( $count_variation_attr > 0 ) {
				$html .= $html1;
			}

			print_r( $html );
		}
		if ( empty( $requestData ) ) {
			wp_die();
		}
	}
	public function save_configuration_data_onboarding() {

		$check_ajax = check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$headers                  = $this->commonCallback->get_common_header();
			$step_completed           = $this->commonCallback->getStepCompleted();
			$title_rule_multiselect   = ! empty( $_POST['title_rule_multiselect'] ) ? array_map( 'sanitize_text_field', $_POST['title_rule_multiselect'] ) : array();
			$configuration_data       = ! empty( $_POST['configuration_data'] ) ? sanitize_text_field( $_POST['configuration_data'] ) : '';
			$configuration_data       = explode( '&', $configuration_data );
			$configuration_final_data = array();
			foreach ( $configuration_data as $key => $value ) {
				$exploded_value                                 = explode( '=', $value );
				$configuration_final_data[ $exploded_value[0] ] = $exploded_value[1];
			}

			if ( 'on' == $configuration_final_data['product_auto_update'] ) {
				$product_auto_update = true;
			} else {
				$product_auto_update = false;
			}

			if ( 'on' == $configuration_final_data['product_auto_create_child'] ) {
				$product_auto_create_child = true;
			} else {
				$product_auto_create_child = false;
			}

			if ( 'on' == $configuration_final_data['product_auto_create_type_change'] ) {
				$product_auto_create_type_change = true;
			} else {
				$product_auto_create_type_change = false;
			}

			if ( 'on' == $configuration_final_data['product_auto_create'] ) {
				$product_auto_create = true;
			} else {
				$product_auto_create = false;
			}

			if ( 'on' == $configuration_final_data['product_auto_delete'] ) {
				$product_auto_delete = true;
			} else {
				$product_auto_delete = false;
			}

			if ( 0 == $configuration_final_data['threshold_inventory'] ) {
				$threshold_inventory = '';
			} else {
				$threshold_inventory = $configuration_final_data['threshold_inventory'];
			}
			$title_rules['text_mappings'][0] = 'title_template_1';
			$title_rules['title_template_1'] = $configuration_final_data['custom_title_keyword'];
			$count                           = 2;
			foreach ( $title_rule_multiselect as $key1 => $value1 ) {
				$title_rules[ 'title_template_' . $count ] = $value1;
				++$count;
			}

			$endpoint = 'connector/config/saveConfig';

			$args     = array(
				'headers'   => $headers,
				'sslverify' => false,
				'body'      => wp_json_encode(
					array(
						'data' => array(
							array(
								'group_code' => 'product',
								'data'       => array(
									'product_auto_update' => $product_auto_update,
									'product_auto_create' => $product_auto_create,
									'product_auto_delete' => $product_auto_delete,
									'product_auto_create_child' => $product_auto_create_child,
									'product_auto_create_type_change' => $product_auto_create_type_change,
									'price_rule'          => array(
										'price_template' => $configuration_final_data['price_rule'],
										'price_template_value' => $configuration_final_data['default_temp_custom_price_rule_value'],
									),
									'title_template'      => $title_rules,
									'currency_conversion' => array(
										'value'    => $configuration_final_data['currency_conversion'],
										'currency' => 'USD',
									),
									'threshold_inventory' => $threshold_inventory,
								),
							),
						),
					)
				),
			);
			$response = $this->apiRequest->post( $endpoint, $args );
			if (
				! empty( $response['data']['success'] ) && 3 == $step_completed &&
				$this->commonCallback->setStepCompleted( 4 )
			) {
				update_option( 'CIFA_step_completed', 4 );
				update_option( 'CIFA_onboarding_completed', 'yes' );
				echo esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress' ) );
			}
		}

		wp_die();
	}
	public function extract_price_rule_query( $string ) {
		$query  = array();
		$string = str_replace( '(', '', $string );
		$string = str_replace( ')', '', $string );
		if ( strpos( $string, '&&' ) !== false ) {
			$explode = explode( '&&', $string );
		} elseif ( strpos( $string, '&amp;&amp;' ) !== false ) {
			$explode = explode( '&amp;&amp;', $string );
		} elseif ( strpos( $string, '||' ) !== false ) {
			$explode = explode( '||', $string );
		} else {
			$explode = array( $string );
		}
		$count = 1;
		foreach ( $explode as $value ) {
			$operator = '';
			if ( strpos( $value, '==' ) ) {
				$str      = explode( '==', $value );
				$operator = 'equals';
			} elseif ( strpos( $value, '%LIKE%' ) ) {
				$str      = explode( '%LIKE%', $value );
				$operator = 'contains';
			} elseif ( strpos( $value, '!%LIKE%' ) ) {
				$str      = explode( '!%LIKE%', $value );
				$operator = 'not_contains';
			} else {
				$str      = explode( '!=', $value );
				$operator = 'not_equals';
			}
			$query[ $count ]['key']      = trim( $str[0] );
			$query[ $count ]['operator'] = $operator;
			$query[ $count ]['value']    = trim( $str[1] );
			++$count;
		}
		return $query;
	}
	public function add_price_rule( $requestData = array() ) {
		if ( ! empty( $requestData ) || check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' ) ) {
			$html                 = '';
			$rule_count           = ! empty( $_POST['rule_count'] ) ? sanitize_text_field( $_POST['rule_count'] ) : $requestData['rule_count'];
			$headers              = $this->commonCallback->get_common_header();
			$endpoint             = 'webapi/rest/v1/woocommerce/category/getRuleGroup';
			$query                = ! empty( $requestData['query'] ) ? $requestData['query'] : array();
			$args                 = array(
				'headers' => $headers,
				'body'    =>
				array(
					'marketplace' => 'aliexpress',
				),
			);
			$rule_groups_response = $this->apiRequest->get( $endpoint, $args );
			$rule_groups          = array();
			if ( ! empty( $rule_groups_response['data']['success'] ) ) {
				$rule_groups = $rule_groups_response['data']['data'];
			}

			if ( ! empty( $rule_groups ) ) {
				if ( ! empty( $requestData['query'] ) ) {
					foreach ( $query as $q ) {
						$selectValue = $q['value'];
						$html       .= '<tr class="price_rule_' . $rule_count . '"><td><select name="rule_group[key][' . $rule_count . ']" class="rule_group_list" data-rule-group-select-id="' . $rule_count . '" id="rule_group_list' . $rule_count . '">';
						$sel         = '';
						foreach ( $rule_groups as $key => $value ) {
							$html  .= '<option data-options="' . ( ! empty( $value['options'] ) ? 'options' : '' ) . '" data-type="' . ( ! empty( $value['code'] ) ? $value['code'] : '' ) . '" value="' . $value['code'] . '" ' . ( $value['code'] == $q['key'] ? 'selected' : '' ) . '>' . $value['title'] . '</option>';
							$select = '';

							if ( ! empty( $value['options'] ) ) {
								$sel    .= '<select style="' . ( ( $value['code'] == $q['key'] ) ? '' : 'display:none' ) . '" name="rule_group[value][' . $rule_count . ']" id="rule_grp_' . $value['code'] . '_select_value_' . $rule_count . '">';
								$select .= '<option value="">--Select--</option>';
								foreach ( $value['options'] as $k => $option ) {
									$selectValue = $k == $q['value'] ? true : false;
									$select     .= '<option ' . ( $k == $q['value'] ? 'selected' : '' ) . ' value="' . $k . '">' . $option . '</option>';
								}
								$sel .= $select;
								$sel .= '</select>';

							} else {
								$sel .= '<input style="' . ( $value['code'] != $q['key'] ? 'display:none' : '' ) . '" type="text" value="' . $q['value'] . '" placeholder="Enter Value" name="rule_group[custom_value][' . $rule_count . ']" id="rule_grp_' . $value['code'] . '_custom_value_' . $rule_count . '">';
							}
						}

						$html .= '</select></td>';
						$html .= '<td><select name="rule_group[operator][' . $rule_count . ']" class="rule_grp_operator_' . $rule_count . '" id="rule_grp_operator_' . $rule_count . '">';
						$html .= '<option ' . ( ! empty( $q['operator'] ) && 'equals' == $q['operator'] ? 'selected' : '' ) . ' value="equals" selected>Equals</option>
						<option ' . ( ! empty( $q['operator'] ) && 'not_equals' == $q['operator'] ? 'selected' : '' ) . ' value="not_equals">Not Equals</option>
						<option ' . ( ! empty( $q['operator'] ) && 'contains' == $q['operator'] ? 'selected' : '' ) . ' value="contains">Contains</option>
						<option ' . ( ! empty( $q['operator'] ) && 'not_contains' == $q['operator'] ? 'selected' : '' ) . ' value="not_contains">Not Contains</option>
						<option ' . ( ! empty( $q['operator'] ) && 'less_than' == $q['operator'] ? 'selected' : '' ) . ' value="less_than" style="display: none;">Less Than</option>
						<option ' . ( ! empty( $q['operator'] ) && 'greater_than' == $q['operator'] ? 'selected' : '' ) . ' value="greater_than" style="display: none;">Greater Than</option>';
						$html .= '</select></td>';
						$html .= '</select></td><td>';
						$html .= $sel . '</td>';
						$html .= '<td style="' . ( $rule_count > 1 ? '' : 'display:none' ) . '"><a style="color:#b32d2e;cursor: pointer;" data-delete-rule-id="' . $rule_count . '" class="delete_price_rule">Delete</a><td></tr>';
						++$rule_count;
					}
				} else {
					$html .= '<tr class="price_rule_' . $rule_count . '"><td><select name="rule_group[key][' . $rule_count . ']" class="rule_group_list" data-rule-group-select-id="' . $rule_count . '" id="rule_group_list' . $rule_count . '">';
					$sel   = '';
					foreach ( $rule_groups as $key => $value ) {
						$html  .= '<option data-options="' . ( ! empty( $value['options'] ) ? 'options' : '' ) . '" data-type="' . ( ! empty( $value['code'] ) ? $value['code'] : '' ) . '"  value="' . $value['code'] . '" ' . ( 'title' == $value['code'] ? 'selected' : '' ) . '>' . $value['title'] . '</option>';
						$select = '';
						$sel   .= '<select style="display:none" name="rule_group[value][' . $rule_count . ']" id="rule_grp_' . $value['code'] . '_select_value_' . $rule_count . '">';
						if ( ! empty( $value['options'] ) ) {
							$select  = '';
							$select .= '<option value="">--Select--</option>';
							foreach ( $value['options'] as $k => $option ) {
								$select .= '<option value="' . $k . '">' . $option . '</option>';
							}
							$sel .= $select;
							$sel .= '</select>';
						} else {

							$sel .= '<input type="text" placeholder="Enter Value" style="' . ( 'title' == $value['code']  ? 'display:none' : '' ) . '" name="rule_group[custom_value][' . $rule_count . ']" id="rule_grp_' . $value['code'] . '_custom_value_' . $rule_count . '">';
						}
					}
					$html .= '</select></td>';

					$html .= '<td><select name="rule_group[operator][' . $rule_count . ']" class="rule_grp_operator_' . $rule_count . '" id="rule_grp_operator_' . $rule_count . '">';
					$html .= '<option value="equals" selected>Equals</option>
					<option value="not_equals">Not Equals</option>
					<option value="contains">Contains</option>
					<option value="not_contains">Not Contains</option>
					<option value="less_than" style="display: none;">Less Than</option>
					<option value="greater_than" style="display: none;">Greater Than</option>';
					$html .= '</select></td><td>';
					$html .= $sel . '</td>';
					$html .= '<td style="' . ( $rule_count > 1 ? '' : 'display:none' ) . '"><a style="color:#b32d2e;cursor: pointer;" data-delete-rule-id="' . $rule_count . '" class="delete_price_rule">Delete</a><td></tr>';
				}
			}
			if ( ! empty( $requestData ) ) {
				return $html;
			}
			print_r( $html );
		}
		wp_die();
	}


	public function display_values_on_click_of_rule_group_title() {

		$check_ajax = check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$rule_group_title     = ! empty( $_POST['rule_group_title'] ) ? sanitize_text_field( $_POST['rule_group_title'] ) : '';
			$endpoint             = 'webapi/rest/v1/woocommerce/category/getRuleGroup';
			$headers              = $this->commonCallback->get_common_header();
			$args                 = array(
				'headers'   => $headers,
				'sslverify' => false,
				'body'      => array(
					'marketplace' => 'aliexpress',
				),
			);
			$rule_groups          = array();
			$html                 = '';
			$rule_groups_response = $this->apiRequest->get( $endpoint, $args );
			if ( isset( $rule_groups_response['data'] ) && true == $rule_groups_response['data']['success'] ) {
				$rule_groups = $rule_groups_response['data']['data'];
			}
			if ( ! empty( $rule_groups ) ) {
				foreach ( $rule_groups as $key => $value ) {
					if ( $rule_group_title == $value['code'] ) {
						if ( isset( $value['options'] ) ) {
							if ( ! empty( $value['options'] ) ) {
								foreach ( $value['options'] as $key1 => $value1 ) {
									$html .= '<option value="' . $key1 . '">' . $value1 . '</option>';
								}
								print_r( $html );
							} else {
								echo 'Options Empty';
							}
						} else {
							echo 'No Options Available';
						}
					}
				}
			}
		}

		wp_die();
	}

	public function run_query_rule_grp() {

		$check_ajax = check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax && ! empty( $_POST['rule_group'] ) ) {
			$query                       = sanitize_text_field( $_POST['rule_group'] );
			$overwrite_existing_products = ! empty( $_POST['override_listing'] ) &&
				'true' == sanitize_text_field( $_POST['override_listing'] ) ? true : false;
			$endpoint                    = 'connector/profile/getQueryProductsCount';
			$headers                     = $this->commonCallback->get_common_header();
			$args                        = array(
				'headers'   => $headers,
				'sslverify' => false,
				'body'      => wp_json_encode(
					array(
						'source'                    => array(
							'shopId'      => json_decode( get_option( 'CIFA_user_data' ), ARRAY_A )['source_shop_id'],
							'marketplace' => 'woocommerce',
						),
						'target'                    => array(
							'shopId'      => json_decode( get_option( 'CIFA_user_data' ), ARRAY_A )['target_shop_id'],
							'marketplace' => 'aliexpress',
						),
						'query'                     => $query,
						'overWriteExistingProducts' => $overwrite_existing_products,
						'useForceQueryUpdate'       => true,
						'useRefinProduct'           => true,
					)
				),
			);
			$rule_groups_response        = $this->apiRequest->post( $endpoint, $args );
			if ( ! empty( $rule_groups_response['data']['success'] ) ) {
				echo wp_json_encode( $rule_groups_response['data'] );
			}
		}
		wp_die();
	}

	public function validate_category_template() {

		$check_ajax = check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax && ! empty( $_POST['template_name'] ) && ! empty( $_POST['rule_group'] ) ) {
			$endpoint = 'connector/profile/validateProfile';
			$headers  = $this->commonCallback->get_common_header();
			$args     = array(
				'headers'   => $headers,
				'sslverify' => false,
				'body'      => wp_json_encode(
					array(
						'target-marketplace' => 'aliexpress',
						'validate_on'        => array(
							'name'  => sanitize_text_field( $_POST['template_name'] ),
							'query' => sanitize_text_field( $_POST['rule_group'] ),
						),
					)
				),
			);
			$response = $this->apiRequest->post( $endpoint, $args );
			echo wp_json_encode( $response['data'] );
			wp_die();
		}
	}

	public function validate_profile_name() {

		$check_ajax = check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax && ! empty( $_POST['template_name'] ) ) {
			$endpoint = 'connector/profile/validateProfieName';
			$headers  = $this->commonCallback->get_common_header();
			$args     = array(
				'headers'   => $headers,
				'sslverify' => false,
				'body'      => wp_json_encode(
					array(
						'name' => sanitize_text_field( $_POST['template_name'] ),
					)
				),
			);
			$response = $this->apiRequest->post( $endpoint, $args );
			echo wp_json_encode( $response['data'] );
		}
		wp_die();
	}

	public function refresh_activities() {
		$check_ajax = check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$headers = $this->commonCallback->get_common_header();
			$url     = 'connector/get/allQueuedTasks';
			$params  = array(
				'headers' => $headers,
			);

			$res                   = $this->apiRequest->get( $url, $params );
			$ongoingActivitiesData = ! empty( $res['data']['data']['rows'] ) ? $res['data']['data']['rows'] : array();
			$ongoingHtml           = '';
			foreach ( $ongoingActivitiesData as $value ) {
				$ongoingHtml .= '<div class="CIFA-select-block">
								<div class="CIFA-flex-common">
									<span><b>' . esc_attr( $value['message'] ) . '</b></span><span class="CIFA-activities-color">' . esc_attr( $value['created_at'] ) . '</span>
								</div>
								<progress style="width: 100%; height: 30px;" class="woocommerce-task-progress-header__progress-bar" max="100" value="' . esc_attr( $value['progress'] ) . '"></progress>
							</div>';
			}
			$getData = ! empty( $_GET ) ? filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
			if ( empty( $getData['activePage'] ) ) {
				$activePage = 1;
			} else {
				$activePage = (int) sanitize_text_field( $getData['activePage'] );
			}
			if ( ! empty( $getData['count'] ) ) {
				$count = (int) sanitize_text_field( $getData['count'] );
			} else {
				$count = 5;
			}
			$url    = 'connector/get/allNotifications';
			$params = array(
				'headers' => $headers,
				'body'    => wp_json_encode(
					array(
						'activePage' => $activePage,
						'count'      => $count,
					)
				),
			);

			$response          = $this->apiRequest->post( $url, $params );
			$rows              = ! empty( $response['data']['data']['rows'] ) ? $response['data']['data']['rows'] : array();
			$completedActivity = '';
			foreach ( $rows as $value ) {
				$completedActivity .= '<div class="CIFA-select-block CIFA-bottom-border">
							<div class="CIFA-flex-common CIFA-flex-static">
								<div class="CIFA-connected-button-wrap">
									<span class="CIFA-circle-instock"></span>
								</div>
								<div class="CIFA-activities-info">
									<p>' . esc_attr( $value['message'] ) . '</p>
									<p class="CIFA-activites-color">' . esc_attr( $value['created_at'] ) . '</p>
								</div>
							</div>
						</div>';
			}
			$totalRows = ! empty( $response['data']['data']['count'] ) ? $response['data']['data']['count'] : 0;
			$maxChunk  = ceil( $totalRows / $count );
			$chunk     = $totalRows && ( $totalRows > $count ) ? ( ( 1 == $activePage ? 1 : ( $count * ( $activePage - 1 ) ) ) . ' - ' . ( $count * $activePage ) ) : $totalRows;
			if ( 1 === $activePage ) {
				$leftArrow = '<a href="javascript:void(0)" style="pointer-events: none;" class="left_arrow"><span class="dashicons dashicons-arrow-left-alt2"></span></a>';
			} else {
				$leftArrow = '<a href="javascript:void(0)" class="left_arrow" style="display: inline-block;"><span class="dashicons dashicons-arrow-left-alt2"></span></a>';
			}
			if ( ( $totalRows - ( $count * $activePage ) ) >= 1 ) {
				$rightArrow = '<a href="javascript:void(0)" class="right_arrow" style="display: inline-block;"><span class="dashicons dashicons-arrow-right-alt2"></span></a>';
			} else {
				$rightArrow = '<a href="javascript:void(0)" style="pointer-events: none;" class="right_arrow"><span class="dashicons dashicons-arrow-right-alt2"></span></a>';
			}
			$options = '<option value="5" ' . ( ( 5 == $count ) ? 'selected' : '' ) . '>5</option><option value="10" ' . ( ( 10 == $count ) ? 'selected' : '' ) . '>10</option>
			<option value="20" ' . ( ( 20 == $count ) ? 'selected' : '' ) . '>20</option><option value="50" ' . ( ( 50 == $count ) ? 'selected' : '' ) . '>50</option>
			<option value="100" ' . ( ( 100 == $count ) ? 'selected' : '' ) . '>100</option>';

			$allowed_html = array(
				'a'    => array(
					'href'  => array(),
					'class' => array(),
					'style' => array(),
				),
				'span' => array(
					'class' => array(),
				),
			);
			$pagination   = '<div class="CIFA-pagination-native">
			<div class="CIFA-pagination-left-container">
				<span>Item: <select class="total_rows_per_page">
								' . $options . '
							</select> showing ' . $chunk . ' of ' . $totalRows . '</span>
			</div>
			<div class="CIFA-right-container">
				<div class="CIFA-pagination-common-right"><span class="CIFA-prev">' . wp_kses( $leftArrow, $allowed_html ) . '</span><input type="text" class="active_page" value="' . esc_html( $activePage ) . '" placeholder="1"> of ' . esc_html( $maxChunk ) . ' <span class="CIFA-paginatrion-right">' . wp_kses( $rightArrow, $allowed_html ) . '</span></div>
			</div>
		</div>';
			echo wp_json_encode(
				array(
					'success'       => true,
					'ongoingHtml'   => $ongoingHtml,
					'completedHtml' => $completedActivity,
					'pagination'    => $pagination,
				)
			);
		}

		wp_die();
	}
	public function getVariantOptions( $requestData ) {
		if (
			! empty( $requestData ) ||
			( check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' )
				&& ! empty( $_POST['attributes'] ) && ! empty( $_POST['id'] ) )
		) {
			$postData        = ! empty( $_POST ) ? filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
			$attributes      = ! empty( $requestData['attributes'] ) ? $requestData['attributes']
				: $postData['attributes'];
			$categoryId      = ! empty( $requestData['category_id'] ) ? sanitize_text_field( $requestData['category_id'] )
				: ( ! empty( $postData['category_id'] ) ? $postData['category_id'] : '' );
			$id              = ! empty( $requestData['id'] ) ? sanitize_text_field( $requestData['id'] ) : $postData['id'];
			$refresh         = ! empty( $postData['refresh'] ) ? (bool) $postData['refresh'] : false;
			$headers         = $this->commonCallback->get_common_header();
			$url             = 'webapi/rest/v1/aliexpress/product/getVariantOptions';
			$attributeValues = ! empty( $requestData['values'] ) ? $requestData['values'] : ( ! empty( $postData['values'] ) ? $postData['values'] : array() );

			$params   = array(
				'headers' => $headers,
				'body'    => wp_json_encode(
					array(
						'refresh'            => $refresh,
						'variant_attributes' => $attributes,
						'source_marketplace' => 'woocommerce',
					)
				),
			);
			$response = $this->apiRequest->post( $url, $params );

			$data = ! empty( $response['data']['data'] ) ? $response['data']['data'] : array();

			if ( $categoryId ) {
				$getVariantAttributesValueMappings = $this->apiRequest->get(
					add_query_arg(
						'category_id',
						esc_html( $categoryId ),
						'webapi/rest/v1/aliexpress/product/getVariantAttributesValueMappings'
					),
					array( 'headers' => $headers )
				);
				$variantAttributesValueMappings    = ! empty( $getVariantAttributesValueMappings['data']['data']['option_mapping'] )
					? $getVariantAttributesValueMappings['data']['data']['option_mapping'] : array();
			}
			$currentLimit = 5;
			$html         = '<table class="form-table CIFA-table-content-width option_table_' . $id . '">
					<thead class="table_header_' . $id . '" style="' . ( $categoryId ? '' : 'display:none' ) . '">
						<tr class="CIFA-bottom-border-wrap">
							<td class="forminp forminp-select">
								<input type="text" value="" class="option_search option_search_' . $id .
				'" data-search-id="' . $id . '" data-search-key="" placeholder="Search Here">
							</td>
							<td class="forminp forminp-select">
								<select class="option_name option_name_' . $id . '" data-select-option-name="' . $id . '" data-option-key="">
								';
			foreach ( $attributes as $value ) {
				$html .= ! empty( $value ) ? '<option value="' . $value . '">' . $value . '</option>' : '';
			}
			$html .= '
								</select>
							</td>
							</tr>
					</thead>
						';
			$count = 1;
			foreach ( $attributes as $key ) {
				$value      = ! empty( $data[ $key ] ) ? $data[ $key ] : array();
				$firstCount = count( $value );
				if ( ! empty( $value ) ) {
					$html       .= '
					<tbody id ="option_body_' . $key . '" data-option-body-key="' . $key . '" class="option_body" style="display:none">
					<tr>
						<td><strong>Woocommerce</strong></td><td><strong>AliExpress</strong></td>
					</tr>
					<tr style="display:none">
					<td><input type="hidden" name="option_mapping[' . $key . '][key]" value="' . $key . '"></td>
					</tr>';
					$optionCount = 1;
					foreach ( $value as $k => $val ) {
						$html .= '<tr class="option_value_row option_value_row_' . $val .
							' option_value_row_counter_' . $optionCount . '" style="' . ( $optionCount >
								$currentLimit ? 'display:none' : '' ) . '"><td><input type="text" 
						  disabled="true" data-option-key-counter="' . $val . '" value="' . $val .
							'" class="option_key_index"></td><td 
							data-td-id="option_mapping[' . $key . '][value][][' . $val . ']"
							 class="target_select_html target_select_html_' . $key . '">';
						if ( ! empty( $attributeValues ) ) {
							$html  .= '<select name="option_mapping[' . $key . '][value][][' . $val . ']">';
							$values = array();
							foreach ( $variantAttributesValueMappings as $options ) {
								if ( $key === $options['key'] ) {
									$values = $options['value'];
								}
							}
							foreach ( $attributeValues as $attr_key => $attr_key_data ) {
								$html .= '<option data-value="' . $attr_key_data['id'] . '" 
								value="' . $attr_key_data['name'] . '" ' . ( ! empty( $values[ $k ][ $val ] ) &&
									$values[ $k ][ $val ] == $attr_key_data['name'] ? 'selected' : '' ) . '>' . $attr_key_data['name'] . '</option>';
							}
							$html .= '</select>';
						}
						$html .= '</td>	
								</tr>';

						++$optionCount;
					}
					$html .= '
						<tr class="CIFA-border-top-wrap">
								<td class="left_pagination left_pagination_' . $key . '"> <select class="total_rows_per_page" data-chunk-limit-key="' . $key . '"
								data-chunk-limit-id="' . $id . '"
								>
									<option value="5" selected="">5</option>
									<option value="10">10</option>
									<option value="20">20</option>
									<option value="50">50</option>
									<option value="100">100</option>
							</select> Items showing <span class="current_chunk">
										' . ( $firstCount ? 1 : 0 ) . ' - ' . ( $firstCount < $currentLimit ? $firstCount : $currentLimit ) . ' </span> of <span class="total_chunk_count">' . $firstCount . '</span></td>
								<td class="CIFA-pagination-common-right right_pagination right_pagination_' . $key . '">
									<span class="CIFA-prev">
										<a href="javascript:void(0)" style="pointer-events: none;" data-left-arrow-key="' . $key . '" 
										data-left-arrow-id="' . $id . '" class="left_arrow">
											<span class="dashicons dashicons-arrow-left-alt2"></span>
										</a>
									</span>
									<input type="text" class="active_page" disabled data-active-page-key="' . $key . '" value="1" placeholder="1"> of <span class="total_pages" style="margin-left:5px;">' . ( $firstCount <= $currentLimit ? 1 : ceil( $firstCount / $currentLimit ) ) . '</span>
									<span class="CIFA-paginatrion-right">
										<a href="javascript:void(0)" class="right_arrow" data-right-arrow-id="' . $id . '" data-right-arrow-key="' . $key . '" style="display: inline-block;' . ( ( $firstCount / $currentLimit ) <= 0 ? 'pointer-events: none;' : '' ) . '">
											<span class="dashicons dashicons-arrow-right-alt2"></span>
										</a>
									</span>
								</td>
							</tr>
						</tbody>
					';
				} else {
					$html .= '<tbody id="option_body_' . $key . '" data-option-body-key="' . $key . '" class="option_body" style="' . ( $count > 1 ? 'display:none' : '' ) . '"><tr class="option_value_row option_value_row_counter_0" style="display:contents">
					<td colspan="2"><label>No values Found under this Attribute</label></td>
					</tr></tbody>';
				}
				++$count;
			}
			$html .= '</table>';

			if ( ! empty( $requestData ) ) {
				return $html;
			} else {
				echo wp_json_encode(
					array(
						'success' => true,
						'data'    => $html,
					)
				);
				wp_die();
			}
		}
	}

	public function get_all_categories() {
		$check_ajax = check_ajax_referer( 'ced-aliexpress-ajax-seurity-string', 'ajax_nonce' );
		if ( $check_ajax ) {
			$search_val = ! empty( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
			$headers    = $this->commonCallback->get_common_header();
			$endpoint   = 'webapi/rest/v1/aliexpress/getTargetCategoryList';
			$params     = array(
				'headers'   => $headers,
				'ssvverify' => false,
			);
			if ( $search_val ) {
				$endpoint = add_query_arg( array( 'search' => $search_val ), $endpoint );
			}
			$res  = $this->apiRequest->get( $endpoint, $params );
			$html = $search_val ? '' : '<option value="" selected>--Select any Category--</option>';
			if ( ! empty( $res['data']['success'] ) ) {
				foreach ( $res['data']['data'] as $key => $option ) {
					$html .= '<option value="' . esc_attr( $option['category_id'] ) . '">'
						. esc_attr( $option['category_path_en'] ) . '</option>';
				}
			}
			echo wp_json_encode(
				array(
					'success' => true,
					'html'    => $html,
				)
			);

			wp_die();
			exit;
		}
	}
}
