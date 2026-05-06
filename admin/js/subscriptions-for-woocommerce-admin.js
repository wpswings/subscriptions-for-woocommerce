
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

		const adminBanner = $('.wps-sfw-admin-banner');
		const adminBannerDismiss = $('.wps-sfw-admin-banner__dismiss');
		const adminBannerStorageKey = 'wps_sfw_admin_banner_dismissed';

		if ( adminBanner.length && window.localStorage && localStorage.getItem( adminBannerStorageKey ) === '1' ) {
			adminBanner.hide();
		}

		$(document).on('click', '.wps-sfw-admin-banner__dismiss', function(e) {
			e.preventDefault();
			adminBanner.slideUp(180);
			if ( window.localStorage ) {
				localStorage.setItem( adminBannerStorageKey, '1' );
			}
		});

		const expertModal = $('[data-wps-sfw-expert-modal]');

		if ( expertModal.length ) {
			const expertForm = expertModal.find('[data-wps-sfw-expert-form]');
			const expertError = expertModal.find('[data-wps-sfw-expert-error]');
			const expertSuccess = expertModal.find('[data-wps-sfw-expert-success]');
			const expertSuccessMessage = expertModal.find('[data-wps-sfw-expert-success-message]');
			const expertSubmit = expertModal.find('[data-wps-sfw-expert-submit]');
			const defaultSuccessMessage = expertSuccessMessage.text();
			let expertCloseTimer = null;

			const setExpertError = function( message ) {
				expertError.text( message ).prop( 'hidden', ! message );
			};

			const setExpertSubmitState = function( isLoading ) {
				expertSubmit.prop( 'disabled', isLoading );
				expertSubmit.text( isLoading ? expertSubmit.data( 'loading-label' ) : expertSubmit.data( 'default-label' ) );
			};

			const resetExpertModal = function() {
				if ( expertCloseTimer ) {
					window.clearTimeout( expertCloseTimer );
					expertCloseTimer = null;
				}

				if ( expertForm.length && expertForm[0] ) {
					expertForm[0].reset();
				}

				expertModal.removeClass( 'is-submitting is-success' );
				expertForm.prop( 'hidden', false );
				expertSuccess.prop( 'hidden', true );
				expertSuccessMessage.text( defaultSuccessMessage );
				setExpertSubmitState( false );
				setExpertError( '' );
			};

			const openExpertModal = function() {
				resetExpertModal();
				expertModal.attr( 'aria-hidden', 'false' ).addClass( 'is-open' );
				$( 'body' ).addClass( 'wps-sfw-expert-modal-open' );
				expertModal.find( 'input, select, textarea, button' ).filter( ':visible:first' ).trigger( 'focus' );
			};

			const closeExpertModal = function() {
				expertModal.attr( 'aria-hidden', 'true' ).removeClass( 'is-open is-submitting is-success' );
				$( 'body' ).removeClass( 'wps-sfw-expert-modal-open' );
				resetExpertModal();
			};

			const normalizeExpertFormData = function( formElement ) {
				const formData = new window.FormData( formElement );
				const normalized = {};

				formData.forEach( function( value, key ) {
					const normalizedKey = key.replace( /\[\]$/, '' );

					if ( Object.prototype.hasOwnProperty.call( normalized, normalizedKey ) ) {
						if ( ! Array.isArray( normalized[ normalizedKey ] ) ) {
							normalized[ normalizedKey ] = [ normalized[ normalizedKey ] ];
						}

						normalized[ normalizedKey ].push( value );
						return;
					}

					normalized[ normalizedKey ] = normalizedKey !== key ? [ value ] : value;
				} );

				return normalized;
			};

			$(document).on( 'click', '[data-wps-sfw-open-expert-modal]', function( e ) {
				e.preventDefault();
				openExpertModal();
			});

			$(document).on( 'click', '[data-wps-sfw-close-expert-modal]', function( e ) {
				e.preventDefault();
				closeExpertModal();
			});

			$(document).on( 'keydown', function( e ) {
				if ( 'Escape' === e.key && expertModal.hasClass( 'is-open' ) ) {
					closeExpertModal();
				}
			});

			expertForm.on( 'submit', function( e ) {
				e.preventDefault();

				setExpertError( '' );
				setExpertSubmitState( true );
				expertModal.addClass( 'is-submitting' );

				$.ajax({
					url: sfw_admin_param.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: sfw_admin_param.talk_to_expert_action,
						nonce: sfw_admin_param.talk_to_expert_nonce,
						form_data: JSON.stringify( normalizeExpertFormData( this ) )
					}
				}).done( function( response ) {
					if ( ! response || ! response.success ) {
						setExpertError( response && response.data && response.data.message ? response.data.message : sfw_admin_param.talk_to_expert_error );
						return;
					}

					expertModal.removeClass( 'is-submitting' ).addClass( 'is-success' );
					expertForm.prop( 'hidden', true );
					expertSuccessMessage.text( response.data && response.data.message ? response.data.message : defaultSuccessMessage );
					expertSuccess.prop( 'hidden', false );

					expertCloseTimer = window.setTimeout( function() {
						closeExpertModal();
					}, parseInt( sfw_admin_param.talk_to_expert_success_delay, 10 ) || 2600 );
				}).fail( function( xhr ) {
					const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : sfw_admin_param.talk_to_expert_error;
					setExpertError( message );
				}).always( function() {
					expertModal.removeClass( 'is-submitting' );
					if ( ! expertModal.hasClass( 'is-success' ) ) {
						setExpertSubmitState( false );
					}
				});
			});
		}

	});

	$(window).load(function(){
		// add select2 for multiselect.
		if( $(document).find('.wps-defaut-multiselect').length > 0 ) {
			$(document).find('.wps-defaut-multiselect').select2();
		}
	});

	jQuery(document).ready(function() {

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

	//supported payment through js.
	jQuery(document).ready(function ($) {
		// Only run this on the WooCommerce > Settings > Payments tab
		if (typeof window.location.href !== 'undefined' && window.location.href.includes('page=wc-settings') && window.location.href.includes('tab=checkout')) {
			const interval = setInterval(function () {
		
				const $items = $('.woocommerce-item__payment-gateway');
				
				if ($items.length) {
					clearInterval(interval);
	
				
	
					$items.each(function () {
						
						let gatewayId = jQuery(this).attr('id');
	
						
	
						let content = '';
						if( sfw_admin_param.is_pro == 1 ){
							if (gatewayId === 'stripe' || gatewayId === 'wps_paypal' || gatewayId === 'payfast' || gatewayId === 'amazon_payments_advanced' || gatewayId === 'woocommerce_payments' || gatewayId === 'ppcp-gateway' || gatewayId === 'authnet' || gatewayId === 'braintree_credit_card' || gatewayId === 'eway' || gatewayId === 'mollie_wc_gateway_' || gatewayId === 'mollie_stand_in' || gatewayId === 'multisafepay_' || gatewayId === 'payhere' || gatewayId === 'stripe_' || gatewayId === 'wps_paypal_subscription') {
								// content = '<div class="custom-extra-info"> Supported Recurring Payment</div>';
								content = '<div class="wps_sfw_recurring_support_symbol"><img src="' + sfw_admin_param.recurring_payment_icon + '" alt="Supported" > ' + sfw_admin_param.Supported_recurring_payment + '</div>';
							}
						}else{
							if (gatewayId === 'stripe' || gatewayId === 'wps_paypal' || gatewayId === 'payfast' || gatewayId === 'amazon_payments_advanced') {
								// content = '<div class="custom-extra-info"> Supported Recurring Payment</div>';
								content = '<div class="wps_sfw_recurring_support_symbol"><img src="' + sfw_admin_param.recurring_payment_icon + '" alt="Supported" > ' + sfw_admin_param.Supported_recurring_payment + '</div>';
							}
						}
	
						$(this).find('.woocommerce-list__item-title').append(content);
					});
				}
			}, 1000);
		}
	});

	//supported payment through js.

	})( jQuery );
	var wps_subscripiton_migration_success = function() {
	
		if ( sfw_admin_param.pending_product_count != 0 && sfw_admin_param.pending_orders_count != 0 && sfw_admin_param.pending_subscription_count != 0 ) {
			jQuery( "#wps_sfw_migration-button" ).click();
			jQuery( "#wps_sfw_migration-button" ).show();
		}else{
			jQuery( "#wps_sfw_migration-button" ).hide();
			
		}
	}
