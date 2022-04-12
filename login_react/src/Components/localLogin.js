import * as React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import Typography from '@mui/material/Typography';
import { useState } from "react";
import TextField from '@mui/material/TextField';
import Stack from '@mui/material/Stack';
import { Link } from 'react-router-dom';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogContentText from '@mui/material/DialogContentText';
import DialogTitle from '@mui/material/DialogTitle';
import { Redirect } from "react-router-dom";
import Slide from '@mui/material/Slide';
import { useDispatch } from 'react-redux';
import { fetchUserData } from '../Redux/Slices/userSlice';
import { useSelector } from "react-redux";
import Backdrop from '@mui/material/Backdrop';
import CircularProgress from '@mui/material/CircularProgress';
import ReCAPTCHA from 'react-google-recaptcha';
import { CardContainer } from '../Styles/styledComponentStyles';

const Transition = React.forwardRef(function Transition(props, ref) {
  return <Slide direction="up" ref={ref} {...props} />;
});

export default function LocalLogin() {

    let dispatch = useDispatch();

    const [openLoading, setOpenLoading] = useState(false);
    const [dialogData, setDialogData] = useState({open: false});
    const [credentials, setCredentials] = useState({username: "", password: ""});
    const [redirect, setRedirect] = useState(false);

    const [captcha, setCaptcha] = useState(false);

    let userSelector = useSelector((state) => state.user.userData);

    let languageSelector = useSelector((state) => {
        let languages = state.languages;
        let selectedLang = languages.find((lang) => lang.selected);
        return selectedLang;
    });

    function onChange(value) {
        setCaptcha(value);
    }

    const handleClose = () => {
        setDialogData({open: false})
    }

    const handleSubmit = (event) => {
        event.preventDefault();
        setOpenLoading(true);
        dispatch(
            fetchUserData(credentials)
        ).then((res) => {
            setOpenLoading(false);
            if (res.payload.id === undefined) {
                setDialogData({open: true, sameCred: res.payload.wrongCred});
                return;
            }

            if (!captcha) {
                setDialogData({open: true, sameCred: "captcha"});
                return;
            }

            setRedirect(true);
        });

    };

    const changeUsername = (event) => {
        setCredentials({username: event.target.value, password: credentials.password});
    }

    const changePassword = (event) => {
        setCredentials({username: credentials.username, password: event.target.value});
    }

    if (redirect && (userSelector && !userSelector.wrongCred && captcha)) {
        return  <Redirect exact to="/dashboard" />
    }

    return (
        <CardContainer>
            <Backdrop
                sx={{ color: '#fff', zIndex: (theme) => theme.zIndex.drawer + 1 }}
                open={openLoading}
            >
                <CircularProgress color="inherit" />
            </Backdrop>
            <Card sx={{ maxWidth: 500 }}>
                <CardContent>
                    <Typography gutterBottom marginBottom={5} variant="h5" component="div">
                        Log in
                    </Typography>
                    <Stack
                        component="form"
                        sx={{
                            width: '35ch',
                        }}
                        spacing={3}
                        onSubmit={handleSubmit}
                        autoComplete="off"
                    >
                        <TextField
                            label={languageSelector.username}
                            className="outlined-size-small"
                            size="small"
                            required={true}
                            onChange = {changeUsername}
                        />
                        <TextField
                            label={languageSelector.password}
                            className="outlined-size-small"
                            size="small"
                            type="password"
                            required={true}
                            onChange = {changePassword}
                        />
                        <ReCAPTCHA
                            sitekey="6Lc_xlMeAAAAAEUYH4-RWC3TV1AccRK98IG0coRj"
                            onChange={onChange}
                        />
                        <Button type="submit" sx={{
                            width: '15ch',
                            alignSelf: 'center'
                        }} variant="contained">{languageSelector.logIn}</Button>
                        <Typography sx = {{alignSelf:"center"}}>
                            {languageSelector.youDontHaveAnAccount} <Link to='/register'><b>{languageSelector.register}</b></Link>
                        </Typography>
                    </Stack>

                </CardContent>
            </Card>
            <Dialog
                open={dialogData.open}
                TransitionComponent={Transition}
                keepMounted
                onClose={handleClose}
                aria-describedby="alert-dialog-slide-description"
            >
                <DialogTitle>{"Error"}</DialogTitle>
                <DialogContent>
                <DialogContentText id="alert-dialog-slide-description">
                    {dialogData.sameCred === "captcha" ? "Please check the reCAPTCHA." : `Wrong ${dialogData.sameCred}.`}
                </DialogContentText>
                </DialogContent>
                <DialogActions>
                <Button onClick={handleClose}>{languageSelector.close}</Button>
                </DialogActions>
            </Dialog>
        </CardContainer>
    );
}
