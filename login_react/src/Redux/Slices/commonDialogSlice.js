import { createSlice } from '@reduxjs/toolkit';

const commonDialogSlice = createSlice({
    name: "commonDialog",
    initialState: {open: false, title: undefined, body: undefined, handleClose: undefined, button1: {text: undefined, action: undefined}, button2: {text: undefined, action: undefined}},
    reducers: {
        toggleDialogOpen: (state, action) => {
            if (action.payload.open !== undefined) {
                state.open = action.payload.open;
            }

            state.title = action.payload.title;
            state.body = action.payload.body;

            if (action.payload.button1 !== undefined) {
                state.button1 = action.payload.button1;
            } else {
                state.button1 = {text: undefined, action: undefined}
            }

            if (action.payload.button2 !== undefined) {
                state.button2 = action.payload.button2;
            } else {
                state.button2 = {text: undefined, action: undefined}
            }
            
            if (typeof action.payload.handleClose === "function") {
                state.handleClose = action.payload.handleClose;
            } else {
                state.handleClose = undefined;
            }
        },
    }
});

export const { toggleCommonDialog } = commonDialogSlice.actions;

export default commonDialogSlice.reducer;