import React, {useState} from 'react';
import {View, StyleSheet, Image} from 'react-native';
import {Menu, Button} from 'react-native-paper';
import {useSelector, useDispatch} from 'react-redux';
import {deleteToken} from '../Redux/Slices/tokenSlice';
import {deleteData} from '../Redux/Slices/userSlice';
import {selectLanguage} from '../Redux/Slices/languageSlice';
import unita_logo from '../Img/unita_logo.png';

function Header(props) {
  let dispatch = useDispatch();
  const [open, setOpen] = useState(false);
  const [openLogout, setOpenLogout] = useState(false);

  const handleLogOut = () => {
    dispatch(deleteToken());
    dispatch(deleteData());
    props.navigation.navigate('Home');
  };

  let tokenSelector = useSelector(state => {
    let token = state.token;
    return token;
  });

  let userToken = tokenSelector.userToken;

  let userSelector = useSelector(state => {
    return state.user;
  });

  const handleClose = (event, data) => {
    if (data) {
      dispatch(
        selectLanguage({
          id: data,
        }),
      );
    }
    setOpen(false);
  };

  const handleCloseLogout = event => {
    setOpenLogout(false);
    handleLogOut();
  };

  let languageSelector = useSelector(state => {
    let languages = state.languages;
    let selectedLang = languages.find(lang => lang.selected);
    return selectedLang;
  });

  const openMenu = () => setOpen(true);

  const openMenuLogout = () => setOpenLogout(true);

  const closeMenu = () => setOpen(false);

  const closeMenuLogout = () => setOpenLogout(false);

  return (
    <View accessibilityRole="header" style={styles.container}>
      <View accessibilityRole="image">
        <Image source={unita_logo} style={styles.image} />
      </View>
      {userToken && (
        <View>
          <Menu
            visible={openLogout}
            onDismiss={closeMenuLogout}
            anchor={
              <Button
                style={styles.button_logout}
                mode="contained"
                onPress={openMenuLogout}>
                {userSelector.userData !== undefined
                  ? userSelector.userData.given_name.charAt(0)
                  : 'error'}
                {userSelector.userData !== undefined
                  ? userSelector.userData.family_name.charAt(0)
                  : 'error'}
              </Button>
            }>
            <Menu.Item
              onPress={e => console.log('profil')}
              title={
                userSelector.userData !== undefined
                  ? userSelector.userData.name
                  : 'error'
              }
            />
            <Menu.Item
              onPress={e => handleCloseLogout(e)}
              title={languageSelector.logOut}
            />
          </Menu>
        </View>
      )}
      <View style={styles.menu_view}>
        <Menu
          visible={open}
          onDismiss={closeMenu}
          anchor={
            <Button style={styles.button} mode="contained" onPress={openMenu}>
              {languageSelector.language}
            </Button>
          }>
          <Menu.Item onPress={e => handleClose(e, 1)} title="English" />
          <Menu.Item onPress={e => handleClose(e, 2)} title="Portuguese" />
          <Menu.Item onPress={e => handleClose(e, 3)} title="French" />
          <Menu.Item onPress={e => handleClose(e, 4)} title="Romanian" />
          <Menu.Item onPress={e => handleClose(e, 5)} title="Italian" />
          <Menu.Item onClick={e => handleClose(e, 6)} title="Spanish" />
        </Menu>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    backgroundColor: 'rgba(255, 255, 255, 0.25)',
    justifyContent: 'space-between',
    textAlign: 'center',
  },
  image: {
    width: 50,
    height: 50,
    marginLeft: 15,
    marginTop: 10,
    marginBottom: 10,
  },
  menu_view: {
    marginLeft: 0,
  },
  button: {
    marginTop: 15,
    marginRight: 15,
  },
  button_logout: {
    marginTop: 15,
    marginLeft: 130,
  },
});

export default Header;
