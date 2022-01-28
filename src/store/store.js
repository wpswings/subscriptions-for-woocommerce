import {createContext} from 'react';

const ReactContext = createContext({
    formFields:{},
    changeHandler: () => {},
    showAvailblePayment :[],
    paymentHandler: () => {},
});

export default ReactContext;