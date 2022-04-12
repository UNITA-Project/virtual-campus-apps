import UniversityCard from "./UniversityCard";
import uvtLogo from "../Img/logo_uvt.png";
import beiraInteriorLogo from "../Img/beira_interior_uni_logo.jpg";
import beiraSmall from "../Img/beira_small.png"
import pauSmall from "../Img/pau_small.png"
import montBlancSmall from "../Img/mont_blanc_small.png"
import unitoSmall from "../Img/unito_small.png"
import uvtSmall from "../Img/uvt_small.png"
import zaragozaSmall from "../Img/zaragoza_small.png"
import dePauLogo from "../Img/depau_uni_logo.png";
import montBlancLogo from "../Img/mont_blanc_uni_logo.png";
import torinoLogo from "../Img/torino_uni_logo.png";
import zaragozaLogo from "../Img/zaragoza_uni_logo.png";
import Button from '@mui/material/Button';
import { useDispatch } from 'react-redux';
import { toggleDialogOpen } from '../Redux/Slices/dialogOpenSlice';
import Cookies from 'js-cookie';
import { getUserFromToken } from '../Redux/Slices/userSlice';
import { useSelector } from "react-redux";
import { Redirect, Route } from "react-router-dom";
import { useState } from "react";
import { PageHolder, GridHolder, ButtonsHolder } from "../Styles/styledComponentStyles";

function MainPage() {

	const [datacloud, setDatacloud] = useState(false);
	const [indics, setIndics] = useState(false);

	let languageSelector = useSelector((state) => {
		let languages = state.languages;
		let selectedLang = languages.find((lang) => lang.selected);
		return selectedLang;
	});

	let dispatch = useDispatch();
	let agreedChoice = useSelector((state) => state.dialogOpen.agreedChoice);
	let userSelector = useSelector((state) => state.user.userData);

	if (userSelector && !userSelector.wrongCred) {
		return  <Redirect exact to='/dashboard'/>
	}
	
	let token = Cookies.get('USER_TOKEN');

	if (token) {
		console.log("Fetching user from token");
		dispatch(
			getUserFromToken(token)
		).then(() => {return  <Redirect exact to="/dashboard" />})
	}

	const handleDialogOpen = () => {
		dispatch(
			toggleDialogOpen({open: true, reference: "login", agreedChoice: agreedChoice})
		);
	};

	const handleDataCloud = () => {setDatacloud(true);};
	const handleIndics = () => {setIndics(true);};

	let beiraReference = "https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=https://idp.ubi.pt/idp/shibboleth";
	let pauReference = "https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=https://idp.univ-pau.fr/idp/shibboleth";
	let savoieReference = "https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=https://shibboleth.univ-savoie.fr/idp/shibboleth";
	let uvtReference = "https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=https://login.e-uvt.ro/aai/saml2/idp/metadata.php";
	let torinoReference = "https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=https://idp-unito-prod.cineca.it/idp/shibboleth";
	let zaragozaReference = "https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=idp-sirfor.unizar.es";

	if (datacloud) {
		return (<Route exact path="/" render={() => {window.location.href = "https://datacloud.univ-unita.eu/index.php/login?redirect_url=/index.php/apps/dashboard/"; return null;}} />);
	}

	if (indics) {
		return (<Route exact path="/" render={() => {window.location.href = "https://enquetes.u-bordeaux.fr/UPPA-SOFT/UNITA_Indics/report.htm"; return null;}} />);
	}

	return(
		<PageHolder>
			<GridHolder>
				<UniversityCard
					imagePath={beiraInteriorLogo}
					miniImagePath={beiraSmall}
					title = {"Universidade da Beira Interior"}
					reference = {beiraReference}
				/>
				<UniversityCard
					imagePath={dePauLogo}
					miniImagePath={pauSmall}
					title = {"Université de Pau et des Pays de l'Adour"}
					reference = {pauReference}
				/>
				<UniversityCard
					imagePath={montBlancLogo}
					miniImagePath={montBlancSmall}
					title = {"Universite Savoie Mont Blanc"}
					reference = {savoieReference}
				/>
				<UniversityCard
					imagePath={uvtLogo}
					miniImagePath={uvtSmall}
					title = {"Universitatea de Vest din Timișoara"}
					reference = {uvtReference}
				/>
				<UniversityCard
					imagePath={torinoLogo}
					miniImagePath={unitoSmall}
					title = {"Universita Degli Studi di Torino"}
					reference = {torinoReference}
				/>
				<UniversityCard
					imagePath={zaragozaLogo}
					miniImagePath={zaragozaSmall}
					title = {"Universidad Zaragoza"}
					reference = {zaragozaReference}
				/>
			</GridHolder>
			<ButtonsHolder>
				<Button variant="contained" style={{width: 150}} onClick={handleDialogOpen}>{languageSelector.localLogin}</Button>
				<Button variant="contained" style={{width: 150}} onClick={handleDataCloud}>Datacloud</Button>
				<Button variant="contained" style={{width: 150}} onClick={handleIndics}>Indics</Button>
			</ButtonsHolder>
		</PageHolder>
	)
}

export default MainPage
