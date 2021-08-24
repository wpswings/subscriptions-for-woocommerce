import React,{useContext} from 'react';
import { Select, FormGroup, InputLabel, MenuItem, Checkbox, FormControlLabel, FormControl, TextField } from '@material-ui/core';
import { makeStyles } from '@material-ui/core/styles';
import { __ } from '@wordpress/i18n';
import Context from '../store/store';
const useStyles = makeStyles({
      margin: {
        marginBottom: '20px',
      },
});
const SecondStep = (props) => {
    const classes = useStyles();
    const ctx = useContext(Context);
    return ( 
    <>
        <h3 className="mwb-title">{__('Create Sample Subscription Product','subscriptions-for-woocommerce') }</h3>
        
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <TextField 
                value={ctx.formFields['ProductName']}
                onChange={ctx.changeHandler} 
                id="ProductName" 
                name="ProductName" 
                label={__('Product Name','subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <TextField 
                value={ctx.formFields['ProductShortDescription']}
                onChange={ctx.changeHandler} 
                id="ProductShortDescription" 
                name="ProductShortDescription" 
                label={__('Product Short Description','subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <TextField 
                value={ctx.formFields['ProductDescription']}
                onChange={ctx.changeHandler} 
                id="ProductDescription" 
                name="ProductDescription" 
                label={__('Product Description','subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <TextField 
                value={ctx.formFields['ProductPrice']}
                onChange={ctx.changeHandler} 
                id="ProductPrice" 
                name="ProductPrice" 
                label={__('Product Price','subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <TextField 
                value={ctx.formFields['SubscriptionNumber']}
                onChange={ctx.changeHandler} 
                id="SubscriptionNumber" 
                name="SubscriptionNumber" 
                label={__('Subscription Interval','subscriptions-for-woocommerce')}  variant="outlined" className={classes.margin}/>
        </FormControl>
        <FormControl component="fieldset" variant="outlined" fullWidth className="fieldsetWrapper">
            <InputLabel id="demo-simple-select-outlined-label">{__('Subscription Frequency','subscriptions-for-woocommerce') }</InputLabel>
            <Select
                labelId="demo-simple-select-outlined-label"
                name="SubscriptionInterval"
                id="demo-simple-select-outlined"
                value={ctx.formFields['SubscriptionInterval']}
                onChange={ctx.changeHandler}
                label={__('Subscription Frequency','subscriptions-for-woocommerce')}
                className={classes.margin}>
                <MenuItem value="day">{__('Days', 'subscriptions-for-woocommerce') }</MenuItem>
                <MenuItem value="week">{ __('Weeks', 'subscriptions-for-woocommerce') }</MenuItem>
                <MenuItem value="month">{ __( 'Months', 'subscriptions-for-woocommerce' ) }</MenuItem>
                <MenuItem value="year">{ __( 'Years','subscriptions-for-woocommerce' ) }</MenuItem>
            </Select>
        </FormControl>
    </>
    )
}
export default SecondStep;