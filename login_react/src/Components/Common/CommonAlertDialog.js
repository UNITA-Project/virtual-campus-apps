import React from "react";
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogContentText from '@mui/material/DialogContentText';
import DialogTitle from '@mui/material/DialogTitle';
import Slide from '@mui/material/Slide';
import { useSelector } from "react-redux";
import { useDispatch } from 'react-redux';
import { toggleCommonDialog } from '../Redux/Slices/commonDialogSlice';

const Transition = React.forwardRef(function Transition(props, ref) {
    return <Slide direction="up" ref={ref} {...props} />;
});

export default function CommonAlertDialog() {

	let dispatch = useDispatch();

    let propsSelector = useSelector((state) => {
        let dialogProps = state.commonDialog;
        return dialogProps;
    });

    const handleClose = propsSelector.handleClose ? propsSelector.handleClose : () => {
        dispatch(
            toggleCommonDialog({open: false})
        );
    };

    return (
        <div>
            <Dialog
                open={propsSelector.open}
                TransitionComponent={Transition}
                keepMounted
                onClose={handleClose}
                aria-describedby="alert-dialog-slide-description"
            >
                <DialogTitle>{propsSelector.title}</DialogTitle>
                <DialogContent>
                <DialogContentText id="alert-dialog-slide-description">
                    {propsSelector.body}
                </DialogContentText>
                </DialogContent>
                <DialogActions>
                    { 
                        (propsSelector.button1.text) ?
                            (propsSelector.button1.action ? 
                                <Button onClick={propsSelector.button1.action}>{propsSelector.button1.text}</Button>
                                : <Button onClick={handleClose()}>{propsSelector.button1.text}</Button>
                                )
                            : null
                    }
                    { 
                        (propsSelector.button2.text && propsSelector.button2.action) ?
                            <Button onClick={propsSelector.button2.action}>{propsSelector.button2.text}</Button>
                            : null
                    }
                </DialogActions>
            </Dialog>
        </div>
    );

}