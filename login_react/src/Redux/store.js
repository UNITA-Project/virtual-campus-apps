import { configureStore } from '@reduxjs/toolkit';
import languageReducer from './Slices/languageSlice';
import dialogReducer from './Slices/dialogOpenSlice';
import commonDialogReducer from './Slices/commonDialogSlice';
import userReducer from './Slices/userSlice';
import thunk from "redux-thunk"
import {combineReducers} from "redux"; 
import { persistReducer } from 'redux-persist';
import storage from 'redux-persist/lib/storage';

const reducers = combineReducers({
    languages: languageReducer,
    dialogOpen: dialogReducer,
    commonDialog: commonDialogReducer,
    user: userReducer
});

const persistConfig = {
    key: 'root',
    storage
};

const persistedReducer = persistReducer(persistConfig, reducers);

export default configureStore({
	reducer: persistedReducer,
    middleware: [thunk]
})