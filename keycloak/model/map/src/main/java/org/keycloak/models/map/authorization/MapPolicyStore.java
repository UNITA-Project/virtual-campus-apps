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

package org.keycloak.models.map.authorization;

import org.jboss.logging.Logger;
import org.keycloak.authorization.AuthorizationProvider;
import org.keycloak.authorization.model.Policy;
import org.keycloak.authorization.model.Policy.SearchableFields;
import org.keycloak.authorization.model.Resource;
import org.keycloak.authorization.model.ResourceServer;
import org.keycloak.authorization.model.Scope;
import org.keycloak.authorization.store.PolicyStore;
import org.keycloak.models.KeycloakSession;
import org.keycloak.models.ModelDuplicateException;
import org.keycloak.models.map.authorization.adapter.MapPolicyAdapter;
import org.keycloak.models.map.authorization.entity.MapPolicyEntity;
import org.keycloak.models.map.authorization.entity.MapPolicyEntityImpl;
import org.keycloak.models.map.storage.MapKeycloakTransaction;
import org.keycloak.models.map.storage.MapStorage;
import org.keycloak.models.map.storage.ModelCriteriaBuilder.Operator;
import org.keycloak.models.map.storage.criteria.DefaultModelCriteria;
import org.keycloak.representations.idm.authorization.AbstractPolicyRepresentation;

import java.util.Arrays;
import java.util.List;
import java.util.Map;
import java.util.Objects;
import java.util.function.Consumer;
import java.util.stream.Collectors;

import static org.keycloak.common.util.StackUtil.getShortStackTrace;
import static org.keycloak.models.map.storage.QueryParameters.withCriteria;
import static org.keycloak.models.map.storage.criteria.DefaultModelCriteria.criteria;

public class MapPolicyStore implements PolicyStore {

    private static final Logger LOG = Logger.getLogger(MapPolicyStore.class);
    private final AuthorizationProvider authorizationProvider;
    final MapKeycloakTransaction<MapPolicyEntity, Policy> tx;

    public MapPolicyStore(KeycloakSession session, MapStorage<MapPolicyEntity, Policy> policyStore, AuthorizationProvider provider) {
        this.authorizationProvider = provider;
        this.tx = policyStore.createTransaction(session);
        session.getTransactionManager().enlist(tx);
    }

    private Policy entityToAdapter(MapPolicyEntity origEntity) {
        if (origEntity == null) return null;
        // Clone entity before returning back, to avoid giving away a reference to the live object to the caller
        return new MapPolicyAdapter(origEntity, authorizationProvider.getStoreFactory());
    }

    private DefaultModelCriteria<Policy> forResourceServer(ResourceServer resourceServer) {
        DefaultModelCriteria<Policy> mcb = criteria();

        return resourceServer == null
                ? mcb
                : mcb.compare(SearchableFields.RESOURCE_SERVER_ID, Operator.EQ,
                resourceServer.getId());
    }

    @Override
    public Policy create(ResourceServer resourceServer, AbstractPolicyRepresentation representation) {
        LOG.tracef("create(%s, %s, %s)%s", representation.getId(), resourceServer.getId(), resourceServer, getShortStackTrace());

        // @UniqueConstraint(columnNames = {"NAME", "RESOURCE_SERVER_ID"})
        DefaultModelCriteria<Policy> mcb = forResourceServer(resourceServer)
                .compare(SearchableFields.NAME, Operator.EQ, representation.getName());

        if (tx.getCount(withCriteria(mcb)) > 0) {
            throw new ModelDuplicateException("Policy with name '" + representation.getName() + "' for " + resourceServer.getId() + " already exists");
        }

        String uid = representation.getId();
        MapPolicyEntity entity = new MapPolicyEntityImpl();
        entity.setId(uid);
        entity.setType(representation.getType());
        entity.setName(representation.getName());
        entity.setResourceServerId(resourceServer.getId());
        
        entity = tx.create(entity);

        return entityToAdapter(entity);
    }

    @Override
    public void delete(String id) {
        LOG.tracef("delete(%s)%s", id, getShortStackTrace());
        tx.delete(id);
    }

    @Override
    public Policy findById(ResourceServer resourceServer, String id) {
        LOG.tracef("findById(%s, %s)%s", id, resourceServer, getShortStackTrace());

        return tx.read(withCriteria(forResourceServer(resourceServer)
                .compare(SearchableFields.ID, Operator.EQ, id)))
                .findFirst()
                .map(this::entityToAdapter)
                .orElse(null);
    }

    @Override
    public Policy findByName(ResourceServer resourceServer, String name) {
        LOG.tracef("findByName(%s, %s)%s", name, resourceServer, getShortStackTrace());

        return tx.read(withCriteria(forResourceServer(resourceServer)
                .compare(SearchableFields.NAME, Operator.EQ, name)))
                .findFirst()
                .map(this::entityToAdapter)
                .orElse(null);
    }

    @Override
    public List<Policy> findByResourceServer(ResourceServer resourceServer) {
        LOG.tracef("findByResourceServer(%s)%s", resourceServer, getShortStackTrace());

        return tx.read(withCriteria(forResourceServer(resourceServer)))
                .map(this::entityToAdapter)
                .collect(Collectors.toList());
    }

