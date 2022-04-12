import { Link } from 'react-router-dom';
import { useSelector } from "react-redux";
import useMediaQuery from '@mui/material/useMediaQuery';
import { FooterContainer } from "../Styles/styledComponentStyles";

function Footer() {
    const matches = useMediaQuery('(min-width:600px)');
    let languageSelector = useSelector((state) => {
        let languages = state.languages;
        let selectedLang = languages.find((lang) => lang.selected);
        return selectedLang;
    });
    
    return (
        <FooterContainer>
            <div style={{display: "flex", justifyContent: "center", rowGap: '5px', columnGap: '25px', width: matches ? "600px" : "300px", flexWrap: "wrap"}}>
                <div>
                    {languageSelector.poweredByText}
                    <Link to="/contact"><b> UNITA</b></Link>
                </div>
                <div>
                    <Link to="/gdpr"><b>GDPR {languageSelector.text.dialogTitleText}</b></Link>
                </div>
                <div style={{color: 'rgb(48,48,48)'}}>
                    <Link to="/toolsSupport">{languageSelector.support}</Link>
                </div>
                <div style={{color: 'rgb(48,48,48)'}}>
                    <Link to="/mobileApp">{languageSelector.mobileApp.linkText}</Link>
                </div>
            </div>            
        </FooterContainer>
    );
}

export default Footer;
