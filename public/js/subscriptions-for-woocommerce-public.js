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
			
			// Get the existing price from the span and convert it to a number
			if( wps_sfw_subscription_box_price == 0 ){

				var existing_price = parseFloat($('.wps_sfw-sb-cta-total span').text()) || 0;
				
				// Calculate the new total price
				var wps_sfw_sub_box_total = existing_price + wps_sfw_sub_box_price;
				
				// Update the span with the new total
				$('.wps_sfw-sb-cta-total span').text(wps_sfw_sub_box_total.toFixed(2));
			}
			
			
				count = count + 1;
				$input.val(count).show(); // Update and show input
				$minusBtn.show(); // Show minus button
			
		});

		$('.wps_sfw_sub_box_prod_minus_btn').on('click', function(e) {
			e.preventDefault();
			let $btn = $(this);
			let $input = $btn.next('.wps_sfw_sub_box_prod_count'); // Get input field
			let count = parseInt($input.val()) || 0;
			let $plusbtn = $('.wps_sfw_sub_box_prod_add_btn');
			$plusbtn.show(); 

			var wps_sfw_sub_box_price = $btn.next('.wps_sfw_sub_box_prod_count').data('wps_sfw_sub_box_price'); 

			var wps_sfw_subscription_box_price = $('.wps_sfw-sb-cta-total').data('wps_sfw_subscription_box_price');
			
			// Get the existing price from the span and convert it to a number
			if( wps_sfw_subscription_box_price == 0 ){

				var existing_price = parseFloat($('.wps_sfw-sb-cta-total span').text()) || 0;
				
				// Calculate the new total price
				var wps_sfw_sub_box_total = existing_price - wps_sfw_sub_box_price;
				
				// Update the span with the new total
				$('.wps_sfw-sb-cta-total span').text(wps_sfw_sub_box_total.toFixed(2));
			}
			
				count = count - 1;
				$input.val(count) // Hide input when 0
				if( count == 0 ){
					$input.val(count).hide(); 
					$btn.hide(); // Hide minus button
				}
			
		});

	
		$('.wps_sfw-empty-cart').on('click', function() {
			console.log('Empty Cart button clicked');
			// Send AJAX request to empty the cart
			$.ajax({
				url: sfw_public_param.ajaxurl,
				type: 'POST',
				data: {
					action: 'wps_sfw_sub_box_empty_cart',
					nonce: sfw_public_param.sfw_public_nonce,
				},
				success: function(response) {
					console.log('Cart emptied:', response);
					// Show success message and update cart totals
					alert('Your cart has been emptied!');
					$('.wps_sfw-empty-cart').hide();
					$(document.body).trigger('updated_cart_totals');
				}
			});
		});

		$('.wps_sfw_product_page-empty-cart').on('click', function() {
			console.log('Empty Cart button clicked');
			// Send AJAX request to empty the cart
			$.ajax({
				url: sfw_public_param.ajaxurl,
				type: 'POST',
				data: {
					action: 'wps_sfw_sub_box_empty_cart',
					nonce: sfw_public_param.sfw_public_nonce,
				},
				success: function(response) {
					console.log('Cart emptied:', response);
					// Show success message and update cart totals
					alert('Your cart has been emptied!');
					$(document.body).trigger('updated_cart_totals');
					window.location.assign(window.location.href);
				}
			});
		});

		//new code.
		$('#wps_sfw_subs_box-form').on('submit', function (e) {
			e.preventDefault();
			var $form = $('#wps_sfw_subs_box-form');
			var $current = $form.find('.wps_sfw-sb-step:visible');
			if(!validateStep($current)){
				e.preventDefault();
				return false;
			}
			let wps_sfw_subscription_product_id = $('.wps_sfw_subscription_product_id').data('subscription-box-id');
		
			let formData = {
				products: [],
				total: $('.wps_sfw-sb-cta-total>span').text(),
				wps_sfw_subscription_product_id: wps_sfw_subscription_product_id,
			};
		
			$('.wps_sfw_sub_box_prod_container .wps_sfw_sub_box_prod_item').each(function () {
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
		
			if (formData.products.length === 0) {
				$('.wps_sfw_subscription_box_error_notice').text('No products selected. Please add at least one product.').show();
				setTimeout(function() {
					$('.wps_sfw_subscription_box_error_notice').fadeOut(500);
				}, 5000); // Hide after 5 seconds
				return;
			}
		
			$.ajax({
				url: sfw_public_param.ajaxurl,
				type: 'POST',
				data: {
					action: 'wps_sfw_handle_subscription_box',
					subscription_data: JSON.stringify(formData),
					nonce: sfw_public_param.sfw_public_nonce,
				},
				success: function (response) {
					console.log('Server Response:', response);
		
					if (response.message === "Subscription added to cart!") {
						window.location.href = sfw_public_param.cart_url; // Redirect to cart page
					} else {
						// var emptyCartButton = '<button type="button" class="button wps_sfw-empty-cart" id="wps_sfw-empty-cart">Empty Cartssssdsdsds</button>';
						$('.wps_sfw_subscription_box_error_notice').html((response.data || 'Something went wrong.') ).show();
						$('.wps_sfw-empty-cart').show();
						setTimeout(function() {
							$('.wps_sfw_subscription_box_error_notice').fadeOut(500);
						}, 5000); // Hide after 5 seconds
						
					}
				},
				error: function (error) {
					console.error('Error:', error);
					$('.wps_sfw_subscription_box_error_notice').text('Failed to process request.').show();
					setTimeout(function() {
						$('.wps_sfw_subscription_box_error_notice').fadeOut(500);
					}, 5000); // Hide after 5 seconds
				}
			});
		});

		$(document).on('click','.wps_show_customer_subscription_box_popup', function(e) {
			e.preventDefault();
			$(this).next('.wps-attached-products-popup').addClass('active_customer_popup');
		});
		$(document).on('click','.wps_sfw_customer_close_popup', function(e) {
			$(this).parent('.wps-attached-products-popup').removeClass('active_customer_popup');
		});

		var $form = $('#wps_sfw_subs_box-form');
    if(!$form.length) return;

    var totalSteps = $form.find('.wps_sfw-sb-step').length;
    var $prevBtn = $form.find('.wps_sfw-sb-prev');
    var $nextBtn = $form.find('.wps_sfw-sb-next');
    var $addBtn  = $form.find('.wps_sfw_subscription_product_id');
    var $error   = $form.find('.wps_sfw_subscription_box_error_notice');

    function showError(msg){
		$error.text(msg).show();
		setTimeout(function() {
			$error.fadeOut(500);
		}, 5000); // Hide after 5 seconds
    }
    function clearError(){
        $error.hide().text('');
    }

    function validateStep($step){
        var min = parseInt($step.data('min-num'), 10) || 0;
        var max = parseInt($step.data('max-num'), 10) || 0;
        if(min === 0 && max === 0){ return true; }

        var totalQty = 0;
        $step.find('.wps_sfw_sub_box_prod_count').each(function(){
            totalQty += parseInt($(this).val(), 10) || 0;
        });
		console.log(totalQty, min, max);
        if(min && totalQty < min){
            showError("Please select at least " + min + " products in this step.");
            return false;
        }
        if(max && totalQty > max){
            showError("You can select maximum " + max + " products in this step.");
            return false;
        }
        return true;
    }

    function showStep(index){
        $form.find('.wps_sfw-sb-step').hide();
        $form.find('.wps_sfw-sb-step[data-step-index="'+index+'"]').show();
        clearError();

        if(index <= 1){
            $prevBtn.hide();
        } else {
            $prevBtn.show().data('goto', index-1);
        }

        if(index >= totalSteps){
            $nextBtn.hide();
            $addBtn.show();
        } else {
            $nextBtn.show().data('goto', index+1);
            $addBtn.hide();
        }
    }

    // Init first step
    showStep(1);

    $prevBtn.on('click', function(e){
        e.preventDefault();
        var gotoIndex = parseInt($(this).data('goto'), 10);
        showStep(gotoIndex);
    });

    $nextBtn.on('click', function(e){
        e.preventDefault();
        var $current = $form.find('.wps_sfw-sb-step:visible');
		 if(!validateStep($current)){
            e.preventDefault();
            return false;
        }
        var gotoIndex = parseInt($(this).data('goto'), 10);
        showStep(gotoIndex);
    });

    $form.on('submit', function(e){
        var $current = $form.find('.wps_sfw-sb-step:visible');
        if(!validateStep($current)){
            e.preventDefault();
            return false;
        }
    });


	});
})( jQuery );
