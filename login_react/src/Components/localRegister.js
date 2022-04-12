import * as React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Button from '@mui/material/Button';
import Typography from '@mui/material/Typography';
import { useState } from "react";
import TextField from '@mui/material/TextField';
import Stack from '@mui/material/Stack';
import {FormControl, InputLabel, Select} from "@mui/material";
import MenuItem from "@mui/material/MenuItem";
import axios from 'axios';
import qs from 'qs';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogContentText from '@mui/material/DialogContentText';
import DialogTitle from '@mui/material/DialogTitle';
import Slide from '@mui/material/Slide';
import Backdrop from '@mui/material/Backdrop';
import CircularProgress from '@mui/material/CircularProgress';
import { useSelector } from "react-redux";
import { Redirect } from "react-router-dom";
import { CardContainer } from '../Styles/styledComponentStyles';

const Transition = React.forwardRef(function Transition(props, ref) {
  return <Slide direction="up" ref={ref} {...props} />;
});

export default function LocalRegister() {

    let languageSelector = useSelector((state) => {
        let languages = state.languages;
        let selectedLang = languages.find((lang) => lang.selected);
        return selectedLang;

    });
    const [openLoading, setOpenLoading] = useState(false);
    const [dialogData, setDialogData] = React.useState({open: false});
    const [credentials, setCredentials] = useState({username: "", first_name: "", last_name: "", affiliation: "", email: "", password: "", user_type: ""});
    const [rePassword, setRePassword] = useState(null);
    const registerAPI = "https://keycloak.unitassotest.uvt.ro/auth/admin/realms/POC/users";
    const getTokenAPI = "https://keycloak.unitassotest.uvt.ro/auth/realms/POC/protocol/openid-connect/token";

    let userSelector = useSelector((state) => state.user.userData);

    if (userSelector) {
        return  <Redirect exact to='/dashboard'  />
    }

    const handleClose = () => {
        setDialogData({open: false})
    }

    const handleSubmit = async (event) => {
        event.preventDefault();
        setOpenLoading(true);

        if (rePassword !== credentials.password) {
            alert("Passwords must match.");
            setOpenLoading(false);
            return;
        }
        let token;
        let data = qs.stringify({
            'client_id': 'admin-cli',
            'username': 'admin-poc',
            'password': 'Parol@grea.123',
            'grant_type': 'password'
        });
        let config = {
                headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
                },
        };

        axios.post(getTokenAPI, data, config)
            .then(result => {
                token = result.data;
                const config = {
                    headers: {
                        'Authorization': "Bearer " + token.access_token,
                        'Content-Type': 'application/json'
                    }
                };

                const params = {
                    "username": credentials.username,
                    "enabled": true,
                    "totp": false,
                    "emailVerified": false,
                    "firstName": credentials.first_name,
                    "lastName": credentials.last_name,
                    "email": credentials.email,
                    "attributes": {
                        "affiliation": [credentials.affiliation],
                        "user_type": [credentials.user_type],
                        "password": [credentials.password],
                        "account_type": "local"
                    },
                    "disableableCredentialTypes": [],
                    "requiredActions": [],
                    "federatedIdentities": [],
                    "notBefore": 0,
                    "access": {
                        "manageGroupMembership": true,
                        "view": true,
                        "mapRoles": true,
                        "impersonate": true,
                        "manage": true
                    }
                };

                axios.post(registerAPI, params, config).then((response) => {
                    setOpenLoading(false);
                    alert("Created user.");
                }).catch((err) => {
                    setOpenLoading(false);
                    if (err.response.data.errorMessage.split(" ").at(-1) === "username") {
                        setDialogData({open: true, sameCred: languageSelector.username});
                        return;
                    }
                    if (err.response.data.errorMessage.split(" ").at(-1) === "email") {
                        setDialogData({open: true, sameCred: languageSelector.email});
                        return;
                    }
                    console.log(err.response.data.errorMessage);
                });
            })
            .catch(err => {
                setOpenLoading(false);
                console.log("catch1 ", err.response);
            });
    };

    const changeUsername = (event) => {
        setCredentials({username: event.target.value, first_name: credentials.first_name, last_name: credentials.last_name, affiliation: credentials.affiliation, email: credentials.email, password: credentials.password, user_type: credentials.user_type});
    }
    const changeFName = (event) => {
        setCredentials({username: credentials.username, first_name: event.target.value, last_name: credentials.last_name, affiliation: credentials.affiliation, email: credentials.email, password: credentials.password, user_type: credentials.user_type});
    }
    const changeLName = (event) => {
        setCredentials({username: credentials.username, first_name: credentials.first_name, last_name: event.target.value, affiliation: credentials.affiliation, email: credentials.email, password: credentials.password, user_type: credentials.user_type});
    }
    const changeAffiliation = (event) => {
        setCredentials({username: credentials.username, first_name: credentials.first_name, last_name: credentials.last_name, affiliation: event.target.value, email: credentials.email, password: credentials.password, user_type: credentials.user_type});
    }
    const changeEmail = (event) => {
        setCredentials({username: credentials.username, first_name: credentials.first_name, last_name: credentials.last_name, affiliation: credentials.affiliation, password: credentials.password, email: event.target.value, user_type: credentials.user_type});
    }
    const changePassword = (event) => {
        setCredentials({username: credentials.username, first_name: credentials.first_name, last_name: credentials.last_name, affiliation: credentials.affiliation, email: credentials.email, password: event.target.value, user_type: credentials.user_type});
    }
    const changeTypeUser = (event) => {
        setCredentials({username: credentials.username, first_name: credentials.first_name, last_name: credentials.last_name, affiliation: credentials.affiliation, email: credentials.email, password: credentials.password, user_type: event.target.value});
    }
    const changeRePassword = (event) => {
        setRePassword(event.target.value);
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
                        Register
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
                            onChange = {changeUsername}
                            required={true}
                        />
                        <TextField
                            label={languageSelector.firstName}
                            className="outlined-size-small"
                            size="small"
                            onChange = {changeFName}
                            required={true}
                        />
                        <TextField
                            label={languageSelector.lastName}
                            className="outlined-size-small"
                            size="small"
                            onChange = {changeLName}
                            required={true}
                        />
                        <TextField
                            label={languageSelector.affiliation}
                            className="outlined-size-small"
                            size="small"
                            onChange = {changeAffiliation}
                            required={true}
                        />
                        <TextField
                            label="Email"
                            className="outlined-size-small"
                            size="small"
                            onChange = {changeEmail}
                            required={true}
                        />
                        <TextField
                            label={languageSelector.password}
                            className="outlined-size-small"
                            size="small"
                            type="password"
                            onChange = {changePassword}
                            required={true}
                        />
                        <TextField
                            label={languageSelector.rePassword}
                            className="outlined-size-small"
                            size="small"
                            type="password"
                            onChange = {changeRePassword}
                            required={true}
                        />
                        <FormControl fullWidth>
                            <InputLabel id="demo-simple-select-label">{languageSelector.typeUser}</InputLabel>
                            <Select
                                labelId="demo-simple-select-label"
                                id="demo-simple-select"
                                value={credentials.user_type}
                                label="Type-user"
                                onChange={changeTypeUser}
                                required={true}
                            >
                                <MenuItem value={'Pupil'}>{languageSelector.pupil}</MenuItem>
                                <MenuItem value={'Partner'}>{languageSelector.partner}</MenuItem>
                                <MenuItem value={'Other'}>{languageSelector.other}</MenuItem>
                            </Select>
                        </FormControl>
                        <Button type="submit" sx={{
                                width: '15ch',
                                alignSelf: 'center'
                            }} 
                            variant="contained"
                        >
                            {languageSelector.register}
                        </Button>
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
                    {languageSelector.userExistsWithSame} {dialogData.sameCred}.
                </DialogContentText>
                </DialogContent>
                <DialogActions>
                <Button onClick={handleClose}>{languageSelector.close}</Button>
                </DialogActions>
            </Dialog>
        </CardContainer>
    );
}
