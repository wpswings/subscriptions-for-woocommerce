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

	    const sfwSubCheck = cartItem.find( item => item.name === 'wps-simple-subscription');

		console.log(sfwSubCheck);
	    if ( sfwPrice ) {
			val = sfwPrice?.value;
			if ( sfwSubCheck && 'yes' != sfwSubCheck?.value ) {
				// val = '';
			}
	        if ( val != '' ) {
	           return defaultValue + val;
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

	registerCheckoutFilters( 'example-extension', {
		subtotalPriceFormat: wpsSfwmodifySubtotalPriceFormat,
		cartItemPrice: wpsWspmodifyCartItemPrice,
	} );
});