import React from 'react';
import {ImageBackground, StyleSheet, View} from 'react-native';
import {NavigationContainer} from '@react-navigation/native';
import {Provider as Provider_RNPaper} from 'react-native-paper';
import {Provider} from 'react-redux';
import {persistStore} from 'redux-persist';
import {PersistGate} from 'redux-persist/integration/react';
import store from './src/Redux/store';
import MainPage from './src/Components/MainPage';
import LoginWebView from './src/Components/LoginWebView';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import Dashboard from './src/Components/Dashboard';
import WebViewService from './src/Components/WebViewService';

// use for redux, async data storage
let persistor = persistStore(store);
// a stack of screens, use for navigation
const Stack = createNativeStackNavigator();

function App() {
  return (
    <React.StrictMode>
      <Provider store={store}>
        <PersistGate loading={null} persistor={persistor}>
          <NavigationContainer>
            <Provider_RNPaper>
              <ImageBackground
                source={require('./src/Img/mountain.jpg')}
                style={styles.image_bkgd}>
                <View style={{flex: 1}}>
                  <Stack.Navigator
                    initialRouteName="Home"
                    screenOptions={{headerShown: false}}>
                    <Stack.Screen name="Home" component={MainPage} />
                    <Stack.Screen name="Login" component={LoginWebView} />
                    <Stack.Screen name="Dashboard" component={Dashboard} />
                    <Stack.Screen
                      name="WebViewService"
                      component={WebViewService}
                    />
                  </Stack.Navigator>
                </View>
              </ImageBackground>
            </Provider_RNPaper>
          </NavigationContainer>
        </PersistGate>
      </Provider>
    </React.StrictMode>
  );
}

const styles = StyleSheet.create({
  image_bkgd: {
    flex: 1,
    resizeMode: 'cover',
  },
});

export default App;
