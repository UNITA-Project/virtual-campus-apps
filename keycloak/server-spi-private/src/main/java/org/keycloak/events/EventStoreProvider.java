/*
 * Copyright 2016 Red Hat, Inc. and/or its affiliates
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

package org.keycloak.events;

import org.keycloak.events.admin.AdminEventQuery;

/**
 * @author <a href="mailto:sthorger@redhat.com">Stian Thorgersen</a>
 */
public interface EventStoreProvider extends EventListenerProvider {

    EventQuery createQuery();

    AdminEventQuery createAdminQuery();

    void clear();

    void clear(String realmId);

    void clear(String realmId, long olderThan);

    /**
     * Clear all expired events in all realms
     */
    void clearExpiredEvents();

    void clearAdmin();

    void clearAdmin(String realmId);

    void clearAdmin(String realmId, long olderThan);

}
