import { Stepper, StepLabel, Step} from '@material-ui/core';
const stepper = (props) => {
    return(
        <Stepper activeStep={props.activeStep} className="mwbStepper">
            {props.steps.map((label) => (
                <Step key={label}>
                    <StepLabel>{label}</StepLabel>
                </Step>
            ))}
        </Stepper>
    )
}
export default stepper;