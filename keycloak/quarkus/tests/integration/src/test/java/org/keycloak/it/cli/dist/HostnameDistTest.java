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

package org.keycloak.it.cli.dist;

import static io.restassured.RestAssured.when;

import org.junit.Assert;
import org.junit.jupiter.api.BeforeAll;
import org.junit.jupiter.api.Test;
import org.keycloak.it.cli.dist.util.CopyTLSKeystore;
import org.keycloak.it.junit5.extension.BeforeStartDistribution;
import org.keycloak.it.junit5.extension.DistributionTest;
import org.keycloak.it.junit5.extension.RawDistOnly;
import org.keycloak.protocol.oidc.representations.OIDCConfigurationRepresentation;

import io.quarkus.test.junit.main.Launch;
import io.restassured.RestAssured;

@DistributionTest(keepAlive = true, reInstall = DistributionTest.ReInstall.BEFORE_TEST)
@BeforeStartDistribution(CopyTLSKeystore.class)
@RawDistOnly(reason = "Containers are immutable")
public class HostnameDistTest {

    @BeforeAll
    public static void onBeforeAll() {
        RestAssured.useRelaxedHTTPSValidation();
    }

    @Test
    @Launch({ "start-dev", "--hostname=mykeycloak.127.0.0.1.nip.io" })
    public void testSchemeAndPortFromRequestWhenNoProxySet() {
        assertFrontEndUrl("http://mykeycloak.127.0.0.1.nip.io:8080", "http://mykeycloak.127.0.0.1.nip.io:8080/");
        assertFrontEndUrl("http://localhost:8080", "http://mykeycloak.127.0.0.1.nip.io:8080/");
        assertFrontEndUrl("https://localhost:8443", "https://mykeycloak.127.0.0.1.nip.io:8443/");
    }

    @Test
    @Launch({ "start-dev", "--hostname=mykeycloak.127.0.0.1.nip.io", "--hostname-strict-https=true" })
    public void testForceHttpsSchemeAndPortWhenStrictHttpsEnabled() {
        assertFrontEndUrl("http://mykeycloak.127.0.0.1.nip.io:8080", "https://mykeycloak.127.0.0.1.nip.io:8443/");
        assertFrontEndUrl("http://localhost:8080", "https://mykeycloak.127.0.0.1.nip.io:8443/");
    }

    @Test
    @Launch({ "start-dev", "--hostname=mykeycloak.127.0.0.1.nip.io", "--hostname-port=8443" })
    public void testForceHostnamePortWhenNoProxyIsSet() {
        assertFrontEndUrl("http://mykeycloak.127.0.0.1.nip.io:8080", "http://mykeycloak.127.0.0.1.nip.io:8443/");
        assertFrontEndUrl("https://mykeycloak.127.0.0.1.nip.io:8443", "https://mykeycloak.127.0.0.1.nip.io:8443/");
    }

    @Test
    @Launch({ "start-dev", "--hostname=mykeycloak.127.0.0.1.nip.io", "--proxy=edge" })
    public void testUseDefaultPortsWhenProxyIsSet() {
        assertFrontEndUrl("http://mykeycloak.127.0.0.1.nip.io:8080", "http://mykeycloak.127.0.0.1.nip.io/");
        assertFrontEndUrl("https://mykeycloak.127.0.0.1.nip.io:8443", "https://mykeycloak.127.0.0.1.nip.io/");
    }

    @Test
    @Launch({ "start-dev", "--hostname=mykeycloak.127.0.0.1.nip.io", "--proxy=edge", "--hostname-strict-https=true" })
    public void testUseDefaultPortsAndHttpsSchemeWhenProxyIsSetAndStrictHttpsEnabled() {
        assertFrontEndUrl("http://mykeycloak.127.0.0.1.nip.io:8080", "https://mykeycloak.127.0.0.1.nip.io/");
    }

    @Test
    @Launch({ "start-dev", "--hostname=mykeycloak.127.0.0.1.nip.io" })
    public void testBackEndUrlFromRequest() {
        assertBackEndUrl("http://localhost:8080", "http://localhost:8080/");
    }

    @Test
    @Launch({ "start-dev", "--hostname=mykeycloak.127.0.0.1.nip.io", "--hostname-strict-backchannel=true" })
    public void testBackEndUrlSameAsFrontEndUrl() {
        assertBackEndUrl("http://localhost:8080", "http://mykeycloak.127.0.0.1.nip.io:8080/");
    }

    @Test
    @Launch({ "start-dev", "--hostname=mykeycloak.127.0.0.1.nip.io", "--hostname-path=/auth", "--hostname-strict=true", "--hostname-strict-backchannel=true" })
    public void testSetHostnamePath() {
        assertFrontEndUrl("http://localhost:8080", "http://mykeycloak.127.0.0.1.nip.io:8080/auth/");
        assertBackEndUrl("http://localhost:8080", "http://mykeycloak.127.0.0.1.nip.io:8080/auth/");
    }

    @Test
    @Launch({ "start-dev", "--hostname=mykeycloak.127.0.0.1.nip.io", "--https-port=8543", "--hostname-strict-https=true" })
    public void testDefaultTlsPortChangeWhenHttpPortSet() {
        assertFrontEndUrl("http://mykeycloak.127.0.0.1.nip.io:8080", "https://mykeycloak.127.0.0.1.nip.io:8543/");
    }

    @Test
    @Launch({ "start-dev", "--hostname=mykeycloak.127.0.0.1.nip.io", "--hostname-strict-https=true", "--hostname-port=8543" })
    public void testWelcomePageAdminUrl() {
        Assert.assertTrue(when().get("http://mykeycloak.127.0.0.1.nip.io:8080").asString().contains("http://mykeycloak.127.0.0.1.nip.io:8080/admin/"));
        Assert.assertTrue(when().get("https://mykeycloak.127.0.0.1.nip.io:8443").asString().contains("https://mykeycloak.127.0.0.1.nip.io:8443/admin/"));
        Assert.assertTrue(when().get("http://localhost:8080").asString().contains("http://localhost:8080/admin/"));
        Assert.assertTrue(when().get("https://localhost:8443").asString().contains("https://localhost:8443/admin/"));
    }

    private OIDCConfigurationRepresentation getServerMetadata(String baseUrl) {
        return when().get(baseUrl + "/realms/master/.well-known/openid-configuration").as(OIDCConfigurationRepresentation.class);
    }

    private void assertFrontEndUrl(String requestBaseUrl, String expectedBaseUrl) {
        Assert.assertEquals(expectedBaseUrl + "realms/master/protocol/openid-connect/auth", getServerMetadata(requestBaseUrl)
                .getAuthorizationEndpoint());
    }

    private void assertBackEndUrl(String requestBaseUrl, String expectedBaseUrl) {
        Assert.assertEquals(expectedBaseUrl + "realms/master/protocol/openid-connect/token", getServerMetadata(requestBaseUrl)
                .getTokenEndpoint());
    }
}