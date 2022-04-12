import { styled } from '@mui/material/styles';
import Box from '@mui/material/Box';
import { Card, TableRow } from "@mui/material";
import TableCell, { tableCellClasses } from '@mui/material/TableCell';

export const MobilityCard = styled (Card) ({
    width: "100%",
    height: "85%",
    overflow:'scroll'
});

export const MobilityBox = styled (Box) ({
    width: "900px",
    height: "700px",
    backgroundColor: "white",
    borderRadius: "5px",
    maxWidth: "90vw",
    maxHeight: "90vh"
});

export const StyledTableCell = styled (TableCell) (({ theme }) => ({
    [`&.${tableCellClasses.head}`]: {
        backgroundColor: "#84A9AC",
        color: "#333333",
    },
    [`&.${tableCellClasses.body}`]: {
        fontSize: 14,
    },
}));

export const StyledTableRow = styled (TableRow) (({ theme }) => ({
    '&:nth-of-type(odd)': {
        backgroundColor: "#F5F4F4",
    },
    '&:last-child td, &:last-child th': {
        border: 0,
    },
    cursor: "pointer"
}));