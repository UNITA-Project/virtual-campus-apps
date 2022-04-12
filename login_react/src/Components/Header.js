import unita_logo from "../Img/unita_logo.png"
import { useState, useRef,useEffect } from "react";
import Button from '@mui/material/Button';
import ClickAwayListener from '@mui/material/ClickAwayListener';
import Grow from '@mui/material/Grow';
import Paper from '@mui/material/Paper';
import Popper from '@mui/material/Popper';
import MenuItem from '@mui/material/MenuItem';
import MenuList from '@mui/material/MenuList';
import { useDispatch } from 'react-redux';
import { selectLanguage } from "../Redux/Slices/languageSlice";
import { logOut } from "../Redux/Slices/userSlice"
import Avatar from '@mui/material/Avatar';
import Stack from '@mui/material/Stack';
import Menu from '@mui/material/Menu';
import IconButton from '@mui/material/IconButton';
import { useSelector } from "react-redux";
import Divider from '@mui/material/Divider';
import ListItemIcon from '@mui/material/ListItemIcon';
import Logout from '@mui/icons-material/Logout';
import { Typography } from "@mui/material";
import { HeaderContainer, LogoLangContainer } from "../Styles/styledComponentStyles";


function Header(){

	let userSelector = useSelector((state) => state.user.userData);
	let dispatch = useDispatch();
	const [open, setOpen] = useState(false);
	const [anchorEl, setAnchorEl] = useState(null);
	const [logoutRedirect, setLogoutRedirect] = useState(false);
	
	const handleMenu = (event) => {
		setAnchorEl(event.currentTarget);
	};

	const handleCloseProfile = () => {
		setAnchorEl(null);
	};

	const handleLogOut = () => {
		dispatch(
			logOut()
		);
		
		setAnchorEl(null);
		setLogoutRedirect(true);
	};

	const anchorRef = useRef(null);

	const handleToggle = () => {
		setOpen((prevOpen) => !prevOpen);
	};

	const handleClose = (event, data) => {
		if (anchorRef.current && anchorRef.current.contains(event.target)) {
			return;
		}
		if(data) {
			dispatch(
				selectLanguage({
					id: data
				})
			);
		}
		setOpen(false);
	};

	function handleListKeyDown(event) {
		if (event.key === 'Tab') {
			event.preventDefault();
			setOpen(false);
		} else if (event.key === 'Escape') {
			setOpen(false);
		}
	}

	const prevOpen = useRef(open);
	useEffect(() => {
		if (prevOpen.current === true && open === false) {
			anchorRef.current.focus();
		}

		prevOpen.current = open;
	}, [open]);

	let languageSelector = useSelector((state) => {
		let languages = state.languages;
		let selectedLang = languages.find((lang) => lang.selected);
		return selectedLang;
	});

	if (logoutRedirect) {
		window.location.assign('https://unitassotest.uvt.ro/');
	}

	return(
		<HeaderContainer>
			<LogoLangContainer>
				<a href="http://univ-unita.eu/"><img style={{width: "90px"}} src={unita_logo} alt="Logo Unita" /></a>
				<div>
					<Stack direction="row" spacing={2}>
						{(userSelector && !userSelector.wrongCred) && (
							<div>
								<IconButton
									sx={{height: "35px", width: "35px"}}
									aria-label="account of current user"
									aria-controls="menu-appbar"
									aria-haspopup="true"
									onClick={handleMenu}
									color="inherit"
								>
									<Avatar sx={{bgcolor: "#2B76D2", boxShadow: "1px 1px 8px rgba(0, 0, 0, 0.100)"}} >{ userSelector.firstName && userSelector.lastName? userSelector.firstName[0] + userSelector.lastName[0] : "S2"}</Avatar>
								</IconButton>
								<Menu
									id="menu-appbar"
									anchorEl={anchorEl}
									placement="bottom-start"
									open={Boolean(anchorEl)}
									onClose={handleCloseProfile}
								>
									<MenuItem >
										<Stack direction="row" spacing={2}>
											<Avatar sx={{height: "25px", width: "25px"}} />
											<Typography>{userSelector.firstName && userSelector.lastName ? userSelector.firstName + " " + userSelector.lastName : "A1" }</Typography>
										</Stack>
									</MenuItem>
									<Divider />
									<MenuItem onClick={handleLogOut}>
										<ListItemIcon >
											<Logout fontSize="small" />
										</ListItemIcon>
										{languageSelector.logOut}
									</MenuItem>
								</Menu>
							</div>
          				)}


						<Button
							ref={anchorRef}
							variant = "contained"
							id="composition-button"
							aria-controls={open ? 'composition-menu' : undefined}
							aria-expanded={open ? 'true' : undefined}
							aria-haspopup="true"
							onClick={handleToggle}
						>
							{languageSelector.language}
						</Button>
						<Popper
							style={{zIndex: "2"}}
							open={open}
							anchorEl={anchorRef.current}
							role={undefined}
							placement="bottom-start"
							transition
							disablePortal
						>
							{({ TransitionProps, placement }) => (
							<Grow
								{...TransitionProps}
								style={{
								transformOrigin:
								placement === 'bottom-start' ? 'left top' : 'left bottom',
								}}
							>
								<Paper>
									<ClickAwayListener onClickAway={handleClose}>
										<MenuList 
											autoFocusItem={open}
											id="composition-menu"
											aria-labelledby="composition-button"
											onKeyDown={handleListKeyDown}
										>
											<MenuItem onClick={((e) => handleClose(e, 1))}>English</MenuItem>
											<MenuItem onClick={((e) => handleClose(e, 2))}>Portuguese</MenuItem>
											<MenuItem onClick={((e) => handleClose(e, 3))}>French</MenuItem>
											<MenuItem onClick={((e) => handleClose(e, 4))}>Romanian</MenuItem>
											<MenuItem onClick={((e) => handleClose(e, 5))}>Italian</MenuItem>
											<MenuItem onClick={((e) => handleClose(e, 6))}>Spanish</MenuItem>
										</MenuList>
									</ClickAwayListener>
								</Paper>
							</Grow>
							)}
						</Popper>
					</Stack>
				</div>
			</LogoLangContainer>
		</HeaderContainer>
	)

}

export default Header