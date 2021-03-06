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

package org.keycloak.storage.ldap.idm.model;

import java.util.List;

import org.junit.Assert;
import org.junit.Test;

/**
 * @author <a href="mailto:mposolda@redhat.com">Marek Posolda</a>
 */
public class LDAPDnTest {

    @Test
    public void testDn() throws Exception {
        LDAPDn dn = LDAPDn.fromString("dc=keycloak, dc=org");
        dn.addFirst("ou", "People");
        Assert.assertEquals("ou=People,dc=keycloak,dc=org", dn.toString());

        dn.addFirst("uid", "Johny,Depp+Pepp\\Foo");
        Assert.assertEquals("uid=Johny\\,Depp\\+Pepp\\\\Foo,ou=People,dc=keycloak,dc=org", dn.toString());
        Assert.assertEquals(LDAPDn.fromString("uid=Johny\\,Depp\\+Pepp\\\\Foo,ou=People,dc=keycloak,dc=org"), dn);

        Assert.assertEquals("ou=People,dc=keycloak,dc=org", dn.getParentDn().toString());

        Assert.assertTrue(dn.isDescendantOf(LDAPDn.fromString("dc=keycloak, dc=org")));
        Assert.assertTrue(dn.isDescendantOf(LDAPDn.fromString("dc=org")));
        Assert.assertTrue(dn.isDescendantOf(LDAPDn.fromString("DC=keycloak, DC=org")));
        Assert.assertFalse(dn.isDescendantOf(LDAPDn.fromString("dc=keycloakk, dc=org")));
        Assert.assertFalse(dn.isDescendantOf(dn));

        Assert.assertEquals("uid", dn.getFirstRdn().getAllKeys().get(0));
        Assert.assertEquals("uid=Johny\\,Depp\\+Pepp\\\\Foo", dn.getFirstRdn().toString());
        Assert.assertEquals("uid=Johny,Depp+Pepp\\Foo", dn.getFirstRdn().toString(false));
        Assert.assertEquals("Johny,Depp+Pepp\\Foo", dn.getFirstRdn().getAttrValue("uid"));
    }

    @Test
    public void testEmptyRDN() throws Exception {
        LDAPDn dn = LDAPDn.fromString("dc=keycloak, dc=org");
        dn.addFirst("ou", "");

        Assert.assertEquals("ou", dn.getFirstRdn().getAllKeys().get(0));
        Assert.assertEquals("", dn.getFirstRdn().getAttrValue("ou"));

        Assert.assertEquals("ou=,dc=keycloak,dc=org", dn.toString());

        dn.addFirst("uid", "Johny,Depp+Pepp\\Foo");
        Assert.assertEquals("uid=Johny\\,Depp\\+Pepp\\\\Foo,ou=,dc=keycloak,dc=org", dn.toString());

        dn = LDAPDn.fromString("uid=Johny\\,Depp\\+Pepp\\\\Foo,ou=,O=keycloak,C=org");
        Assert.assertTrue(dn.isDescendantOf(LDAPDn.fromString("ou=, O=keycloak,C=org")));
        Assert.assertTrue(dn.isDescendantOf(LDAPDn.fromString("OU=, o=keycloak,c=org")));
        Assert.assertFalse(dn.isDescendantOf(LDAPDn.fromString("ou=People, O=keycloak,C=org")));
    }

    @Test
    public void testCorrectEscape() throws Exception {
        LDAPDn dn = LDAPDn.fromString("dc=keycloak, dc=org");
        dn.addFirst("cn", "Johny,D????a Foo");
        Assert.assertEquals("cn=Johny\\,D????a Foo,dc=keycloak,dc=org", dn.toString());
        Assert.assertEquals("Johny,D????a Foo", dn.getFirstRdn().getAttrValue("cn"));

        dn = LDAPDn.fromString("dc=keycloak, dc=org");
        dn.addFirst("cn", "Johny,D????a Foo ");
        Assert.assertEquals("cn=Johny\\,D????a Foo\\ ,dc=keycloak,dc=org", dn.toString());
        Assert.assertEquals("Johny,D????a Foo ", dn.getFirstRdn().getAttrValue("cn"));

        dn = LDAPDn.fromString("dc=keycloak, dc=org");
        dn.addFirst("cn", "Johny,D????a ");
        Assert.assertEquals("cn=Johny\\,D????a\\ ,dc=keycloak,dc=org", dn.toString());
        Assert.assertEquals("Johny,D????a ", dn.getFirstRdn().getAttrValue("cn"));
    }

    @Test
    public void testDNWithMultivaluedRDN() throws Exception {
        LDAPDn dn = LDAPDn.fromString("uid=john+cn=John Do\\+e??,dc=keycloak+ou=foo, dc=org");

        Assert.assertEquals("uid=john+cn=John Do\\+e??", dn.getFirstRdn().toString());
        List<String> keys = dn.getFirstRdn().getAllKeys();
        Assert.assertEquals("uid", keys.get(0));
        Assert.assertEquals("cn", keys.get(1));
        Assert.assertEquals("john", dn.getFirstRdn().getAttrValue("UiD"));
        Assert.assertEquals("John Do+e??", dn.getFirstRdn().getAttrValue("CN"));

        Assert.assertEquals("dc=keycloak+ou=foo,dc=org", dn.getParentDn().toString());

        dn.getFirstRdn().setAttrValue("UID", "john2");
        Assert.assertEquals("uid=john2+cn=John Do\\+e??", dn.getFirstRdn().toString());

        dn.getFirstRdn().setAttrValue("some", "somet+hing");
        Assert.assertEquals("uid=john2+cn=John Do\\+e??+some=somet\\+hing", dn.getFirstRdn().toString());
    }
}
