import './App.css';
import MainPage from './Components/MainPage';
import Header from "./Components/Header"
import Footer from './Components/Footer';
import GDPRDialog from './Components/GDPRDialog';
import Contact from './Components/Contact';
import {Route, Switch} from 'react-router-dom';
import LocalLogin from './Components/localLogin';
import LocalRegister from './Components/localRegister';
import Dashboard from './Components/Dashboard';
import VirtualMobilities from './Components/VirtualMobilities';
import GDPRAgreementPage from './Components/GDPRAgreementPage';
import ToolsSupport from './Components/ToolsSupport';
import { MobileAppPage } from './Components/MobileAppPage';
import { useDispatch } from 'react-redux';
import { selectLanguage } from "./Redux/Slices/languageSlice";
import { useEffect } from 'react';

function App() {

	let dispatch = useDispatch();
	
	useEffect(() => {
		let languageId = localStorage.getItem("language");

		dispatch(
			selectLanguage({
				id: languageId ? parseInt(languageId) : 1
			})
		);
		localStorage.removeItem("persist:root");
	}, [dispatch]);

	return (
		<div className={"appContainer"}>
			<Switch>
				<Route exact path='/' component={MainPage}></Route>
				<Route exact path='/contact' component={Contact}></Route>
				<Route exact path='/login' component={LocalLogin}></Route>
				<Route exact path='/register' component={LocalRegister}></Route>
				<Route exact path='/dashboard' component={Dashboard}></Route>
				<Route exact path='/virtualMobilities' component={VirtualMobilities}></Route>
				<Route exact path='/gdpr' component={GDPRAgreementPage}></Route>
				<Route exact path='/toolsSupport' component={ToolsSupport}></Route>
				<Route exact path='/mobileApp' component={MobileAppPage}></Route>
			</Switch>
			<GDPRDialog />
			<Header/>
			<Footer/>
		</div>
	);
}

export default App;
