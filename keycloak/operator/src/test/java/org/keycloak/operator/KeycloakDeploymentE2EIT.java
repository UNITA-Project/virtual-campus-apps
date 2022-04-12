package org.keycloak.operator;

import io.fabric8.kubernetes.api.model.EnvVarBuilder;
import io.fabric8.kubernetes.api.model.apps.DeploymentSpecBuilder;
import io.quarkus.logging.Log;
import io.quarkus.test.junit.QuarkusTest;
import org.awaitility.Awaitility;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.condition.EnabledIfSystemProperty;
import org.keycloak.operator.utils.K8sUtils;
import org.keycloak.operator.v2alpha1.KeycloakAdminSecret;
import org.keycloak.operator.v2alpha1.KeycloakDeployment;
import org.keycloak.operator.v2alpha1.KeycloakService;
import org.keycloak.operator.v2alpha1.crds.Keycloak;
import org.keycloak.operator.v2alpha1.crds.ValueOrSecret;

import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.util.Base64;
import java.util.List;
import java.util.Map;
import java.util.concurrent.atomic.AtomicReference;

import static org.assertj.core.api.Assertions.assertThat;
import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNotEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;
import static org.keycloak.operator.utils.K8sUtils.deployKeycloak;
import static org.keycloak.operator.utils.K8sUtils.getDefaultKeycloakDeployment;
import static org.keycloak.operator.utils.K8sUtils.waitForKeycloakToBeReady;

@QuarkusTest
public class KeycloakDeploymentE2EIT extends ClusterOperatorTest {
    @Test
    public void testBasicKeycloakDeploymentAndDeletion() {
        try {
            // CR
            Log.info("Creating new Keycloak CR example");
            var kc = getDefaultKeycloakDeployment();
            var deploymentName = kc.getMetadata().getName();
            deployKeycloak(k8sclient, kc, true);

            // Check Operator has deployed Keycloak
            Log.info("Checking Operator has deployed Keycloak deployment");
            assertThat(k8sclient.apps().deployments().inNamespace(namespace).withName(deploymentName).get()).isNotNull();

            // Check Keycloak has correct replicas
            Log.info("Checking Keycloak pod has ready replicas == 1");
            assertThat(k8sclient.apps().deployments().inNamespace(namespace).withName(deploymentName).get().getStatus().getReadyReplicas()).isEqualTo(1);

            // Delete CR
            Log.info("Deleting Keycloak CR and watching cleanup");
            k8sclient.resources(Keycloak.class).delete(kc);
            Awaitility.await()
                    .untilAsserted(() -> assertThat(k8sclient.apps().deployments().inNamespace(namespace).withName(deploymentName).get()).isNull());
        } catch (Exception e) {
            savePodLogs();
            throw e;
        }
    }

    @Test
    public void testCRFields() {
        try {
            var kc = getDefaultKeycloakDeployment();
            var deploymentName = kc.getMetadata().getName();
            deployKeycloak(k8sclient, kc, true);

            final var dbConf = new ValueOrSecret("db-password", "Ay Caramba!");

            kc.getSpec().setImage("quay.io/keycloak/non-existing-keycloak");
            kc.getSpec().getServerConfiguration().remove(dbConf);
            kc.getSpec().getServerConfiguration().add(dbConf);
            deployKeycloak(k8sclient, kc, false);

            Awaitility.await()
                    .during(Duration.ofSeconds(15)) // check if the Deployment is stable
                    .untilAsserted(() -> {
                        var c = k8sclient.apps().deployments().inNamespace(namespace).withName(deploymentName).get()
                                .getSpec().getTemplate().getSpec().getContainers().get(0);
                        assertThat(c.getImage()).isEqualTo("quay.io/keycloak/non-existing-keycloak");
                        assertThat(c.getEnv().stream()
                                .anyMatch(e -> e.getName().equals(KeycloakDeployment.getEnvVarName(dbConf.getName()))
                                        && e.getValue().equals(dbConf.getValue())))
                                .isTrue();
                    });

        } catch (Exception e) {
            savePodLogs();
            throw e;
        }
    }

    @Test
    public void testConfigInCRTakesPrecedence() {
        try {
            var kc = getDefaultKeycloakDeployment();
            var health = new ValueOrSecret("health-enabled", "false");
            var e = new EnvVarBuilder()
                    .withName(KeycloakDeployment.getEnvVarName(health.getName()))
                    .withValue(health.getValue())
                    .build();
            kc.getSpec().getServerConfiguration().add(health);
            deployKeycloak(k8sclient, kc, false);

            assertThat(Constants.DEFAULT_DIST_CONFIG.get(health.getName())).isEqualTo("true"); // just a sanity check default values did not change

            Awaitility.await()
                    .ignoreExceptions()
                    .untilAsserted(() -> {
                        Log.info("Asserting default value was overwritten by CR value");
                        var c = k8sclient.apps().deployments().inNamespace(namespace).withName(kc.getMetadata().getName()).get()
                                .getSpec().getTemplate().getSpec().getContainers().get(0);

                        assertThat(c.getEnv()).contains(e);
                    });
        } catch (Exception e) {
            savePodLogs();
            throw e;
        }
    }

