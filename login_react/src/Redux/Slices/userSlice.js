import { createAsyncThunk, createSlice } from '@reduxjs/toolkit'
import axios from 'axios';
import qs from 'qs';

function setCookie(name,value,hours) {
    var expires = "";
    if (hours) {
        var date = new Date();
        date.setTime(date.getTime() + (hours*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; domain=.uvt.ro; path=/";
}

function deleteCookie( name, path, domain ) {
    document.cookie = name + "=" +
        ((path) ? ";path="+path:"")+
        ((domain)?";domain="+domain:"") +
        ";expires=Thu, 01 Jan 1970 00:00:01 GMT";
}

export const fetchUserData = createAsyncThunk(
    "userSlice/fetchUserData",
    async (credentials, thunkAPI) => {

        const getTokenAPI = "https://keycloak.unitassotest.uvt.ro/auth/realms/POC/protocol/openid-connect/token";
        const getUserListAPI = "https://keycloak.unitassotest.uvt.ro/auth/admin/realms/POC/users";
        let token;
        let data = qs.stringify({
            'client_id': 'admin-cli',
            'username': 'admin-poc',
            'password': 'Parol@grea.123',
            'grant_type': 'password' 
        });
        let config = {
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded'
            },
        };

        return await axios.post(getTokenAPI, data, config)
            .then(async (result) => {
                token = result.data;
                const config = {
                    headers: {
                        'Authorization': "Bearer " + token.access_token
                    }
                }
                return await axios.get(getUserListAPI, config)
                    .then(async (result) => {

                        let user = result.data.find((item) => item.username === credentials.username);

                        if (user === undefined) {
                            return {wrongCred: "username"};
                        }
                        
                        let tokendData = qs.stringify({
                            'client_id': 'unitassotest',
                            'grant_type': 'urn:ietf:params:oauth:grant-type:token-exchange',
                            'subject_token': token.access_token,
                            'requested_token_type': 'urn:ietf:params:oauth:token-type:refresh_token',
                            'audience': 'unitassotest',
                            'client_secret': 'ab3d41be-be7d-43ff-9b48-451ceccd404f',
                            'requested_subject': user.id
                        });
                
                        let tokenConfig = {
                            headers: { 
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                        };

                        if (user.attributes.password[0] !== credentials.password) {
                            return {wrongCred: "password"};
                        }

                        return await axios.post(getTokenAPI, tokendData, tokenConfig)
                            .then((result) => {
                                setCookie('USER_TOKEN', result.data.access_token, 1);

                                return user;
                            });
                    })
                    .catch((err) => {
                        console.log("catch2 ", err);
                        return err;
                    });
            })
            .catch((err) => {
                console.log("catch1 ", err);
                return err;
            })
    }
)

export const getUserFromToken = createAsyncThunk(
    "userSlice/getUserFromToken",
    async (token, thunkAPI) => {
        const getDataAPI = "https://keycloak.unitassotest.uvt.ro/auth/realms/POC/protocol/openid-connect/userinfo";

        let config = {
            crossDomain: true,
            headers: { 
                'Authorization': "Bearer " + token,    
                'Content-Type': 'application/x-www-form-urlencoded'
            },
        };

        return await axios.get(getDataAPI, config)
            .then((result) => {
                return result.data;
            })
            .catch((err) => {
                console.log("catch87 ", err);
                return err;
            });
    }
)

const userSlice = createSlice({
    name: 'user',
    initialState: {userData: null},
    reducers: {
        logOut: (state, action) => {
            state.userData = null;
            deleteCookie( "USER_TOKEN", "/", ".uvt.ro" );
        }
    },
    extraReducers: (builder) => {
        builder.addCase(fetchUserData.fulfilled, (state, action) => {
            state.userData = action.payload;
        });
        builder.addCase(getUserFromToken.fulfilled, (state, action) => {
            let newUserData = action.payload;
            newUserData.firstName = newUserData.given_name;
            delete newUserData.given_name;
            newUserData.lastName = newUserData.family_name;
            delete newUserData.family_name;

            state.userData = newUserData;
        });
    }
})

export const { logOut } = userSlice.actions;

export default userSlice.reducer;