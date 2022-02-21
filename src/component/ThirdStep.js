import React,{useContext,useState} from 'react';
import Context from '../store/store';
import { List,ListSubheader } from '@material-ui/core';
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
    </List>
    </>
    )
}
export default ThirdStep;