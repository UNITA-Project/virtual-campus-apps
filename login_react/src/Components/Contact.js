import * as React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import { useSelector } from "react-redux";
import { CardContainer } from '../Styles/styledComponentStyles';

export default function Contact() {

    let languageSelector = useSelector((state) => {
        let languages = state.languages;
        let selectedLang = languages.find((lang) => lang.selected);
        return selectedLang.contactText;
    });

    return (
        <CardContainer>
            <Card sx={{ width: 425, boxShadow: "5px 10px 8px rgba(0, 0, 0, 0.486)" }}>
                <CardContent>
                    <Typography gutterBottom variant="h5" component="div">
                        {languageSelector.contactTitle}
                    </Typography>
                    <Typography variant="body2" component="div" color="text.secondary">
                        <h4>WP5 {languageSelector.coordinator}: </h4><p>Vlad Petcu - vlad.petcu@e-uvt.ro</p>
                        <h4>UNITA {languageSelector.engineer}: </h4><p>Darian Onchiș - darian.onchis@e-uvt.ro</p>
                        <h4>{languageSelector.team}: </h4>
                        <p>Bogdan Balazs - bogdan.balazs@e-uvt.ro</p>
                        <p>Matei Bertea - matei.bertea99@e-uvt.ro</p>
                        <p>Flavia Costi - flavia.costi99@e-uvt.ro</p>
                        <p>Roxana Flueraș - roxana.flueras@e-uvt.ro</p>
                        <p>Andrei Popică - andreipopica1@gmail.com</p>
                        <p>Ioan Samuilă - ioan.samuila@e-uvt.ro</p>
                    </Typography>
                </CardContent>
            </Card>
        </CardContainer>
    );
}