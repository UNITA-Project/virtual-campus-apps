import * as React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import { useSelector } from "react-redux";
import { Table, TableBody, TableCell, TableHead, TableRow, Tooltip } from '@mui/material';
import { CardContainer } from '../Styles/styledComponentStyles';

export default function ToolsSupport (props) {
    let languageSelector = useSelector((state) => {
        let languages = state.languages;
        let selectedLang = languages.find((lang) => lang.selected);
        return selectedLang;
    });
    
    return (
        <CardContainer>
            <Card sx={{ width: 400, maxHeight: "70vh", overflowY: "scroll", boxShadow: "5px 10px 8px rgba(0, 0, 0, 0.486)"}}>
                <CardContent>
                    <Typography gutterBottom variant="h5" component="div">
                        {languageSelector.support}
                    </Typography>
                    <Table sx={{width: 350, background: 'rgb(220, 220, 220)', borderRadius: '5px'}} size="small" aria-label="simple table">
                        <TableHead>
                            <TableRow>
                                <TableCell><b>{languageSelector.service}</b></TableCell>
                                <TableCell>{languageSelector.serviceDescription}</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            <TableRow>
                                <TableCell><b>Forum</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="flavia.costi99@e-uvt.ro"><p>Flavia Costi, </p></Tooltip>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell><b>LimeSurvey</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="matei.bertea99@e-uvt.ro"><p>Matei Bertea,</p></Tooltip>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell><b>Analytics</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="bogdan.balazs@e-uvt.ro"><p>Bogdan Balazs,</p></Tooltip>
                                    <Tooltip title="alan.rousic@univ-pau.fr"><p>Alan Rousic</p></Tooltip>
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell><b>Wiki</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="andreipopica1@gmail.com"><p>Andrei Popica,</p></Tooltip>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                            <TableRow sx={{'&:last-child td, &:last-child th': { border: 0 } }}>
                                <TableCell><b>Social Network</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="bogdan.balazs@e-uvt.ro"><p>Bogdan Balazs,</p></Tooltip>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <Table sx={{marginTop: '20px', width: 350, background: 'rgb(220, 220, 220)', borderRadius: '5px'}} size="small" aria-label="simple table">
                        <TableHead>
                            <TableRow>
                                <TableCell><b>WP</b></TableCell>
                                <TableCell>{languageSelector.wpDescription}</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            <TableRow>
                                <TableCell><b>1</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="flavia.costi99@e-uvt.ro"><p>Flavia Costi,</p></Tooltip>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell><b>2</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="matei.bertea99@e-uvt.ro"><p>Matei Bertea,</p></Tooltip>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell><b>3</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="alan.rousic@univ-pau.fr"><p>Alan Rousic,</p></Tooltip>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell><b>4</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="bogdan.balazs@e-uvt.ro"><p>Bogdan Balazs,</p></Tooltip>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell><b>6</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell><b>7</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px'}}>
                                    <Tooltip title="andreipopica1@gmail.com"><p>Andrei Popica,</p></Tooltip>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                            <TableRow sx={{'&:last-child td, &:last-child th': { border: 0 } }}>
                                <TableCell><b>8</b></TableCell>
                                <TableCell sx={{display: 'flex', justifyContent: 'flex-start', columnGap: '5px', flexWrap: 'wrap'}}>
                                    <Tooltip title="batista@ubi.pt"><p>Tiago Batista,</p></Tooltip>
                                    <Tooltip title="mjsantos@ubi.pt"><p>Maria João Santos,</p></Tooltip>
                                    <Tooltip title="roxana.flueras@e-uvt.ro"><p>Roxana Flueraș</p></Tooltip>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </CardContainer>
    );
};