(function( $ ) {
	'use strict';

    $(document).ready(function() {
        function mwb_sfw_show_subscription_settings_tab(){
            if( $('#_mwb_sfw_product').prop('checked') ) {
                
                $(document).find('.mwb_sfw_product_options').show();
                $(document).find('.mwb_sfw_product_options').removeClass('active');
            }
            else{
                
             $(document).find('.mwb_sfw_product_options').hide();
             $(document).find('#mwb_sfw_product_target_section').hide();
             $(document).find('.general_tab').addClass('active');
             $(document).find('#general_product_data').show();
             
            }
        }
        mwb_sfw_show_subscription_settings_tab();
        $('#_mwb_sfw_product').on('change', function(){
            mwb_sfw_show_subscription_settings_tab();
        });
        
         /*Subscription interval set*/
         $('#mwb_sfw_subscription_interval').on('change', function() {
            var current_selection = $(this).val();
            var expiry_interval = $('#mwb_sfw_subscription_expiry_interval');
            if ( current_selection == 'day' ) {
                 expiry_interval.empty();
                 expiry_interval.append($('<option></option>').attr('value','day').text( sfw_product_param.day ) );
                 expiry_interval.append($('<option></option>').attr('value','week').text( sfw_product_param.week ) );
                 expiry_interval.append($('<option></option>').attr('value','month').text( sfw_product_param.month ) );
                 expiry_interval.append($('<option></option>').attr('value','year').text( sfw_product_param.year ) );
            }
            else if ( current_selection == 'week' ) {
                 expiry_interval.empty();
                 expiry_interval.append($('<option></option>').attr('value','week').text( sfw_product_param.week ) );
                 expiry_interval.append($('<option></option>').attr('value','month').text( sfw_product_param.month ) );
                 expiry_interval.append($('<option></option>').attr('value','year').text( sfw_product_param.year ) );
            }
            else if( current_selection == 'month' ) {
                expiry_interval.empty();
                expiry_interval.append($('<option></option>').attr('value','month').text( sfw_product_param.month ) );
                expiry_interval.append($('<option></option>').attr('value','year').text( sfw_product_param.year ) );
            }
            else if( current_selection == 'year' ) {
                expiry_interval.empty();
                expiry_interval.append($('<option></option>').attr('value','year').text( sfw_product_param.year ) );
            }
        });

        
        /*Expiry interval validation*/
        $(document).on('submit','#post', function(e) {
       
            var subscription_number = $('#mwb_sfw_subscription_number').val();
            var subscription_expiry = $('#mwb_sfw_subscription_expiry_number').val();
            if ( subscription_expiry != '' ) {
                if ( subscription_expiry < subscription_number ) {
                    alert( sfw_product_param.expiry_notice );
                    jQuery('#publish').siblings('span').removeClass('is-active');
                    $('#publish').removeClass('disabled');
                    e.preventDefault();
                }
            }
            
        });
    });
})( jQuery );