    @Test
    public void testDeploymentDurability() {
        try {
            var kc = getDefaultKeycloakDeployment();
            var deploymentName = kc.getMetadata().getName();
            deployKeycloak(k8sclient, kc, true);

            Log.info("Trying to delete deployment");
            assertThat(k8sclient.apps().deployments().withName(deploymentName).delete()).isTrue();
            Awaitility.await()
                    .untilAsserted(() -> assertThat(k8sclient.apps().deployments().withName(deploymentName).get()).isNotNull());

            waitForKeycloakToBeReady(k8sclient, kc); // wait for reconciler to calm down to avoid race condititon

            Log.info("Trying to modify deployment");

            var deployment = k8sclient.apps().deployments().withName(deploymentName).get();
            var labels = Map.of("address", "EvergreenTerrace742");
            var flandersEnvVar = new EnvVarBuilder().withName("NEIGHBOR").withValue("Stupid Flanders!").build();
            var origSpecs = new DeploymentSpecBuilder(deployment.getSpec()).build(); // deep copy

            deployment.getMetadata().getLabels().putAll(labels);
            deployment.getSpec().getTemplate().getSpec().getContainers().get(0).setEnv(List.of(flandersEnvVar));
            k8sclient.apps().deployments().createOrReplace(deployment);

            Awaitility.await()
                    .untilAsserted(() -> {
                        var d = k8sclient.apps().deployments().withName(deploymentName).get();
                        assertThat(d.getMetadata().getLabels().entrySet().containsAll(labels.entrySet())).isTrue(); // additional labels should not be overwritten
                        assertThat(d.getSpec()).isEqualTo(origSpecs); // specs should be reconciled back to original values
                    });
        } catch (Exception e) {
            savePodLogs();
            throw e;
        }
    }

    @Test
    public void testTlsUsesCorrectSecret() {
        try {
            var kc = getDefaultKeycloakDeployment();
            deployKeycloak(k8sclient, kc, true);

            var service = new KeycloakService(k8sclient, kc);
            Awaitility.await()
                    .ignoreExceptions()
                    .untilAsserted(() -> {
                        String url = "https://" + service.getName() + "." + namespace + ":" + Constants.KEYCLOAK_HTTPS_PORT;
                        Log.info("Checking url: " + url);

                        var curlOutput = K8sUtils.inClusterCurl(k8sclient, namespace, "--insecure", "-s", "-v", url);
                        Log.info("Curl Output: " + curlOutput);

                        assertTrue(curlOutput.contains("issuer: O=mkcert development CA; OU=aperuffo@aperuffo-mac (Andrea Peruffo); CN=mkcert aperuffo@aperuffo-mac (Andrea Peruffo)"));
                    });
        } catch (Exception e) {
            savePodLogs();
            throw e;
        }
    }

    @Test
    public void testTlsDisabled() {
        try {
            var kc = getDefaultKeycloakDeployment();
            kc.getSpec().setTlsSecret(Constants.INSECURE_DISABLE);
            deployKeycloak(k8sclient, kc, true);

            var service = new KeycloakService(k8sclient, kc);
            Awaitility.await()
                    .ignoreExceptions()
                    .untilAsserted(() -> {
                        String url = "http://" + service.getName() + "." + namespace + ":" + Constants.KEYCLOAK_HTTP_PORT;
                        Log.info("Checking url: " + url);

                        var curlOutput = K8sUtils.inClusterCurl(k8sclient, namespace, url);
                        Log.info("Curl Output: " + curlOutput);

                        assertEquals("200", curlOutput);
                    });
        } catch (Exception e) {
            savePodLogs();
            throw e;
        }
    }

    @Test
    public void testHostnameStrict() {
        try {
            var kc = getDefaultKeycloakDeployment();
            deployKeycloak(k8sclient, kc, true);

            var service = new KeycloakService(k8sclient, kc);
            Awaitility.await()
                    .ignoreExceptions()
                    .untilAsserted(() -> {
                        String url = "https://" + service.getName() + "." + namespace + ":" + Constants.KEYCLOAK_HTTPS_PORT + "/admin/master/console/";
                        Log.info("Checking url: " + url);

                        var curlOutput = K8sUtils.inClusterCurl(k8sclient, namespace, "-s", "--insecure", "-H", "Host: foo.bar", url);
                        Log.info("Curl Output: " + curlOutput);

                        assertTrue(curlOutput.contains("var authServerUrl = 'https://example.com:8443';"));
                    });
        } catch (Exception e) {
            savePodLogs();
            throw e;
        }
    }

