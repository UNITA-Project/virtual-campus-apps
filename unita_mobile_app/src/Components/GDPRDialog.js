import React from 'react';
import {useDispatch, useSelector} from 'react-redux';
import {View, Text} from 'react-native';
import Dialog, {
  DialogButton,
  DialogContent,
  DialogFooter,
  DialogTitle,
} from 'react-native-popup-dialog';
// import {Checkbox} from 'react-native-paper';
import {toggleDialogOpen} from '../Redux/Slices/dialogOpenSlice';
import Checkbox from '@react-native-community/checkbox';

function GDPRDialog(props) {
  let dispatch = useDispatch();
  const [checked, setChecked] = React.useState(true);

  let openSelector = useSelector(state => {
    let openValue = state.dialogOpen;
    return openValue;
  });

  let languageSelector = useSelector(state => {
    let languages = state.languages;
    let selectedLang = languages.find(lang => lang.selected);
    return selectedLang.text;
  });

  const handleClose = () => {
    dispatch(toggleDialogOpen({open: false, agreedChoice: false}));
  };

  const handleAgree = () => {
    if (checked) {
      dispatch(toggleDialogOpen({open: false, agreedChoice: true}));
      props.navigation.navigate('Login');
    } else {
      dispatch(toggleDialogOpen({open: false, agreedChoice: false}));
    }
  };

  return (
    <View>
      <Dialog
        onTouchOutside={handleClose}
        visible={openSelector.open}
        dialogTitle={<DialogTitle title={languageSelector.dialogTitleText} />}
        footer={
          <DialogFooter>
            <DialogButton
              text={languageSelector.dialogDisagreeText}
              onPress={event => {
                handleClose();
              }}
            />
            <DialogButton
              text={languageSelector.dialogAgreeText}
              onPress={event => {
                handleAgree();
              }}
            />
          </DialogFooter>
        }>
        <DialogContent>
          <Text>{languageSelector.dialogBodyText}</Text>
          <Checkbox
            value={checked}
            onValueChange={value => {
              setChecked(value);
            }}
          />
        </DialogContent>
      </Dialog>
    </View>
  );
}

export default GDPRDialog;
