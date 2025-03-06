jQuery(function(){
	if ( ! window.wc ) {
		return;
	}
	var { registerCheckoutFilters } = window.wc.blocksCheckout;

	const wpsSfwmodifySubtotalPriceFormat = (
		defaultValue,
		extensions,
		args,
		validation
	) => {
		const isCartContext = args?.context === 'cart';
		
		if ( ! isCartContext ) {
			return defaultValue;
		}
	    const cartItem = args?.cartItem.item_data;
		var sfwPrice = '';
		if(cartItem != '' && cartItem != undefined){
	     sfwPrice = cartItem.find( item => item.name === 'wps-sfw-price-html');
		}
	    if ( sfwPrice ) {
			val = sfwPrice?.value;
	        if ( val != '' ) {
				return defaultValue + ' ' + val;
	        }
	    }
		return defaultValue;
	};

	const wpsWspmodifyCartItemPrice = (
		defaultValue,
		extensions,
		args,
		validation
	) => {
		const isCartContext = args?.context === 'cart' || args?.context === 'summary';
	
		if ( ! isCartContext ) {
			return defaultValue;
		}
		// console.log(args?.cartItem);
	    const cartItem = args?.cartItem.item_data;
		var wspData = '';
		if(cartItem != '' && cartItem != undefined){
			 wspData = cartItem.find( item => item.name === 'wps-wsp-switch-direction');
		}

		// subscription box.
		if ( ! cartItem ) {
			return defaultValue;
		}
		const cartkey = cartItem.find( item => item.name === 'wps_sfw_subscription_box_cart_key' );
		const cartIndex = cartItem.find( item => item.name === 'wps_sfw_subscription_box_cart_index' );

		if (cartkey) {
			let cartKey = cartkey.value;
			jQuery.ajax({
				url: sfw_public_block.ajaxurl,
				type: "POST",
				data: {
					action: "wps_get_cart_item",
					cart_key: cartKey,
					nonce: sfw_public_param.sfw_public_nonce,
				},
				success: function (response) {
					console.log(response);
					const cartBoxIndex = parseInt(cartIndex.value);
					if (response.success) {
						let attachedProducts = response.data.attached_products;
						if (attachedProducts.length > 0) {
							let attachedProductsHtml = `<div class="wps-attached-products-popup">
								<strong>Attached Products:</strong><ul>`;
		
							attachedProducts.forEach(product => {
								attachedProductsHtml += `<li>
									<img src="${product.image}" width="40" height="40" />
									${product.name} x ${product.quantity}
								</li>`;
							});
		
							attachedProductsHtml += `</ul>
								<span class="wps_sfw_customer_close_popup" style="cursor: pointer;">&times;</span>
							</div>`;

							const getElementRow = jQuery(".wc-block-cart-items__row").eq(cartBoxIndex).find( '.wc-block-cart-item__prices' );
							const getElementRowCheckout = jQuery(".wc-block-components-order-summary-item").eq(cartBoxIndex).find( '.wc-block-components-product-name' );

							if ( getElementRowCheckout.length ) {
								getElementRowCheckout.after( '<a href="#" class="wps_show_customer_subscription_box_popup">View Attached Products</a>' + attachedProductsHtml );
							}
							if ( getElementRow.length ) {
								getElementRow.after( '<a href="#" class="wps_show_customer_subscription_box_popup">View Attached Products</a>' + attachedProductsHtml );
							}
						}
					}
				},
				error: function (error) {
					console.error("Error fetching cart item:", error);
				},
			});
		
			// ✅ Return only the default value (without extra HTML)
			return defaultValue;
		}
		// subscription box.
		
	    if ( wspData ) {
			val = wspData?.value;
	        if ( val != '' ) {
	           return defaultValue + ' ' + val;
	        }
	    }
		return defaultValue;
	};

	

	const modifyPlaceOrderButtonLabel = ( defaultValue, extensions, args ) => {

		if ( sfw_public_block.place_order_button_text ) {
			return sfw_public_block.place_order_button_text;
		}
		return defaultValue;
	};

	
	registerCheckoutFilters( 'wps-sfw-checkout-block', {
		subtotalPriceFormat: wpsSfwmodifySubtotalPriceFormat,
		cartItemPrice: wpsWspmodifyCartItemPrice,
		placeOrderButtonLabel: modifyPlaceOrderButtonLabel,
	} );
});