    @Test
    public void testHostnameStrictDisabled() {
        try {
            var kc = getDefaultKeycloakDeployment();
            kc.getSpec().setHostname(Constants.INSECURE_DISABLE);
            deployKeycloak(k8sclient, kc, true);

            var service = new KeycloakService(k8sclient, kc);
            Awaitility.await()
                    .ignoreExceptions()
                    .untilAsserted(() -> {
                        String url = "https://" + service.getName() + "." + namespace + ":" + Constants.KEYCLOAK_HTTPS_PORT + "/admin/master/console/";
                        Log.info("Checking url: " + url);

                        var curlOutput = K8sUtils.inClusterCurl(k8sclient, namespace, "-s", "--insecure", "-H", "Host: foo.bar", url);
                        Log.info("Curl Output: " + curlOutput);

                        assertTrue(curlOutput.contains("var authServerUrl = 'https://foo.bar:8443';"));
                    });
        } catch (Exception e) {
            savePodLogs();
            throw e;
        }
    }

    // Reference curl command:
    // curl --insecure --data "grant_type=password&client_id=admin-cli&username=admin&password=adminPassword" https://localhost:8443/realms/master/protocol/openid-connect/token
    @Test
    public void testInitialAdminUser() {
        try {
            // Recreating the database to keep this test isolated
            deleteDB();
            deployDB();
            var kc = getDefaultKeycloakDeployment();
            deployKeycloak(k8sclient, kc, true);

            var decoder = Base64.getDecoder();
            var service = new KeycloakService(k8sclient, kc);
            var kcAdminSecret = new KeycloakAdminSecret(k8sclient, kc);

            AtomicReference<String> adminUsername = new AtomicReference<>();
            AtomicReference<String> adminPassword = new AtomicReference<>();
            Awaitility.await()
                    .ignoreExceptions()
                    .untilAsserted(() -> {
                        Log.info("Checking secret, ns: " + namespace + ", name: " + kcAdminSecret.getName());
                        var adminSecret = k8sclient
                                .secrets()
                                .inNamespace(namespace)
                                .withName(kcAdminSecret.getName())
                                .get();

                        adminUsername.set(new String(decoder.decode(adminSecret.getData().get("username").getBytes(StandardCharsets.UTF_8))));
                        adminPassword.set(new String(decoder.decode(adminSecret.getData().get("password").getBytes(StandardCharsets.UTF_8))));

                        String url = "https://" + service.getName() + "." + namespace + ":" + Constants.KEYCLOAK_HTTPS_PORT + "/realms/master/protocol/openid-connect/token";
                        Log.info("Checking url: " + url);

                        var curlOutput = K8sUtils.inClusterCurl(k8sclient, namespace, "--insecure", "-s", "--data", "grant_type=password&client_id=admin-cli&username=" + adminUsername.get() + "&password=" + adminPassword.get(), url);
                        Log.info("Curl Output: " + curlOutput);

                        assertTrue(curlOutput.contains("\"access_token\""));
                        assertTrue(curlOutput.contains("\"token_type\":\"Bearer\""));
                    });

            // Redeploy the same Keycloak without redeploying the Database
            k8sclient.resource(kc).delete();
            deployKeycloak(k8sclient, kc, true);
            Awaitility.await()
                    .ignoreExceptions()
                    .untilAsserted(() -> {
                        Log.info("Checking secret, ns: " + namespace + ", name: " + kcAdminSecret.getName());
                        var adminSecret = k8sclient
                                .secrets()
                                .inNamespace(namespace)
                                .withName(kcAdminSecret.getName())
                                .get();

                        var newPassword = new String(decoder.decode(adminSecret.getData().get("password").getBytes(StandardCharsets.UTF_8)));

                        String url = "https://" + service.getName() + "." + namespace + ":" + Constants.KEYCLOAK_HTTPS_PORT + "/realms/master/protocol/openid-connect/token";
                        Log.info("Checking url: " + url);

                        var curlOutput = K8sUtils.inClusterCurl(k8sclient, namespace, "--insecure", "-s", "--data", "grant_type=password&client_id=admin-cli&username=" + adminUsername.get() + "&password=" + adminPassword.get(), url);
                        Log.info("Curl Output: " + curlOutput);

                        assertTrue(curlOutput.contains("\"access_token\""));
                        assertTrue(curlOutput.contains("\"token_type\":\"Bearer\""));
                        assertNotEquals(adminPassword.get(), newPassword);
                    });
        } catch (Exception e) {
            savePodLogs();
            throw e;
        }
    }

    @Test
    @EnabledIfSystemProperty(named = OPERATOR_CUSTOM_IMAGE, matches = ".+")
    public void testCustomImage() {
        try {
            var kc = getDefaultKeycloakDeployment();
            kc.getSpec().setImage(customImage);
            deployKeycloak(k8sclient, kc, true);

            var pods = k8sclient
                    .pods()
                    .inNamespace(namespace)
                    .withLabels(Constants.DEFAULT_LABELS)
                    .list()
                    .getItems();

            assertEquals(1, pods.get(0).getSpec().getContainers().get(0).getArgs().size());
            assertEquals("start", pods.get(0).getSpec().getContainers().get(0).getArgs().get(0));
        } catch (Exception e) {
            savePodLogs();
            throw e;
        }
    }

}
