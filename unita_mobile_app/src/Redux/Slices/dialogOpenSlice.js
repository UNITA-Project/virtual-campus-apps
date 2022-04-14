import {createSlice} from '@reduxjs/toolkit';

const dialogOpenSlice = createSlice({
  name: 'dialogOpen',
  initialState: {open: false, reference: '#', agreedChoice: false},
  reducers: {
    toggleDialogOpen: (state, action) => {
      if (action.payload.open !== undefined) {
        state.open = action.payload.open;
      }
      if (action.payload.reference !== undefined) {
        state.reference = action.payload.reference;
      }
      if (action.payload.agreedChoice !== undefined) {
        state.agreedChoice = action.payload.agreedChoice;
      }
    },
  },
});

export const {toggleDialogOpen} = dialogOpenSlice.actions;

export default dialogOpenSlice.reducer;
