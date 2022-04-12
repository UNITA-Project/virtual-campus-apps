import CardContent from '@mui/material/CardContent';
import CardMedia from '@mui/material/CardMedia';
import Typography from '@mui/material/Typography';
import { useDispatch } from 'react-redux';
import { toggleDialogOpen } from '../Redux/Slices/dialogOpenSlice';
import React from "react";
import useWindowSize from "../utils/useWindowSize";
import { GridItem, MiniGridItem, MiniGridItemHolder } from '../Styles/styledComponentStyles';

function UniversityCard(props){

	let dispatch = useDispatch();

	const handleDialogOpen = () => {
		dispatch(
			toggleDialogOpen({open: true, reference: props.reference})
		);
	};

	const { width } = useWindowSize();

	if (width > 650) {
		return(
			<div onClick={handleDialogOpen}>
				<GridItem>
					<CardMedia component="img"
						image={props.imagePath}
					/>
					{props.title && width > 1000 ?
						<CardContent style={{ position: 'relative', display: 'flex', justifyContent: 'center', width: '80%', textAlign: 'center' }} component="div">
							<Typography style={{ position: 'absolute', bottom: 5 }} variant="h6" component="span">
								{props.title}
							</Typography>
						</CardContent>
					: null 
					}
				</GridItem>
			</div> 
		);
	}

	return(
		<MiniGridItemHolder onClick={handleDialogOpen}>
			<MiniGridItem>
				<CardMedia component="img"
					image={props.miniImagePath}
				/>
			</MiniGridItem>
		</MiniGridItemHolder>
	)
}

export default UniversityCard;