import * as React from 'react';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import { CardContainer } from '../Styles/styledComponentStyles';

export default function GDPRAgreementPage(props) {

    return (
        <CardContainer>
            <Card sx={{ width: "90vw", maxHeight: "70vh", boxShadow: "5px 10px 8px rgba(0, 0, 0, 0.486)", overflowY: "scroll" }}>
                <CardContent>
                    <Typography gutterBottom variant="h5" component="div">
                    UNITA VIRTUAL CAMPUS GDPR
                    </Typography>
                    <Typography variant="body2" component="div" color="text.secondary">
                        <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;For the UNITA Federative Login and the proper functioning of  services offered by the UNITA VIRTUAL CAMPUS ie. #UVC, we need to process your personal data from your home institution, that we receive via EDUGAIN on our Service Provider. We take all reasonable care to ensure that your personal data is processed safely.</p>
                        <h3>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1. UNITA Federative Login</h3>
                        <p>When you login to the service via Federated Login (Shibboleth), the following personal data is processed:</p>
                        <ul>
                            <li>
                                <h4>Data received from user’s home organization:</h4>
                                <ul style={{listStyleType: "disc"}}>
                                    <li>SAML persistent identifier (attribute: eduPersonTargetedID) - unique ID to identify the user</li>
                                    <li>Alternative identifier (attribute: eduPersonPrincipalName)</li>
                                    <li>User’s name (attributes: displayName, sn, givenName)</li>
                                    <li>User’s email (attribute: mail)</li>
                                    <li>User’s affiliation within the home institution (attribute: eduPersonScopedAffiliation)</li>
                                </ul>
                            </li>
                            <li>
                                <h4>Other data gathered from the user:</h4>
                                <ul style={{listStyleType: "disc"}}>
                                    <li>In case the home organization does not provide some of the requested attributes, the user might be requested to provide the missing information in order to use certain functionality, or otherwise this functionality may not be available to the user.</li>
                                    <li>In case user makes use of personalization functionality (e.g. store personalized workspace settings)</li>
                                    <li>In case the user is contributor to the resources (work in a collaborative environment) all the changes are tracked</li>
                                </ul>
                            </li>
                            <br/>
                            <li><b>General network traffic data</b> (like IP address) is logged and is used in anonymized usage statistics. The tool for processing the analytics is Matomo.</li>
                        </ul>
                        <h3>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2. UNITA Services</h3>
                        <p>A unique user identifier (JWT Token) is needed to recognize the federated user over sessions, to be able to associate user-related information (customization, authorization settings, user-generated content).</p>
                        <p>The identification of the user as well as the affiliation information serves as a means to determine the authorization to access protected resources (which may be granted to certain groups, like members of an institute or partners in a project).</p>
                        <p>Attributes concerning user's name are used in communication with the user (via e-mail and on the website) and for marking user's contribution. If the requested attributes are obtained, they are used by default, the user still has the possibility to change the display name.</p>
                        <p>The user's affiliation can be used for managing access to resources (authorization), e.g. if access to a resource is granted to members of an institute. If no information about affiliation is available, user-based authorization is used.</p>
                        <h3>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3. UNITA Office Contact</h3>
                        <p>For more information about data security and the UNITA Service Provider, please contact our Data officer <a href="mailto:ionut.dragoi@e-uvt.ro">ionut.dragoi@e-uvt.ro</a> with cc to <a href="mailto:unita@e-uvt.ro">unita@e-uvt.ro</a></p>
                    </Typography>
                </CardContent>
            </Card>
        </CardContainer>
    );
};