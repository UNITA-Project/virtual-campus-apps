import React from 'react';
import {Card, Title} from 'react-native-paper';
import {useDispatch} from 'react-redux';
import {setReference} from '../Redux/Slices/webViewSlice';

const ServiceCard = props => {
  let dispatch = useDispatch();
  const handleRedirectionWebView = () => {
    dispatch(setReference({reference: props.reference}));
    props.navigation.navigate('WebViewService');
  };

  return (
    <Card style={props.styleCard} onPress={handleRedirectionWebView}>
      <Card.Cover
        resizeMode="contain"
        source={props.logo}
        style={props.styleImage}
      />
      <Card.Content style={props.styleTitle}>
        <Title>{props.title}</Title>
      </Card.Content>
    </Card>
  );
};

export default ServiceCard;
