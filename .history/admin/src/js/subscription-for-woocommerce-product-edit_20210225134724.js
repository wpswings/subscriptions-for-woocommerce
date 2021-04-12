(function( $ ) {
	'use strict';

    $(document).ready(function() {
        $('#_mwb_sfw_product').on('change', function(){
           if( $('#_mwb_sfw_product').prop('checked') ) {
               $(document).find('.mwb_sfw_product_options').show();
           }
           else{
            $(document).find('.mwb_sfw_product_options').hide();
           }
        });
    });
})( jQuery );