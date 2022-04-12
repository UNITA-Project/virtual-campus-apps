import { Button, Card, CardContent, CardMedia } from "@mui/material";
import React, { useState } from "react";
import Typography from '@mui/material/Typography';
import MobileAppQR from '../Img/VC_Mobile_App.png'
import { useSelector } from "react-redux";
import { CardContainer } from "../Styles/styledComponentStyles";

export function MobileAppPage () {

    const [redirect, setRedirect] = useState(false);

    const languageSelector = useSelector((state) => {
        let languages = state.languages;
        let selectedLang = languages.find((lang) => lang.selected);
        return selectedLang;
    });
    const mobileAppText = languageSelector.mobileApp;

    const handleClick = () => {
        setRedirect(true);
    };

    if (redirect) {
        window.location.href = 'https://github.com/UNITA-Project/unita_mobile_app/releases/tag/0.1.0';
    }

    return (
        <CardContainer>
            <Card sx={{ width: 350, boxShadow: "5px 10px 8px rgba(0, 0, 0, 0.486)" }}>
                <CardContent>
                    <Typography gutterBottom variant="h5" component="div">
                        {mobileAppText.mobileVC}
                    </Typography>
                    <Typography variant="body2" component="div" color="text.secondary">
                        {mobileAppText.buttonCaption}:
                    </Typography>
                    <br />
                    <Button onClick={handleClick} variant="contained">
                        {mobileAppText.linkText}
                    </Button>
                    <Typography style={{marginTop: '20px'}} variant="body2" component="div" color="text.secondary">
                        {mobileAppText.QRCaption}:
                    </Typography>
                    <CardMedia component="img" 
                        image={MobileAppQR}
                    />
                </CardContent>
            </Card>
        </CardContainer>
    );
};