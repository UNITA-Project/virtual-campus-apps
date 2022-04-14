import React from 'react';
import {
  ScrollView,
  StyleSheet,
  ImageBackground,
  Dimensions,
  BackHandler,
  Alert,
  ToastAndroid,
} from 'react-native';
import {useSelector, useDispatch} from 'react-redux';
import limeSurveyLogo from '../Img/lime_survey.png';
import media_wiki_logo from '../Img/MediaWiki_logo.png';
import unita_logo from '../Img/unita_logo.png';
import humhub_logo from '../Img/humhub_logo.jpeg';
import unita_bw_logo from '../Img/unita_bw_logo.jpg';
import matomo_logo from '../Img/Matomo_logo_big.png';
import Header from './Header';
import {useFocusEffect} from '@react-navigation/native';
import {deleteToken} from '../Redux/Slices/tokenSlice';
import {deleteData} from '../Redux/Slices/userSlice';
import BottomDrawerStudentCard from './BottomDrawerStudentCard';
import ServiceCard from './ServiceCard';

const Dashboard = ({route, navigation}) => {
  let dispatch = useDispatch();
  let limeSurvey =
    'https://survey.unitassotest.uvt.ro/index.php/admin/authentication/sa/login';
  let APForum = 'https://forum.unitassotest.uvt.ro/';
  let wiki =
    'https://wiki.unitassotest.uvt.ro/index.php?title=Special:UserLogin&returnto=Main+Page';
  let social = 'https://social.unitassotest.uvt.ro/';

  // let socialNet = "https://socialnet.univ-unita.eu/";
  // let xaion = "https://xaion.uvt.ro/";
  // let yourUniversityPortal = "https://intranet.uvt.ro/";

  let virtualMobilities = 'https://unitassotest.uvt.ro/virtualMobilities';
  let analyticsURL = 'https://analytics.unitassotest.uvt.ro/';

  const deviceHeight = Dimensions.get('window').height;
  const deviceWidth = Dimensions.get('window').width;

  let languageSelector = useSelector(state => {
    let languages = state.languages;
    let selectedLang = languages.find(lang => lang.selected);
    return selectedLang;
  });

  const handleLogOut = () => {
    dispatch(deleteToken());
    dispatch(deleteData());
    navigation.navigate('Home');
  };

  let userSelector = useSelector(state => {
    return state.user;
  });
  if (userSelector.userData !== undefined) {
    let userDataExp = userSelector.userData.exp;
    let expiresDate = new Date(userDataExp * 1000);
    let now = new Date();
    if (expiresDate < now) {
      ToastAndroid.show(languageSelector.expiredSessionToast, 6000);
      setTimeout(handleLogOut, 6500);
    }
  }

  useFocusEffect(
    React.useCallback(() => {
      const handleLogOut = () => {
        dispatch(deleteToken());
        dispatch(deleteData());
        navigation.navigate('Home');
      };

      const onBackPress = () => {
        Alert.alert(
          languageSelector.dialogDisconnectWarning,
          languageSelector.dialogDisconnectText,
          [
            {
              text: languageSelector.dialogDisconnectDontQuit,
              style: 'cancel',
              onPress: () => {},
            },
            {
              text: languageSelector.dialogDisconnectQuit,
              style: 'destructive',
              onPress: () => {
                handleLogOut();
              },
            },
          ],
        );
        return true;
      };

      BackHandler.addEventListener('hardwareBackPress', onBackPress);

      return () =>
        BackHandler.removeEventListener('hardwareBackPress', onBackPress);
    }, [dispatch, navigation, languageSelector]),
  );

  return (
    <ImageBackground source={require('../Img/mountain.jpg')} style={{flex: 1}}>
      <Header navigation={navigation} />
      <ScrollView style={{height: deviceHeight, width: deviceWidth}}>
        <ServiceCard
          styleTitle={styles.card_title}
          styleCard={styles.card}
          styleImage={styles.image}
          logo={limeSurveyLogo}
          title="Surveys"
          reference={limeSurvey}
          navigation={navigation}
        />
        <ServiceCard
          styleTitle={styles.card_title}
          styleCard={styles.card}
          styleImage={styles.image}
          logo={unita_logo}
          title="Forum"
          reference={APForum}
          navigation={navigation}
        />
        <ServiceCard
          styleTitle={styles.card_title}
          styleCard={styles.card}
          styleImage={styles.image}
          logo={media_wiki_logo}
          title="Wiki"
          reference={wiki}
          navigation={navigation}
        />
        <ServiceCard
          styleTitle={styles.card_title}
          styleCard={styles.card}
          styleImage={styles.image}
          logo={humhub_logo}
          title="Social Network"
          reference={social}
          navigation={navigation}
        />
        <ServiceCard
          styleTitle={styles.card_title}
          styleCard={styles.card}
          styleImage={styles.image}
          logo={unita_bw_logo}
          title="Virtual Mobilities"
          reference={virtualMobilities}
          navigation={navigation}
        />
        <ServiceCard
          styleTitle={styles.card_title}
          styleCard={styles.lastCard}
          styleImage={styles.image}
          logo={matomo_logo}
          title="Analytics"
          reference={analyticsURL}
          navigation={navigation}
        />
      </ScrollView>
      {/* <BottomDrawerStudentCard style={styles.drawer} /> */}
    </ImageBackground>
  );
};

const styles = StyleSheet.create({
  image: {
    flex: 1,
    backgroundColor: 'transparent',
    borderRadius: 35,
  },
  card_title: {
    alignItems: 'center',
  },
  card: {
    borderRadius: 35,
    marginLeft: 'auto',
    marginRight: 'auto',
    marginTop: 10,
    width: 300,
    height: 150,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 12,
    },
    shadowOpacity: 0.58,
    shadowRadius: 16.0,
    elevation: 24,
  },
  lastCard: {
    borderRadius: 35,
    marginLeft: 'auto',
    marginRight: 'auto',
    marginTop: 10,
    width: 300,
    height: 150,
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: 12,
    },
    shadowOpacity: 0.58,
    shadowRadius: 16.0,
    elevation: 24,
    marginBottom: 120,
  },
  drawer: {
    paddingBottom: 0,
  },
});

export default Dashboard;
