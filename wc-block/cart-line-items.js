jQuery(function($){
	if ( ! window.wc ) {
		return;
	}
	if ( typeof window.sfw_public_block === 'undefined' || typeof window.sfw_public_param === 'undefined' ) {
		return;
	}
	if ( ! window.wc.blocksCheckout || ! window.wc.blocksCheckout.registerCheckoutFilters ) {
		return;
	}

	var registerCheckoutFilters = window.wc.blocksCheckout.registerCheckoutFilters;
	var cartItemRequestCache = {};
	var cartItemRequestPending = {};

	function getArgsContext( args ) {
		return ( args && args.context ) ? args.context : '';
	}

	function getCartItemData( args ) {
		return ( args && args.cartItem && args.cartItem.item_data ) ? args.cartItem.item_data : null;
	}

	function wpsSfwmodifySubtotalPriceFormat( defaultValue, extensions, args ) {
		var isCartContext = ( getArgsContext( args ) === 'cart' );
		if ( ! isCartContext ) {
			return defaultValue;
		}

		var cartItem = getCartItemData( args );
		if ( ! cartItem ) {
			return defaultValue;
		}

		var sfwPrice = cartItem.find( function( item ) {
			return item && item.name === 'wps-sfw-price-html';
		} );

		if ( sfwPrice && typeof sfwPrice.value !== 'undefined' && sfwPrice.value !== '' ) {
			return defaultValue + ' ' + sfwPrice.value;
		}

		return defaultValue;
	}

	function applyAttachedProductsUI( attachedProducts, cartBoxIndex ) {
		if ( ! attachedProducts || ! attachedProducts.length ) {
			return;
		}

		if ( jQuery('.wps_show_customer_subscription_box_popup').length > 0 ) {
			return;
		}

		var attachedProductsHtml = '<div class="wps-attached-products-popup"><strong>Attached Products:</strong><ul>';
		for ( var i = 0; i < attachedProducts.length; i++ ) {
			var product = attachedProducts[i];
			if ( ! product ) {
				continue;
			}
			attachedProductsHtml += '<li><img src="' + product.image + '" width="40" height="40" />' + product.name + ' x ' + product.quantity + '</li>';
		}
		attachedProductsHtml += '</ul><span class="wps_sfw_customer_close_popup" style="cursor: pointer;">&times;</span></div>';

		var viewLabelHTML = '<a href="#" class="wps_show_customer_subscription_box_popup">View Attached Products</a>' + attachedProductsHtml;

		var containers = [
			jQuery(".wc-block-components-order-summary-item").eq( cartBoxIndex ).find('.wc-block-components-product-name'),
			jQuery(".wc-block-cart-items__row").eq( cartBoxIndex ).find('.wc-block-cart-item__prices')
		];

		for ( var c = 0; c < containers.length; c++ ) {
			var container = containers[c];
			if ( container && container.length ) {
				container.after( viewLabelHTML );
			}
		}
	}

	function wpsWspmodifyCartItemPrice( defaultValue, extensions, args ) {
		var ctx = getArgsContext( args );
		var isCartContext = ( ctx === 'cart' || ctx === 'summary' );
		if ( ! isCartContext ) {
			return defaultValue;
		}

		var cartItem = getCartItemData( args );
		if ( ! cartItem ) {
			return defaultValue;
		}

		var cartkey = cartItem.find( function( item ) {
			return item && item.name === 'wps_sfw_subscription_box_cart_key';
		} );
		var cartIndex = cartItem.find( function( item ) {
			return item && item.name === 'wps_sfw_subscription_box_cart_index';
		} );

		if ( cartkey && cartIndex && cartkey.value ) {
			var cartKey = cartkey.value;
			var cartBoxIndex = parseInt( cartIndex.value, 10 );

			if ( cartItemRequestCache.hasOwnProperty( cartKey ) ) {
				applyAttachedProductsUI( cartItemRequestCache[ cartKey ], cartBoxIndex );
				return defaultValue;
			}

			if ( cartItemRequestPending.hasOwnProperty( cartKey ) ) {
				return defaultValue;
			}

			cartItemRequestPending[ cartKey ] = true;
			jQuery.ajax({
				url: sfw_public_block.ajaxurl,
				type: "POST",
				data: {
					action: "wps_get_cart_item",
					cart_key: cartKey,
					nonce: sfw_public_param.sfw_public_nonce
				},
				success: function (response) {
					var attachedProducts = [];
					if ( response && response.success && response.data && response.data.attached_products ) {
						attachedProducts = response.data.attached_products;
					}
					cartItemRequestCache[ cartKey ] = attachedProducts;
					delete cartItemRequestPending[ cartKey ];
					applyAttachedProductsUI( attachedProducts, cartBoxIndex );
				},
				error: function () {
					delete cartItemRequestPending[ cartKey ];
				}
			});

			return defaultValue;
		}

		var wspData = cartItem.find( function( item ) {
			return item && item.name === 'wps-wsp-switch-direction';
		} );

		if ( wspData && typeof wspData.value !== 'undefined' && wspData.value !== '' ) {
			return defaultValue + ' ' + wspData.value;
		}

		return defaultValue;
	}

	function modifyPlaceOrderButtonLabel( defaultValue ) {
		if ( sfw_public_block.place_order_button_text ) {
			return sfw_public_block.place_order_button_text;
		}
		return defaultValue;
	}

	registerCheckoutFilters( 'wps-sfw-checkout-block', {
		subtotalPriceFormat: wpsSfwmodifySubtotalPriceFormat,
		cartItemPrice: wpsWspmodifyCartItemPrice,
		placeOrderButtonLabel: modifyPlaceOrderButtonLabel
	} );
});
