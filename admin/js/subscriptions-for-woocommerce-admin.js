
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
		console.log(pending_product);
		// const sfw_pending_order      = sfw_admin_param.wps_sfw_pending_order;
		// const sfw_pending_order_count = sfw_admin_param.wps_sfw_pending_order_count
		$( document ).on( 'click', '#migration-button', function(e) {
			e.preventDefault();
			Swal.fire({
				icon: 'warning',
				title: 'We Have got ' + pending_product_count + ' Products<br/> And ' + ' Data',
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
						title   : 'Product are being imported!',
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
			// console.log(request);
			jQuery.post( ajaxUrl , request ).done(function( response ){
				console.log(response);
				products = JSON.parse( response );
			}).then(
				function( products ) {
					products = JSON.parse( products ).products;
					count = Object.keys(products).length;
					// count = products.length;
					jQuery('.order-progress-report').text( count + ' are left to import' );

					if( ! jQuery.isEmptyObject(products) ) {
						startImport(products);
					} else{
						// All products imported!
						Swal.fire({
								title   : 'All of the Data are Migrated successfully!',
							});
						
					}
					// else {
					// 	// All products imported!
					// 	Swal.fire({
					// 		title   : 'Order are being imported!',
					// 		html    : 'Do not reload/close this tab.',
					// 		footer  : '<span class="order-progress-report">' + sfw_pending_order_count + ' are left to import',
					// 		didOpen: () => {
					// 		Swal.showLoading()
					// 		}
					// 	});
					// 	// startImportOrders( sfw_pending_order );
					// }
			}, function(error) {
				console.error(error);
			});
		}

		// const startImportOrders = ( orders ) => {
		// 	// console.log(orders);
		// 	var count;
		// 	var event   = 'wps_sfw_import_single_order';
		// 	var request = { action, event, nonce, orders };
		// 	jQuery.post( ajaxUrl , request ).done(function( response ){
		// 		orders = JSON.parse( response );
		// 	}).then(
		// 	function( orders ) {
		// 		orders = JSON.parse( orders ).orders;
		// 		if( ! orders == undefined ){

		// 			count = Object.keys(orders).length;
		// 			// count = orders.length;
		// 			jQuery('.order-progress-report').text( count + ' are left to import' );
		// 			if( ! jQuery.isEmptyObject(orders) ) {
		// 				startImportOrders(orders);
		// 			} else {
		// 				// All orders imported!
		// 				Swal.fire({
		// 					title   : 'All of the Data are Migrated successfully!',
		// 				});
		// 			}
		// 		}
		// 	}, function(error) {
		// 		console.error(error);
		// 	});

		// }

	});

	$(window).load(function(){
		// add select2 for multiselect.
		if( $(document).find('.wps-defaut-multiselect').length > 0 ) {
			$(document).find('.wps-defaut-multiselect').select2();
		}
	});

	})( jQuery );
