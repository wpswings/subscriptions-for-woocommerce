import {useState} from 'react';
import { makeStyles } from '@material-ui/core/styles';
import { Button, Typography, Container, CircularProgress} from '@material-ui/core';
import Stepper from './component/Stepper';
import FirstStep from './component/FirstStep';
import SecondStep from './component/SecondStep';
import ThirdStep from './component/ThirdStep';
import FinalStep from './component/FinalStep';
import Context from './store/store';
import axios from 'axios';
import { __ } from '@wordpress/i18n';
import qs from 'qs';
const useStyles = makeStyles((theme) => ({
    instructions: {
        marginTop: theme.spacing(1),
        marginBottom: theme.spacing(1),
    },
}));
function App(props) {
    const [loading, setLoading] = useState(false);
    const [state, setState] = useState({
        EnablePlugin:true,
        AddToCartText:'Add to cart',
        PlaceOrderText:'Place order',
        ProductName:'',
        ProductDescription:'',
        ProductShortDescription:'',
        ProductPrice:'10',
        SubscriptionNumber:'1',
        SubscriptionInterval:'day',
        consetCheck:'yes',
    });
    
    const supported_payment_gateway = frontend_ajax_object.supported_gateway;
    
    const [showAvailblePayment,setAvailblePayment] = useState(supported_payment_gateway);
    const GatewaySubmitHandler = ( id ) => {
        const newState = [...showAvailblePayment];
        const findIndex = newState.findIndex( item => item.id === id );
        newState[findIndex].is_activated = true;
        setAvailblePayment(  newState );
    }
    const classes = useStyles();
    const [activeStep, setActiveStep] = useState(0);
    const steps = [ __( 'General Settings', 'subscriptions-for-woocommerce' ), __( 'Create Subscription', 'subscriptions-for-woocommerce' ), __( 'Subscription Payment Gateway Configuration', 'subscriptions-for-woocommerce' ), __( 'Final Step', 'subscriptions-for-woocommerce' )];

    
    const onFormFieldHandler = (event) => {
        let value = ('checkbox' === event.target.type ) ? event.target.checked : event.target.value;
        setState({ ...state, [event.target.name]: value });
    };
    const getStepContent = (stepIndex) => {
        switch (stepIndex) {
            case 0:
                return (<FirstStep />);
            case 1:
                return (<SecondStep/>);
            case 2:
                return <ThirdStep />;
            case 3:
            return <FinalStep />;
            case 4:
                return <h1>{__( 'Thanks for your details', 'subscriptions-for-woocommerce' )}</h1>;
            default:
                return __( 'Unknown stepIndex', 'subscriptions-for-woocommerce' );
        }
    }
    const handleNext = () => {
        setActiveStep((prevActiveStep) => prevActiveStep + 1);
    };

    const handleBack = () => {
        setActiveStep((prevActiveStep) => prevActiveStep - 1);
    };

    const handleFormSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        const user = {
            ...state,
            'action': 'mwb_sfw_save_settings_filter',
            nonce: frontend_ajax_object.mwb_sfw_react_nonce,   // pass the nonce here
        };
        
        axios.post(frontend_ajax_object.ajaxurl, qs.stringify(user) )
            .then(res => {
                setLoading(false);
                
                handleNext();
                setTimeout(() => {
                window.location.href = frontend_ajax_object.redirect_url; 
                    return null;
                }, 3000);
            }).catch(error=>{

        })
        
    }

    let nextButton = (
        <Button
            variant="contained" color="primary" onClick={handleNext} size="large">
            Next
        </Button>
    );
    if (activeStep === steps.length-1 ) {
        nextButton = (
            <Button
                onClick={handleFormSubmit}
                variant="contained" color="primary" size="large">
                Finish
            </Button>
        )
    }
    
    return (
        <Context.Provider value={{
            formFields:state,
            changeHandler:  onFormFieldHandler,
            showAvailblePayment: showAvailblePayment,
            paymentHandler : GatewaySubmitHandler
        }}>
            <div className="mwbMsfWrapper">
                <Stepper activeStep={activeStep} steps={steps}/>
                <div className="mwbHeadingWrap">
                    <h2>{__( 'Welcome to Subscriptions For WooCommerce', 'subscriptions-for-woocommerce' ) }</h2>
                    <h3>{__('Complete steps to start selling subscriptions','subscriptions-for-woocommerce') }</h3>
                </div>
                <Container maxWidth="sm">
                    <form className="mwbMsf">
                        <Typography className={classes.instructions}>
                            {(loading) ? <CircularProgress className="mwbCircularProgress" /> :getStepContent(activeStep)}
                        </Typography>
                        <div className="mwbButtonWrap">
                            {activeStep !== steps.length && <Button
                                disabled={activeStep === 0}
                                onClick={handleBack}
                                variant="contained" size="large">
                            Back
                            </Button>}
                            {activeStep !== steps.length && nextButton}
                        </div>
                    </form>
                </Container >
            </div>
        </Context.Provider>
    );
}

export default App;