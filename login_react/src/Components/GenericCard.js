import CardContent from '@mui/material/CardContent';
import CardMedia from '@mui/material/CardMedia';
import Typography from '@mui/material/Typography';
import React from "react";
import { GridItem, MiniGridItem } from '../Styles/styledComponentStyles';
import useWindowSize from '../utils/useWindowSize';

function GenericCard(props){

    const { width } = useWindowSize();

    if (width > 650) {
        return (
            <a href={props.reference}>
                <GridItem>
                    <CardMedia component="img"
						image={props.imagePath}
                        style={{height: "70%"}}
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
            </a>
        );
    }

    return(
        <a href={props.reference}>
        	<MiniGridItem>
				<CardMedia component="img"
					image={props.miniImagePath}
				/>
			</MiniGridItem>
		</a>
    );
}


export default GenericCard;