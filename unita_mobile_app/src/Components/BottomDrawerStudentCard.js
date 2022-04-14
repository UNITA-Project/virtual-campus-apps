import React from 'react';
import {Text, View, Image} from 'react-native';
import QRCode from 'react-native-qrcode-svg';
import SwipeUpDown from 'react-native-swipe-up-down';
import person_logo from '../Img/person.png';
import {useSelector} from 'react-redux';

const BottomDrawerStudentCard = props => {
  let userSelector = useSelector(state => {
    return state.user;
  });

  let languageSelector = useSelector(state => {
    let languages = state.languages;
    let selectedLang = languages.find(lang => lang.selected);
    return selectedLang;
  });

  let base64Logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOEAA..';

  return (
    <SwipeUpDown
      itemMini={
        <View style={{flex: 1, alignItems: 'center'}}>
          <Text
            style={{
              fontWeight: 'bold',
              color: 'black',
              fontSize: 18,
              justifyContent: 'center',
            }}>
            {languageSelector.expiredSessionToast}
          </Text>
        </View>
      }
      itemFull={
        <View>
          <View style={{flexDirection: 'row'}}>
            <Image source={person_logo} style={{height: 200, width: 200}} />
            <View>
              <Text>
                {languageSelector.lastName}:{' '}
                {userSelector.userData !== undefined
                  ? userSelector.userData.family_name
                  : 'error'}
              </Text>
              <Text>
                {languageSelector.firstName}:{' '}
                {userSelector.userData !== undefined
                  ? userSelector.userData.given_name
                  : 'error'}
              </Text>
            </View>
          </View>
          <View style={{marginTop: 50, marginLeft: 50}}>
            <QRCode
              value="Just some string value"
              size={300}
              logo={{uri: base64Logo}}
              logoSize={30}
              logoBackgroundColor="transparent"
            />
          </View>
        </View>
      }
      onShowMini={() => console.log('mini')}
      onShowFull={() => console.log('full')}
      animation="spring"
      iconColor="grey"
      iconSize={30}
      disablePressToShow={true}
      style={{
        backgroundColor: '#fff',
        shadowColor: '#000',
        shadowOffset: {
          width: 0,
          height: 12,
        },
        shadowOpacity: 1,
        shadowRadius: 16.0,
        elevation: 24,
      }} // style for swipe
    />
  );
};

export default BottomDrawerStudentCard;
