import React from 'react';
import {
  ScrollView,
  StyleSheet,
  ImageBackground,
  Dimensions,
} from 'react-native';
import UniversityCard from './UniversityCard';
import GDPRDialog from './GDPRDialog';
import {useSelector} from 'react-redux';
import {Button} from 'react-native-paper';
import Header from './Header';

function MainPage({navigation}) {
  let tokenSelector = useSelector(state => {
    let token = state.token;
    return token;
  });

  let languageSelector = useSelector(state => {
    let languages = state.languages;
    let selectedLang = languages.find(lang => lang.selected);
    return selectedLang;
  });

  let userToken = tokenSelector.userToken;
  if (userToken !== undefined) {
    navigation.navigate('Dashboard');
  }

  let beiraReference =
    'https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=https://idp.ubi.pt/idp/shibboleth';
  let pauReference =
    'https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=https://idp.univ-pau.fr/idp/shibboleth';
  let savoieReference =
    'https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=https://shibboleth.univ-savoie.fr/idp/shibboleth';
  let uvtReference =
    'https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=https://login.e-uvt.ro/aai/saml2/idp/metadata.php';
  let torinoReference =
    'https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=https://idp-unito-prod.cineca.it/idp/shibboleth';
  let zaragozaReference =
    'https://unitasso.uvt.ro/Shibboleth.sso/Login?target=https://unitasso.uvt.ro/secure&entityID=idp-sirfor.unizar.es';

  return (
    <ImageBackground
      source={require('../Img/mountain.jpg')}
      style={styles.image_bkgd}>
      <Header navigation={navigation} />
      <GDPRDialog navigation={navigation} />
      <ScrollView style={styles.scrollview}>
        <UniversityCard
          imagePath={require('../Img/beira_interior_uni_logo.jpg')}
          title={'Universidade da Beira Interior'}
          reference={beiraReference}
          navigation={navigation}
        />
        <UniversityCard
          imagePath={require('../Img/depau_uni_logo.png')}
          title={"Université de Pau et des Pays de l'Adour"}
          reference={pauReference}
          navigation={navigation}
        />
        <UniversityCard
          imagePath={require('../Img/mont_blanc_uni_logo.png')}
          title={'Universite Savoie Mont Blanc'}
          reference={savoieReference}
          navigation={navigation}
        />
        <UniversityCard
          imagePath={require('../Img/logo_uvt.png')}
          title={'Universitatea de Vest din Timișoara'}
          reference={uvtReference}
          navigation={navigation}
        />
        <UniversityCard
          imagePath={require('../Img/torino_uni_logo.png')}
          title={'Universita Degli Studi di Torino'}
          reference={torinoReference}
          navigation={navigation}
        />
        <UniversityCard
          imagePath={require('../Img/zaragoza_uni_logo.png')}
          title={'Universidad Zaragoza'}
          reference={zaragozaReference}
          navigation={navigation}
        />
        <Button mode="contained" style={styles.button}>
          {languageSelector.localLogin}
        </Button>
      </ScrollView>
    </ImageBackground>
  );
}

const deviceHeight = Dimensions.get('window').height;
const deviceWidth = Dimensions.get('window').width;

const styles = StyleSheet.create({
  scrollview: {height: deviceHeight, width: deviceWidth},
  button: {marginBottom: 110, marginTop: 30},
});

export default MainPage;
