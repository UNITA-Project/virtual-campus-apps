import {configureStore} from '@reduxjs/toolkit';
import AsyncStorage from '@react-native-async-storage/async-storage';
import languageReducer from './Slices/languageSlice';
import dialogReducer from './Slices/dialogOpenSlice';
import userReducer from './Slices/userSlice';
import tokenReducer from './Slices/tokenSlice';
import webViewReducer from './Slices/webViewSlice';
import thunk from 'redux-thunk';
import {combineReducers} from 'redux';
import {persistReducer} from 'redux-persist';

const reducers = combineReducers({
  languages: languageReducer,
  dialogOpen: dialogReducer,
  user: userReducer,
  token: tokenReducer,
  webview: webViewReducer,
});

const persistConfig = {
  key: 'root',
  storage: AsyncStorage,
};

const persistedReducer = persistReducer(persistConfig, reducers);

export default configureStore({
  reducer: persistedReducer,
  middleware: [thunk],
});
