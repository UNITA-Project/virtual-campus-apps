import {createSlice} from '@reduxjs/toolkit';

const userSlice = createSlice({
  name: 'user',
  initialState: {userData: undefined},
  reducers: {
    addData: (state, action) => {
      state.userData = action.payload.userData;
    },
    deleteData: (state, action) => {
      state.userData = undefined;
    },
  },
});

export const {addData} = userSlice.actions;

export const {deleteData} = userSlice.actions;

export default userSlice.reducer;
