import jwt_decode from 'jwt-decode';
import React from 'react';
import {WebView} from 'react-native-webview';
import {View, StyleSheet, Dimensions} from 'react-native';
import {useDispatch, useSelector} from 'react-redux';
import {addToken} from '../Redux/Slices/tokenSlice';
import {addData} from '../Redux/Slices/userSlice';
import CookieManager from '@react-native-community/cookies';

const LoginWebView = ({route, navigation}) => {
  let dialogSelector = useSelector(state => {
    let dialog = state.dialogOpen;
    return dialog;
  });

  let dispatch = useDispatch();
  let userTokenAddToSlice = res => {
    dispatch(addToken({userToken: res}));
    const token = res.USER_TOKEN.value;
    var token_decoded = jwt_decode(token);
    dispatch(addData({userData: token_decoded}));
  };

  return (
    <View style={{flex: 1}}>
      <WebView
        originWhitelist={['https://*']}
        style={styles.site}
        javaScriptEnabled={true}
        incognito={true}
        keyboardDisplayRequiresUserAction={false} //ios
        autoFocus={true} //android
        startInLoadingState={true}
        useWebKit={true}
        onLoadStart={syntheticEvent => {
          const {nativeEvent} = syntheticEvent;
          // console.log('event - loadstart = ' + nativeEvent.url);
        }}
        onLoadProgress={syntheticEvent => {
          const {nativeEvent} = syntheticEvent;
          // console.log('event - loadprogress = ' + nativeEvent.url);
          // console.log('event - loadprogress = ' + nativeEvent.progress);
        }}
        onLoadEnd={syntheticEvent => {
          const {nativeEvent} = syntheticEvent;
          // console.log('event - loadend = ' + nativeEvent.url);
        }}
        cacheEnabled={false}
        thirdPartyCookiesEnabled={true}
        onNavigationStateChange={event => {
          navChange(event).then(res => {
            userTokenAddToSlice(res);
            navigation.popToTop();
            navigation.navigate('Dashboard');
          });
        }}
        source={{
          uri: dialogSelector.reference,
        }}
      />
    </View>
  );
};
const navChange = e => {
  return new Promise(function (resolve, reject) {
    if (e.url === 'https://unitassotest.uvt.ro/') {
      CookieManager.get('https://unitassotest.uvt.ro/')
        .then(res => {
          resolve(res);
          if (!res) {
            CookieManager.clearAll(true).then(res => {});
          }
        })
        .done();
    }
  });
};

const deviceHeight = Dimensions.get('window').height;
const deviceWidth = Dimensions.get('window').width;

const styles = StyleSheet.create({
  container: {},
  site: {width: deviceWidth, height: deviceHeight, opacity: 0.99},
});

export default LoginWebView;
