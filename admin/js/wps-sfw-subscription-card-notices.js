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
    }
);