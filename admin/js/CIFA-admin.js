(function ($) {
	'use strict';

	var ajax_url = CIFA_obj.ajax_url;
	var ajax_nonce = CIFA_obj.ajax_nonce;


	$(document).on(
		'click',
		'.ced_connector_aliexpress_manual_table',
		function () {
			if ($('#connector_dev').is(':checked')) {
				$('.pre-connectForm').toggle();
				$('.CIFA_connect_button').hide();
			} else {
				$('.pre-connectForm').hide();
				$('.CIFA_connect_button').show();
			}
		}
	);

	$(document).on(
		'click',
		'.CIFA_connect_button',
		function () {
			$('.CIFA-loader').show();
			$.ajax(
				{
					url: ajax_url,
					data: {
						ajax_nonce: ajax_nonce,
						action: 'CIFA_connect_account',
						terms_and_conditions: $(".CIFA-terms-conditions").is(":checked") ? true : false
					},
					type: 'POST',
					success: function (response) {
						$('.CIFA-loader').hide();
						response = jQuery.parseJSON(response);
						window.location.href = response.auth_url;
					}
				}
			);
		}
	);
	$(document).on(
		'click',
		'.CIFA-terms-conditions',
		function () {
			if ($(this).is(":checked")) {
				$(".CIFA_connect_button").removeClass("CIFA-readonly");
			} else {
				$(".CIFA_connect_button").addClass("CIFA-readonly");
			}
		}
	);

	$(document).on(
		'click',
		'.CIFA_manual_connect_button',
		function () {
			var consumer_key = $(document).find('.ced_connector_consumer_key').val();
			var consumer_secret = $(document).find('.ced_connector_consumer_secret').val();
			$('.CIFA-loader').show();
			$.ajax(
				{
					url: ajax_url,
					data: {
						ajax_nonce: ajax_nonce,
						consumer_key: consumer_key,
						consumer_secret: consumer_secret,
						action: 'CIFA_manual_connect_account',
					},
					type: 'POST',
					success: function (response) {
						$('.CIFA-loader').hide();
						response = jQuery.parseJSON(response);
						if (response.status == 200) {
							window.location.href = response.auth_url;
						} else {
							var html = '<div class="notice notice-error is-dismissible"><p><strong >' + response.message + '</strong>          </p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
							$('.error_success_notification_display').show();
							$('.error_success_notification_display').html(html);
							$(document).scrollTop(0);
						}

					}
				}
			);
		}
	);

	$(document).on(
		'click',
		'#onboarding_step2_submit',
		function (e) {
			e.preventDefault();
			var supplier_id = $('#supplier_id').val();
			var api_key = $('#api_key').val();
			var secret_key = $('#secret_key').val();
			$('.CIFA-loader').show();
			$.ajax(
				{
					url: ajax_url,
					data: {
						ajax_nonce: ajax_nonce,
						supplier_id: supplier_id,
						api_key: api_key,
						secret_key: secret_key,
						action: 'CIFA_onboarding_step2',
					},

					type: 'POST',
					success: function (response) {
						$('.CIFA-loader').hide();
						if (isValidURL(response)) {
							var path = response;
							window.location.href = path;
						} else {
							var error_message = JSON.parse(response)['error_message'];
							var html = '<div class="notice notice-error is-dismissible"><p><strong >' + error_message + '</strong>          </p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
							$('.error_success_notification_display').show();
							$('.error_success_notification_display').html(html);
							$(document).scrollTop(0);
						}

					}
				}
			);
		}
	);

	function isValidURL(string) {
		var res = string.match(/(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/g);
		return (res !== null)
	};



	$(document).on(
		'change',
		'#get_target_categories',
		function (e) {
			e.preventDefault();
			if ('--Select any Category--' != $('#get_target_categories').val()) {
				var category_name = jQuery("#get_target_categories").find("option[value='" + $('#get_target_categories').val() + "']").text();
				$('#aliexpress_default_category_name').val(category_name);
				$(".CIFA_selected_category_section").show()
				$(".CIFA_selected_category_name").html(category_name)
				$('.aliexpress_attribute_mapping_default_profiling').css('display', 'block');
				var category_id = $('#get_target_categories').val();
				$('.aliexpress_attribute_section_mapping').html('');
				$('.CIFA-loader').show();
				$.ajax(
					{
						url: ajax_url,
						data: {
							ajax_nonce: ajax_nonce,
							category_id: category_id,
							action: 'CIFA_get_values_for_attr_mapping_default_template',
						},
						type: 'POST',
						success: function (response) {

							$('.CIFA-loader').hide();
							$('.aliexpress_attribute_section_mapping').html(response);
							$('.select2-with-checkbox-brand').select2({
								templateResult: function (data, container) {
									if (data.element) {
										$(container).prepend($(".input_chkbox_group").html());
									}
									return data.text;
								}
							});
							$('.select2-checkbox').on('click', function (e) {
								e.stopPropagation();
							});
							$(".select2-with-checkbox").select2({ closeOnSelect: false });
							$('#get_target_categories').val(category_id);
						}
					}
				);

			} else {
				$('.aliexpress_attribute_mapping_default_profiling').css('display', 'none');
				$('.aliexpress_attribute_section_mapping').html('<div></div>');
				$('#aliexpress_default_category_name').val('');
			}
		}
	);
	$(document).on(
		'select2:open',
		'#get_target_categories',
		function (e) {
			e.preventDefault();
			let timeoutID = null;
			$('.select2-search__field').on('input', function (event) {
				clearTimeout(timeoutID);
				var enteredText = event.target.value;
				var data = {
					ajax_nonce: ajax_nonce,
					action: 'CIFA_get_all_categories',
				};
				if (enteredText.length >= 3) {
					data.search = enteredText;
				}
				timeoutID = setTimeout(() => {
					$.ajax(
						{
							url: ajax_url,
							data: data,
							type: 'POST',
							success: function (response) {
								var res = JSON.parse(response);
								if (res.success) {
									$("#get_target_categories").html(res.html)
									$("#get_target_categories").select2()
									$("#get_target_categories").val('').trigger('change.select2')
									$("#get_target_categories").select2("open")
									$('#get_target_categories').data('select2').dropdown.$search.val(enteredText);
								}
							}
						}
					);
				}, 500);
			});
		}
	);
	$(document).ready(function ($) {
		$('.select2-with-checkbox').select2({
			templateResult: function (data, container) {
				if (data.element) {
					$(container).prepend($(".input_chkbox_group").html());
				}
				return data.text;
			}
		});
		$('.select2-checkbox').on('click', function (e) {
			e.stopPropagation();
		});
	});


	$(document).ready(function ($) {
		$('.select2-with-checkbox-brand').select2({
			templateResult: function (data, container) {
				if (data.element) {
					$(container).prepend($(".input_chkbox_group").html());
				}
				return data.text;
			}
		});
		$('.select2-checkbox').on('click', function (e) {
			e.stopPropagation();
		});
	});


	$(document).on(
		'click',
		'.aliexpress_products_attributes_label',
		function (e) {
			e.preventDefault();
			if (!$('.aliexpress_products_attributes_values').hasClass('active')) {
				$('.aliexpress_products_attributes_values').addClass('active');
				$('.aliexpress_products_attributes_values').css('display', 'inline-block');
			} else {
				$('.aliexpress_products_attributes_values').removeClass('active');
				$('.aliexpress_products_attributes_values').css('display', 'none');
			}
		}
	);

	$(document).on(
		'change',
		'.brand_attr',
		function (e) {
			e.preventDefault();
			if ('select_woocommerce_attr' == $('.brand_attr').val()) {
				$('.brand_trendy_attr').attr('disabled', true);
				$('.brand_trendy_attr').hide();
				$('.brand_trendy_attr').next().hide();
				$('.brand_woo_attr').attr('disabled', false);
				$('.brand_woo_attr').show();
			} else if ('select_aliexpress_attr' == $('.brand_attr').val()) {
				$('.brand_trendy_attr').attr('disabled', false);
				$('.brand_trendy_attr').show();
				$('.brand_trendy_attr').next().show();
				$('.brand_woo_attr').attr('disabled', true);
				$('.brand_woo_attr').hide();

			}
		}
	);

	$(document).ready(function ($) {
		if ('disabled' == $('.brand_trendy_attr').attr('disabled')) {
			$('.brand_trendy_attr').next().hide();
		}
	});

	$(document).on(
		'keyup',
		'.select2-search__field',
		function (e) {
			e.preventDefault();
			if ('select2-bulk-action-selector-top-results' == $(this).parent().next().children().attr('id')) {
				var search_val = $(this).val();
				$.ajax(
					{
						url: ajax_url,
						data: {
							ajax_nonce: ajax_nonce,
							search_val: search_val,
							action: 'CIFA_display_searched_brands',
						},

						type: 'POST',
						success: function (response) {

							$(".brand_trendy_attr").trigger('click');
							$(".brand_trendy_attr").append(response);

						}
					}
				);
			}
		}
	);

	$(document).on(
		'click',
		'#aliexpress_onboarding_step4_save_config',
		function (e) {
			e.preventDefault();
			var configuration_data = $("form").serialize();
			var title_rule_multiselect = $('#aliexpress_title_rule_multiselect').val();
			$('.CIFA-loader').show();
			$.ajax(
				{
					url: ajax_url,
					data: {
						ajax_nonce: ajax_nonce,
						configuration_data: configuration_data,
						title_rule_multiselect: title_rule_multiselect,
						action: 'CIFA_save_configuration_data_onboarding',
					},
					type: 'POST',
					success: function (response) {
						$('.CIFA-loader').hide();
						var path = response;
						window.location.href = path;
					}
				}
			);
		}
	);
	$(document).on(
		'change',
		'.select_source_target_attr_type',
		function (e) {
			e.preventDefault();
			var id = $(this).attr("data-attribute-id")
			$(".attribute_value_" + id).val("");
			if ($(this).attr("class").includes("variation_attribute") && $(this).val() != "select_woocommerce_attr") {
				$(".attribute_woocommerce_" + id).next().hide()
				$(".select_attribute_values_select2").trigger("change")
			} else {
				$(".attribute_woocommerce_" + id).next().show()
			}
			if ('select_woocommerce_attr' == $(this).val()) {
				$(".attribute_woocommerce_" + id).show()
				if ($(".attribute_required_" + id) != undefined && $(".attribute_required_" + id).attr("data-attribute-required") == "1") {
					$(".attribute_woocommerce_" + id).attr("required", true)
					$(".attribute_aliexpress_" + id).attr("required", false)
					$(".attribute_custom_" + id).attr("required", false)
				}
				$(".attribute_custom_" + id).hide()
				$(".attribute_aliexpress_" + id).hide()
			} else if ('select_custom_value' == $(this).val()) {
				if ($(".attribute_required_" + id) != undefined && $(".attribute_required_" + id).attr("data-attribute-required") == "1") {
					$(".attribute_aliexpress_" + id).attr("required", false)
					$(".attribute_woocommerce_" + id).attr("required", false)
					$(".attribute_custom_" + id).attr("required", true)
				}
				$(".attribute_custom_" + id).show()
				$(".attribute_woocommerce_" + id).hide()
				$(".attribute_aliexpress_" + id).hide()
			} else if ('select_aliexpress_attr' == $(this).val()) {
				$(".attribute_aliexpress_" + id).show()
				console.log($(".attribute_required_" + id).attr("data-attribute-required"))
				if ($(".attribute_required_" + id) != undefined && $(".attribute_required_" + id).attr("data-attribute-required") == "1") {
					$(".attribute_aliexpress_" + id).attr("required", true)
					$(".attribute_woocommerce_" + id).attr("required", false)
					$(".attribute_custom_" + id).attr("required", false)
				}
				$(".attribute_woocommerce_" + id).hide()
				$(".attribute_custom_" + id).hide()
			}
		}
	);

	$(document).on(
		'click',
		'#add_new_rule_create_template',
		function (e) {
			e.preventDefault();
			var rule_count = $('#rule_groups').attr('data-row');
			rule_count = parseInt(rule_count) + 1;
			$('.CIFA-loader').show();

			$.ajax(
				{
					url: ajax_url,
					data: {
						ajax_nonce: ajax_nonce,
						rule_count: rule_count,
						action: 'CIFA_add_price_rule',
					},
					type: 'POST',
					success: function (response) {
						$('.CIFA-loader').hide();
						$('#rule_groups').append(response);
						$('#rule_groups').attr('data-row', rule_count);

					}
				}
			);
		}
	);

	$(document).on(
		'change',
		'#default_temp_custom_price_rule_type',
		function (e) {
			e.preventDefault();
			if ('' == $('#default_temp_custom_price_rule_type').val()) {
				$('#default_temp_custom_price_rule_value').hide();
				$('#default_temp_custom_price_rule_value').val('');
			} else {
				$('#default_temp_custom_price_rule_value').show();
			}
		}
	);

	$(document).on(
		'change',
		'#edit_temp_custom_price_rule_type',
		function (e) {
			e.preventDefault();
			if ('' == $('#edit_temp_custom_price_rule_type').val()) {
				$('#edit_temp_custom_price_rule_value').hide();
				$('#edit_temp_custom_price_rule_value').val('');
			} else {
				$('#edit_temp_custom_price_rule_value').show();
			}
		}
	);

	$(document).on(
		'change',
		'.rule_group_list',
		function (e) {
			e.preventDefault();
			var value = $(this).val();
			var id = $(this).attr("data-rule-group-select-id");
			var options = $(this).find("option:selected").attr("data-options");
			var type = $(this).find("option:selected").attr("data-type");
			console.log($("#rule_grp_"+type+"_select_value_" + id).val());
			var select_count = $("#rule_grp_"+type+"_select_value_" + id).find("option").length
			if (select_count && "options" === options) {
				if(type == 'category'){
					var hide = 'type';
				}
				if(type == 'type'){
					var hide = 'category';
				}
				$("#rule_grp_operator_" + id + " option[value='not_equals']").show();
				$("#rule_grp_operator_" + id + " option[value='equals']").show();
				$("#rule_grp_operator_" + id + " option[value='contains']").hide();
				$("#rule_grp_operator_" + id + " option[value='not_contains']").hide();
				$("#rule_grp_operator_" + id + " option[value='less_than']").hide();
				$("#rule_grp_operator_" + id + " option[value='greater_than']").hide();
				$("#rule_grp_"+type+"_select_value_" + id).show();
				$("#rule_grp_"+type+"_custom_value_" + id).hide();
				$("#rule_grp_"+hide+"_select_value_" + id).hide();
				$("#rule_grp_"+hide+"_custom_value_" + id).hide();
				$("#rule_grp_quantity_custom_value_" + id).hide();
				$("#rule_grp_title_custom_value_" + id).hide();
			} else if( type == "quantity" ) {
				$("#rule_grp_operator_" + id + " option[value='contains']").hide();
				$("#rule_grp_operator_" + id + " option[value='not_contains']").hide();
				$("#rule_grp_operator_" + id + " option[value='not_equals']").hide();
				$("#rule_grp_operator_" + id + " option[value='equals']").show();
				$("#rule_grp_operator_" + id + " option[value='less_than']").show();
				$("#rule_grp_operator_" + id + " option[value='greater_than']").show();
				$("#rule_grp_"+type+"_select_value_" + id).hide();
				$("#rule_grp_title_custom_value_" + id).hide();
				$("#rule_grp_type_select_value_" + id).hide();
				$("#rule_grp_category_select_value_" + id).hide();
				$("#rule_grp_"+type+"_custom_value_" + id).show();
			} else {
				$("#rule_grp_operator_" + id + " option[value='contains']").show();
				$("#rule_grp_operator_" + id + " option[value='not_contains']").show();
				$("#rule_grp_operator_" + id + " option[value='equals']").show();
				$("#rule_grp_operator_" + id + " option[value='not_equals']").show();
				$("#rule_grp_operator_" + id + " option[value='less_than']").hide();
				$("#rule_grp_operator_" + id + " option[value='greater_than']").hide();
				$("#rule_grp_"+type+"_select_value_" + id).hide();
				$("#rule_grp_"+type+"_custom_value_" + id).show();
				$("#rule_grp_quantity_custom_value_" + id).hide();
				$("#rule_grp_type_select_value_" + id).hide();
				$("#rule_grp_category_select_value_" + id).hide();
			}
		}
	);

	$(document).on(
		'click',
		'.delete_price_rule',
		function (e) {
			e.preventDefault();
			var id = $(this).attr("data-delete-rule-id");
			$(".price_rule_" + id).remove()
		}
	);

	$(document).on(
		'click',
		'.CIFA-Btn',
		function (e) {
			e.preventDefault();
			$(this).next().show();
		}
	);

	$(document).on(
		'click',
		'.CIFA-close-button',
		function (e) {
			e.preventDefault();
			$(this).parent().parent().hide();
		}
	);

	$(document).on(
		'keyup',
		'.currency_conversion',
		function (e) {
			e.preventDefault();
			if ($(this).val() <= 0) {
				$(this).val('');
			}
		}
	);

	$(document).on(
		'keyup',
		'.threshold_inventory',
		function (e) {
			e.preventDefault();
			if ($(this).val() < 0) {
				$(this).val('');
			}
			if ($(this).val() == '') {
				$(this).val('');
			}
			if (e.key == '.') {
				$(this).val($(this).val().replace('.', ''));
				$(this).focus();
				var tmpStr = $(this).val();
				$(this).val('');
				$(this).val(tmpStr);
			}
		}
	);

	$(document).on(
		'keyup',
		'.custom_price_rule',
		function (e) {
			e.preventDefault();
			if ($(this).val() <= 0) {
				$(this).val('');
			}
		}
	);

	$(document).ready(function () {

		$('#edit_template_form').on('input[type="text"] keyup', function () {
			$('#save_edit_template').attr('disabled', false);
		});
		$('#edit_template_form').on('input[type="radio"] change', function () {
			$('#save_edit_template').attr('disabled', false);
		});
		$('#edit_template_form').on('input#woocommerce_override_listing change', function () {
			$('#save_edit_template').attr('disabled', false);
		});
	})

	$(window).load(function () {
		$('.select_source_target_attr_type').each(function () {
			if ("select_custom_value" == $(this).val() &&
				$(this).attr("class").includes("variation_attribute")) {
				$(this).next().next().css("display", "none")
			}
		})
	});
	$(document).ready(function () {
		$('#edit_default_template_form').on('input[type="text"] keyup', function () {
			$('#aliexpress_edit_default_template').attr('disabled', false);
		});
		$('#edit_default_template_form').on('input[type="radio"] change', function () {
			$('#aliexpress_edit_default_template').attr('disabled', false);
		});
		$('#edit_default_template_form').on('input#woocommerce_override_listing change', function () {
			$('#aliexpress_edit_default_template').attr('disabled', false);
		});
	})

	$(document).on(
		'change',
		'#edit_template_form select',
		function (e) {
			e.preventDefault();
			$('#save_edit_template').attr('disabled', false);
		}
	);

	$(document).on(
		'change',
		'#edit_default_template_form select',
		function (e) {
			e.preventDefault();
			$('#aliexpress_edit_default_template').attr('disabled', false);
		}
	);

	$(document).on(
		'click',
		'#CIFA-category-template-listing .row-actions .delete',
		function (e) {
			e.preventDefault();
			$('.ced-modal').html('<div class="ced-modal-text-content"><h4>Are you sure want to delete </h4><div class="ced-button-wrap-popup"><a href="' + $(this).children().attr('data-link') + '"><span class="ced-close-button button-primary woocommerce-save-button">YES</span></a><span class="ced-close-button button-primary woocommerce-save-button ced-cancel">Cancel</span></div></div>');
			$('.ced-modal').show();
		}
	);

	$(document).on(
		'click',
		'.ced-cancel',
		function (e) {
			e.preventDefault();
			$('.ced-modal').hide();
		}
	);

	$(document).ready(function () {
		$('#CIFA-category-template-listing #searchbtn-search-input').attr('maxlength', '30');
	});

	function get_rule_group() {
		var rule_group = "";
		var condition_type = $("input[name='CIFA-condition']:checked").val();
		var operation = condition_type == "any_condition" ? " || " : " && ";
		var title_list = [];
		var operator_list = [];
		var values = []
		$(".rule_group_list").each(function () {
			var id = $(this).attr("data-rule-group-select-id")
			title_list.push($(this).val())
			operator_list.push($(".rule_grp_operator_" + id).val());
			var type = $(this).find("option:selected").attr("data-type");
			if ("options" == $(this).find("option:selected").attr("data-options")) {
				values.push($("#rule_grp_"+type+"_select_value_" + id).val())
			} else {
				values.push($("#rule_grp_"+type+"_custom_value_" + id).val())
			}
		})

		title_list.forEach((item, index) => {
			var operator = "";
			switch (operator_list[index]) {
				case "equals":
					operator = "=="
					break;
				case "contains":
					operator = "%LIKE%"
					break;
				case "not_contains":
					operator = "!%LIKE%"
					break;
				case "less_than":
					operator = "<"
					break;
				case "greater_than":
					operator = ">"
					break;
				default:
					operator = "!="
					break;
			}
			rule_group = (rule_group.length ? rule_group + operation : rule_group) + item + ' ' + operator + ' ' + values[index]
		})
		return " ( " + rule_group + " ) ";
	}
	$(document).on(
		'click',
		'#run_rule_group_query',
		function (e) {
			e.preventDefault();
			var override_listing = $("#woocommerce_override_listing").is(":checked") ? true : false;
			console.log("override_listing", override_listing)
			var rule_group = get_rule_group();
			$('.CIFA-loader').show();
			$.ajax(
				{
					url: ajax_url,
					data: {
						ajax_nonce: ajax_nonce,
						rule_group: rule_group,
						override_listing: override_listing,
						action: 'CIFA_run_query_rule_grp',
					},
					type: 'POST',
					success: function (response) {
						$('.CIFA-loader').hide();
						$('#display_run_query_message').show();
						console.log("response", response)
						var res = JSON.parse(response);
						var message = '<h3 style="color:red;">';
						if (res.success) {
							var overwrite_products_count = parseInt(res.data.overwrite_true)
							var count = parseInt(res.data.count)
							var without_overwrite = parseInt(res.data.overwrite_false)
							if (override_listing || (!override_listing && without_overwrite == overwrite_products_count)) {
								message += count + ' Products Fetched.';
							} else {
								message += overwrite_products_count + ` product(s) will be affected out of which `
									+ (overwrite_products_count - without_overwrite) + ` product(s) are already assigned 
								to some other profile. If you do not override then only ` + without_overwrite +
									` products will come under this profile.`;
							}
						}
						$('#prod_count_run_query').val(res.data.count != "0" ? res.data.count : "0");
						message += "</h3>";
						$('#display_run_query_message').html(message);
					}
				}
			);
		}
	);
	
	function validateRequiredFields() {
		var required_fields = true;
		$(".category_template").find('input[required]').each(function () {
			if (!$(this).val()) {
				required_fields = false;
				$(this).addClass("CIFA-required-field");

			} else {
				$(this).removeClass("CIFA-required-field");
			}
		});
		$(".category_template").find('select[required]').each(function () {
			if (!$(this).val()) {
				required_fields = false;
				$(this).addClass("CIFA-required-field");
			} else {
				$(this).removeClass("CIFA-required-field");
			}
		});
		$(".select2-with-checkbox").each(function (e) {
			var id = $(this).attr("data-select-id")
			if ($(this).attr("required") && !$(this).select2("data").length) {
				required_fields = false;
				$(".attribute_value_" + id).next().addClass("CIFA-required-field")
			} else {
				$(".attribute_value_" + id).next().removeClass("CIFA-required-field");
			}
		})
		return required_fields;
	}
	$(document).on(
		'click',
		'#create_new_template',
		function (e) {
			e.preventDefault();
			var rule_group = get_rule_group()
			var success = true;
			var message = "";
			if (!parseInt($("#prod_count_run_query").val())) {
				message = "Affected product count must be greater than 0.";
				success = false;
			}
			if ($("#CIFA_template_name").val() == "") {
				message = "Template Name should not empty, Please provide a name to the Template.";
				success = false;
			}
			if ($("#get_target_categories").val() == "") {
				message = "Please select a category first to Save Category template.";
				success = false;
			}

			if ($("#default_temp_custom_price_rule_type").val() != "" && $("#default_temp_custom_price_rule_value").val() == "") {
				message = "Please enter price rule value if you are selecting any price rule.";
				success = false;
			}
			var required_fields = validateRequiredFields();
			if (!required_fields) {
				success = false;
				message = "Required fields are missing."
			}
			if (success && $("#CIFA_template_id").val() == undefined) {
				$('.CIFA-loader').show();
				$.ajax(
					{
						url: ajax_url,
						data: {
							ajax_nonce: ajax_nonce,
							template_name: $("#CIFA_template_name").val(),
							action: 'CIFA_validate_profile_name',
						},
						type: 'POST',
						success: function (response) {
							var res = JSON.parse(response)
							if (res['success']) {
								$.ajax(
									{
										url: ajax_url,
										data: {
											ajax_nonce: ajax_nonce,
											template_name: $("#CIFA_template_name").val(),
											rule_group: rule_group,
											action: 'CIFA_validate_category_template',
										},
										type: 'POST',
										success: function (response) {
											var res = JSON.parse(response)
											if (res['success']) {
												$("#rule_group_condition").val(rule_group)
												console.log("aaaaa")
												$("#create_template").submit()
											} else {
												$('.CIFA-loader').hide();
												$('.error_success_notification_display').show();
												message = ""
												if (res['data']['duplicate keys'] != undefined) {
													res['data']['duplicate keys'].forEach((item) => {
														message += item;
													})
												}
												message = "A template already exist with this " + message
												$('.CIFA-loader').hide();
												$('.error_success_notification_display').show();
												$('.error_success_notification_display').html(`<div class="notice notice-error is-dismissible"><p>
												<strong >`+ message + `</strong>          
											</p>
											<button type="button" class="notice-dismiss">
												<span class="screen-reader-text">Dismiss this notice.</span>
											</button></div>`);
												$(document).scrollTop(0);
											}
										}
									}
								);
							} else {
								$('.CIFA-loader').hide();
								$('.error_success_notification_display').show();
								$('.error_success_notification_display').html('<div class="notice notice-error is-dismissible">' + res.message + '</div>');
								$(document).scrollTop(0);
							}
						}
					}
				);
			}
			if (success && $("#CIFA_template_id").val()) {
				$("#rule_group_condition").val(rule_group)
				$("#create_template").submit()
			}
			if (!success) {
				$('.error_success_notification_display').show();
				$('.error_success_notification_display').html(`<div class="notice notice-error is-dismissible"> 		
				<p>
					<strong >`+ message + `</strong>          
				</p>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text">Dismiss this notice.</span>
				</button>
				</div>`);
				$(document).scrollTop(0);
			}
		}
	);
	
	$(document).on(
		'click',
		'#aliexpress_edit_default_template',
		function (e) {
			e.preventDefault();
			console.log("ajsksa")
			var required_fields = validateRequiredFields();
			if (!required_fields) {
				var message = "Required fields are missing."
				$('.error_success_notification_display').show();
				$('.error_success_notification_display').html(`<div class="notice notice-error is-dismissible"> 		
				<p>
					<strong >`+ message + `</strong>          
				</p>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text">Dismiss this notice.</span>
				</button>
				</div>`);
				$(document).scrollTop(0);
			} else {
				$(".category_template").submit()
			}
		}
	);
	$(document).on(
		'change',
		'#woocommerce_override_listing',
		function (e) {
			e.preventDefault();
			if (1 == $('#prod_count_run_query').val()) {
				$('#prod_count_run_query').val('0');
				$('#display_run_query_message').html('<h3>Please do run query before saving template.');
			} else if (0 == $('#prod_count_run_query').val()) {
				$('#display_run_query_message').html('<h3>Please do run query before saving template.');
			}
		}
	);

	$(document).on(
		'click',
		'.notice-dismiss',
		function (e) {
			$(this).closest('.notice').fadeOut('fast');
		}
	);

	$(document).ready(function () {
		$('select[name="order_status"]').on('change', function (e) {
			let val = $(this).val();
			let allowedStatusToRemove = ['wc-refunded', 'wc-failed', 'wc-cancelled'];
			let metaFieldToRemove = document.querySelector('div[id="CIFA_manage_orders_metabox"]');
			let trackingCompany = document.querySelector('select[name="trackingCompany"]');
			let trackingNumber = document.querySelector('input[id="trackingNumber"]');
			if (allowedStatusToRemove.includes(val)) {
				if (metaFieldToRemove) {
					if (trackingCompany) {
						trackingCompany.removeAttribute('required')
					}
					if (trackingNumber) {
						trackingNumber.removeAttribute('required')
					}
				}
			} else {
				if (metaFieldToRemove) {
					if (trackingCompany) {
						trackingCompany.setAttribute('required', '')
					}
					if (trackingNumber) {
						trackingNumber.setAttribute('required', '')
					}
				}
			}
		});
		$('.from_date').datepicker({
			dateFormat: 'yy-mm-dd',
			maxDate: 0,
			minDate: "-3m"
		});
		$('.to_date').datepicker({
			dateFormat: 'yy-mm-dd',
			maxDate: 0,
			minDate: "-3m",
			onSelect: function () {
				var startDate = $('.from_date').val();
				if (!startDate) {
					$(this).val("");
					$(".order_error").html("Select start date first");
				}
				var startDate = new Date(startDate);
				var toDate = new Date($(this).val())
				var endDate = new Date()
				endDate.setDate(startDate.getDate() + 14);
				if (toDate && toDate > endDate) {
					$(this).val("");
					$(".sync_order").css("cursor", "not-allowed")
					$(".sync_order").prop("disabled", true)
					$(".order_error").html("To date must not be at gretaer than 14 days after start date.");
				} else if (toDate && toDate < startDate) {
					$(this).val("");
					$(".sync_order").css("cursor", "not-allowed")
					$(".sync_order").prop("disabled", true)
					$(".order_error").html("To date must be at greater than start date.");
				} else {
					$(".sync_order").css("cursor", "pointer")
					$(".sync_order").prop("disabled", false)
					$(".order_error").html("");
				}
			}
		});
	})

	$(document).on(
		'change',
		'#price_rule',
		function () {
			if (!$(this).val()) {
				$(".price_template_value").hide()
			} else {
				$(".price_template_value").show()
			}
		}
	)

	$(document).on(
		'load',
		'.CIFA-main-body .page_count',
		function () {
			console.log("loaded")
			var urlParams = new URLSearchParams(window.location.search);
			if (urlParams.has("count")) {
				var count = parseInt(urlParams.get('count'));
			} else {
				count = 10;
			}
			console.log("loaded")
			$(this).val(count)
		}
	)

	$(document).on(
		'change',
		'.CIFA-main-body .page_count',
		function () {
			const value = $(this).val();
			if (value) {
				var urlParams = new URLSearchParams(window.location.search);
				var section = urlParams.get('section');
				const page_url = $("." + section).attr("href");
				window.location.href = page_url + "&count=" + value
			}
		}
	)

	$(document).on(
		'click',
		'.error_status',
		function () {
			const id = $(this).attr("data-error-id");
			$('.error-modal-' + id).show()
		}
	)
	$(document).on(
		'click',
		'.CIFA-close-button',
		function () {
			const id = $(this).attr("data-error-id");
			$('.error-modal-' + id).hide()
		}
	)
	function getActivities(count, activePage) {
		$('.CIFA-loader').show();
		$.ajax(
			{
				url: ajax_url,
				data: {
					ajax_nonce: ajax_nonce,
					count: count,
					activePage: activePage,
					action: 'CIFA_refresh_activities',
				},
				type: 'GET',
				success: function (response) {
					const res = JSON.parse(response)
					$('.CIFA-loader').hide();
					$('.ongoing_activity').html(res.ongoingHtml);
					$('.completed_activities').html(res.completedHtml);
					$('.activity_pagination').html(res.pagination);
				}
			}
		);
	}
	$(document).on(
		'click',
		'.refresh_activity',
		function (e) {
			e.preventDefault();
			const count = $(".total_rows_per_page").val();
			const activePage = $(".active_page").val();
			getActivities(count, activePage);
		}
	);
	$(document).on(
		'change',
		'.activity_pagination .total_rows_per_page',
		function (e) {
			e.preventDefault();
			const count = $(this).val();
			const activePage = 1;
			console.log(count, activePage)
			getActivities(count, activePage);
		}
	);
	$(document).on(
		'click',
		'.activity_pagination .left_arrow',
		function (e) {
			e.preventDefault();
			const count = $(".total_rows_per_page").val();
			const activePage = parseInt($(".active_page").val()) - 1;
			getActivities(count, activePage);
		}
	);
	$(document).on(
		'click',
		'.activity_pagination .right_arrow',
		function (e) {
			e.preventDefault();
			var count = $(".total_rows_per_page").val();
			var activePage = parseInt($(".active_page").val()) + 1;
			console.log(count, activePage);
			getActivities(count, activePage);
		}
	);
	$(document).on(
		'click',
		'.upload_by_category',
		function (e) {
			$(".bulk_pload_popup").show()
		}
	);
	$(document).on(
		'change',
		'.selected_category',
		function (e) {
			var products = parseInt($(this).find('option:selected').data('product-count'));
			console.log("products", products)
			if (products) {
				$(".products_message").show()
				$(".submit_upload").css("cursor", "pointer")
				$(".submit_upload").prop("disabled", false)
			} else {
				$(".products_message").hide()
				$(".submit_upload").css("cursor", "not-allowed")
				$(".submit_upload").prop("disabled", true)
			}
			$(".total_products").html(products)
		}
	);
	$(document).on(
		'click',
		'#reauthorize',
		function (e) {
			$(".reauthorize-modal").show()
		}
	);
	$(document).on(
		'click',
		'.sync_order_by_date',
		function (e) {
			$(".sync_order_popup").show()
		}
	);
	$(document).on(
		'change',
		'.from_date',
		function (e) {
			if ($(this).val() && $(".to_date").val()) {
				$(".sync_order").css("cursor", "pointer")
				$(".sync_order").prop("disabled", false)
			} else {
				$(".sync_order").css("cursor", "not-allowed")
				$(".sync_order").prop("disabled", true)
			}
		}
	);
	$(document).on(
		'change',
		'.to_date',
		function (e) {
			if ($(this).val() && $(".from_date").val()) {
				console.log("value exists")
				$(".sync_order").css("cursor", "pointer")
				$(".sync_order").prop("disabled", false)
			} else {
				$(".sync_order").css("cursor", "not-allowed")
				$(".sync_order").prop("disabled", true)
			}
		}
	);
	$(document).on(
		'click',
		'.sync_order',
		function (e) {
			var startDate = $('.from_date').val();
			if (!startDate) {
				e.preventDefault()
				$('.from_date').val("");
				$(".sync_order").css("cursor", "not-allowed")
				$(".sync_order").prop("disabled", true)
				$(".order_error").html("Select start date first");
			}
			var startDate = new Date(startDate);
			var toDate = new Date($(".to_date").val())
			var endDate = new Date()
			endDate.setDate(startDate.getDate() + 14);
			if (toDate && toDate > endDate) {
				e.preventDefault()
				$('.to_date').val("");
				$(".sync_order").css("cursor", "not-allowed")
				$(".sync_order").prop("disabled", true)
				$(".order_error").html("To date must be at least 14 days after start date.");
			} else if (toDate && toDate < startDate) {
				$('to_date').val("");
				$(".sync_order").css("cursor", "not-allowed")
				$(".sync_order").prop("disabled", true)
				$(".order_error").html("To date must be at greater than start date.");
			} else {
				$(".sync_order").css("cursor", "pointer")
				$(".sync_order").prop("disabled", false)
				$(".order_error").html("");
			}

		}
	);
	$(document).on(
		'select2:select',
		'.select_attribute_values_select2',
		function (e) {
			var data = $(this).select2('data');
			var value = "";
			var selectVal = e.params.data.id;
			$('.select_attribute_values_select2').find('option[value="' + selectVal + '"]').prop('disabled', true).trigger('change');
			data.forEach((item, index) => {
				value += item.id + ","
			})
			var id = $(this).attr('data-select-id')
			$("#select-" + id).val(value)
			$('.select_attribute_values_select2').select2()
			$(this).select2("open")
		}
	);
	$(document).on(
		'change',
		'.select_attribute_values',
		function (e) {
			var value = $(this).find('option:selected').data('value')
			var id = $(this).attr('data-select-id')
			$("#select-" + id).val(value);
			if (value != "") {
				$(this).removeClass("CIFA-required-field");
			}
		}
	);

	$(document).on(
		'change',
		'.select_attribute_values_customized',
		function (e) {
			var value = $(this).find('option:selected').data('value')
			var id = $(this).attr('data-select-id')
			$("#select-" + id).val(value)
			if (value != "") {
				$(this).removeClass("CIFA-required-field");
			}
		}
	);

	$(document).on(
		'change',
		'.attribute_type',
		function (e) {
			var value = $(this).find('option:selected').val()
			var id = $(this).attr('data-attribute-id')
			if ("select_custom_value" === value) {
				$("#select-" + id).val("");
			}

		}
	);
	$(document).on(
		'change',
		'.variant_attribute',
		function (e) {
			var value = $(this).select2('data');
			var id = $(this).attr("data-select-id")
			console.log("id", id)

			if (value.length) {
				$(".option_value_mapping_" + id).show();
			} else {
				$(".option_value_mapping_" + id).hide();
			}
		}
	);
	$(document).on(
		'select2:unselect',
		'.variant_attribute',
		function (e) {
			var item = e.params.data.id
			var id = $(this).attr("data-select-id")
			$('.option_table_' + id).find('#option_body_' + item).remove();
			$('.option_table_' + id + ' .option_name_' + id + ' option[value="' + item + '"]').remove();
			if ($('.option_table_' + id + ' .option_name_' + id).children('option').length > 0) {
				$('.option_table_' + id + ' .option_name_' + id)[0].selectIndex = 0;
				var selected_val = $('.option_table_' + id + ' .option_name_' + id).find('option:selected').val()
				$('.option_value_body_' + id + ' .option_search_' + id).attr('data-search-key', selected_val)
				$('.option_value_body_' + id + ' .option_name_' + id).attr('data-option-key', selected_val)
				$(".option_body").hide()
				$('#option_body_' + selected_val).show()
			}
			console.log("item", item)
			$('.select_attribute_values_select2').find('option[value="' + item + '"]').prop('disabled', false);
			$('.select_attribute_values_select2').trigger("change")
			var values = "";
			if ($(this).select2('data').length) {
				$(this).select2('data').forEach((i) => {
					console.log(i)
					if (i.id != undefined) {
						values += i.id;
					}
				})
			}
			$("#select-" + id).val(values)
			$(".select_attribute_values_select2").select2()

		}
	);

	$(document).on(
		'click',
		'.option_value_mapping',
		function (e) {
			var id = $(this).attr("data-option-id")
			var previousSelected = []
			var options = []
			$(".option_value_mapping_select_" + id).select2('data').forEach((item) => {
				options.push(item.id)
			});
			var difference = [];
			var currentOptions = [];
			$('.option_value_body_' + id + ' .option_body').each(function () {
				console.log($(this).attr("data-option-body-key"))
				previousSelected.push($(this).attr("data-option-body-key"))
			});
			if (previousSelected.length) {
				difference = $(options).not(previousSelected).get();
				currentOptions = $.merge(previousSelected, difference)
			} else {
				difference = options
				currentOptions = options
			}
			console.log("previousSelected", previousSelected)
			console.log("difference", difference)
			console.log("options", options)


			if ($.trim($('.option_value_body_' + id).html()) === '' || difference.length) {
				$('.CIFA-loader').show();
				$.ajax(
					{
						url: ajax_url,
						data: {
							ajax_nonce: ajax_nonce,
							attributes: difference.length ? difference : options,
							id: id,
							action: 'CIFA_getVariantOptions',
						},

						type: 'POST',
						success: function (response) {
							$('.CIFA-loader').hide();
							console.log("under if")
							var res = JSON.parse(response)
							if (res.success) {
								$('.option_form_' + id).show()
								$('.option_value_body_' + id).append(res.data)
								var select_html = $("#target_select_" + id).html()

								$('.option_value_body_' + id + ' .target_select_html').each(function () {
									if ($.trim($(this).html()) === '') {
										$(this).html(select_html)
									}
									var dataId = $(this).attr('data-td-id');
									var inputField = $(this).find('select');
									inputField.attr('name', dataId);
								});
								var selected_val = previousSelected.length ? $('.option_value_body_' + id + ' .option_name').find('option:selected').val() : options[0]
								$('.option_value_body_' + id + ' #option_body_' + selected_val).show()
								$('.option_value_body_' + id + ' .option_search_' + id).attr('data-search-key', selected_val)
								$('.option_value_body_' + id + ' .option_name_' + id).attr('data-option-key', selected_val)
								if (difference.length) {
									$('.option_value_body_' + id + ' .option_name').html("")
									currentOptions.forEach((item) => {
										$('.option_value_body_' + id + ' .option_name').append(new Option(item, item));
									})
									$('.option_value_body_' + id + ' .option_name option[value="' + selected_val + '"]')
									$('.option_value_body_' + id + ' .option_name').trigger("change")
								}
								if ($.trim($('.option_value_body_' + id + " .table_header_" + id).html()) !== '') {
									$('.option_value_body_' + id + ' .table_header_' + id + ":first").css("display", "contents")
								}
								$('.error_success_notification_display').hide();
							}
						}
					}
				);
			} else {
				console.log("under else")
				$('.option_value_body_' + id + ' .option_name').val($('.option_value_body_' + id + ' .option_name option:first').val());
				$('.option_value_body_' + id + ' .option_name').trigger("change")
				$('.option_form_' + id).show()
			}
		}
	);
	$(document).on(
		'change',
		'.option_name',
		function (e) {
			var id = $(this).attr('data-select-option-name')
			var selected_val = $('.option_value_body_' + id + ' .option_name_' + id).find('option:selected').val()
			$('.option_value_body_' + id + ' .option_search_' + id).attr('data-search-key', selected_val)
			$('.option_value_body_' + id + ' .option_name_' + id).attr('data-option-key', selected_val)
			$(".option_body").hide()
			$('#option_body_' + selected_val).show()
			handleSearchOption(0, 0, id)
		}
	);
	$(document).on(
		'click',
		'#option_value_submit',
		function (e) {
			$(".option-value-mapping-form").hide()
		}
	);
	function handleSearchOption(startIndex = 0, endIndex = 0, id = "") {
		var keyword = $(".option_value_body_" + id + " .option_search_" + id).val();
		keyword = keyword != undefined?.toUpperCase();
		var key = $(".option_value_body_" + id + " .option_search").attr('data-search-key')
		var chunk = parseInt($('.option_value_body_' + id + ' .left_pagination_' + key + ' .total_rows_per_page').val())
		var totalRows = 0;
		var totalPages = 0;
		var activePage = parseInt($('.option_value_body_' + id + '.right_pagination_' + key + ' .active_page').val())
		if (keyword.length/* >2 */) {
			$('.option_value_body_' + id + ' #option_body_' + key + ' .option_value_row').hide()
			var ids = [];
			$('.option_value_body_' + id + ' #option_body_' + key + ' .option_value_row .option_key_index').each(function () {
				var option_key = $(this).val();
				console.log(keyword, option_key)

				if ((option_key.toUpperCase()).indexOf(keyword) !== -1) {
					ids.push($(this).attr("data-option-key-counter"));
					totalRows++;
				}
			});
			if (totalRows) {
				startIndex = !startIndex ? 1 : (activePage * chunk) - 1;
				endIndex = !endIndex ? (chunk < totalRows ? chunk : totalRows) : endIndex;
				ids.forEach((item, index) => {
					if ((index + 1) >= startIndex && (index + 1) <= endIndex) {
						$('.option_value_body_' + id + ' .option_value_row_' + item).show()
					}
				})
			}
		} else if (!(keyword.length)) {
			var totalRows = $('#option_body_' + key + ' .option_value_row .option_key_index').length;
			if (totalRows) {
				$('.option_value_body_' + id + ' .option_table_' + id + ' .option_value_row').hide()
				startIndex = startIndex ? startIndex : 1;
				endIndex = endIndex ? endIndex : (endIndex <= chunk && endIndex ? endIndex : chunk);
			}
			var index = startIndex
			while (index <= endIndex) {
				$(".option_value_body_" + id + " #option_body_" + key + " .option_value_row_counter_" + index).show()
				index++;
			}
		}
		console.log(startIndex, endIndex)
		totalPages = totalRows <= chunk ? 1 : (Math.ceil(totalRows / chunk))
		if (totalPages == 1) {
			$(".option_value_body_" + id + " #option_body_" + key + " .right_arrow").css("pointer-events", "none")
			$(".option_value_body_" + id + " #option_body_" + key + " .left_arrow").css("pointer-events", "none")
		}
		if (endIndex == totalRows) {
			$(".option_value_body_" + id + " #option_body_" + key + " .right_arrow").css("pointer-events", "none")
		}
		if (startIndex <= 1 || activePage == 1) {
			$(".option_value_body_" + id + " #option_body_" + key + " .left_arrow").css("pointer-events", "none")
		}
		if (endIndex < totalRows) {
			$(".option_value_body_" + id + " #option_body_" + key + " .right_arrow").css("pointer-events", "auto")
		}
		if (activePage > 1) {
			$(".option_value_body_" + id + " #option_body_" + key + " .left_arrow").css("pointer-events", "auto")
		}
		$('.option_value_body_' + id + ' .right_pagination_' + key + ' .active_page').val(1)
		$(".option_value_body_" + id + " #option_body_" + key + " .total_pages").html(totalPages)
		$(".option_value_body_" + id + " #option_body_" + key + " .total_chunk_count").text(totalRows)
		$(".option_value_body_" + id + " #option_body_" + key + " .current_chunk").html(startIndex + " - " + endIndex)
	}
	$(document).on(
		'keyup',
		'.option_search',
		function (e) {
			handleSearchOption(0, 0, $(this).attr("data-search-id"))
		}
	);
	$(document).on(
		'change',
		'.left_pagination .total_rows_per_page',
		function () {
			var key = $(this).attr('data-chunk-limit-key')
			var id = $(this).attr('data-chunk-limit-id')
			var chunk = parseInt($('.left_pagination_' + key + ' .total_rows_per_page').val())
			var activePage = parseInt($('.right_pagination_' + key + ' .active_page').val())
			var totalCount = parseInt($('.left_pagination_' + key + ' .total_chunk_count').text())
			var endIndex = parseInt($(this).val()) > totalCount ? totalCount : parseInt($(this).val())
			activePage = activePage != 1 ? 1 : activePage
			var endIndex = activePage * chunk;
			var startIndex = activePage == 1 ? 1 : (endIndex - chunk);
			console.log(startIndex, endIndex)
			handleSearchOption(startIndex, endIndex, id)
		}
	);
	$(document).on(
		'click',
		'.right_pagination .right_arrow',
		function () {
			var key = $(this).attr('data-right-arrow-key')
			var id = $(this).attr('data-right-arrow-id')
			var chunk = parseInt($('.left_pagination_' + key + ' .total_rows_per_page').val())
			var activePage = parseInt($('.right_pagination_' + key + ' .active_page').val())
			var totalCount = parseInt($('.left_pagination_' + key + ' .total_chunk_count').text())
			var startIndex = (activePage * chunk) + 1;
			var nextPage = activePage + 1;
			var endIndex = (chunk * nextPage) > totalCount ? totalCount : chunk * nextPage;
			handleSearchOption(startIndex, endIndex, id)
			$('.option_value_body_' + id + ' .right_pagination_' + key + ' .active_page').val(nextPage)
			$(".option_value_body_" + id + " #option_body_" + key + " .left_arrow").css("pointer-events", "auto")
		}
	);
	$(document).on(
		'click',
		'.right_pagination .left_arrow',
		function () {
			var key = $(this).attr('data-left-arrow-key')
			var id = $(this).attr('data-left-arrow-id')
			var chunk = parseInt($('.left_pagination_' + key + ' .total_rows_per_page').val())
			var activePage = parseInt($('.right_pagination_' + key + ' .active_page').val())
			var totalCount = parseInt($('.left_pagination_' + key + ' .total_chunk_count').text())
			activePage = activePage == 1 ? activePage : activePage - 1
			var startIndex = ((activePage - 1) * chunk) + 1
			var endIndex = (chunk * activePage) > totalCount ? totalCount : chunk * activePage;
			handleSearchOption(startIndex, endIndex, id)
			$('.option_value_body_' + id + ' .right_pagination_' + key + ' .active_page').val(activePage)
			if (activePage == 1) {
				$(".option_value_body_" + id + " #option_body_" + key + " .left_arrow").css("pointer-events", "none")
			}
			$(".option_value_body_" + id + " #option_body_" + key + " .right_arrow").css("pointer-events", "auto")
		}
	);
	$(document).on(
		'click',
		'.refresh_variant_options',
		function () {
			var id = $(this).attr('data-attribute-id')
			var attributes = $(this).attr('data-attributes').split(",")
			var category_id = $(this).attr('data-category-id')
			var values = JSON.parse($(this).attr('data-attribute-values'))
			$('.CIFA-loader').show();
			$.ajax(
				{
					url: ajax_url,
					data: {
						ajax_nonce: ajax_nonce,
						attributes: attributes,
						category_id: category_id,
						id: id,
						values: values,
						refresh: true,
						action: 'CIFA_getVariantOptions',
					},

					type: 'POST',
					success: function (response) {
						$('.CIFA-loader').hide();
						var res = JSON.parse(response)
						if (res.success) {
							$('.option_form_' + id).show()
							$('.option_value_body_' + id).html(res.data)
							var select_html = $("#target_select_" + id).html()

							$('.option_value_body_' + id + ' .target_select_html').each(function () {
								if ($.trim($(this).html()) === '') {
									$(this).html(select_html)
								}
								var dataId = $(this).attr('data-td-id');
								var inputField = $(this).find('select');
								inputField.attr('name', dataId);
							});
							var selected_val = attributes[0]
							$('.option_value_body_' + id + ' #option_body_' + selected_val).show()
							$('.option_value_body_' + id + ' .option_search_' + id).attr('data-search-key', selected_val)
							$('.option_value_body_' + id + ' .option_name_' + id).attr('data-option-key', selected_val)
						}
					}
				}
			);

		}
	);
	$(document).on(
		'click',
		'.CIFA_unselect_category',
		function (e) {
			e.preventDefault();
			$(".aliexpress_attribute_section_mapping").html("")
			$(".CIFA_selected_category_section").hide()
			$(".CIFA_selected_category_name").html("")
			$("#get_target_categories option:selected").prop("selected", false)
			$("#aliexpress_edit_default_template").prop("disabled", true)
			$.ajax(
				{
					url: ajax_url,
					data: {
						ajax_nonce: ajax_nonce,
						action: 'CIFA_get_all_categories',
					},
					type: 'POST',
					success: function (response) {
						var res = JSON.parse(response);
						if (res.success) {
							$("#get_target_categories").html(res.html)
							$("#get_target_categories").select2()
						}
					}
				}
			);
		}
	);
	$(document).on(
		'input',
		'.CIFA-number-input',
		function () {
			var value = $(this).val();
			value = value.replace(/\D/g, '');
			$(this).val(value);
		}
	);
	$(document).on(
		'keypress',
		'.custom_price_rule',
		function (e) {
			var value = $(this).val();
			console.log(value, value.length)
			if (value.length >= 4) {
				e.preventDefault()
			}
		}
	);

	$(document).on(
		'select2:select',
		'.choose_title_option',
		function (e) {
			var values = $(this).select2("data");
			var selected_options = []
			values.forEach((item) => {
				selected_options.push(item.id)
			})
			if (values.length >= 5) {
				e.preventDefault()
				$(this).find("option").each(function () {
					if ($.inArray($(this).val(), selected_options) != -1) {
						console.log("exists")
						$(this).prop("disabled", false)
					} else {
						console.log("not exists")
						$(this).prop("disabled", true)
					}
				})
			}
			$(this).select2()
		}
	);
	$(document).on(
		'select2:unselect',
		'.choose_title_option',
		function (e) {
			var values = $(this).select2("data");
			var selected_options = []
			values.forEach((item) => {
				selected_options.push(item.id)
			})
			if (values.length < 5) {
				e.preventDefault()
				$(this).find("option").each(function () {
					$(this).prop("disabled", false)
				})
			}
			$(this).select2()
		}
	);
	$(document).on(
		'keyup',
		'.custom_value_for_attribute',
		function (e) {
			if ($(this).val() == "") {
				$(this).val("")
			} else {
				$(this).removeClass("CIFA-required-field");
			}
		}
	);
	function scrollingTopHeader() {
		var delay = 5000;
		var currentIndex = 0;
		setInterval(function () {
			var paragraphs = $('.CIFA-reauthorize-notice').find('p');
			var container = $('.CIFA-reauthorize-notice');
			var currentParagraph = paragraphs.eq(currentIndex);

			container.animate({ scrollTop: currentParagraph.position().top }, 500);

			currentIndex = (currentIndex + 1) % paragraphs.length;
		}, delay);
	}
	scrollingTopHeader();

})(jQuery);