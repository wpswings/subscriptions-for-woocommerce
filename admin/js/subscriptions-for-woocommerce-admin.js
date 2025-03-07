
(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	 $(document).ready(function() {

		const MDCText = mdc.textField.MDCTextField;
        const textField = [].map.call(document.querySelectorAll('.mdc-text-field'), function(el) {
            return new MDCText(el);
        });
        const MDCRipple = mdc.ripple.MDCRipple;
        const buttonRipple = [].map.call(document.querySelectorAll('.mdc-button'), function(el) {
            return new MDCRipple(el);
        });
        const MDCSwitch = mdc.switchControl.MDCSwitch;
        const switchControl = [].map.call(document.querySelectorAll('.mdc-switch'), function(el) {
            return new MDCSwitch(el);
        });

        $(document).on('click','.wps-password-hidden', function() {
            if ($('.wps-form__password').attr('type') == 'text') {
                $('.wps-form__password').attr('type', 'password');
            } else {
                $('.wps-form__password').attr('type', 'text');
            }
        });
		
		// PRO popup start
		
			$(document).on( 'click', '.wps_pro_settings_tag', function(e) {
				
				e.preventDefault();
				$('.wps_sfw_lite_go_pro_popup_wrap').addClass('wps_sfw_lite_go_pro_popup_show');
				
			});
	
			$(document).on( 'click', '.wps_sfw_lite_go_pro_popup_close', function() {
				$('.wps_sfw_lite_go_pro_popup_wrap').removeClass('wps_sfw_lite_go_pro_popup_show');
			});
				
		
		// PRO popup end

	});

	$(window).load(function(){
		// add select2 for multiselect.
		if( $(document).find('.wps-defaut-multiselect').length > 0 ) {
			$(document).find('.wps-defaut-multiselect').select2();
		}
	});

	jQuery(document).ready(function() {
        jQuery('.wp-swings_page_subscriptions_for_woocommerce_menu td.status').each(function() {
            const html_val = jQuery(this).text();
            jQuery(this).empty();
            const html_tag = jQuery(this).append('<span>' + html_val + '</span>');
            jQuery('.wp-swings_page_subscriptions_for_woocommerce_menu td.status span').css({ 'padding': '2px 10px', 'border-radius': '2px', 'text-transform': 'capitalize' });

            if (html_val == 'expired') {
                jQuery(this).children().css({ 'color': '#943126', 'background': '#F5B7B1' });
            } else if (html_val == 'cancelled' || html_val == 'failed' ) {
                jQuery(this).children().css({ 'color': '#873600', 'background': '#edbb99' });
            } else if (html_val == 'active' || html_val == 'completed' ) {
                jQuery(this).children().css({ 'color': '#196f3d', 'background': '#a9dfbf' });
            } else if (html_val == 'on-hold') {
                jQuery(this).children().css({ 'color': '#9a7d0a', 'background': '#f9e79f' });
            } else if (html_val == 'paused') {
                jQuery(this).children().css({ 'color': '#21618c', 'background': '#aed6f1' });
            } else {
                jQuery(this).children().css({ 'color': '#515A5A', 'background': '#CCD1D1' });
            }

        });

		jQuery(document).on( 'click', '.wps_sfw_paypal_validate', function(e){
			e.preventDefault();
			var clientID = jQuery( 'input[name="woocommerce_wps_paypal_client_id"]' ).val();
			var clientSecret = jQuery( 'input[name="woocommerce_wps_paypal_client_secret"]' ).val();
			var testMode = jQuery( 'input[name="woocommerce_wps_paypal_testmode"]' ).is(':checked');
			var data = {
				clientID : clientID,
				clientSecret : clientSecret,
				testMode : testMode,
				nonce: sfw_admin_param.sfw_auth_nonce,
				action: 'wps_sfw_paypal_keys_validation',
			}
			if ( ! clientID && ! clientSecret ) {
				alert( sfw_admin_param.empty_fields );
				return;
			}
			jQuery.ajax({
				type: 'post',
				dataType: 'json',
				url: sfw_admin_param.ajaxurl,
				data: data,
				success: function(data) {
					alert( data.msg );
				}
			});
		})
    });

	// Open API tab details.
	jQuery(document).ready(function(){

		jQuery('.wps_sfw_rest_api_response').hide();
		jQuery('.wps_sfw_rest_api_response').first().show();
		jQuery('.wps_sfw_api_details_main_wrapper h4').first().addClass('active');

		jQuery(document).on('click','.wps_sfw_api_details_main_wrapper h4', function(){
		jQuery(this).next('.wps_sfw_rest_api_response').slideToggle(500);
			jQuery(this).toggleClass('active');
	})
	})

	})( jQuery );
	var wps_subscripiton_migration_success = function() {
	
		if ( sfw_admin_param.pending_product_count != 0 && sfw_admin_param.pending_orders_count != 0 && sfw_admin_param.pending_subscription_count != 0 ) {
			jQuery( "#wps_sfw_migration-button" ).click();
			jQuery( "#wps_sfw_migration-button" ).show();
		}else{
			jQuery( "#wps_sfw_migration-button" ).hide();
			
		}
	}