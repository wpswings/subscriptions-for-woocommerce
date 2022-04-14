
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

	});

	jQuery(document).ready( function($) {

		const ajaxUrl          = sfw_admin_param.ajaxurl;
		const nonce    		   = sfw_admin_param.wps_sfw_react_nonce;
		const action           = sfw_admin_param.wps_sfw_callback;
		const pending_product 	   = sfw_admin_param.wps_sfw_pending_product;
		const pending_product_count  = sfw_admin_param.wps_sfw_pending_product_count;

		const pending_orders 	   = sfw_admin_param.wps_sfw_pending_orders;
		const pending_orders_count  = sfw_admin_param.wps_sfw_pending_orders_count;

		const pending_subscription 	   = sfw_admin_param.wps_sfw_pending_subs;
		const pending_subscription_count  = sfw_admin_param.wps_sfw_pending_subs_count;
		console.log(sfw_admin_param);
		
		$( document ).on( 'click', '#wps_sfw_migration-button', function(e) {
			e.preventDefault();
			Swal.fire({
				icon: 'warning',
				title: 'We have got ' + pending_product_count + ' Product,</br> ' + pending_orders_count + ' Renewal order</br>And ' + pending_subscription_count + ' Subscription Related Data',
				text: 'Click to start import',
				footer: 'Please do not reload/close this page until prompted',
				showCloseButton: true,
				showCancelButton: true,
				focusConfirm: false,
				confirmButtonText:
			  		'<i class="fa fa-thumbs-up"></i> Start!',
				confirmButtonAriaLabel: 'Thumbs up',
				cancelButtonText:
			  		'<i class="fa fa-thumbs-down"> Cancel</i>',
				cancelButtonAriaLabel: 'Thumbs down'	  
			}).then((result)=>{
				if (result.isConfirmed) {
					Swal.fire({
						title   : 'Product Data are being migrated!',
						html    : 'Do not reload/close this tab.',
						footer  : '<span class="order-progress-report">' + pending_product_count + ' are left to import',
						didOpen: () => {
							Swal.showLoading()
						}
					});
					startImport( pending_product  );
				} else if (result.isDismissed) {
					Swal.fire('Import Stopped', '', 'info');
				}
			})
		});
	
		const startImport = ( products ) => {
			var count;
			var event   = 'wps_sfw_import_single_product';
			var request = { action, event, nonce, products };
			jQuery.post( ajaxUrl , request ).done(function( response ){
				products = JSON.parse( response );
			}).then(
				function( products ) {
					products = JSON.parse( products ).products;
					if ( jQuery.isEmptyObject(products) ) {
						count = 0;
					} else {
						count = Object.keys(products).length;
					}
					// count = products.length;
					jQuery('.order-progress-report').text( count + ' are left to import' );

					if( ! jQuery.isEmptyObject(products) ) {
						startImport(products);
					} else{
						Swal.fire({
							title   : 'Renewal Orders Data are being migrated!',
							html    : 'Do not reload/close this tab.',
							footer  : '<span class="order-progress-report">' + pending_orders_count + ' are left to import',
							didOpen: () => {
								Swal.showLoading()
							}
						});
						// All products imported!
						startRenImport( pending_orders );
						
					}
			}, function(error) {
				console.error(error);
			});
		}

		const startRenImport = ( orders ) => {
			var count;
			var event   = 'wps_sfw_import_single_renewal';
			var request = { action, event, nonce, orders };
		
			jQuery.post( ajaxUrl , request ).done(function( response ){
				orders = JSON.parse( response );
			}).then(
				function( orders ) {
					orders = JSON.parse( orders ).orders;
					if ( jQuery.isEmptyObject(orders) ) {
						count = 0;
					} else {
						count = Object.keys(orders).length;
					}
					// count = products.length;
					jQuery('.order-progress-report').text( count + ' are left to import' );
					if( ! jQuery.isEmptyObject(orders) ) {
						startRenImport(orders);
					} else{
						Swal.fire({
							title   : 'Subscriptions Orders Data are being migrated!',
							html    : 'Do not reload/close this tab.',
							footer  : '<span class="order-progress-report">' + pending_subscription_count + ' are left to import',
							didOpen: () => {
								Swal.showLoading()
							}
						});
						startSubImport( pending_subscription )
						
					}
			}, function(error) {
				console.error(error);
			});
		}
		const startSubImport = ( subscriptions ) => {
			var count;
			var event   = 'wps_sfw_import_single_subscription';
			var request = { action, event, nonce, subscriptions };
		
			jQuery.post( ajaxUrl , request ).done(function( response ){
				subscriptions = JSON.parse( response );
			}).then(
				function( subscriptions ) {
					subscriptions = JSON.parse( subscriptions ).subscriptions;
					if ( jQuery.isEmptyObject(subscriptions) ) {
						count = 0;
					} else {
						count = Object.keys(subscriptions).length;
					}
					// count = products.length;
					jQuery('.order-progress-report').text( count + ' are left to import' );

					if( ! jQuery.isEmptyObject(subscriptions) ) {
						startSubImport(subscriptions);
					} else{
						// All subscriptions are imported!
						Swal.fire({
								title   : 'All of the Data are Migrated successfully!',
						});
						
					}
			}, function(error) {
				console.error(error);
			});
		}

	});

	$(window).load(function(){
		// add select2 for multiselect.
		if( $(document).find('.wps-defaut-multiselect').length > 0 ) {
			$(document).find('.wps-defaut-multiselect').select2();
		}
	});

	})( jQuery );
	var wps_subscripiton_migration_success = function() {
	
		if ( sfw_admin_param.pending_product_count != 0 && sfw_admin_param.pending_orders_count != 0 && sfw_admin_param.pending_subscription_count != 0 ) {
			jQuery( "#wps_sfw_migration-button" ).click();
			jQuery( "#wps_sfw_migration-button" ).show();
		}else{
			jQuery( "#wps_sfw_migration-button" ).hide();
			
		}
	}