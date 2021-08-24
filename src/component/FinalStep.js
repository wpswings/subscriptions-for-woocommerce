import React,{useContext,Fragment} from 'react';
import Context from '../store/store';
import {Radio,RadioGroup, FormControlLabel, FormControl, FormLabel, TextField } from '@material-ui/core';
import { makeStyles } from '@material-ui/core/styles';
import { __ } from '@wordpress/i18n';
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
                <FormLabel component="legend" className="mwbFormLabel">{ __('This plugin can send a list of active plugins and theme used on your site.This allow our support team to help you much faster and to contact you in advance about potentials compatibility problem and their solutions', 'subscriptions-for-woocommerce') }
                </FormLabel>
                <RadioGroup aria-label="gender" name="consetCheck" value={ctx.formFields['consetCheck']} onChange={ctx.changeHandler} className={classes.margin}>
                    <FormControlLabel value="yes" control={<Radio color="primary"/>} label="Yes" className="mwbFormRadio"/>
                    <FormControlLabel value="no" control={<Radio color="primary"/>} label="No" className="mwbFormRadio"/>
                </RadioGroup>
            </FormControl>
            
        </Fragment> 

    );
}