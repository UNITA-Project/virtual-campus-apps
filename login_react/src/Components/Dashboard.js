import unita_logo from "../Img/unita_logo.png"
import unita_bw_logo from "../Img/unita_bw_logo.jpg"
import GenericCard from "./GenericCard";
import mediaWiki from "../Img/MediaWiki_logo.png"
import limeSurveyLogo from "../Img/lime_survey.png";
import limeSurveySmall from "../Img/lime_survey_small.png";
import analyticsLogoSmall from "../Img/matomo_logo.png";
import analyticsLogo from "../Img/Matomo_logo_big.png";
import socialLogo from "../Img/humhub_logo.jpeg";
import socialLogoSmall from "../Img/humhub_logo_small.jpg";
import { useSelector } from "react-redux";
import { useEffect } from "react";
import { Redirect } from "react-router-dom";
import { PageHolder, GridHolder} from '../Styles/styledComponentStyles';
import Cookies from 'js-cookie';
import { logOut } from "../Redux/Slices/userSlice"
import { useDispatch } from 'react-redux';
import { selectLanguage } from "../Redux/Slices/languageSlice";

export default function Dashboard(props) {

  let dispatch = useDispatch();
  let userSelector = useSelector((state) => state.user.userData);


  let token = Cookies.get('USER_TOKEN');

  useEffect(() => {
		let data;  
		let country = "com";
		if (userSelector && userSelector.email) {
			country = userSelector.email.split(".")[1];
		}

		switch (country) {
			case "com":
				data = 1;
				break;
			case "pt":
				data = 2;
				break;
			case "fr":
				data = 3;
				break;
			case "ro":
				data = 4;
				break;
			case "it":
				data = 5;
				break;
			case "es":
				data = 6;
				break;
			default:
				data = 1;
				break;    
		}

		dispatch(
			selectLanguage({
				id: data
			})
    	);
	});

	let limeSurvey = "https://survey.unitassotest.uvt.ro/index.php/admin/authentication/sa/login";
	let APForum = "https://forum.unitassotest.uvt.ro/";
	let wiki = "https://wiki.unitassotest.uvt.ro/index.php?title=Special:UserLogin&returnto=Main+Page";
	let social = "https://social.unitassotest.uvt.ro/";

	let virtualMobilities = "/virtualMobilities";
	let analyticsURL = "https://analytics.unitassotest.uvt.ro/";


	if (!token) {
		dispatch(
			logOut()
		);
	}
  
	if(!userSelector){
		return <Redirect to="/" />
	}

	return(
		<PageHolder>
			<GridHolder>
				<GenericCard
					imagePath={socialLogo}
					miniImagePath={socialLogoSmall}
					title={"Social Network"}
					reference={social}
				/>
				<GenericCard
					imagePath={limeSurveyLogo}
					miniImagePath={limeSurveySmall}
					title={"Surveys"}
					reference={limeSurvey}
				/>
				<GenericCard
					imagePath={unita_logo}
					miniImagePath={unita_logo}
					title={"Forum"}
					reference={APForum}
				/>
				<GenericCard
					imagePath={mediaWiki}
					miniImagePath={mediaWiki}
					title={"Wiki"}
					reference={wiki}
				/>
				<GenericCard 
					imagePath={unita_bw_logo}
					miniImagePath={unita_bw_logo}
					title={"Virtual Mobilities"}
					reference={virtualMobilities}
				/>
				<GenericCard 
					imagePath={analyticsLogo}
					miniImagePath={analyticsLogoSmall}
					title={"Analytics"}
					reference={analyticsURL}
				/>
			</GridHolder>
		</PageHolder>
	);
}