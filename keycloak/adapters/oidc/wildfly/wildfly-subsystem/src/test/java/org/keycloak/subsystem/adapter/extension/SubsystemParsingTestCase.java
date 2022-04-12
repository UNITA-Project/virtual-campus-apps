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
package org.keycloak.subsystem.adapter.extension;

import org.hamcrest.CoreMatchers;
import org.jboss.as.controller.PathAddress;
import org.jboss.as.controller.PathElement;
import org.jboss.as.controller.descriptions.ModelDescriptionConstants;
import org.jboss.as.subsystem.test.AbstractSubsystemBaseTest;
import org.jboss.as.subsystem.test.KernelServices;
import org.jboss.dmr.ModelNode;
import org.junit.Assert;
import org.junit.Test;
import org.keycloak.adapters.KeycloakDeploymentBuilder;
import org.keycloak.representations.adapters.config.AdapterConfig;

import java.io.ByteArrayInputStream;
import java.io.IOException;
import java.util.Map;

import static org.hamcrest.MatcherAssert.assertThat;


/**
 * Tests all management expects for subsystem, parsing, marshaling, model definition and other
 * Here is an example that allows you a fine grained controller over what is tested and how. So it can give you ideas what can be done and tested.
 * If you have no need for advanced testing of subsystem you look at {@link SubsystemBaseParsingTestCase} that testes same stuff but most of the code
 * is hidden inside of test harness
 *
 * @author <a href="kabir.khan@jboss.com">Kabir Khan</a>
 * @author Tomaz Cerar
 * @author <a href="marko.strukelj@gmail.com">Marko Strukelj</a>
 */
public class SubsystemParsingTestCase extends AbstractSubsystemBaseTest {

    public SubsystemParsingTestCase() {
        super(KeycloakExtension.SUBSYSTEM_NAME, new KeycloakExtension());
    }

    @Test
    public void testJson() throws Exception {
        ModelNode node = new ModelNode();
        node.get("realm").set("demo");
        node.get("resource").set("customer-portal");
        node.get("realm-public-key").set("MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCrVrCuTtArbgaZzL1hvh0xtL5mc7o0NqPVnYXkLvgcwiC3BjLGw1tGEGoJaXDuSaRllobm53JBhjx33UNv+5z/UMG4kytBWxheNVKnL6GgqlNabMaFfPLPCF8kAgKnsi79NMo+n6KnSY8YeUmec/p2vjO2NjsSAVcWEQMVhJ31LwIDAQAB");
        node.get("auth-url").set("http://localhost:8080/auth-server/rest/realms/demo/protocol/openid-connect/login");
        node.get("code-url").set("http://localhost:8080/auth-server/rest/realms/demo/protocol/openid-connect/access/codes");
        node.get("ssl-required").set("external");
        node.get("confidential-port").set(443);
        node.get("expose-token").set(true);

        ModelNode jwtCredential = new ModelNode();
        jwtCredential.get("client-keystore-file").set("/tmp/keystore.jks");
        jwtCredential.get("client-keystore-password").set("changeit");
        ModelNode credential = new ModelNode();
        credential.get("jwt").set(jwtCredential);
        node.get("credentials").set(credential);

        System.out.println("json=" + node.toJSONString(false));
    }

    @Test
    public void testJsonFromSignedJWTCredentials() {
        KeycloakAdapterConfigService service = KeycloakAdapterConfigService.getInstance();

        PathAddress addr = PathAddress.pathAddress(PathElement.pathElement("subsystem", "keycloak"), PathElement.pathElement("secure-deployment", "foo"));
        ModelNode deploymentOp = new ModelNode();
        deploymentOp.get(ModelDescriptionConstants.OP_ADDR).set(addr.toModelNode());
        ModelNode deployment = new ModelNode();
        deployment.get("realm").set("demo");
        deployment.get("resource").set("customer-portal");
        service.addSecureDeployment(deploymentOp, deployment, false);

        addCredential(addr, service, "secret", "secret1");
        addCredential(addr, service, "jwt.client-keystore-file", "/tmp/foo.jks");
        addCredential(addr, service, "jwt.token-timeout", "10");
    }

    private void addCredential(PathAddress parent, KeycloakAdapterConfigService service, String key, String value) {
        PathAddress credAddr = PathAddress.pathAddress(parent, PathElement.pathElement("credential", key));
        ModelNode credOp = new ModelNode();
        credOp.get(ModelDescriptionConstants.OP_ADDR).set(credAddr.toModelNode());
        ModelNode credential = new ModelNode();
        credential.get("value").set(value);
        service.addCredential(credOp, credential);
    }

    @Override
    protected String getSubsystemXml() throws IOException {
        return readResource("keycloak-1.2.xml");
    }

    @Override
    protected String getSubsystemXsdPath() throws Exception {
        return "schema/wildfly-keycloak_1_2.xsd";
    }

    @Override
    protected String[] getSubsystemTemplatePaths() throws IOException {
        return new String[]{
                "/subsystem-templates/keycloak-adapter.xml"
        };
    }

