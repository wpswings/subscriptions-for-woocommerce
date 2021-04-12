(function( $ ) {
	'use strict';

    $(document).ready(function() {
        function mwb_sfw_show_subscription_settings_tab(){
            if( $('#_mwb_sfw_product').prop('checked') ) {
                console.log('test1vcvxcv');
                $(document).find('.mwb_sfw_product_options').show();
            }
            else{
                console.log('test2');
             $(document).find('.mwb_sfw_product_options').hide();
            }
        }
        mwb_sfw_show_subscription_settings_tab();
        $('#_mwb_sfw_product').on('change', function(){
            mwb_sfw_show_subscription_aettings_tab();
        });
        
        
    });
})( jQuery );