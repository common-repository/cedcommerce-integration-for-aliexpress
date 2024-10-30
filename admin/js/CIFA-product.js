(function ($) {
	'use strict';

	var ajax_url   = CIFA_obj.ajax_url;
	var ajax_nonce = CIFA_obj.ajax_nonce;
	$( document ).on(
		'change',
		'.data_sync_enable',
		function () {
			var data_sync_enable = $( this ).is( ":checked" ) ? true : false;
			if (data_sync_enable) {
				$( ".product_edit_fields" ).css( "pointer-events", "none" )
				$( ".product_edit_fields" ).css( "background-color", "#f0f0f0 !important" )
				$( ".product_edit_fields" ).css( "color", "#999 !important" )
			} else {
				$( ".product_edit_fields" ).css( "pointer-events", "auto" )
				$( ".product_edit_fields" ).css( "background-color", "#fff" )
				$( ".product_edit_fields" ).css( "color", "#000" )
			}
		}
	);
	$( ".data_sync_enable" ).ready(
		function () {
			var data_sync_enable = $( ".data_sync_enable" ).is( ":checked" ) ? true : false;
			if (data_sync_enable) {
				$( ".product_edit_fields" ).css( "pointer-events", "none" )
				$( ".product_edit_fields" ).css( "background-color", "#f0f0f0 !important" )
				$( ".product_edit_fields" ).css( "color", "#999 !important" )
			} else {
				$( ".product_edit_fields" ).css( "pointer-events", "auto" )
				$( ".product_edit_fields" ).css( "background-color", "#fff" )
				$( ".product_edit_fields" ).css( "color", "#000" )
			}

		}
	);
	$( document ).on(
		'input',
		'.CIFA-number-input',
		function () {
			var value = $( this ).val();
			value     = value.replace( /\D/g, '' );
			$( this ).val( value );
		}
	);
})( jQuery );