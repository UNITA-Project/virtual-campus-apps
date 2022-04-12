/*
 * Copyright 2021 Red Hat, Inc. and/or its affiliates
 * and other contributors as indicated by the @author tags.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

package org.keycloak.models.map.storage.hotRod;

import org.jboss.logging.Logger;
import org.keycloak.Config;
import org.keycloak.common.Profile;
import org.keycloak.component.AmphibianProviderFactory;
import org.keycloak.models.ClientModel;
import org.keycloak.models.ClientScopeModel;
import org.keycloak.models.GroupModel;
import org.keycloak.models.KeycloakSession;
import org.keycloak.models.KeycloakSessionFactory;
import org.keycloak.models.RealmModel;
import org.keycloak.models.RoleModel;
import org.keycloak.models.UserLoginFailureModel;
import org.keycloak.models.UserModel;
import org.keycloak.models.map.authSession.MapAuthenticationSessionEntity;
import org.keycloak.models.map.authSession.MapRootAuthenticationSessionEntity;
import org.keycloak.models.map.clientscope.MapClientScopeEntity;
import org.keycloak.models.map.group.MapGroupEntity;
import org.keycloak.models.map.loginFailure.MapUserLoginFailureEntity;
import org.keycloak.models.map.realm.MapRealmEntity;
import org.keycloak.models.map.realm.entity.MapAuthenticationExecutionEntity;
import org.keycloak.models.map.realm.entity.MapAuthenticationFlowEntity;
import org.keycloak.models.map.realm.entity.MapAuthenticatorConfigEntity;
import org.keycloak.models.map.realm.entity.MapClientInitialAccessEntity;
import org.keycloak.models.map.realm.entity.MapComponentEntity;
import org.keycloak.models.map.realm.entity.MapIdentityProviderEntity;
import org.keycloak.models.map.realm.entity.MapIdentityProviderMapperEntity;
import org.keycloak.models.map.realm.entity.MapOTPPolicyEntity;
import org.keycloak.models.map.realm.entity.MapRequiredActionProviderEntity;
import org.keycloak.models.map.realm.entity.MapRequiredCredentialEntity;
import org.keycloak.models.map.realm.entity.MapWebAuthnPolicyEntity;
import org.keycloak.models.map.role.MapRoleEntity;
import org.keycloak.models.map.storage.hotRod.authSession.HotRodAuthenticationSessionEntityDelegate;
import org.keycloak.models.map.storage.hotRod.authSession.HotRodRootAuthenticationSessionEntity;
import org.keycloak.models.map.storage.hotRod.authSession.HotRodRootAuthenticationSessionEntityDelegate;
import org.keycloak.models.map.storage.hotRod.loginFailure.HotRodUserLoginFailureEntity;
import org.keycloak.models.map.storage.hotRod.loginFailure.HotRodUserLoginFailureEntityDelegate;
import org.keycloak.models.map.storage.hotRod.role.HotRodRoleEntity;
import org.keycloak.models.map.storage.hotRod.role.HotRodRoleEntityDelegate;
import org.keycloak.models.map.storage.hotRod.client.HotRodClientEntity;
import org.keycloak.models.map.storage.hotRod.client.HotRodClientEntityDelegate;
import org.keycloak.models.map.storage.hotRod.client.HotRodProtocolMapperEntityDelegate;
import org.keycloak.models.map.client.MapClientEntity;
import org.keycloak.models.map.client.MapProtocolMapperEntity;
import org.keycloak.models.map.common.DeepCloner;
import org.keycloak.models.map.storage.hotRod.clientscope.HotRodClientScopeEntity;
import org.keycloak.models.map.storage.hotRod.clientscope.HotRodClientScopeEntityDelegate;
import org.keycloak.models.map.storage.hotRod.common.HotRodEntityDescriptor;
import org.keycloak.models.map.storage.hotRod.connections.HotRodConnectionProvider;
import org.keycloak.models.map.storage.MapStorageProvider;
import org.keycloak.models.map.storage.MapStorageProviderFactory;
import org.keycloak.models.map.storage.hotRod.group.HotRodGroupEntity;
import org.keycloak.models.map.storage.hotRod.group.HotRodGroupEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.HotRodRealmEntity;
import org.keycloak.models.map.storage.hotRod.realm.HotRodRealmEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodAuthenticationExecutionEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodAuthenticationFlowEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodAuthenticatorConfigEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodClientInitialAccessEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodComponentEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodIdentityProviderEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodIdentityProviderMapperEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodOTPPolicyEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodRequiredActionProviderEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodRequiredCredentialEntityDelegate;
import org.keycloak.models.map.storage.hotRod.realm.entity.HotRodWebAuthnPolicyEntityDelegate;
import org.keycloak.models.map.storage.hotRod.user.HotRodUserConsentEntityDelegate;
import org.keycloak.models.map.storage.hotRod.user.HotRodUserCredentialEntityDelegate;
import org.keycloak.models.map.storage.hotRod.user.HotRodUserEntity;
import org.keycloak.models.map.storage.hotRod.user.HotRodUserEntityDelegate;
import org.keycloak.models.map.storage.hotRod.user.HotRodUserFederatedIdentityEntityDelegate;
import org.keycloak.models.map.user.MapUserConsentEntity;
import org.keycloak.models.map.user.MapUserCredentialEntity;
import org.keycloak.models.map.user.MapUserEntity;
import org.keycloak.models.map.user.MapUserFederatedIdentityEntity;
import org.keycloak.provider.EnvironmentDependentProviderFactory;
import org.keycloak.sessions.RootAuthenticationSessionModel;

import java.util.HashMap;
import java.util.Map;

public class HotRodMapStorageProviderFactory implements AmphibianProviderFactory<MapStorageProvider>, MapStorageProviderFactory, EnvironmentDependentProviderFactory {

    public static final String PROVIDER_ID = "hotrod";
    private static final Logger LOG = Logger.getLogger(HotRodMapStorageProviderFactory.class);

    private final static DeepCloner CLONER = new DeepCloner.Builder()
            .constructor(MapRootAuthenticationSessionEntity.class,  HotRodRootAuthenticationSessionEntityDelegate::new)
            .constructor(MapAuthenticationSessionEntity.class,      HotRodAuthenticationSessionEntityDelegate::new)
            .constructor(MapClientEntity.class,                     HotRodClientEntityDelegate::new)
            .constructor(MapProtocolMapperEntity.class,             HotRodProtocolMapperEntityDelegate::new)
            .constructor(MapClientScopeEntity.class,                HotRodClientScopeEntityDelegate::new)
            .constructor(MapGroupEntity.class,                      HotRodGroupEntityDelegate::new)
            .constructor(MapRoleEntity.class,                       HotRodRoleEntityDelegate::new)
            .constructor(MapUserEntity.class,                       HotRodUserEntityDelegate::new)
            .constructor(MapUserCredentialEntity.class,             HotRodUserCredentialEntityDelegate::new)
            .constructor(MapUserFederatedIdentityEntity.class,      HotRodUserFederatedIdentityEntityDelegate::new)
            .constructor(MapUserConsentEntity.class,                HotRodUserConsentEntityDelegate::new)
            .constructor(MapUserLoginFailureEntity.class,           HotRodUserLoginFailureEntityDelegate::new)

            .constructor(MapRealmEntity.class,                      HotRodRealmEntityDelegate::new)
            .constructor(MapAuthenticationExecutionEntity.class,    HotRodAuthenticationExecutionEntityDelegate::new)
            .constructor(MapAuthenticationFlowEntity.class,         HotRodAuthenticationFlowEntityDelegate::new)
            .constructor(MapAuthenticatorConfigEntity.class,        HotRodAuthenticatorConfigEntityDelegate::new)
            .constructor(MapClientInitialAccessEntity.class,        HotRodClientInitialAccessEntityDelegate::new)
            .constructor(MapComponentEntity.class,                  HotRodComponentEntityDelegate::new)
            .constructor(MapIdentityProviderEntity.class,           HotRodIdentityProviderEntityDelegate::new)
            .constructor(MapIdentityProviderMapperEntity.class,     HotRodIdentityProviderMapperEntityDelegate::new)
            .constructor(MapOTPPolicyEntity.class,                  HotRodOTPPolicyEntityDelegate::new)
            .constructor(MapRequiredActionProviderEntity.class,     HotRodRequiredActionProviderEntityDelegate::new)
            .constructor(MapRequiredCredentialEntity.class,         HotRodRequiredCredentialEntityDelegate::new)
            .constructor(MapWebAuthnPolicyEntity.class,             HotRodWebAuthnPolicyEntityDelegate::new)

            .build();

    public static final Map<Class<?>, HotRodEntityDescriptor<?, ?>> ENTITY_DESCRIPTOR_MAP = new HashMap<>();
    static {
        // Authentication sessions descriptor
        ENTITY_DESCRIPTOR_MAP.put(RootAuthenticationSessionModel.class,
                new HotRodEntityDescriptor<>(RootAuthenticationSessionModel.class,
                        HotRodRootAuthenticationSessionEntity.class,
                        HotRodRootAuthenticationSessionEntityDelegate::new));

        // Clients descriptor
        ENTITY_DESCRIPTOR_MAP.put(ClientModel.class,
                new HotRodEntityDescriptor<>(ClientModel.class,
                        HotRodClientEntity.class,
                        HotRodClientEntityDelegate::new));

        ENTITY_DESCRIPTOR_MAP.put(ClientScopeModel.class,
                new HotRodEntityDescriptor<>(ClientScopeModel.class,
                        HotRodClientScopeEntity.class,
                        HotRodClientScopeEntityDelegate::new));

        // Groups descriptor
        ENTITY_DESCRIPTOR_MAP.put(GroupModel.class,
                new HotRodEntityDescriptor<>(GroupModel.class,
                        HotRodGroupEntity.class,
                        HotRodGroupEntityDelegate::new));

        // Roles descriptor
        ENTITY_DESCRIPTOR_MAP.put(RoleModel.class,
                new HotRodEntityDescriptor<>(RoleModel.class,
                        HotRodRoleEntity.class,
                        HotRodRoleEntityDelegate::new));

        // Users descriptor
        ENTITY_DESCRIPTOR_MAP.put(UserModel.class,
                new HotRodEntityDescriptor<>(UserModel.class,
                        HotRodUserEntity.class,
                        HotRodUserEntityDelegate::new));

        // Login failure descriptor
        ENTITY_DESCRIPTOR_MAP.put(UserLoginFailureModel.class,
                new HotRodEntityDescriptor<>(UserLoginFailureModel.class,
                        HotRodUserLoginFailureEntity.class,
                        HotRodUserLoginFailureEntityDelegate::new));

        // Realm descriptor
        ENTITY_DESCRIPTOR_MAP.put(RealmModel.class,
                new HotRodEntityDescriptor<>(RealmModel.class,
                        HotRodRealmEntity.class,
                        HotRodRealmEntityDelegate::new));
    }

    @Override
    public MapStorageProvider create(KeycloakSession session) {
        HotRodConnectionProvider cacheProvider = session.getProvider(HotRodConnectionProvider.class);
        
        if (cacheProvider == null) {
            throw new IllegalStateException("Cannot find HotRodConnectionProvider interface implementation");
        }
        
        return new HotRodMapStorageProvider(this, cacheProvider, CLONER);
    }

    public HotRodEntityDescriptor<?, ?> getEntityDescriptor(Class<?> c) {
        return ENTITY_DESCRIPTOR_MAP.get(c);
    }

    @Override
    public void init(Config.Scope config) {

    }

    @Override
    public void postInit(KeycloakSessionFactory factory) {

    }

    @Override
    public String getId() {
        return PROVIDER_ID;
    }

    @Override
    public boolean isSupported() {
        return Profile.isFeatureEnabled(Profile.Feature.MAP_STORAGE);
    }

    @Override
    public String getHelpText() {
        return "HotRod map storage";
    }
}
