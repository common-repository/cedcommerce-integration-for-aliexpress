<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class CIFA_Products extends WP_List_Table {


	public $postData;
	public $getData;
	public $apiRequest;
	public $commonCallback;
	public $plugin_name;
	public $version;
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'Product',     // Singular name of the item
				'plural'   => 'Products',    // Plural name of the item
				'ajax'     => false,      // Enable AJAX handling
			)
		);
		$this->postData       = isset( $_REQUEST['aliexpress_nonce'] ) && wp_verify_nonce(
			wp_unslash( sanitize_text_field( $_REQUEST['aliexpress_nonce'] ) ),
			'aliexpress_nonce_field'
		) ? $_REQUEST : array();
		$this->plugin_name    = CIFA_PLUGIN_NAME;
		$this->version        = CIFA_VERSION;
		$this->apiRequest     = new CIFA_Api_Base();
		$this->commonCallback = new CIFA_Common_Callback();
	}
	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox" />',
			'image'    => __( 'Image', 'cedcommerce-integration-for-aliexpress' ),
			'title'    => __( 'Title', 'cedcommerce-integration-for-aliexpress' ),
			'sku'      => __( 'SKU', 'cedcommerce-integration-for-aliexpress' ),
			'price'    => __( 'Price', 'cedcommerce-integration-for-aliexpress' ),
			'quantity' => __( 'Quantity', 'cedcommerce-integration-for-aliexpress' ),
			'status'   => __( 'Status', 'cedcommerce-integration-for-aliexpress' ),
			'category' => __( 'Category Template', 'cedcommerce-integration-for-aliexpress' ),

		);
	}
	public function column_default( $item, $column_name ) {

		return $item[ $column_name ];
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="source_product_ids[]" value="' . $item['id'] . '" />'
		);
	}
	public function prepare_items() {
		$postData = $this->postData;
		$query    = array();
		$search   = ! empty( $postData['s'] ) ? sanitize_text_field( $postData['s'] ) : false;
		if ( ! empty( $search ) ) {
			$query['or_filter']['title'][3]             = $search;
			$query['or_filter']['sku'][3]               = $search;
			$query['or_filter']['source_product_id'][3] = $search;
		}
		if ( ! empty( $postData['status'] ) && ! empty( $postData['filter_action'] ) ) {
			$status = sanitize_text_field( $postData['status'] );
		} else {
			$status = false;
		}
		if ( ! empty( $status ) ) {
			if ( 'not_uploaded' === $status ) {
				$query['filter']['items.0.status'][12] = false;
			} else {
				$query['filter']['items.0.status'][1] = $status;
			}
		}
		$categoryTemplate = ! empty( $postData['category_template'] ) &&
			! empty( $postData['filter_action'] ) ? sanitize_text_field( $postData['category_template'] ) : false;

		if ( ! empty( $categoryTemplate ) ) {
			$query['filter']['profile.profile_name'][1] = $categoryTemplate;
		}
		if ( ! empty( $postData['product_type'] ) && ! empty( $postData['filter_action'] ) ) {
			$productType = sanitize_text_field( $postData['product_type'] );
		} else {
			$productType = false;
		}
		if ( ! empty( $productType ) ) {
			$query['filter']['type'][1] = $productType;
		}
		$query['sortBy'] = '_id';
		$data            = $this->get_data( $query );

		$this->_column_headers = array( $this->get_columns(), array() );
		if ( ! empty( $postData['count'] ) && ! empty( $postData['filter_action'] ) ) {
			$count = (int) sanitize_text_field( $postData['count'] );
		} else {
			$count = 10;
		}
		$per_page    = $this->get_items_per_page( 'items_per_page', $count );
		$total_items = $this->get_total_count( $query );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$this->items = $data;
	}
	public function get_data( $query = array() ) {
		$postData   = $this->postData;
		$count      = ! empty( $postData['count'] ) ? (int) sanitize_text_field( $postData['count'] ) : 10;
		$activePage = ! empty( $postData['paged'] ) ? (int) sanitize_text_field( $postData['paged'] ) : 1;

		$bodyParams = array(
			'count'      => $count,
			'activePage' => $activePage,
		);
		if ( ! empty( $query ) ) {
			$bodyParams = array_merge( $bodyParams, $query );
		}
		$url     = 'connector/product/getRefineProducts';
		$headers = $this->commonCallback->get_common_header();

		$params   = array(
			'headers' => $headers,
			'body'    => wp_json_encode( $bodyParams ),
		);
		$response = $this->apiRequest->post( $url, $params );

		$products = $response['data'];

		$rows         = ! empty( $products['data']['rows'] ) ? $products['data']['rows'] : array();
		$productArray = array();
		foreach ( $rows as $value ) {
			$newArray             = array();
			$image                = ! empty( $value['main_image'] ) ? $value['main_image'] :
				CIFA_URL . 'admin/images/broken-product.png';
			$newArray['image']    = '<img src="' . $image . '" style="max-width:60px;max-height:60px;">';
			$newArray['id']       = $value['source_product_id'];
			$newArray['title']    = '<a class="row-title" href="' .
				esc_url( admin_url( 'post.php?post=' . $value['source_product_id'] . '&action=edit' ) ) .
				'">' . $value['title'] . '</a>';
			$newArray['category'] = ! empty( $value['profile']['profile_name'] ) ?
				$value['profile']['profile_name'] : 'Default';
			$newArray['sku']      = $value['items'][0]['sku'];
			if ( ! empty( $value['items'][0]['status'] ) && 'error' === $value['items'][0]['status'] ) {
				$newArray['status'] = '<div class="ced-disconnected-button-wrap">
                <a href="javascript:void(0)" class="error_status ced-connected-link" data-error-id="'
					. $value['source_product_id'] . '"><span class="ced-circle"></span>'
					. $value['items'][0]['status'] . '</a>';
				echo '<div id="CIFA-myPopup" class="CIFA-modal-wrap error-modal-' .
					esc_attr( $value['source_product_id'] ) . '">
                    <div class="CIFA-content">
                        <span class="CIFA-close-button" data-error-id="' . esc_attr( $value['source_product_id'] ) .
					'"><span class="dashicons dashicons-no-alt"></span></span>
                        <div class="CIFA-title-holder">
                            <h3>Product Errors</h3>
                        </div>
                        <div class="CIFA-popup-content-wrapper">
                            <div class="CIFA-popup-content">
                            <div class="CIFA-product-errors">
                            <ul>
                                ';
				if ( ! empty( $value['items'][0]['error_details']['error'] ) ) {
					if ( is_array( $value['items'][0]['error_details']['error'] ) ) {
						foreach ( $value['items'][0]['error_details']['error'] as $val ) {
							echo '<li>' . esc_attr( $val ) . '</li>';
						}
					} else {
						echo '<li>' . esc_attr( $value['items'][0]['error_details']['error'] ) . '</li>';
					}
				}
				if ( ! empty( $value['items'][0]['warning_details'] ) ) {
					foreach ( $value['items'][0]['warning_details'] as $val ) {
						echo '<li>' . esc_attr( $val ) . '</li>';
					}
				}
				echo '</ul>
                        </div>
                            </div>
                        </div>
                    </div>
              </div>';
			} elseif ( ! empty( $value['items'][0]['status'] ) && 'rejected' === $value['items'][0]['status'] ) {
				$newArray['status'] = '<div class="ced-disconnected-button-wrap">
                <a href="javascript:void(0)" class="error_status ced-connected-link" data-error-id="'
					. $value['source_product_id'] . '"><span class="ced-circle"></span>'
					. $value['items'][0]['status'] . '</a>';
				echo '<div id="CIFA-myPopup" class="CIFA-modal-wrap error-modal-' .
					esc_attr( $value['source_product_id'] ) . '">
                    <div class="CIFA-content">
                        <span class="CIFA-close-button" data-error-id="' . esc_attr( $value['source_product_id'] ) .
					'"><span class="dashicons dashicons-no-alt"></span></span>
                        <div class="CIFA-title-holder">
                            <h3>Product Warnings</h3>
                        </div>
                        <div class="CIFA-popup-content-wrapper">
                            <div class="CIFA-popup-content">
                            <div class="CIFA-product-errors">
                            <ul>
                                ';
				if ( ! empty( $value['items'][0]['error_details']['error'] ) ) {
					if ( is_array( $value['items'][0]['error_details']['error'] ) ) {
						foreach ( $value['items'][0]['error_details']['error'] as $val ) {
							echo '<li>' . esc_attr( $val ) . '</li>';
						}
					} else {
						echo '<li>' . esc_attr( $value['items'][0]['error_details']['error'] ) . '</li>';
					}
				}
				if ( ! empty( $value['items'][0]['warning_details'] ) ) {
					foreach ( $value['items'][0]['warning_details'] as $val ) {
						echo '<li>' . esc_attr( $val ) . '</li>';
					}
				}
				echo '</ul>
                        </div>
                            </div>
                        </div>
                    </div>
              </div>';
			} else {
				$status             = ! empty( $value['items'][0]['status'] ) ? $value['items'][0]['status'] : 'Not uploaded';
				$newArray['status'] = '<div class="ced-disconnected-button-wrap"><a class="ced-connected-link"><span class="ced-circle"></span>' . $status . '</a> </div>';
			}
			$price    = '';
			$quantity = '';
			if ( count( $value['items'] ) == 1 ) {
				$price    = $value['items'][0]['price'];
				$quantity = $value['items'][0]['quantity'];
			} else {
				$priceArray    = array();
				$quantityArray = array();
				foreach ( $value['items'] as $value ) {
					if ( ! empty( $value['price'] ) ) {
						$priceArray[] = $value['price'];
					}
					if ( ! empty( $value['quantity'] ) ) {
						$quantityArray[] = $value['quantity'];
					}
				}
				if ( ! empty( $priceArray ) ) {
					$price = min( $priceArray ) . ' - ' . max( $priceArray );
				}
				if ( ! empty( $quantityArray ) ) {
					$quantity = min( $quantityArray ) . ' - ' . max( $quantityArray );
				}
			}
			$newArray['price']    = $price;
			$newArray['quantity'] = $quantity;
			$productArray[]       = $newArray;
		}

		return $productArray;
	}
	public function get_total_count( $query = array() ) {
		$url     = 'connector/product/getRefineProductCount';
		$headers = $this->commonCallback->get_common_header();
		$params  = array(
			'headers' => $headers,
		);
		if ( ! empty( $query ) ) {
			$params['body'] = wp_json_encode( $query );
		}
		$response = $this->apiRequest->post( $url, $params );
		$products = $response['data'];
		return ! empty( ( $products['data']['count'] ) ) ? (int) ( $products['data']['count'] ) : 0;
	}
	public function get_category_templates() {
		$url     = 'connector/profile/getProfileData?count=100';
		$headers = $this->commonCallback->get_common_header();

		$params     = array(
			'headers' => $headers,
		);
		$response   = $this->apiRequest->get( add_query_arg( array( 'useRefinProduct' => true ), $url ), $params );
		$categories = $response['data'];
		return ! empty( $categories['data']['rows'] ) ? $categories['data']['rows'] : array();
	}
	public function extra_tablenav( $which ) {
		if ( 'top' == $which ) {
			$postData = $this->postData;
			echo '<select name="bulk_actions">
                    <option value="">Bulk actions</option>
                    <option value="upload_products">Upload Products</option>
                    <option value="update_products">Update Product(s)</option>
                    <option value="sync_price">Sync Price</option>
                    <option value="sync_inventory">Sync Inventory</option>
                    <option value="product_validate">Validate Product</option>
                    <option value="online_products">Online Product(s)</option>
                    <option value="offline_products">Offline Product(s)</option>
                </select>';
			submit_button( 'Apply', 'button', 'bulk', false );
			echo '&nbsp&nbsp;&nbsp;&nbsp;';
			$status = ! empty( $postData['status'] ) && ! empty( $postData['filter_action'] ) ? sanitize_text_field( $postData['status'] ) : false;

			$categoryTemplate = ! empty( $postData['category_template'] ) &&
				! empty( $postData['filter_action'] ) ? sanitize_text_field( $postData['category_template'] ) : false;

			$productType = ! empty( $postData['product_type'] ) && ! empty( $postData['filter_action'] )
				? sanitize_text_field( $postData['product_type'] ) : false;
			echo '<select name="status">';
			echo '<option value="">All Statuses</option>';
			echo '<option value="onSelling" ' . selected( 'onSelling' == $status, true, false ) . '>On Selling</option>';
			echo '<option value="not_uploaded" ' . selected( 'not_uploaded' == $status, true, false ) .
				'>Not Uploaded</option>';
			echo '<option value="error" ' . selected( 'error' == $status, true, false ) . '>Error</option>';
			echo '<option value="not_ready" ' . selected( 'not_ready' == $status, true, false ) . '>Not Ready</option>';
			echo '<option value="editingRequired" ' . selected( 'editingRequired' == $status, true, false ) . '>Editing Required</option>';
			echo '<option value="auditing" ' . selected( 'auditing' == $status, true, false ) . '>Auditing</option>';
			echo '<option value="offline" ' . selected( 'offline' == $status, true, false ) . '>Offline</option>';
			echo '</select>';
			$categories = $this->get_category_templates();
			echo '<select name="category_template">';
			echo '<option value="">Category templates</option>';
			foreach ( $categories as $category ) {
				echo '<option value="' . esc_attr( $category['name'] ) . '" '
					. selected( esc_attr( $category['name'] == $categoryTemplate ), true, false ) . '>' .
					esc_attr( $category['name'] ) . '</option>';
			}
			echo '</select>';
			echo '<select name="product_type">';
			echo '<option value="">Product Type</option>';
			echo '<option value="simple" ' . selected( 'simple' == $productType, true, false )
				. '>Simple</option>';
			echo '<option value="variation" ' . selected( 'variation' == $productType, true, false )
				. '>Variant</option>';
			echo '</select>';
			$count = '';
			if ( ! empty( $postData['count'] ) && ! empty( $postData['filter_action'] ) ) {
				$count = (int) sanitize_text_field( $postData['count'] );
			}
			echo '<select name="count">
                <option value="10" ' . selected( 10 == $count, true, false ) . '>10</option>
                <option value="20" ' . selected( 20 == $count, true, false ) . '>20</option>
                <option value="50" ' . selected( 50 == $count, true, false ) . '>50</option>
                <option value="100" ' . selected( 100 == $count, true, false ) . '>100</option>
                </select>';
			submit_button( 'Filter', 'button', 'filter_action', false );
			if ( $productType || $categoryTemplate || $status || ! empty( $postData['s'] ) || $count ) {
				echo '&nbsp;';
				submit_button( 'Reset', 'primary', 'reset_filter', false );
			}
		}
	}
	public function handle_params( $postData = array() ) {
		if ( ( ! empty( $postData ) && ! empty( $postData['filter_action'] ) ) || ! empty( $postData['s'] ) || ! empty( $postData['paged'] ) ) {
			$allowed_filters = array( 'product_type', 'category_template', 'status', 's', 'count', 'filter_action', 'paged', 'aliexpress_nonce' );

			$params = array();
			foreach ( $postData as $key => $value ) {
				if ( in_array( $key, $allowed_filters ) ) {
					if ( 'paged' === $key && $value < 1 ) {
						$value = -$value;
					}
					$params[ $key ] = $value;
				}
			}
			if ( ! empty( $params ) ) {
				wp_redirect(
					add_query_arg( $params, admin_url( 'admin.php?page=ced_integration_aliexpress&section=products' ) )
				);
			}
		}
	}
	public function handlePostRequest( $postData = array() ) {
		if ( ! empty( $postData['reset_filter'] ) ) {
			wp_redirect( admin_url( 'admin.php?page=ced_integration_aliexpress&section=products' ) );
			wp_die();
		}
		if ( ! empty( $postData['bulk_actions'] ) && ! empty( $postData['source_product_ids'] ) && ! empty( $postData['bulk'] ) ) {
			if (
				isset( $postData['aliexpress_nonce'] ) &&
				! wp_verify_nonce( wp_unslash( $postData['aliexpress_nonce'] ), 'aliexpress_nonce_field' )
			) {

				wp_die( 'Unauthorized Access' );
			}
			$bulk_action     = sanitize_text_field( $postData['bulk_actions'] );
			$api_request     = new CIFA_Api_Base();
			$common_callback = new CIFA_Common_Callback();
			$headers         = $common_callback->get_common_header();
			switch ( $bulk_action ) {
				case 'upload_products':
					$bodyParams = array(
						'source_product_ids' => array_map( 'sanitize_text_field', $postData['source_product_ids'] ),
						'action'             => 'product_upload',
						'source'             => array(
							'marketplace' => $headers['Ced-Source-Name'],
							'shopId'      => $headers['Ced-Source-Id'],
						),
						'target'             => array(
							'marketplace' => $headers['Ced-Target-Name'],
							'shopId'      => $headers['Ced-Target-Id'],
						),
						'mergeVariants'      => true,
						'limit'              => 500,
					);
					$url        = 'webapi/rest/v1/aliexpress/product';
					break;
				case 'update_products':
					$bodyParams = array(
						'source_product_ids' => array_map( 'sanitize_text_field', $postData['source_product_ids'] ),
						'action'             => 'product_update',
						'source'             => array(
							'marketplace' => $headers['Ced-Source-Name'],
							'shopId'      => $headers['Ced-Source-Id'],
						),
						'target'             => array(
							'marketplace' => $headers['Ced-Target-Name'],
							'shopId'      => $headers['Ced-Target-Id'],
						),
						'mergeVariants'      => true,
						'limit'              => 500,
					);
					$url        = 'connector/product/productSync';
					break;
				case 'sync_price':
					$bodyParams = array(
						'source_product_ids' => array_map( 'sanitize_text_field', $postData['source_product_ids'] ),
						'action'             => 'price_sync',
						'source'             => array(
							'marketplace' => $headers['Ced-Source-Name'],
							'shopId'      => $headers['Ced-Source-Id'],
						),
						'target'             => array(
							'marketplace' => $headers['Ced-Target-Name'],
							'shopId'      => $headers['Ced-Target-Id'],
						),
						'mergeVariants'      => true,
						'limit'              => 500,
					);
					$url        = 'connector/product/priceSync';
					break;
				case 'sync_inventory':
					$bodyParams = array(
						'source_product_ids' => array_map( 'sanitize_text_field', $postData['source_product_ids'] ),
						'action'             => 'inventory_sync',
						'source'             => array(
							'marketplace' => $headers['Ced-Source-Name'],
							'shopId'      => $headers['Ced-Source-Id'],
						),
						'target'             => array(
							'marketplace' => $headers['Ced-Target-Name'],
							'shopId'      => $headers['Ced-Target-Id'],
						),
						'mergeVariants'      => true,
						'limit'              => 500,
					);
					$url        = 'connector/product/inventorySync';
					break;
				case 'product_validate':
					$bodyParams = array(
						'source_product_ids' => array_map( 'sanitize_text_field', $postData['source_product_ids'] ),
						'action'             => 'product_validate',
						'source'             => array(
							'marketplace' => $headers['Ced-Source-Name'],
							'shopId'      => $headers['Ced-Source-Id'],
						),
						'target'             => array(
							'marketplace' => $headers['Ced-Target-Name'],
							'shopId'      => $headers['Ced-Target-Id'],
						),
						'operationType'      => 'product_validate',
						'mergeVariants'      => true,
					);
					$url        = 'webapi/rest/v1/aliexpress/product/validate';
					break;
				case 'online_products':
					$bodyParams = array(
						'source_product_ids' => array_map( 'sanitize_text_field', $postData['source_product_ids'] ),
						'action'             => 'online_products',
						'source'             => array(
							'marketplace' => $headers['Ced-Source-Name'],
							'shopId'      => $headers['Ced-Source-Id'],
						),
						'target'             => array(
							'marketplace' => $headers['Ced-Target-Name'],
							'shopId'      => $headers['Ced-Target-Id'],
						),
						'operationType'      => 'online_products',
						'mergeVariants'      => true,
					);
					$url        = 'webapi/rest/v1/aliexpress/product/online';
					break;
				case 'offline_products':
					$bodyParams = array(
						'source_product_ids' => array_map( 'sanitize_text_field', $postData['source_product_ids'] ),
						'action'             => 'offline_products',
						'source'             => array(
							'marketplace' => $headers['Ced-Source-Name'],
							'shopId'      => $headers['Ced-Source-Id'],
						),
						'target'             => array(
							'marketplace' => $headers['Ced-Target-Name'],
							'shopId'      => $headers['Ced-Target-Id'],
						),
						'operationType'      => 'offline_products',
						'mergeVariants'      => true,
					);
					$url        = 'webapi/rest/v1/aliexpress/product/offline';
					break;

				default:
					return 'undefined action';
			}
			$params = array(
				'headers' => $headers,
				'body'    => wp_json_encode( $bodyParams ),
			);
			$res    = $api_request->post( $url, $params );
			if ( ! empty( $res['data']['success'] ) ) {
				wp_redirect(
					admin_url( 'admin.php?page=ced_integration_aliexpress&section=activities' )
				);
				wp_die();
			}
			set_transient( 'CIFA_error_message', $res['data']['message'] );
			wp_redirect(
				admin_url( 'admin.php?page=ced_integration_aliexpress&section=products' )
			);
		}
	}
	public function handleGetRequest( $getData = array() ) {

		if ( ! empty( $getData['action'] ) ) {
			$api_request     = new CIFA_Api_Base();
			$common_callback = new CIFA_Common_Callback();
			$headers         = $common_callback->get_common_header();
			$action_name     = $getData['action'];
			switch ( $action_name ) {
				case 'product_import':
					$bodyParams = array(
						'source'      => array(
							'marketplace' => $headers['Ced-Source-Name'],
							'shopId'      => $headers['Ced-Source-Id'],
						),
						'target'      => array(
							'marketplace' => $headers['Ced-Target-Name'],
							'shopId'      => $headers['Ced-Target-Id'],
						),
						'marketplace' => $headers['Ced-Source-Name'],
					);
					$url        = 'connector/product/import';

					$params = array(
						'headers' => $headers,
						'body'    => wp_json_encode( $bodyParams ),
					);
					$res    = $api_request->post( $url, $params );
					break;
				case 'sync_status':
					$url    = 'webapi/rest/v1/aliexpress/product/status/sync';
					$params = array(
						'headers' => $headers,
					);
					$res    = $api_request->get( add_query_arg( 'marketplace', 'aliexpress', $url ), $params );
					break;
				case 'Upload':
					$url = 'webapi/rest/v1/aliexpress/product';
					if ( ! empty( $getData['profile_id'] ) ) {
						if ( 'default' == $getData['profile_id'] ) {
							$params = array(
								'headers' => $headers,
								'body'    => wp_json_encode(
									array(
										'create_notification' => false,
										'source'        => array(
											'marketplace' => $headers['Ced-Source-Name'],
											'shopId'      => $headers['Ced-Source-Id'],
										),
										'target'        => array(
											'marketplace' => $headers['Ced-Target-Name'],
											'shopId'      => $headers['Ced-Target-Id'],
										),
										'marketplace'   => $headers['Ced-Target-Name'],
										'mergeVariants' => true,
										'filter'        => array(
											'profile.profile_id' => array(
												12 => '0',
											),
										),
										'action'        => 'product_upload',
										'limit'         => 500,
									)
								),
							);
							$res    = $api_request->post( $url, $params );
						} else {
							$params = array(
								'headers' => $headers,
								'body'    => wp_json_encode(
									array(
										'source'        => array(
											'marketplace' => $headers['Ced-Source-Name'],
											'shopId'      => $headers['Ced-Source-Id'],
										),
										'target'        => array(
											'marketplace' => $headers['Ced-Target-Name'],
											'shopId'      => $headers['Ced-Target-Id'],
										),
										'mergeVariants' => true,
										'profile_id'    => ! empty( $getData['profile_id'] ) ? $getData['profile_id'] : '',
										'limit'         => 500,
										'action'        => 'product_upload',
									)
								),
							);
							$res    = $api_request->post( $url, $params );
						}
					}
					break;
				default:
					wp_redirect(
						admin_url( 'admin.php?page=ced_integration_aliexpress&section=products' )
					);
					break;
			}

			if ( ! empty( $res['data']['success'] ) ) {
				wp_redirect(
					admin_url( 'admin.php?page=ced_integration_aliexpress&section=activities' )
				);
				wp_die();
			}
			set_transient( 'CIFA_error_message', $res['data']['message'] );
			wp_redirect(
				admin_url( 'admin.php?page=ced_integration_aliexpress&section=products' )
			);
		}
	}
	public function display_html() {
		$getData  = isset( $_GET['aliexpress_nonce'] ) && wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_GET['aliexpress_nonce'] ) ),
			'aliexpress_nonce_field'
		) ? filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
		$postData = isset( $_POST['aliexpress_nonce'] ) && wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['aliexpress_nonce'] ) ),
			'aliexpress_nonce_field'
		) ? filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
		$this->handleGetRequest( $getData );
		$this->handlePostRequest( $postData );
		$this->handle_params( $postData );
		echo '<div class="CIFA-main-body">
		<div class="section">
		<form class="CIFA-product-grid" method="post">
		';
		wp_nonce_field(
			'aliexpress_nonce_field',
			'aliexpress_nonce'
		);
		echo '<div class="CIFA-product-main-heading">

		<div class="CIFA-section-title-header">
		<h1>Products</h1>
		';
		echo '<a href="' . esc_url(
			admin_url(
				'admin.php?page=ced_integration_aliexpress&section=products&action=sync_status'
			)
		) . '" class="components-button is-secondary">Sync Status</a>
		<a class="components-button is-primary upload_by_category" href="javascript:void(0)">Bulk Upload</a></div>';
		$this->prepare_items();
		$this->search_box( 'search', 'searchbtn' );
		$this->display();
		$categories = $this->get_category_templates();
		$header     = $this->commonCallback->get_common_header();

		$endpoint_p             = 'connector/product/getRefineProductCount?filter[profile.profile_name][12]=0';
		$header['Content-type'] = 'plain/text';
		$args_p                 = array(
			'sslverify' => false,
			'headers'   => $header,
		);
		$response_p             = $this->apiRequest->get( $endpoint_p, $args_p );
		$default_count          = 0;
		$all_profile_count      = 0;
		if ( ! empty( $response_p['data']['success'] ) ) {
			$default_count = $response_p['data']['data']['count'];
		}

		echo '</form></div></div>';
		echo '<div id="CIFA-myPopup" class="CIFA-modal-wrap bulk_pload_popup">
                        <div class="CIFA-content">
                            <span class="CIFA-close-button"><span class="dashicons dashicons-no-alt"></span></span>
                            <div class="CIFA-title-holder">
                                <p><b>Select a category template to upload product(s) to AliExpress.</b></p>
                            </div>
                            <div class="CIFA-popup-content-wrapper">
                                <div class="CIFA-popup-content">
                                    <form method="get" action="' . esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress&section=products' ) ) . '">
                                    <table class="form-table CIFA-table-content-width">
                                        <tbody>
                                            <tr>
                                                <td scope="row" class="row-title">
                                                    <label for="">Category Template</label>
                                                </td>
                                                <td class="forminp forminp-select">
                                                    <select style="width: 100%; margin-bottom:15px;" class="selected_category" name="profile_id" data-fieldid="">
                                                        <option data-product-count="0" value="">None</option>';

		foreach ( $categories as  $value ) {
			$all_profile_count = ! empty( $value['product_count'][0]['count'] ) ? esc_attr( $value['product_count'][0]['count'] ) : 0;
			echo '<option data-product-count="' . ( ! empty( $value['product_count'][0]['count'] ) ? esc_attr( $value['product_count'][0]['count'] ) : '0' ) .
				'" value=' . esc_attr( $value['_id']['$oid'] ) . '>' . esc_attr( $value['name'] ) . '</option>';
		}
		echo '<option data-product-count="' . esc_attr( $default_count - $all_profile_count ) . '" value="default">Default</option>';
		echo '</select>';
		echo '                                              
                                                    <p class="products_message" style="display:none">Are you sure you want to upload <span class="total_products">0</span> Product(s) ?</p>
                                                </td>
                                            </tr>
                                            <tr style="display:none;">
                                                 <td>
                                                        <input type="hidden" name="page" value="' .
			esc_attr( ! empty( $getData['page'] ) ? $getData['page'] : '' ) . '">
                                                        <input type="hidden" name="section" value="' .
			esc_attr( ! empty( $getData['section'] ) ? $getData['section'] : '' ) . '">

                                                 </td>       
                                            </tr>
                                            <tr>
                                                <hr>
                                                <td scope="row" class="row-title">
                                                    
                                                </td>
                                                <td class="forminp forminp-select">
                                                    <input disabled="true" style="cursor:not-allowed" class="components-button is-primary submit_upload" name="action" type="submit" value="Upload">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
	}
}
$products = new CIFA_Products();
$products->display_html();
