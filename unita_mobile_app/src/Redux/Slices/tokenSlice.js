import {createSlice} from '@reduxjs/toolkit';

const tokenSlice = createSlice({
  name: 'token',
  initialState: {userToken: undefined},
  reducers: {
    addToken: (state, action) => {
      state.userToken = action.payload.userToken;
    },
    deleteToken: (state, action) => {
      state.userToken = undefined;
    },
  },
});

export const {addToken} = tokenSlice.actions;

export const {deleteToken} = tokenSlice.actions;

export default tokenSlice.reducer;
