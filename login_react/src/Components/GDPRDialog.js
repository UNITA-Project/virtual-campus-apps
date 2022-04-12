import * as React from 'react';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogContentText from '@mui/material/DialogContentText';
import DialogTitle from '@mui/material/DialogTitle';
import Slide from '@mui/material/Slide';
import { useSelector } from "react-redux";
import FormGroup from '@mui/material/FormGroup';
import FormControlLabel from '@mui/material/FormControlLabel';
import Checkbox from '@mui/material/Checkbox';
import { useDispatch } from 'react-redux';
import { toggleDialogOpen } from '../Redux/Slices/dialogOpenSlice';
import { Route } from "react-router-dom";

const Transition = React.forwardRef(function Transition(props, ref) {
    return <Slide direction="up" ref={ref} {...props} />;
});

function GDPRDialog(props) {

	let dispatch = useDispatch();
    const [checked, setChecked] = React.useState(true);
    const [redirect, setRedirect] = React.useState(false);
    let openSelector = useSelector((state) => {
        let openValue = state.dialogOpen;
        return openValue;
    });

    let languageSelector = useSelector((state) => {
        let languages = state.languages;
        let selectedLang = languages.find((lang) => lang.selected);
        return selectedLang.text;
    });

    if (openSelector.agreedChoice === "true" && openSelector.open) {
        return <Route exact path="/" render={() => {
            dispatch(
                toggleDialogOpen({open: false})
            );
            window.location.href = openSelector.reference;
            return null;
        }} />
    } else {
        const handleChange = (event) => {
            setChecked(event.target.checked);
        };  
        
        const handleClose = () => {
            dispatch(
                toggleDialogOpen({open: false})
            );
        };

        const handleAgree = () => {
        
            if (checked) {
                dispatch(
                    toggleDialogOpen({open: false, agreedChoice: "true"})
                );
            }

            dispatch(
                toggleDialogOpen({open: false})
            ); 
            
            setRedirect(true);
        };
        
        if (redirect) {
            return <Route exact path="/" render={() => {window.location.href = openSelector.reference; return null;}} />
        }
        
        return (
            <div>
                <Dialog
                    open={openSelector.open}
                    TransitionComponent={Transition}
                    keepMounted
                    onClose={handleClose}
                    aria-describedby="alert-dialog-slide-description"
                >
                    <DialogTitle>{languageSelector.dialogTitleText}</DialogTitle>
                    <DialogContent>
                        <DialogContentText id="alert-dialog-slide-description">
                            {languageSelector.dialogBodyText} <a href='/gdpr' style={{color: "blue", textDecoration: "underline"}} onClick={handleClose}>{languageSelector.dialogTitleText.toLowerCase()}</a>
                        </DialogContentText>
                        <FormGroup>
                            <FormControlLabel control={<Checkbox defaultChecked onChange={handleChange} />} style={{color: "rgb(103, 103, 103)"}} label={languageSelector.rememberChoiceText} />
                        </FormGroup>
                    </DialogContent>
                    <DialogActions>
                        <Button onClick={handleClose}>{languageSelector.dialogDisagreeText}</Button>
                        <Button onClick={handleAgree}>{languageSelector.dialogAgreeText}</Button>
                    </DialogActions>
                </Dialog>
            </div>
        );
    }
}

export default GDPRDialog;