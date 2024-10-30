<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$getData         = filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$api_request     = new CIFA_Api_Base();
$common_callback = new CIFA_Common_Callback();
$headers         = $common_callback->get_common_header();
if ( isset( $getData['action'] ) && ( 'clear_activities' === $getData['action'] ) ) {
	$url    = 'connector/get/clearNotifications';
	$params = array(
		'headers' => $headers,
	);

	$api_request->get( $url, $params );
}
$url    = 'connector/get/allQueuedTasks';
$params = array(
	'headers' => $headers,
);

$response              = $api_request->get( $url, $params );
$ongoingActivitiesData = ! empty( $response['data']['data']['rows'] ) ? $response['data']['data']['rows'] : array();
$activePage            = empty( $getData['activePage'] ) ? 1 : (int) $getData['activePage'];
$count                 = ! empty( $getData['count'] ) ? (int) $getData['count'] : 5;

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

$response         = $api_request->post( $url, $params );
$allNotifications = ! empty( $response['data']['data']['rows'] ) ? $response['data']['data']['rows'] : array();
if ( empty( $allNotifications ) && empty( $ongoingActivitiesData ) ) {
	echo '<div class="CIFA-header-activities-wrap">
    <div class="CIFA-section-title-header"><h2>Activites</h2><a href="" class="button">Refresh</a></div>
    <div class="activity_section"><div class="components-card is-size-medium woocommerce-table">
    <div class="components-panel"><div class="wc-progress-form-content woocommerce-importer CIFA-padding2 CIFA-heading-margin">
    <h4>No Activities Found</h4></div></div></div></div>
    </div>';
} else {
	$totalRows = ! empty( $response['data']['data']['count'] ) ? $response['data']['data']['count'] : 0;
	$maxChunk  = ceil( $totalRows / $count );
	$chunk     = $totalRows && ( $totalRows > $count ) ? ( ( $count * ( $activePage - 1 ) ) . ' - ' . ( $count * $activePage ) ) : $totalRows;

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
	$options  = ( 5 == $count ) ? '<option value="5" selected>5</option>' : '<option value="5">5</option>';
	$options .= ( 10 == $count ) ? '<option value="10" selected>10</option>' : '<option value="10">10</option>';
	$options .= ( 20 == $count ) ? '<option value="20" selected>20</option>' : '<option value="20">20</option>';
	$options .= ( 50 == $count ) ? '<option value="50" selected>50</option>' : '<option value="50">50</option>';
	$options .= ( 100 == $count ) ? '<option value="100" selected>100</option>' : '<option value="100">100</option>';
	echo '<div class="CIFA-header-activities-wrap">
    <div class="CIFA-section-title-header"><h2>Activites</h2>
    <a href="javascript:void(0)" class="button refresh_activity">Refresh</a>
    <a href="' . esc_url( admin_url( 'admin.php?page=ced_integration_aliexpress&section=activities&action=clear_activities' ) ) . '" class="button">Clear Activities</a>
    </div>
    </div>
    <div class="activity_section">
    <div class="components-card is-size-medium woocommerce-table">
    <div class="components-panel">
        <div class="wc-progress-form-content woocommerce-importer CIFA-padding2 CIFA-heading-margin">
            <h4>Ongoing Activities</h4><div class="ongoing_activity">
            ';
	foreach ( $ongoingActivitiesData as $key => $value ) {
		echo '<div class="CIFA-select-block">
                        <div class="CIFA-flex-common">
                            <span><b>' . esc_attr( $value['message'] ) . '</b></span><span class="CIFA-activities-color">' . esc_attr( $value['created_at'] ) . '</span>
                        </div>
                        <progress style="width: 100%; height: 30px;" class="woocommerce-task-progress-header__progress-bar" max="100" value="' . esc_attr( $value['progress'] ) . '"></progress>
            </div>';
	}
	echo '</div></div>
        </div>
    </div>
    </div>
    <div class="components-card is-size-medium woocommerce-table">
        <div class="components-panel">
            <div class="wc-progress-form-content woocommerce-importer CIFA-padding2 CIFA-heading-margin">
                <h4>Completed Activities</h4>
                <div class="completed_activities">
                ';
	foreach ( $allNotifications as $key => $value ) {
		echo '<div class="CIFA-select-block CIFA-bottom-border">
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
	echo '</div>

            </div>
            <div class="CIFA-pagination-wrapper activity_pagination">
                <div class="CIFA-pagination-native">
                    <div class="CIFA-pagination-left-container">
                        <span>Item: <select class="total_rows_per_page">
                                        ';
	print_r( $options );
	echo '
                                    </select> showing ' . esc_attr( $chunk ) . ' of ' . esc_attr( $totalRows ) . '</span>
                    </div>
                    <div class="CIFA-right-container">
                        <div class="CIFA-pagination-common-right"><span class="CIFA-prev">' . wp_kses( $leftArrow, $allowed_html ) . '</span><input type="text" class="active_page" value="' . esc_attr( $activePage ) . '" placeholder="1"> of ' . esc_attr( $maxChunk ) . ' <span class="CIFA-paginatrion-right">' . wp_kses( $rightArrow, $allowed_html ) . '</span></div>

                    </div>
                </div>
            </div>
        </div>
    </div>';
}
