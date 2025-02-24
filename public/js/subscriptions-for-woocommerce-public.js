(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
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
	jQuery(document).ready(function($) {
		$('.wps_sfw_subs_box-button').on('click', function(e) {
			e.preventDefault();

			
			$('#wps_sfw_subs_box-popup').css('display','flex');
			$('#wps_sfw_subs_box-popup').fadeIn();

			
		});

		$('.wps_sfw_subs_box-close, #wps_sfw_subs_box-popup').on('click', function() {
			$('#wps_sfw_subs_box-popup').fadeOut();
		});

		$('.wps_sfw_subs_box-content').on('click', function(e) {
			e.stopPropagation();
		});

		$('.wps_sfw_sub_box_prod_add_btn').on('click', function(e) {
			e.preventDefault();
			let $btn = $(this);
			let $input = $btn.prev('.wps_sfw_sub_box_prod_count'); // Get input field
			let $minusBtn = $input.prev('.wps_sfw_sub_box_prod_minus_btn'); // Get minus button
			let count = parseInt($input.val()) || 0;

			var wps_sfw_sub_box_price = $btn.prev('.wps_sfw_sub_box_prod_count').data('wps_sfw_sub_box_price'); 

			var wps_sfw_subscription_box_price = $('.wps_sfw-sb-cta-total').data('wps_sfw_subscription_box_price');
			console.log(wps_sfw_subscription_box_price); 
			// Get the existing price from the span and convert it to a number
			if( wps_sfw_subscription_box_price == 0 ){

				var existing_price = parseFloat($('.wps_sfw-sb-cta-total span').text()) || 0;
				
				// Calculate the new total price
				var wps_sfw_sub_box_total = existing_price + wps_sfw_sub_box_price;
				
				// Update the span with the new total
				$('.wps_sfw-sb-cta-total span').text(wps_sfw_sub_box_total.toFixed(2));
			}
			
			// console.log(wps_sfw_sub_box_total);
			// if (count < 1) {
				count = 1;
				$input.val(count).show(); // Update and show input
				$minusBtn.show(); // Show minus button
				// $btn.hide();
			// }
		});

		$('.wps_sfw_sub_box_prod_minus_btn').on('click', function(e) {
			e.preventDefault();
			let $btn = $(this);
			let $input = $btn.next('.wps_sfw_sub_box_prod_count'); // Get input field
			let count = parseInt($input.val()) || 0;
			let $plusbtn = $('.wps_sfw_sub_box_prod_add_btn');
			$plusbtn.show(); 
			// if (count > 0) {
				count = 0;
				$input.val(count).hide(); // Hide input when 0
				$btn.hide(); // Hide minus button
			// }
		});

		// Ensure manual input stays within range
		// $('.wps_sfw_sub_box_prod_count').on('input', function() {
		// 	let value = parseInt($(this).val());
		// 	if (value > 1) $(this).val(1);
		// 	if (value < 0) $(this).val(0);
		// });

		//new code.
		// jQuery(document).ready(function($) {
			$('#wps_sfw_subs_box-form').on('submit', function(e) {
				e.preventDefault();
			
				let wps_sfw_subscription_product_id = $('.wps_sfw_subscription_product_id').data('subscription-box-id'); 
				
				let formData = {
					products: [],
					total: $('.wps_sfw-sb-cta-total>span').text(),
					wps_sfw_subscription_product_id : wps_sfw_subscription_product_id,
				};
			
				$('.wps_sfw_sub_box_prod_container .wps_sfw_sub_box_prod_item').each(function() {
					let container = $(this);
					let productId = container.find('.wps_sfw_sub_box_prod_add_btn').data('product-id'); 
					let quantity = container.find('.wps_sfw_sub_box_prod_count').val();

			
					if (productId && quantity > 0) {
						formData.products.push({
							product_id: productId,
							quantity: quantity
						});
					}
				});
			
				// console.log('Final Data:', formData);
			
				if (formData.products.length === 0) {
					alert('No products selected. Check your selection.');
					return;
				}
			
				$.ajax({
					url: sfw_public_param.ajaxurl,
					type: 'POST',
					data: {
						action: 'wps_sfw_handle_subscription_box',
						subscription_data: JSON.stringify(formData)
					},
					success: function(response) {
						console.log('Server Response:', response);
			
						if ( response.message === "Subscription added to cart!") {
							window.location.href = sfw_public_param.cart_url;; // Redirect to cart page
						} else {
							alert('Error: ' + (response.data || 'Something went wrong.'));
						}
					},
					error: function(error) {
						console.error('Error:', error);
						alert('Failed to process request.');
					}
				});
			});
			
			// });


		//new code. 

		
	});
})( jQuery );
