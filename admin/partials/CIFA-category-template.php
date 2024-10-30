<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CIFA_Category_Template_List_Table extends WP_List_Table {

	public $requestData;
	public $apiRequest;
	public $commonCallback;
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

		$this->apiRequest     = new CIFA_Api_Base();
		$this->commonCallback = new CIFA_Common_Callback();
	}

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$perPage     = 5;
		$query       = array();
		$requestData = $this->requestData;
		if ( ! empty( $requestData['count'] ) ) {
			$perPage = (int) sanitize_text_field( $requestData['count'] );
		}
		$activePage = 1;
		if ( ! empty( $requestData['paged'] ) ) {
			$activePage = (int) sanitize_text_field( $requestData['paged'] );
		}
		if ( ! empty( $requestData['s'] ) ) {
			$query['name'] = wp_unslash( sanitize_text_field( $requestData['s'] ) );
		}
		$totalItems           = $this->get_profile_count( $query )['total_count'];
		$query['active_page'] = $activePage;
		$query['count']       = $perPage;
		$data                 = $this->table_data( $query );
		usort( $data, array( &$this, 'sort_data' ) );

		$currentPage = $this->get_pagenum();

		$this->set_pagination_args(
			array(
				'total_items' => $totalItems,
				'per_page'    => $perPage,
				'total_pages' => ceil( $totalItems / $perPage ),
			)
		);
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array
	 */
	public function get_columns() {
		$columns = array(
			'name'           => 'Name',
			'category'       => 'Category',
			'rules'          => 'Rules',
			'total_products' => 'Total Products',
		);

		return $columns;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Define the sortable columns
	 *
	 * @return Array
	 */
	public function get_sortable_columns() {
		return array( 'name' => array( 'name', false ) );
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data( $query = array() ) {
		$data     = array();
		$admin    = new CIFA_Admin( $this->plugin_name, $this->version );
		$header   = $this->commonCallback->get_common_header();
		$endpoint = 'connector/profile/getProfileData';
		$args     = array(
			'headers' => $header,
		);
		$response = $this->apiRequest->get( add_query_arg( $query, $endpoint ), $args );
		if ( ! empty( $response['data']['data']['rows'] ) ) {
			$list_of_category_template = $response['data']['data']['rows'];
			foreach ( $list_of_category_template as $key => $value ) {
				$rule_html  = '<div id="CIFA-myPopup" class="CIFA-modal-wrap">
                    <div class="CIFA-content">
                      <span class="CIFA-close-button"><span class="dashicons dashicons-no-alt"></span></span>
                     <div class="CIFA-title-holder">
                         <h3>Rule Group</h3>
                     </div>
                     <div class="CIFA-popup-content-wrapper">';
				$rule_query = $value['query'];
				if ( ! empty( $rule_query ) ) {
					$rules = $admin->extract_price_rule_query( $rule_query );
					foreach ( $rules as $rule ) {
						$rule_html .= '<div class="CIFA-popup-content">
                            <p>' . $rule['key'] . '</p>
                            <p><b>' . ucfirst( str_replace( '_', ' ', $rule['operator'] ) ) . '</b></p>
                            <p>' . $rule['value'] . '</p>
                            </div>
                            <hr>';
					}
				} else {
					$rule_html .= '<div class="CIFA-popup-content">
                                    <p><b>No Rules Available</b></p></div>';
				}

				$rule_html .= '</div>
                    </div>
                  </div>';
				if ( ! empty( $value['product_count'][0]['count'] ) ) {
					$prod_count = $value['product_count'][0]['count'];
				} else {
					$prod_count = 0;
				}
				$data[] = array(
					'name'           => '<a href="admin.php?page=ced_integration_aliexpress&section=category-template&sub-section=create_edit_template&action=edit&id=' . $value['_id']['$oid'] . '">' . urldecode( $value['name'] ) . '</a>',
					'category'       => $value['category_id']['label'],
					'rules'          => '<a class="CIFA-Btn" href="javascript:void(0)">View Rule</a>' . $rule_html,
					'total_products' => $prod_count,
				);
			}
		}

		return $data;
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  Array  $item        Data
	 * @param  String $column_name - Current column name
	 *
	 * @return Mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
			case 'category':
			case 'rules':
			case 'total_products':
				return $item[ $column_name ];

			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data( $a, $b ) {
		// Set defaults
		$orderby     = 'name';
		$order       = 'asc';
		$requestData = $this->requestData;
		// If orderby is set, use this as the sort column
		if ( ! empty( $requestData['orderby'] ) ) {
			$orderby = wp_unslash( sanitize_text_field( $requestData['orderby'] ) );
		}

		// If order is set use this as the order
		if ( ! empty( $requestData['order'] ) ) {
			$order = wp_unslash( sanitize_text_field( $requestData['order'] ) );
		}

		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		if ( 'asc' === $order ) {
			return $result;
		}

		return -$result;
	}

	public function column_name( $item ) {
		$name_data = substr( $item['name'], 2 );
		$name_href = explode( '&', $name_data );
		$templ_id  = '';
		foreach ( $name_href as $key => $value ) {
			$value = explode( '=', $value );
			if ( 'id' == $value[0] ) {
				$templ_id = $value[1];
				$templ_id = explode( '">', $templ_id );
				$templ_id = $templ_id[0];
			}
		}
		$requestData = $this->requestData;
		$actions     = array(
			'edit'   => sprintf(
				'<a href="?page=%s&section=category-template&sub-section=create_edit_template&action=%s&id=%s">Edit</a>',
				isset( $requestData['page'] ) ? wp_unslash( sanitize_text_field( $requestData['page'] ) ) : '',
				'edit',
				$templ_id
			),
			'delete' => sprintf(
				'<a href="?page=%s&section=category-template&action=%s&id=%s">Delete</a>',
				isset( $requestData['page'] ) ? wp_unslash( sanitize_text_field( $requestData['page'] ) ) : '',
				'delete',
				$templ_id
			),
		);
		return sprintf( '%1$s %2$s', $item['name'], $this->row_actions( $actions ) );
	}

	public function get_profile_count( $query = array() ) {
		$common_callback        = new CIFA_Common_Callback();
		$header                 = $common_callback->get_common_header();
		$endpoint               = 'connector/profile/getProfileDataCount';
		$header['Content-type'] = 'plain/text';
		$args                   = array(
			'sslverify' => false,
			'headers'   => $header,
		);
		return $this->apiRequest->post( add_query_arg( $query, $endpoint ), $args )['data'];
	}
	public function display_html() {
		$getData  = map_deep( $_GET, 'sanitize_text_field' );
		$templ_id = '';
		$header   = $this->commonCallback->get_common_header();
		if ( isset( $getData['action'] ) && 'delete' == $getData['action'] && isset( $getData['id'] ) ) {
			$templ_id = wp_unslash( $getData['id'] );
			$endpoint = 'connector/profile/deleteProfile';
			$args     = array(
				'sslverify' => false,
				'headers'   => $header,
				'body'      => array(
					'id' => $templ_id,
				),
			);
			$this->apiRequest->get( $endpoint, $args );
			wp_redirect( admin_url( 'admin.php?page=ced_integration_aliexpress&section=category-template' ) );
			wp_die();
		}
		echo '<div class="CIFA-category-header-wrap">
			<div class="CIFA-category-content">
				<h1>Category Template</h1>
				<p>Category Templates simplify the process of uploading and managing products on AliExpress.</p>
			</div>
			<div class="CIFA-category-button-wrap">
				<a class="components-button is-secondary" href="admin.php?page=ced_integration_aliexpress&section=category-template&sub-section=edit_default_template">Edit Default Template</a>
				<a class="components-button is-primary" href="admin.php?page=ced_integration_aliexpress&section=category-template&sub-section=create_edit_template">Create Template</a>
			</div>
		</div>';
		$this->prepare_items();
		echo '<form action="" method="POST">';

		$this->search_box( 'search', 'searchbtn' );
		$this->display();
		wp_nonce_field(
			'aliexpress_nonce_field',
			'aliexpress_nonce'
		);
		echo '</form>';
	}
}

$obj = new CIFA_Category_Template_List_Table();
$obj->display_html();
