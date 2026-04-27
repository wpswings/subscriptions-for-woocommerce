jQuery(document).ready(function($) {

    const MDCText = mdc.textField.MDCTextField;
    const textField = [].map.call(document.querySelectorAll('.mdc-text-field'), function(el) {
        return new MDCText(el);
    });
    const MDCRipple = mdc.ripple.MDCRipple;
    const buttonRipple = [].map.call(document.querySelectorAll('.mdc-button'), function(el) {
        return new MDCRipple(el);
    });
    const MDCSwitch = mdc.switchControl.MDCSwitch;
    const switchControl = [].map.call(document.querySelectorAll('.mdc-switch'), function(el) {
        return new MDCSwitch(el);
    });

    var dialog = '';
    if ( $('.wps-sfw-on-boarding-dialog').length > 0 ) {
        dialog = mdc.dialog.MDCDialog.attachTo(document.querySelector('.wps-sfw-on-boarding-dialog'));
    }
    /*if device is mobile*/
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        jQuery('body').addClass('mobile-device');
    }

    var deactivate_url = '';

    // Add Select2.
    jQuery('.on-boarding-select2').select2({
        placeholder: 'Select All Suitable Options...',
    });

    // On click of deactivate.
    if ('plugins.php' == wps_sfw_onboarding.sfw_current_screen) {

        wps_sfw_bind_deactivation_popup(wps_sfw_onboarding.sfw_current_supported_slug);
        wps_sfw_toggle_deactivation_reason_field(jQuery('input[name="plugin_deactivation_reason"]:checked').val() || '');

        jQuery(document).on('change', 'input[name="plugin_deactivation_reason"]', function() {
            wps_sfw_toggle_deactivation_reason_field(jQuery(this).val());
        });
    } else {
        
        // Show Popup after 1 second of entering into the WPS pagescreen.
        if (jQuery('#wps-sfw-show-counter').length > 0 && jQuery('#wps-sfw-show-counter').val() == 'not-sent') {
            setTimeout(wps_sfw_show_onboard_popup, 1000);
        }
    }

    /* Close Button Click */
    jQuery(document).on('click', '.wps-sfw-on-boarding-close-btn a', function(e) {
        e.preventDefault();
        wps_sfw_hide_onboard_popup();
    });

    /* Skip and deactivate. */
    jQuery(document).on('click', '.wps-sfw-deactivation-no_thanks', function(e) {
        e.preventDefault();

        if (deactivate_url) {
            window.location.replace(deactivate_url);
        }
        wps_sfw_hide_onboard_popup();
    });

    /* Skip For a day. */
    jQuery(document).on('click', '.wps-sfw-on-boarding-no_thanks', function(e) {

        jQuery.ajax({
            type: 'post',
            dataType: 'json',
            url: wps_sfw_onboarding.ajaxurl,
            data: {
                nonce: wps_sfw_onboarding.sfw_auth_nonce,
                action: 'sfw_skip_onboarding_popup',
            },
            success: function(msg) {
                wps_sfw_hide_onboard_popup();
            }
        });

    });

    /* Submitting Form */
    jQuery(document).on('submit', 'form.wps-sfw-on-boarding-form', function(e) {

        e.preventDefault();
        var form_data = JSON.stringify(jQuery('form.wps-sfw-on-boarding-form').serializeArray());

        jQuery.ajax({
            type: 'post',
            dataType: 'json',
            url: wps_sfw_onboarding.ajaxurl,
            data: {
                nonce: wps_sfw_onboarding.sfw_auth_nonce,
                action: 'wps_sfw_send_onboarding_data',
                form_data: form_data,
            },
            success: function(msg) {
                if ('plugins.php' == wps_sfw_onboarding.sfw_current_screen) {
                    window.location.replace(deactivate_url);
                }
                wps_sfw_hide_onboard_popup();
            }
        });
    });

    /* Open Popup */
    function wps_sfw_show_onboard_popup() {
        if (!dialog) {
            return;
        }

        dialog.open();
        if (!jQuery('body').hasClass('mobile-device')) {
            jQuery('body').addClass('wps-on-boarding-wrapper-control');
        }
    }

    /* Close Popup */
    function wps_sfw_hide_onboard_popup() {
        if (!dialog) {
            return;
        }

        dialog.close();
        if (!jQuery('body').hasClass('mobile-device')) {
            jQuery('body').removeClass('wps-on-boarding-wrapper-control');
        }
    }

    function wps_sfw_toggle_deactivation_reason_field(selected_reason) {
        var $deactivation_field = jQuery('#wps-sfw-deactivation-reason-text').closest('.wps-sfw-setting-field');

        if (!$deactivation_field.length) {
            return;
        }

        if ('other' === selected_reason) {
            $deactivation_field.removeClass('wps-sfw-setting-field--hidden');
        } else {
            $deactivation_field.addClass('wps-sfw-setting-field--hidden');
        }
    }



    function wps_sfw_open_deactivation_popup($trigger) {
        var placeholder = '';
        var plugin_name = '';
        var $deactivation_reason = jQuery('#wps-sfw-deactivation-reason-text');

        deactivate_url = $trigger.attr('href');
        plugin_name = $trigger.attr('aria-label') || $trigger.closest('tr').find('.plugin-title strong').text() || '';
        plugin_name = plugin_name.replace(/^Deactivate\s+/i, '').trim();

        jQuery('#wps-sfw-plugin-name').val(plugin_name);
        if (plugin_name) {
            jQuery('.wps-sfw-on-boarding-heading').text(plugin_name + ' Feedback');
        }

        if ($deactivation_reason.length) {
            placeholder = $deactivation_reason.attr('placeholder') || '';

            if (placeholder.indexOf('{plugin-name}') !== -1) {
                $deactivation_reason.attr('placeholder', placeholder.replace('{plugin-name}', plugin_name));
            }
        }

        wps_sfw_show_onboard_popup();
    }

    /* Attach deactivation popup to supported plugin rows reliably across plugin list refreshes. */
    function wps_sfw_bind_deactivation_popup(all_slugs) {
        jQuery(document).on('click', 'tr[data-slug] .deactivate a', function(e) {
            var slug = jQuery(this).closest('tr').attr('data-slug') || '';

            if (-1 === jQuery.inArray(slug, all_slugs)) {
                return;
            }

            e.preventDefault();
            wps_sfw_open_deactivation_popup(jQuery(this));
        });
    }

    // End of scripts.
});
