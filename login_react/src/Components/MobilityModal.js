import React from "react";
import Modal from '@mui/material/Modal';
import { Button, CardActions, CardContent, CardHeader, Paper, Table, TableBody, TableCell } from "@mui/material";
import { useSelector } from "react-redux";
import { MobilityCard, MobilityBox, StyledTableRow } from "../Styles/MUIStyled";

const MobilityModal = ({open, setOpen, dataObject}) => {
    const handleClose = () => setOpen(false);

    const languageSelector = useSelector((state) => {
        let languages = state.languages;
        let selectedLang = languages.find((lang) => lang.selected);
        return selectedLang;
    });
    const mobilityText = languageSelector.mobilityColumns;

    return (
        <Modal
            keepMounted
            open={open}
            onClose={handleClose}
            aria-labelledby="keep-mounted-modal-title"
            aria-describedby="keep-mounted-modal-description"
            style={{display: "flex", justifyContent: "center", alignItems: "center"}}
        >
            <MobilityBox>
                <CardHeader title={dataObject["Name"]} style={{backgroundColor: "#84A9AC", color: "#333333", borderRadius: "5px"}} />
                <MobilityCard>
                    <CardContent>
                        <Paper>
                            <Table>
                                <TableBody>
                                    {dataObject ? Object.keys(dataObject).map(key => {
                                        const value = dataObject[key];
                                        console.log("..." + key + "...")
                                        return (
                                            <StyledTableRow key={key} hover>
                                                <TableCell>{key[key.length - 1] === '|' ? mobilityText[key.substring(0, key.length - 1)] : mobilityText[key]}</TableCell>
                                                <TableCell align="right">{typeof value === "string" && value.substring(0, 5).includes("http") ? <a style={{color: "blue"}} href={value} onClick={(event) => {event.stopPropagation()}}>Go to page</a> : (typeof value === "string" && value[value.length - 1] === '|' ? value.substring(0, value.length - 1) : value)}</TableCell>
                                            </StyledTableRow>
                                        );
                                    }) : null}
                                </TableBody>
                            </Table>
                        </Paper>
                    </CardContent>
                </MobilityCard>
                <CardActions>
                        <Button onClick={handleClose}>Close</Button>
                </CardActions>
            </MobilityBox>
        </Modal>
    );

};

export default MobilityModal;