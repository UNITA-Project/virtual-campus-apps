import {createSlice} from '@reduxjs/toolkit';

const webViewSlice = createSlice({
  name: 'webview',
  initialState: {reference: null},
  reducers: {
    setReference: (state, action) => {
      state.reference = action.payload.reference;
    },
  },
});

export const {setReference} = webViewSlice.actions;

export default webViewSlice.reducer;
