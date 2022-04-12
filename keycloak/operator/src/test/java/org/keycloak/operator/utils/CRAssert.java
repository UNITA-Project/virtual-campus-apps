/*
 * Copyright 2022 Red Hat, Inc. and/or its affiliates
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

package org.keycloak.operator.utils;

import io.quarkus.logging.Log;
import org.keycloak.operator.v2alpha1.crds.Keycloak;
import org.keycloak.operator.v2alpha1.crds.KeycloakRealmImport;

import static org.assertj.core.api.Assertions.assertThat;

/**
 * @author Vaclav Muzikar <vmuzikar@redhat.com>
 */
public final class CRAssert {

    public static void assertKeycloakStatusCondition(Keycloak kc, String condition, boolean status) {
        assertKeycloakStatusCondition(kc, condition, status, null);
    }
    public static void assertKeycloakStatusCondition(Keycloak kc, String condition, boolean status, String containedMessage) {
        Log.infof("Asserting CR: %s, condition: %s, status: %s, message: %s", kc.getMetadata().getName(), condition, status, containedMessage);
        assertThat(kc.getStatus().getConditions().stream()
                .anyMatch(c ->
                        c.getType().equals(condition) &&
                        c.getStatus() == status &&
                        (containedMessage == null || c.getMessage().contains(containedMessage)))
                    ).isTrue();
    }

    public static void assertKeycloakRealmImportStatusCondition(KeycloakRealmImport kri, String condition, boolean status) {
        assertThat(kri.getStatus().getConditions().stream()
                .anyMatch(c -> c.getType().equals(condition) && c.getStatus() == status)).isTrue();
    }
}
