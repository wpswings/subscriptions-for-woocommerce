jQuery(function(){
	if ( ! window.wc ) {
		return;
	}
	const { registerCheckoutFilters } = window.wc.blocksCheckout;

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
		
	    const sfwPrice = cartItem.find( item => item.name === 'wps-sfw-price-html');
		
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
	    const cartItem = args?.cartItem.item_data;

		const wspData = cartItem.find( item => item.name === 'wps-wsp-switch-direction');
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