    @Override
    public List<Policy> findByResourceServer(ResourceServer resourceServer, Map<Policy.FilterOption, String[]> attributes, Integer firstResult, Integer maxResults) {
        LOG.tracef("findByResourceServer(%s, %s, %d, %d)%s", attributes, resourceServer, firstResult, maxResults, getShortStackTrace());

        DefaultModelCriteria<Policy> mcb = forResourceServer(resourceServer).and(
                attributes.entrySet().stream()
                        .map(this::filterEntryToDefaultModelCriteria)
                        .filter(Objects::nonNull)
                        .toArray(DefaultModelCriteria[]::new)
        );

        if (!attributes.containsKey(Policy.FilterOption.OWNER) && !attributes.containsKey(Policy.FilterOption.ANY_OWNER)) {
            mcb = mcb.compare(SearchableFields.OWNER, Operator.NOT_EXISTS);
        }

        return tx.read(withCriteria(mcb).pagination(firstResult, maxResults, SearchableFields.NAME))
            .map(MapPolicyEntity::getId)
            // We need to go through cache
            .map(id -> authorizationProvider.getStoreFactory().getPolicyStore().findById(resourceServer, id))
            .collect(Collectors.toList());
    }

    private DefaultModelCriteria<Policy> filterEntryToDefaultModelCriteria(Map.Entry<Policy.FilterOption, String[]> entry) {
        Policy.FilterOption name = entry.getKey();
        String[] value = entry.getValue();

        DefaultModelCriteria<Policy> mcb = criteria();
        switch (name) {
            case ID:
            case SCOPE_ID:
            case RESOURCE_ID:
            case OWNER:
                return mcb.compare(name.getSearchableModelField(), Operator.IN, Arrays.asList(value));
            case PERMISSION: {
                mcb = mcb.compare(SearchableFields.TYPE, Operator.IN, Arrays.asList("resource", "scope", "uma"));
                
                if (!Boolean.parseBoolean(value[0])) {
                    mcb = DefaultModelCriteria.<Policy>criteria().not(mcb); // TODO: create NOT_IN operator
                }
                
                return mcb;
            }
            case ANY_OWNER:
                return null;
            case CONFIG:
                if (value.length != 2) {
                    throw new IllegalArgumentException("Config filter option requires value with two items: [config_name, expected_config_value]");
                }
                
                value[1] = "%" + value[1] + "%";
                return mcb.compare(SearchableFields.CONFIG, Operator.LIKE, (Object[]) value);
            case TYPE:
            case NAME:
                return mcb.compare(name.getSearchableModelField(), Operator.ILIKE, "%" + value[0] + "%");
            default:
                throw new IllegalArgumentException("Unsupported filter [" + name + "]");

        }
    }

    @Override
    public void findByResource(ResourceServer resourceServer, Resource resource, Consumer<Policy> consumer) {
        LOG.tracef("findByResource(%s, %s, %s)%s", resourceServer, resource, consumer, getShortStackTrace());

        tx.read(withCriteria(forResourceServer(resourceServer)
                .compare(SearchableFields.RESOURCE_ID, Operator.EQ, resource.getId())))
                .map(this::entityToAdapter)
                .forEach(consumer);
    }

    @Override
    public void findByResourceType(ResourceServer resourceServer, String type, Consumer<Policy> policyConsumer) {
        tx.read(withCriteria(forResourceServer(resourceServer)
                .compare(SearchableFields.CONFIG, Operator.LIKE, (Object[]) new String[]{"defaultResourceType", type})))
                .map(this::entityToAdapter)
                .forEach(policyConsumer);
    }

    @Override
    public List<Policy> findByScopes(ResourceServer resourceServer, List<Scope> scopes) {
        return tx.read(withCriteria(forResourceServer(resourceServer)
                .compare(SearchableFields.SCOPE_ID, Operator.IN, scopes.stream().map(Scope::getId))))
                .map(this::entityToAdapter)
                .collect(Collectors.toList());
    }

    @Override
    public void findByScopes(ResourceServer resourceServer, Resource resource, List<Scope> scopes, Consumer<Policy> consumer) {
        DefaultModelCriteria<Policy> mcb = forResourceServer(resourceServer)
                .compare(SearchableFields.TYPE, Operator.EQ, "scope")
                .compare(SearchableFields.SCOPE_ID, Operator.IN, scopes.stream().map(Scope::getId));

        if (resource != null) {
            mcb = mcb.compare(SearchableFields.RESOURCE_ID, Operator.EQ, resource.getId());
            //                @NamedQuery(name="findPolicyIdByNullResourceScope", query="PolicyEntity pe left join fetch pe.config c inner join pe.scopes s  where pe.resourceServer.id = :serverId and pe.type = 'scope' and pe.resources is empty and s.id in (:scopeIds) and not exists (select pec from pe.config pec where KEY(pec) = 'defaultResourceType')"),
        } else {
            mcb = mcb.compare(SearchableFields.RESOURCE_ID, Operator.NOT_EXISTS)
                    .compare(SearchableFields.CONFIG, Operator.NOT_EXISTS, (Object[]) new String[] {"defaultResourceType"});
        }

        tx.read(withCriteria(mcb)).map(this::entityToAdapter).forEach(consumer);
    }

    @Override
    public List<Policy> findByType(ResourceServer resourceServer, String type) {
        return tx.read(withCriteria(forResourceServer(resourceServer)
                .compare(SearchableFields.TYPE, Operator.EQ, type)))
                .map(this::entityToAdapter)
                .collect(Collectors.toList());
    }

    @Override
    public List<Policy> findDependentPolicies(ResourceServer resourceServer, String id) {
        return tx.read(withCriteria(forResourceServer(resourceServer)
                .compare(SearchableFields.ASSOCIATED_POLICY_ID, Operator.EQ, id)))
                    .map(this::entityToAdapter)
                    .collect(Collectors.toList());
    }
}
