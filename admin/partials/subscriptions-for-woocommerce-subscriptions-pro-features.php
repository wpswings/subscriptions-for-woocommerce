<style>
.wsp-secion-pro-wrap h3 {
	margin: 0 0 15px;
    font-size: 20px;
    background-color: #F2F2F1;
    padding: 8px 15px;
    border-radius: 12px;
    position: relative;
	cursor: pointer;
}
.wsp-secion-pro-wrap h3::before {
    position: absolute;
	content: "\f347";
	top: 50%;
	right: 15px;
	transform: translateY(-50%);
	font-size: 24px;
	line-height: 20px;
	font-family: dashicons;
}
.wsp-secion-pro-wrap .wps-form-group {
    overflow: hidden;
    transition: opacity 0.4s ease-in-out, height 0.4s ease-out, padding 0.4s ease-out, margin 0.4s ease-out;
    padding: 0 15px;
}

.wsp-secion-pro-wrap .wps-form-group.wps_wsp_active_section {
    opacity: 1;
    height: auto;
    padding: 10px 15px;
    margin: 0 0 15px;
}
.wsp-secion-pro-wrap .wps-form-group__control {
    position: relative;
    padding-bottom: 25px;
}
</style>
<div class="wsp-secion-pro-wrap">
									
								
                <h3>Subscription Manage by Customer</h3>
            
        
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Give ability to pause the subscription for a certain time</label>
        </div>
        <div class="wps-form-group__control ">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_enable_pause_susbcription_by_customer" type="checkbox" id="wsp_enable_pause_susbcription_by_customer" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Give ability to reactivate the paused subscription by customer</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_start_pause_susbcription_by_customer" type="checkbox" id="wsp_start_pause_susbcription_by_customer" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Give ability to edit their active subscription</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_allow_customer_subsciption_edit" type="checkbox" id="wsp_allow_customer_subsciption_edit" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Allow the time duration for the subscription cancellation</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_allow_time_subscription_cancellation" type="checkbox" id="wsp_allow_time_subscription_cancellation" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
                                <div class="wps-form-group wps-wsp-number wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="wsp_time_duration_subscription_cancellation" class="wps-form-label">Enter the number of days after which the user will be able to cancel their subscription</label>
        </div>
        <div class="wps-form-group__control">
            <label class="mdc-text-field mdc-text-field--outlined">
                <span class="mdc-notched-outline mdc-notched-outline--no-label">
                    <span class="mdc-notched-outline__leading"></span>
                    <span class="mdc-notched-outline__notch">
                                                                </span>
                    <span class="mdc-notched-outline__trailing"></span>
                </span>
                <input class="mdc-text-field__input wsp-number-class" name="wsp_time_duration_subscription_cancellation" id="wsp_time_duration_subscription_cancellation" type="number" value="1" placeholder="" min="1">
            </label>
            <div class="mdc-text-field-helper-line">
                <div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"></div>
            </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Allow customer to choose subscription expiry date</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_allow_subscription_expiry_customer" type="checkbox" id="wsp_allow_subscription_expiry_customer" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
                                    
            
                <h3>Failed Renewal Attempts</h3>
            
        
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Enable automatic payment retry for failed attempts</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_enable_automatic_retry_failed_attempts" type="checkbox" id="wsp_enable_automatic_retry_failed_attempts" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
                                <div class="wps-form-group wps-wsp-number wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="wsp_after_no_failed_attempt_cancel" class="wps-form-label">Enter the number of failed payment attempts</label>
        </div>
        <div class="wps-form-group__control">
            <label class="mdc-text-field mdc-text-field--outlined">
                <span class="mdc-notched-outline mdc-notched-outline--no-label">
                    <span class="mdc-notched-outline__leading"></span>
                    <span class="mdc-notched-outline__notch">
                                                                </span>
                    <span class="mdc-notched-outline__trailing"></span>
                </span>
                <input class="mdc-text-field__input wsp-number-class" name="wsp_after_no_failed_attempt_cancel" id="wsp_after_no_failed_attempt_cancel" type="number" value="1" placeholder="Enter the number after certain failed attempts subscription will be canceled" min="1">
            </label>
            <div class="mdc-text-field-helper-line">
                <div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true">After a certain number of failed attempts, the subscription will be canceled.</div>
            </div>
        </div>
    </div>
                                    
            
                <h3>Email Notification</h3>
            
        
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Ability to send subscription is going to expire email notification</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_enable_plan_going_to_expire" type="checkbox" id="wsp_enable_plan_going_to_expire" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
                                <div class="wps-form-group wps-wsp-number wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="wsp_plan_going_to_expire_before_days" class="wps-form-label">Enter the number of days before subscription expire email send</label>
        </div>
        <div class="wps-form-group__control">
            <label class="mdc-text-field mdc-text-field--outlined">
                <span class="mdc-notched-outline mdc-notched-outline--no-label">
                    <span class="mdc-notched-outline__leading"></span>
                    <span class="mdc-notched-outline__notch">
                                                                </span>
                    <span class="mdc-notched-outline__trailing"></span>
                </span>
                <input class="mdc-text-field__input wsp-number-class" name="wsp_plan_going_to_expire_before_days" id="wsp_plan_going_to_expire_before_days" type="number" value="1" placeholder="Enter the number of days before subscription expire email send" min="1">
            </label>
            <div class="mdc-text-field-helper-line">
                <div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"></div>
            </div>
        </div>
    </div>
                                <div class="wps-form-group wps-wsp-number wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="wsp_send_before_recurring_reminder" class="wps-form-label">Enter the number of days before which you want to send the recurring payment reminder.</label>
        </div>
        <div class="wps-form-group__control">
            <label class="mdc-text-field mdc-text-field--outlined">
                <span class="mdc-notched-outline mdc-notched-outline--no-label">
                    <span class="mdc-notched-outline__leading"></span>
                    <span class="mdc-notched-outline__notch">
                                                                </span>
                    <span class="mdc-notched-outline__trailing"></span>
                </span>
                <input class="mdc-text-field__input wsp-number-class" name="wsp_send_before_recurring_reminder" id="wsp_send_before_recurring_reminder" type="number" value="1" placeholder="Enter the number of days before which you want to send the recurring payment reminder." min="1">
            </label>
            <div class="mdc-text-field-helper-line">
                <div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true">Enter the number of days before which you want to send the recurring payment reminder.</div>
            </div>
        </div>
    </div>
                                    
            
                <h3>Variable Subscriptions Upgrade/Downgrade</h3>
            
        
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Give ability to upgrade/downgrade</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_enbale_downgrade_upgrade_subscription" type="checkbox" id="wsp_enbale_downgrade_upgrade_subscription" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Allow Upgrade/Downgrade only with same interval</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_enable_allow_same_interval" type="checkbox" id="wsp_enable_allow_same_interval" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Do not allow Downgrade</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wps_wsp_downgrade_variable_subscription" type="checkbox" id="wps_wsp_downgrade_variable_subscription" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
                                <div class="wps-form-group wps-wsp-text wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="wps_wsp_upgrade_downgrade_btn_text" class="wps-form-label">Upgrade and Downgrade button text</label>
        </div>
        <div class="wps-form-group__control">
            <label class="mdc-text-field mdc-text-field--outlined">
                <span class="mdc-notched-outline mdc-notched-outline--upgraded mdc-notched-outline--notched">
                    <span class="mdc-notched-outline__leading"></span>
                    <span class="mdc-notched-outline__notch" style="width: 180.5px;">
                                                                        <span class="mdc-floating-label mdc-floating-label--float-above" id="my-label-id" style="">Upgrade and Downgrade button text</span>
                                                                </span>
                    <span class="mdc-notched-outline__trailing"></span>
                </span>
                <input class="mdc-text-field__input wsp-text-class" name="wps_wsp_upgrade_downgrade_btn_text" id="wps_wsp_upgrade_downgrade_btn_text" type="text" value="Upgrade and Downgrade" placeholder="Upgrade and Downgrade button text" min="">
            </label>
            <div class="mdc-text-field-helper-line">
                <div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"></div>
            </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Ability to accept prorate price during Upgrade/Downgrade</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_enable_prorate_on_price_downgrade_upgrade_subscription" type="checkbox" id="wsp_enable_prorate_on_price_downgrade_upgrade_subscription" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
                                <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="wps_wsp_manage_prorate_amount" class="wps-form-label">Manage prorate price during Upgrade/Downgrade</label>
        </div>
        <div class="wps-form-group__control wps-pl-4">
            <div class="wps-flex-col">
                                                        <div class="mdc-form-field">
                        <div class="mdc-radio">
                            <input name="wps_wsp_manage_prorate_amount" value="wps_manage_prorate_next_payment_date" type="radio" class="mdc-radio__native-control wsp-radio-switch-class">
                            <div class="mdc-radio__background">
                                <div class="mdc-radio__outer-circle"></div>
                                <div class="mdc-radio__inner-circle"></div>
                            </div>
                            <div class="mdc-radio__ripple"></div>
                        </div>
                        <label for="radio-1">Extend next payment date</label>
                    </div>	
                                                            <div class="mdc-form-field">
                        <div class="mdc-radio">
                            <input name="wps_wsp_manage_prorate_amount" value="wps_manage_prorate_using_wallet" type="radio" class="mdc-radio__native-control wsp-radio-switch-class">
                            <div class="mdc-radio__background">
                                <div class="mdc-radio__outer-circle"></div>
                                <div class="mdc-radio__inner-circle"></div>
                            </div>
                            <div class="mdc-radio__ripple"></div>
                        </div>
                        <label for="radio-1">Put left amount in the user wallet</label>
                    </div>	
                                                    </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Ability to accept signup fee during Upgrade/Downgrade</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_enable_signup_fee_downgrade_upgrade_subscription" type="checkbox" id="wsp_enable_signup_fee_downgrade_upgrade_subscription" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
                                    
            
                <h3>Renewal Date Synchronization</h3>
            
        
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Ability to take renewal payment from the certain date</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_start_susbcription_from_certain_date_of_month" type="checkbox" id="wsp_start_susbcription_from_certain_date_of_month" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
                                <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label class="wps-form-label" for="wsp_prorate_price_on_sync">Prorate price for certain date of month</label>
        </div>
        <div class="wps-form-group__control">
            <div class="wps-form-select">
                <select id="wsp_prorate_price_on_sync" name="wsp_prorate_price_on_sync" class="mdl-textfield__input wsp-select-class">
                                                                <option value="wps_wsp_prorate_no" selected="selected">
                            Do not charge prorate price											</option>
                                                                    <option value="wps_wsp_prorate_simple">
                            Charge prorate price											</option>
                                                                    <option value="wps_wsp_prorate_if_free_trial">
                            Charge prorate price, even if free trial											</option>
                                                            </select>
                <label class="mdl-textfield__label" for="octane"></label>
            </div>
        </div>
    </div>

                                    
            
                <h3>Manage Subscription Products and Quantities in the Cart</h3>
            
        
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Ability to allow the customer to add multiple quantity subscription in cart.</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_allow_multiple_quantity_subscription" type="checkbox" id="wsp_allow_multiple_quantity_subscription" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Ability to allow the customer to add multiple subscriptions in cart</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_allow_to_add_multiple_subscription_cart" type="checkbox" id="wsp_allow_to_add_multiple_subscription_cart" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
                                    
            
                <h3>Manage Shipping Cost</h3>
            
        
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Allow shipping cost during checkout only</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_allow_shipping_on_subscription_first_puchase" type="checkbox" id="wsp_allow_shipping_on_subscription_first_puchase" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Allow shipping costs for renewals</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_allow_shipping_subscription" type="checkbox" id="wsp_allow_shipping_subscription" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>

    <h3>User Roles</h3>
    <div class="wps-form-group wps_pro_settings_tag">
            <div class="wps-form-group__label">
                <label class="wps-form-label" for="wps_wsp_subsciber_user_role">Default subscriber role</label>
            </div>
            <div class="wps-form-group__control">
                <div class="wps-form-select">
                    <select id="wps_wsp_subsciber_user_role" name="wps_wsp_subsciber_user_role" class="mdl-textfield__input wsp-select-class">
                                                                    <option value="none">
                                Choose any role											</option>
                                                                        <option value="administrator">
                                Administrator											</option>
                                                                        <option value="editor">
                                Editor											</option>
                                                                        <option value="author">
                                Author											</option>
                                                                        <option value="contributor">
                                Contributor											</option>
                                                                        <option value="subscriber" selected="selected">
                                Subscriber											</option>
                                                                        <option value="customer">
                                Customer											</option>
                                                                        <option value="shop_manager">
                                Shop manager											</option>
                                                                </select>
                    <br>
                    <label class="mdl-textfield__label" for="octane">Assign this role to new users when a subscription is activated, either manually or after a successful purchase</label>
                </div>
            </div>
        </div>
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label class="wps-form-label" for="wps_wsp_inactive_subscriber_role">Inactive subscriber role</label>
        </div>
        <div class="wps-form-group__control">
            <div class="wps-form-select">
                <select id="wps_wsp_inactive_subscriber_role" name="wps_wsp_inactive_subscriber_role" class="mdl-textfield__input wsp-select-class">
                                                                <option value="none">
                            Choose any role											</option>
                                                                    <option value="administrator">
                            Administrator											</option>
                                                                    <option value="editor">
                            Editor											</option>
                                                                    <option value="author">
                            Author											</option>
                                                                    <option value="contributor">
                            Contributor											</option>
                                                                    <option value="subscriber">
                            Subscriber											</option>
                                                                    <option value="customer" selected="selected">
                            Customer											</option>
                                                                    <option value="shop_manager">
                            Shop manager											</option>
                                                            </select>
                <br>
                <label class="mdl-textfield__label" for="octane">Assign this role when a subscriber's subscription is cancelled or expires</label>
            </div>
        </div>
    </div>
                                    
            
    <h3>Other Settings</h3>
            
        
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Ability to checkout with BACS, COD and Cheque Payment Gateways</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch mdc-switch--checked">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_enbale_accept_manual_payment" type="checkbox" id="wsp_enbale_accept_manual_payment" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="true">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
        
    <div class="wps-form-group wps_pro_settings_tag">
        <div class="wps-form-group__label">
            <label for="" class="wps-form-label">Allow start date on subscription products.</label>
        </div>
        <div class="wps-form-group__control">
            <div>
                <div class="mdc-switch">
                    <div class="mdc-switch__track"></div>
                    <div class="mdc-switch__thumb-underlay mdc-ripple-upgraded mdc-ripple-upgraded--unbounded" style="--mdc-ripple-fg-size: 28px; --mdc-ripple-fg-scale: 1.7142857142857142; --mdc-ripple-left: 10px; --mdc-ripple-top: 10px;">
                        <div class="mdc-switch__thumb"></div>
                        <input name="wsp_allow_start_date_subscription" type="checkbox" id="wsp_allow_start_date_subscription" value="on" class="mdc-switch__native-control wsp-radio-switch-class" role="switch" aria-checked="false">
                    </div>
                    <label for="checkbox-1"></label>
                </div>
            </div>
        </div>
    </div>
    <div class="wps-form-group-save">   
    </div>