    /**
     * Checks if the subsystem is still capable of reading a configuration that uses version 1.1 of the schema.
     *
     * @throws Exception if an error occurs while running the test.
     */
    @Test
    public void testSubsystem1_1() throws Exception {
        KernelServices servicesA = super.createKernelServicesBuilder(createAdditionalInitialization())
                .setSubsystemXml(readResource("keycloak-1.1.xml")).build();
        Assert.assertTrue("Subsystem boot failed!", servicesA.isSuccessfulBoot());
        ModelNode modelA = servicesA.readWholeModel();
        super.validateModel(modelA);
    }

    /**
     * Tests a subsystem configuration that contains a {@code redirect-rewrite-rule}, checking that the resulting JSON
     * can be properly used to create an {@link AdapterConfig}.
     *
     * Added as part of the fix for {@code KEYCLOAK-18302}.
     */
    @Test
    public void testJsonFromRedirectRewriteRuleConfiguration() {
        KeycloakAdapterConfigService service = KeycloakAdapterConfigService.getInstance();

        // add a secure deployment with a redirect-rewrite-rule
        PathAddress addr = PathAddress.pathAddress(PathElement.pathElement("subsystem", "keycloak"), PathElement.pathElement("secure-deployment", "foo"));
        ModelNode deploymentOp = new ModelNode();
        deploymentOp.get(ModelDescriptionConstants.OP_ADDR).set(addr.toModelNode());
        ModelNode deployment = new ModelNode();
        deployment.get("realm").set("demo");
        deployment.get("resource").set("customer-portal");
        service.addSecureDeployment(deploymentOp, deployment, false);
        this.addRedirectRewriteRule(addr, service, "^/wsmaster/api/(.*)$", "api/$1");

        // get the subsystem config as JSON
        String jsonConfig = service.getJSON("foo");

        // attempt to create an adapter config instance from the subsystem JSON config
        AdapterConfig config = KeycloakDeploymentBuilder.loadAdapterConfig(new ByteArrayInputStream(jsonConfig.getBytes()));
        Assert.assertNotNull(config);

        // assert that the config has the configured rule
        Map<String, String> redirectRewriteRules = config.getRedirectRewriteRules();
        Assert.assertNotNull(redirectRewriteRules);
        Map.Entry<String, String> entry = redirectRewriteRules.entrySet().iterator().next();
        Assert.assertEquals("^/wsmaster/api/(.*)$", entry.getKey());
        Assert.assertEquals("api/$1", entry.getValue());
    }

    @Test
    public void testJsonHttpClientAttributes() {
        KeycloakAdapterConfigService service = KeycloakAdapterConfigService.getInstance();

        // add a secure deployment
        PathAddress addr = PathAddress.pathAddress(PathElement.pathElement("subsystem", "keycloak"), PathElement.pathElement("secure-deployment", "foo"));
        ModelNode deploymentOp = new ModelNode();
        deploymentOp.get(ModelDescriptionConstants.OP_ADDR).set(addr.toModelNode());

        ModelNode deployment = new ModelNode();
        deployment.get("realm").set("demo");
        deployment.get("resource").set("customer-portal");

        deployment.get(SharedAttributeDefinitons.SOCKET_TIMEOUT.getName()).set(3000L);
        deployment.get(SharedAttributeDefinitons.CONNECTION_TIMEOUT.getName()).set(5000L);
        deployment.get(SharedAttributeDefinitons.CONNECTION_TTL.getName()).set(1000L);

        service.addSecureDeployment(deploymentOp, deployment, false);

        // get the subsystem config as JSON
        String jsonConfig = service.getJSON("foo");

        // attempt to create an adapter config instance from the subsystem JSON config
        AdapterConfig config = KeycloakDeploymentBuilder.loadAdapterConfig(new ByteArrayInputStream(jsonConfig.getBytes()));
        assertThat(config, CoreMatchers.notNullValue());

        assertThat(config.getSocketTimeout(), CoreMatchers.notNullValue());
        assertThat(config.getSocketTimeout(), CoreMatchers.is(3000L));

        assertThat(config.getConnectionTimeout(), CoreMatchers.notNullValue());
        assertThat(config.getConnectionTimeout(), CoreMatchers.is(5000L));

        assertThat(config.getConnectionTTL(), CoreMatchers.notNullValue());
        assertThat(config.getConnectionTTL(), CoreMatchers.is(1000L));
    }

    private void addRedirectRewriteRule(PathAddress parent, KeycloakAdapterConfigService service, String key, String value) {
        PathAddress redirectRewriteAddr = PathAddress.pathAddress(parent, PathElement.pathElement("redirect-rewrite-rule", key));
        ModelNode redirectRewriteOp = new ModelNode();
        redirectRewriteOp.get(ModelDescriptionConstants.OP_ADDR).set(redirectRewriteAddr.toModelNode());
        ModelNode rule = new ModelNode();
        rule.get("value").set(value);
        service.addRedirectRewriteRule(redirectRewriteOp, rule);
    }
}
