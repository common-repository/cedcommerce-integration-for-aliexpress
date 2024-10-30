<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class CIFA_Orders extends WP_List_Table {

	public $requestData;
	public $plugin_name;
	public $version;
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'Order',     // Singular name
				'plural'   => 'Order',    // Plural name
				'ajax'     => false,      // Enable AJAX handling
			)
		);
		$this->plugin_name = CIFA_PLUGIN_NAME;
		$this->version     = CIFA_VERSION;
		$this->requestData = isset( $_REQUEST['aliexpress_nonce'] ) && wp_verify_nonce(
			wp_unslash( sanitize_text_field( $_REQUEST['aliexpress_nonce'] ) ),
			'aliexpress_nonce_field'
		) ? $_REQUEST : $_REQUEST;
	}
	public function get_columns() {
		return array(
			'woocommerce_order_id' => __( 'Woocommerce Order Id', 'cedcommerce-integration-for-aliexpress' ),
			'order_id'             => __( 'AliExpress Order ID', 'cedcommerce-integration-for-aliexpress' ),
			'customer_name'        => __( 'Customer Name', 'cedcommerce-integration-for-aliexpress' ),
			'skus'                 => __( "SKU's", 'cedcommerce-integration-for-aliexpress' ),
			'created_at'           => __( 'Created At', 'cedcommerce-integration-for-aliexpress' ),
			'price'                => __( 'Price', 'cedcommerce-integration-for-aliexpress' ),
			'order_status'         => __( 'Order Status', 'cedcommerce-integration-for-aliexpress' ),
			'actions'              => __( 'Actions', 'cedcommerce-integration-for-aliexpress' ),
		);
	}
	public function column_default( $item, $column_name ) {

		return $item[ $column_name ];
	}
	public function get_sortable_columns() {
		return array();
	}

	public function prepare_items() {
		$user_data = json_decode( get_option( 'CIFA_user_data' ) );
		$query     = array(
			'filter' => array(
				'object_type' => array( 1 => 'source_order' ),
				'marketplace' => array( 1 => 'aliexpress' ),
				'shop_id'     => array( 1 => $user_data->target_shop_id ),
			),
		);
		$postData  = $this->requestData;
		if ( ! empty( $postData['status'] ) ) {
			$query['filter']['marketplace_status'][1] = sanitize_text_field( $postData['status'] );
		}
		if ( ! empty( $postData['aliexpress_order_id'] ) ) {
			$query['filter']['marketplace_reference_id'][3] = sanitize_text_field( $postData['aliexpress_order_id'] );
		}
		if ( ! empty( $postData['customer_name'] ) ) {
			$query['filter']['customer.name'][3] = sanitize_text_field( $postData['customer_name'] );
		}
		if ( ! empty( $postData['product_sku'] ) ) {
			$query['filter']['items.sku'][3] = sanitize_text_field( $postData['product_sku'] );
		}
		$orders = $this->get_data( $query );
		$data   = $orders['data'];

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$count                 = ! empty( $postData['count'] ) ? sanitize_text_field( $postData['count'] ) : 10;
		$per_page              = $this->get_items_per_page( 'items_per_page', $count );
		$total_items           = $orders['count'];

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
		$postData   = $this->requestData;
		$count      = ! empty( $postData['count'] ) ? (int) sanitize_text_field( $postData['count'] ) : 10;
		$activePage = ! empty( $postData['paged'] ) ? (int) sanitize_text_field( $postData['paged'] ) : 1;
		$bodyParams = array(
			'count'      => $count,
			'activePage' => $activePage,
		);
		if ( ! empty( $query ) ) {
			$bodyParams = array_merge( $bodyParams, $query );
		}
		$url             = 'connector/order/getAll';
		$api_request     = new CIFA_Api_Base();
		$common_callback = new CIFA_Common_Callback();
		$headers         = $common_callback->get_common_header();
		$params          = array(
			'headers' => $headers,
			'body'    => wp_json_encode( $bodyParams ),
		);
		$response        = $api_request->post( $url, $params );
		$orders          = $response['data'];
		$rows            = ! empty( $orders['data']['rows'] ) ? $orders['data']['rows'] : array();
		$orderArray      = array();
		foreach ( $rows as $value ) {
			$newArray                         = array();
			$newArray['order_id']             = ! ( empty( $value['marketplace_reference_id'] ) ) ?
				esc_attr( $value['marketplace_reference_id'] ) : '';
			$newArray['woocommerce_order_id'] = ! empty( $value['targets'][0]['order_id'] ) ?
				'<a href="' . esc_url(
					get_edit_post_link(
						$value['targets'][0]['order_id']
					)
				) .
				'">' . esc_attr( $value['targets'][0]['order_id'] ) . '</a>' : 'N/A';
			$newArray['customer_name']        = ! empty( $value['customer']['name'] ) ? esc_attr( $value['customer']['name'] ) : '';
			if ( ! empty( $value['created_at'] ) ) {
				$date                   = new DateTime( $value['created_at'] );
				$newArray['created_at'] = $date->format( 'Y-m-d H:i:s' );
			} else {
				$newArray['created_at'] = '';
			}
			$newArray['price'] = ! ( empty( $value['total']['price'] ) ) ? esc_attr( $value['total']['price'] ) : '';
			$errorCheck        = empty( $value['targets'][0]['order_id'] ) || ( ! empty( $value['shipment_error'] ) &&
				'created' == strtolower( $value['status'] ) && 'failed' == $value['shipping_status'] ) ? true : false;

			if ( ! empty( $value['status'] ) ) {
				$status_order = ! empty( $value['status'] ) ? esc_attr( ucfirst( $value['marketplace_status'] ) ) : '';
				if ( $errorCheck ) {
					$status_order .= '<div class="ced-disconnected-button-wrap">
                            <a href="javascript:void(0)" class="error_status ced-connected-link" data-error-id="'
						. $value['_id']['$oid'] . '"><span class="ced-circle"></span>'
						. ( empty( $value['targets'][0]['order_id'] ) ? $value['targets'][0]['status'] :
							$value['shipping_error'] ) . '</a>';
					echo '<div id="CIFA-myPopup" class="CIFA-modal-wrap error-modal-' .
						esc_attr( $value['_id']['$oid'] ) . '">
                                <div class="CIFA-content">
                                    <span class="CIFA-close-button" data-error-id="' . esc_attr( $value['_id']['$oid'] ) .
						'"><span class="dashicons dashicons-no-alt"></span></span>
                                    <div class="CIFA-title-holder">
                                        <h3>Order Errors</h3>
                                    </div>
                                    <div class="CIFA-popup-content-wrapper">
                                        <div class="CIFA-popup-content">
                                        <div class="CIFA-product-errors">
                                        <ul>
                                            ';
					if ( ! empty( $value['targets'][0]['errors'] ) ) {
						foreach ( $value['targets'][0]['errors'] as $val ) {
							echo '<li>' . esc_attr( $val ) . '</li>';
						}
					} elseif ( ! empty( $value['shipment_error'] ) ) {
						foreach ( $value['shipment_error'][0]['errors'] as $val ) {
							echo '<li>' . esc_attr( $val ) . '</li>';
						}
					}

					echo '</ul>
                                    </div>
                                        </div>
                                    </div>
                                </div>
                        </div>';
				}
			}
			$newArray['skus'] = '';
			foreach ( $value['items'] as $item ) {
				$sku               = '<span>' . $item['sku'] . '</span>';
				$newArray['skus'] .= ! empty( $newArray['skus'] ) ? '<br>' . esc_attr( $sku ) : esc_attr( $sku );
			}
			$newArray['order_status'] = $status_order;
			$newArray['actions']      = ! $errorCheck ? '' : '<a href="' . esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress&section=orders&action=create&order_id=' . $value['marketplace_reference_id'] . '&user_id=' . $value['user_id'] ) ) . '">Create</a>';

			$orderArray[] = $newArray;
		}
		return array(
			'data'  => $orderArray,
			'count' => $orders['data']['count'],
		);
	}
	public function extra_tablenav( $which ) {

		if ( 'top' == $which ) {
			$postData = $this->requestData;
			if (
				isset( $postData['aliexpress_nonce'] ) &&
				! wp_verify_nonce( wp_unslash( $postData['aliexpress_nonce'] ), 'aliexpress_nonce_field' )
			) {

				wp_die( 'Unauthorized Access' );
			}
			$aliexpressOrderId = '';
			if ( ! empty( $postData['aliexpress_order_id'] ) ) {
				$aliexpressOrderId = $postData['aliexpress_order_id'];
			}
			$customerName = '';
			if ( ! empty( $postData['customer_name'] ) ) {
				$customerName = $postData['customer_name'];
			}
			$sku = '';
			if ( ! empty( $postData['product_sku'] ) ) {
				$sku = $postData['product_sku'];
			}
			echo '<input type="text" placeholder="Customer Name" value="' . esc_attr( $customerName ) . '" name="customer_name">';
			echo '<input type="text" placeholder="AliExpress Order ID" value="' . esc_attr( $aliexpressOrderId ) . '" name="aliexpress_order_id">';
			echo '<input type="text" placeholder="Product SKU" value="' . esc_attr( $sku ) . '" name="product_sku">';

			$count = '';
			if ( ! empty( $postData['count'] ) ) {
				$count = (int) $postData['count'];
			}
			$status = ! empty( $postData['status'] ) ? $postData['status'] : '';
			echo '<select name="status" class="CIFA-nav-select-btn">
                    <option value="">Status</option>
                    <option value="WAIT_SELLER_SEND_GOODS" ' . selected( 'WAIT_SELLER_SEND_GOODS' == $status, true, false ) . '>Wait Seller Send Goods</option>
                    <option value="SELLER_PART_SEND_GOODS" ' . selected( 'SELLER_PART_SEND_GOODS' == $status, true, false ) . '>Seller Part Send Goods</option>
                    <option value="SHIPPED" ' . selected( 'SHIPPED' == $status, true, false ) . '>Shipped</option>
                    <option value="FINISHED" ' . selected( 'FINISHED' == $status, true, false ) . '>Finished</option>
                    </select>';
			echo '<select name="count" class="CIFA-nav-select-btn">
                    <option value="10" ' . selected( 10 == $count, true, false ) . '>10</option>
                    <option value="20" ' . selected( 20 == $count, true, false ) . '>20</option>
                    <option value="50" ' . selected( 50 == $count, true, false ) . '>50</option>
                    <option value="100" ' . selected( 100 == $count, true, false ) . '>100</option>

                    </select>';
			submit_button( 'Filter', 'button', 'filter_action', false );
			if ( $aliexpressOrderId || $customerName || $sku || ! empty( $postData['s'] ) || $count || $status ) {
				echo '&nbsp;';
				submit_button( 'Reset', 'primary', 'reset_filter', false );
			}
		}
	}
	public function handle_params( $postData = array() ) {
		if ( isset( $postData['aliexpress_nonce'] ) && ! wp_verify_nonce( wp_unslash( $postData['aliexpress_nonce'] ), 'aliexpress_nonce_field' ) ) {

			wp_die( 'Unauthorized Access' );
		}
		if ( ! empty( $postData ) ) {
			$allowed_filters = array( 'aliexpress_order_id', 'customer_name', 'product_sku', 's', 'filter_action', 'status', 'aliexpress_nonce' );
			$params          = array();
			foreach ( $postData as $key => $value ) {
				if ( in_array( $key, $allowed_filters ) ) {
					$params[ $key ] = $value;
				}
			}
			if ( ! empty( $params ) ) {
				wp_redirect(
					add_query_arg( $params, admin_url( 'admin.php?page=ced_integration_aliexpress&section=orders' ) )
				);
			}
		}
	}
	public function handleGetRequest( $getData = array() ) {
		if ( ! empty( $getData['action'] ) ) {
			$api_request     = new CIFA_Api_Base();
			$common_callback = new CIFA_Common_Callback();
			$headers         = $common_callback->get_common_header();
			$tok             = json_decode( get_option( 'CIFA_token_data' ) )->token;
			$tokenParts      = explode( '.', $tok );
			$tokenHeader     = base64_decode( $tokenParts[0] );
			$tokenPayload    = base64_decode( $tokenParts[1] );
			$jwtPayload      = json_decode( $tokenPayload );
			$user_id         = $jwtPayload->user_id;
			$action          = $getData['action'];
			$params          = array(
				'headers' => $headers,
			);
			if ( 'Sync' === $action ) {
				$url = 'webapi/rest/v1/aliexpress/order/fetchOrderDateRange';
				$res = $api_request->get(
					add_query_arg(
						array(
							'shop_id'    => $headers['Ced-Target-Id'],
							'appTag'     => 'aliexpress_connector',
							'user_id'    => $user_id,
							'end_date'   => ( ! empty( $getData['to_date'] ) ?
								$getData['to_date'] : gmdate( 'Y-m-d' )
								. ' 23:59:59' ),
							'start_date' => ( ! empty( $getData['from_date'] ) ?
								$getData['from_date'] : gmdate( 'Y-m-d' )
								. ' 00:00:00' ),
						),
						$url
					),
					$params
				);

				if ( empty( $res['data']['success'] ) ) {
					set_transient( 'CIFA_error_message', esc_attr( $res['data']['msg'] ) );
					wp_redirect(
						admin_url( 'admin.php?page=ced_integration_aliexpress&section=orders' )
					);
				} else {
					wp_redirect(
						admin_url( 'admin.php?page=ced_integration_aliexpress&section=activities' )
					);
				}
				exit;
			} elseif ( 'create' === $action ) {
				$url = 'webapi/rest/v1/aliexpress/createSingleOrder';
				if ( ! empty( $getData['order_id'] ) && ! empty( $getData['user_id'] ) ) {
					$params['body'] = wp_json_encode(
						array(
							'order_id' => $getData['order_id'],
							'user_id'  => $getData['user_id'],
						)
					);
					$res            = $api_request->post( $url, $params );
					if ( empty( $res['data']['success'] ) ) {
						set_transient( 'CIFA_error_message', esc_attr( $res['data']['msg'] ) );
					} else {
						set_transient( 'CIFA_success_message', esc_attr( $res['data']['msg'] ) );
					}
					wp_redirect(
						admin_url( 'admin.php?page=ced_integration_aliexpress&section=orders' )
					);
					exit;
				} else {
					set_transient( 'CIFA_error_message', 'Order Id or User Id are missing' );
				}
			}
		}
	}
	public function handlePostRequest( $postData = array() ) {
		if ( ! empty( $postData['reset_filter'] ) ) {
			wp_redirect( admin_url( 'admin.php?page=ced_integration_aliexpress&section=orders' ) );
			wp_die();
		}
	}
	public function render_html() {
		$getData  = isset( $_GET['aliexpress_nonce'] ) && wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_GET['aliexpress_nonce'] ) ),
			'aliexpress_nonce_field'
		) ? filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
		$postData = isset( $_POST['aliexpress_nonce'] ) && wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['aliexpress_nonce'] ) ),
			'aliexpress_nonce_field'
		) ? filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : array();
		$this->handleGetRequest( $this->requestData );
		$this->handlePostRequest( $this->requestData );
		$this->handle_params( $this->requestData );
		echo '<div class="CIFA-main-body">
            <div class="section">
                <div class="CIFA-product-main-heading">
                    <div class="CIFA-section-title-header">
                    <h1>Orders</h1>
                    <a class="components-button is-primary sync_order_by_date" href="javascript:void(0)">Sync Order</a>
                    <div id="CIFA-myPopup" class="CIFA-modal-wrap sync_order_popup">
                        <div class="CIFA-content">
                            <span class="CIFA-close-button"><span class="dashicons dashicons-no-alt"></span></span>
                            <div class="CIFA-title-holder">
                                <p><b>Sync orders by date range.</b></p>
                            </div>
                            <div class="CIFA-popup-content-wrapper">
                                <div class="CIFA-popup-content CIFA-popup-content-wrap-common">
                                    <form method="GET" action="' . esc_url(
			admin_url( 'admin.php?page=ced_integration_aliexpress&section=orders' )
		) . '">
                                    <table class="form-table CIFA-table-content-width">
                                        <tbody>
                                            <tr>
                                                <th scope="row" class="row-title">
                                                    <label for="from_date">From Date</label>
                                                </th>
                                                <td class="forminp forminp-select">
                                                    <input type="text" id="from_date" name="from_date" class="datepicker from_date">
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row" class="row-title">
                                                    <label for="to_date">To Date</label>
                                                </th>
                                                <td class="forminp forminp-select">
                                                    <input type="text" id="to_date" name="to_date" class="datepicker to_date">
                                                    <p class="order_error"></p> 
                                                </td>
                                            </tr>
                                            <tr style="display:none;">
                                                <td>
                                                        <input type="hidden" name="page" value="' .
			esc_attr( ! empty( $this->requestData['page'] ) ? $this->requestData['page'] : '' ) . '">
                                                        <input type="hidden" name="section" value="' .
			esc_attr( ! empty( $this->requestData['section'] ) ? $this->requestData['section'] : '' ) . '">

                                                </td>
                                            </tr>
                                            <tr>
                                                <hr>
                                                <td scope="row" class="row-title">
                                                    
                                                </td>
                                                <td class="forminp forminp-select">
                                                    <input disabled="true" style="cursor:not-allowed" 
													class="components-button is-primary sync_order" name="action" type="submit" value="Sync">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>                
                </div>
            <form method="post">';
		$this->prepare_items();
		$this->display();
		wp_nonce_field(
			'aliexpress_nonce_field',
			'aliexpress_nonce'
		);
		echo '</form></div></div>';
	}
}
$orders = new CIFA_Orders();
$orders->render_html();
