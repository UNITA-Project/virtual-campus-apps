import React from 'react';
import {StyleSheet} from 'react-native';
import {useDispatch, useSelector} from 'react-redux';
import {toggleDialogOpen} from '../Redux/Slices/dialogOpenSlice';
import {Card} from 'react-native-paper';

function UniversityCard(props) {
  let dispatch = useDispatch();

  let dialogSelector = useSelector(state => {
    let dialog = state.dialogOpen;
    return dialog;
  });

  let agreedChoice = dialogSelector.agreedChoice;

  let tokenSelector = useSelector(state => {
    let token = state.token;
    return token;
  });

  const handleDialogOpen = () => {
    if (agreedChoice === true && tokenSelector.userToken !== undefined) {
      props.navigation.navigate('Dashboard');
    } else if (agreedChoice === true) {
      dispatch(
        toggleDialogOpen({
          reference: props.reference,
        }),
      );
      props.navigation.navigate('Login');
    } else {
      dispatch(
        toggleDialogOpen({
          open: true,
          reference: props.reference,
          agreedChoice: agreedChoice,
        }),
      );
    }
  };

  return (
    <Card style={styles.card} onPress={handleDialogOpen}>
      <Card.Cover
        resizeMode="contain"
        source={props.imagePath}
        style={styles.image}
      />
    </Card>
  );
}

const styles = StyleSheet.create({
  image: {
    flex: 1,
    backgroundColor: 'white',
    borderRadius: 35,
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
});

export default UniversityCard;
