/**
 * All of the code for notices on your admin-facing JavaScript source
 * should reside in this file.
 *
 * @package           woo-gift-cards-lite
 */
jQuery( document ).ready(
    function($){
        $( document ).on(
            'click',
            '#dismiss-banner',
            function(e){
                e.preventDefault();
                var data = {
                    action:'wps_sfw_dismiss_notice_banner',
                    wps_nonce:wps_sfw_branner_notice.wps_sfw_nonce
                };
                $.ajax(
                    {
                        url: wps_sfw_branner_notice.ajaxurl,
                        type: "POST",
                        data: data,
                        success: function(response)
                        {
                            window.location.reload();
                        }
                    }
                );
            }
        );
        // $(document).on('change','.block-editor-block-list__block[data-template-block-id="product-wps_sfw_subscription_interval"]', function() {
        //     var current_selection = $(this).find('select').val();
        //     console.log(current_selection);
        //     var expiry_interval = $('.block-editor-block-list__block[data-template-block-id="product-wps_sfw_subscription_expiry_interval"]').find('select');
        //     // console.log(expiry_interval);
        //       if ( current_selection == 'day' ) {
        //            expiry_interval.empty();
        //            expiry_interval.append($('<option></option>').attr('value','day').text( 'Day' ));

        //       }
        //       else if ( current_selection == 'week' ) {
        //            expiry_interval.empty();
        //            expiry_interval.append($('<option></option>').attr('value','week').text( 'Week' ));

        //       }
        //       else if( current_selection == 'month' ) {
        //           expiry_interval.empty();
        //           expiry_interval.append($('<option></option>').attr('value','month').text( 'Month' ));

        //       }
        //       else if( current_selection == 'year' ) {
        //           expiry_interval.empty();
        //           expiry_interval.append($('<option></option>').attr('value','year').text( 'Year' ));
        //       }
        //   });

       


    }

);