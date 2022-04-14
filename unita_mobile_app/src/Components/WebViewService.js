import React from 'react';
import {WebView} from 'react-native-webview';
import {View, Dimensions, StyleSheet} from 'react-native';
import {deleteToken} from '../Redux/Slices/tokenSlice';
import {useSelector, useDispatch} from 'react-redux';
import {deleteData} from '../Redux/Slices/userSlice';

const WebViewService = ({route, navigation}) => {
  let dispatch = useDispatch();
  let tokenSelector = useSelector(state => {
    let token = state.token;
    return token;
  });

  let reference = useSelector(state => {
    let reference = state.webview.reference;
    return reference;
  });

  const handleLogOut = () => {
    dispatch(deleteToken());
    dispatch(deleteData());
    navigation.popToTop();
    navigation.navigate('Home');
  };

  return (
    <View style={{flex: 1}}>
      <WebView
        source={{uri: reference}}
        style={styles.site}
        javaScriptEnabled={true}
        // incognito={true}
        keyboardDisplayRequiresUserAction={false} //ios
        autoFocus={true} //android
        startInLoadingState={true}
        // useWebKit={true}
        // cacheEnabled={false}
        thirdPartyCookiesEnabled={true}
        onNavigationStateChange={navState => {
          // console.log(navState);
          if (navState.url === 'https://unitassotest.uvt.ro/') {
            handleLogOut();
          }
        }}
      />
    </View>
  );
};

const deviceHeight = Dimensions.get('window').height;
const deviceWidth = Dimensions.get('window').width;

const styles = StyleSheet.create({
  site: {width: deviceWidth, height: deviceHeight, opacity: 0.99},
});

export default WebViewService;
