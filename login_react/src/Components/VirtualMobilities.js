import React from "react";
import { useEffect, useState } from "react";
import { PageHolder } from '../Styles/styledComponentStyles';
import { readString } from 'react-papaparse';
import Paper from '@mui/material/Paper';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableContainer from '@mui/material/TableContainer';
import TableHead from '@mui/material/TableHead';
import TablePagination from '@mui/material/TablePagination';
import TableRow from '@mui/material/TableRow';
import blob from "../CSV/virtual_mobilities.csv"
import MobilityModal from "./MobilityModal";
import { TableSortLabel } from "@mui/material";
import { useSelector } from "react-redux";
import { StyledTableCell, StyledTableRow } from "../Styles/MUIStyled";

const VirtualMobilities = () => {
    const [mobilities, setMobilities] = useState(null);
    
    const languageSelector = useSelector((state) => {
        let languages = state.languages;
        let selectedLang = languages.find((lang) => lang.selected);
        return selectedLang;
    });
    const mobilityText = languageSelector.mobilityColumns;

    useEffect(() => {
        const parseFile = csvFile => {
            readString(csvFile, {
                download: true,
                header: true,
                delimiter: "$",
                linebreak: "|",
                dynamicTyping: true,
                complete: (results, file) => {
                    setMobilities(results);
                }
            });
        }
        parseFile(blob);
    }, []);

    const importantFields = [{
            name: "Institution",
            minWidth: 200
        }, {
            name: "Field (ISCED code)*",
            minWidth: 200
        }, {
            name: "Name",
            minWidth: 200
        }, {
            name: "Language of instruction",
            minWidth: 200
        }, {
            name: "ECTS",
            minWidth: 200
        }, {
            name: "Asynchronous / Synchronous Course (optional)",
            minWidth: 200
        }, {
            name: "Web Link to the description course",
            minWidth: 200
        }, {
            name: "Contact of the course responsible",
            minWidth: 200
        }
    ];

    const [page, setPage] = useState(0);
    const [rowsPerPage, setRowsPerPage] = useState(10);
    const [open, setOpen] = useState(false);
    const [modalData, setModalData] = useState(null);
    const [orderBy, setOrderBy] = useState({column: "None", order: "asc"});
    const sortOptions = { sensitivity: 'base', ignorePunctuation: true };

    const handleChangePage = (event, newPage) => {
        setPage(newPage);
    };

    const handleChangeOfOrder = (selectedColumn) => {
        const mobilitiesCpy = {...mobilities};
        const orderByCpy = {...orderBy};

        if (orderByCpy.column === selectedColumn) {
            orderByCpy.order = orderByCpy.order === "asc" ? "desc" : "asc";
        } else {
            orderByCpy.column = selectedColumn;
            orderByCpy.order = "asc";
        }

        mobilitiesCpy.data.sort((a, b) => (orderByCpy.order === "asc") ? String(a[selectedColumn]).localeCompare(b[selectedColumn], 'en', sortOptions) : (-1) * String(a[selectedColumn]).localeCompare(b[selectedColumn], 'en', sortOptions) );

        setMobilities(mobilitiesCpy);
        setOrderBy(orderByCpy);
    };

    const handleChangeRowsPerPage = (event) => {
        setRowsPerPage(+event.target.value);
        setPage(0);
    };

    return (
        <PageHolder>
            {(mobilities === null) ? null : 
                <Paper sx={{ width: '90%', overflow: 'hidden', maxHeight: "80%", marginTop: "35px"}}>
                    <TableContainer sx={{ maxHeight: "90%" }}>
                        <Table stickyHeader aria-label="sticky table">
                            <TableHead sx={{zIndex: "1"}}>
                                <TableRow>
                                    {importantFields.map((column) => (
                                        <StyledTableCell
                                            key={"Col" + column.name}
                                            align={"left"}
                                            style={{ minWidth: column.minWidth + "px" }}
                                        >
                                            <TableSortLabel 
                                                active={orderBy.column === column.name}
                                                direction={orderBy.column === column.name ? orderBy.order : 'asc'}
                                                onClick={() => {handleChangeOfOrder(column.name)}}
                                            >
                                                {mobilityText[column.name]}
                                            </TableSortLabel>
                                        </StyledTableCell>
                                    ))}
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {mobilities.data.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage).map((row, indexR) => (
                                        <StyledTableRow hover role="checkbox" tabIndex={-1} key={"Row" + indexR} onClick={() => {setOpen(true); setModalData(row);}}>
                                            {importantFields.map((column, indexC) => {
                                                const value = row[column.name];
                                                return (
                                                    <StyledTableCell key={indexC + "-" + indexR}>
                                                    {/* {(indexC === 6 && typeof value === "string") ? (value.includes("http") ? <a style={{color: "blue"}}href={value}>Go to page</a> : value) : value} */}
                                                    {typeof value === "string" && value.includes("http") ? <a style={{color: "blue"}} href={value} onClick={(event) => {event.stopPropagation()}}>Go to page</a> : value}
                                                    </StyledTableCell>
                                                );
                                            })}
                                        </StyledTableRow>
                                    )
                                )}
                            </TableBody>
                        </Table>
                    </TableContainer>
                    <TablePagination
                        rowsPerPageOptions={[10, 25, 100]}
                        component="div"
                        count={mobilities.data.length}
                        rowsPerPage={rowsPerPage}
                        page={page}
                        onPageChange={handleChangePage}
                        onRowsPerPageChange={handleChangeRowsPerPage}
                    />
                </Paper> 
            }
            {modalData === null ? null : <MobilityModal open={open} setOpen={setOpen} dataObject={modalData} />}
        </PageHolder>
    );
};

export default VirtualMobilities;
