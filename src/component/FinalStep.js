import React,{useContext,Fragment} from 'react';
import Context from '../store/store';
import {Radio,RadioGroup, FormControlLabel, FormControl, FormLabel, TextField } from '@material-ui/core';
import { makeStyles } from '@material-ui/core/styles';
const { __ } = wp.i18n;
const useStyles = makeStyles({
      margin: {
        marginBottom: '20px',
      },
});
export default function FinalStep(props) {
    const classes = useStyles();
    const ctx = useContext(Context)
    return (
        <Fragment>
            <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
                <FormLabel component="legend" className="mwbFormLabel">{ __('Bingo! You are all set to take advantage of your subscription business. Lastly, we urge you to allow us collect some','subscriptions-for-woocommerce')} <a href='https://makewebbetter.com/plugin-usage-tracking/' target="_blank" >{__('information','subscriptions-for-woocommerce') }</a> { __( 'in order to improve this plugin and provide better support. If you want, you can dis-allow anytime settings, We never track down your personal data. Promise!', 'subscriptions-for-woocommerce') }
                </FormLabel>
                <RadioGroup aria-label="gender" name="consetCheck" value={ctx.formFields['consetCheck']} onChange={ctx.changeHandler} className={classes.margin}>
                    <FormControlLabel value="yes" control={<Radio color="primary"/>} label={ __( 'Yes. Definitely, you guys rock!', 'subscriptions-for-woocommerce' ) } className="mwbFormRadio"/>
                    <FormControlLabel value="no" control={<Radio color="primary"/>} label={ __( 'No, not required.', 'subscriptions-for-woocommerce' ) } className="mwbFormRadio"/>
                </RadioGroup>
            </FormControl>
            
        </Fragment> 

    );
}