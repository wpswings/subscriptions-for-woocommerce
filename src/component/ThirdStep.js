import React,{useContext,useState} from 'react';
import Context from '../store/store';
import { List, ListSubheader, TextField, FormControlLabel, Checkbox, FormControl } from '@material-ui/core';
const { __ } = wp.i18n;
import { makeStyles } from '@material-ui/core/styles';
import PaymentGateway from './PaymentGateway';
const useStyles = makeStyles({
      margin: {
        marginBottom: '20px',
      },
});
const ThirdStep = (props) => {
    const classes = useStyles();
    const ctx = useContext(Context);
    const show_payment_gateway = ctx.showAvailblePayment;
    return ( 
    <>
    <List
      component="nav"
      aria-labelledby="nested-list-subheader"
      subheader={
        <ListSubheader component="div" id="nested-list-subheader">
         { __( 'Supported payment gateways for subscription', 'subscriptions-for-woocommerce' ) }
        </ListSubheader>
      }
      className={classes.root}
    >
        { show_payment_gateway.length !== 0 && show_payment_gateway.map( item => ( <PaymentGateway key={item.id}  item={item} name={item.name} {...item} /> ) ) }
        
        <h1>{ __( 'Setup Build-in Paypal Gateway', 'subscriptions-for-woocommerce' ) }</h1>
		  	<h5>{ __( 'To get your API credentials please create a', 'subscriptions-for-woocommerce' ) } <a href="https://developer.paypal.com" target="_blank">{ __( 'PayPal developer account', 'subscriptions-for-woocommerce' ) }</a>. { __( 'Visit', 'subscriptions-for-woocommerce' ) } <a href="https://developer.paypal.com/developer/applications" target="_blank">{ __( 'My Apps & Credentials', 'subscriptions-for-woocommerce' ) }</a> { __( 'select the tab ( Sandbox or Live ), Create app and get the below credentails', 'subscriptions-for-woocommerce' ) }.</h5>
        <h6>{ __( 'You can setup the Build-in Paypal From Woocommerce Payments Settings', 'subscriptions-for-woocommerce' ) } <a href={frontend_ajax_object.wps_build_in_paypal_setup_url} target="_blank">{ __( 'here', 'subscriptions-for-woocommerce' ) }</a></h6>

        <FormControlLabel
        control={
        <Checkbox
            checked={ctx.formFields['EnableWpsPaypalTestmode']}
            onChange={ctx.changeHandler}
            name="EnableWpsPaypalTestmode"
            color="primary"
        />
        }
        label= {__('Check this box to enable the Build-in Paypal Gateway','subscriptions-for-woocommerce')}
        className="wpsFormLabel" />
        <FormControlLabel
        control={
        <Checkbox
            checked={ctx.formFields['EnableWpsPaypal']}
            onChange={ctx.changeHandler}
            name="EnableWpsPaypal"
            color="primary"
        />
        }
        label= {__('Check this box to enable Testmode','subscriptions-for-woocommerce')}
        className="wpsFormLabel" />
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
          <TextField 
          value={ctx.formFields['WpsPaypalClientId']}
          onChange={ctx.changeHandler} 
          id="WpsPaypalClientId" 
          name="WpsPaypalClientId"
          helperText={__('Please enter client ID here.','subscriptions-for-woocommerce') } 
          label={__('Add Paypal Client ID','subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
          <TextField 
          value={ctx.formFields['WpsPaypalClientSecret']}
          onChange={ctx.changeHandler} 
          id="WpsPaypalClientSecret" 
          name="WpsPaypalClientSecret"
          helperText={__('Please enter client secret here.','subscriptions-for-woocommerce') } 
          label={__('Add Paypal Client Secret','subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
    </List>
    </>
    )
}
export default ThirdStep;