jQuery(function(){
	if ( ! window.wc ) {
		return;
	}
	const { registerCheckoutFilters } = window.wc.blocksCheckout;

	const { applyCheckoutFilter } = wc.blocksCheckout;

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

	
	const options = {
		filterName: 'subtotalPriceFormat',
		defaultValue: wpsSfwmodifySubtotalPriceFormat,
	}
	
	const filteredValue = applyCheckoutFilter( options );
	
	registerCheckoutFilters( 'wps-sfw-checkout-block', {
		subtotalPriceFormat: filteredValue,
		cartItemPrice: wpsWspmodifyCartItemPrice,
	} );
	console.log(filteredValue);
});