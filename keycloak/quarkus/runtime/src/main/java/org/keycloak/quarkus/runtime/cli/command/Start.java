/*
 * Copyright 2020 Red Hat, Inc. and/or its affiliates
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

package org.keycloak.quarkus.runtime.cli.command;

import static org.keycloak.quarkus.runtime.Environment.setProfile;
import static org.keycloak.quarkus.runtime.cli.Picocli.NO_PARAM_LABEL;
import static org.keycloak.quarkus.runtime.configuration.Configuration.getRawPersistedProperty;

import org.keycloak.quarkus.runtime.Environment;
import org.keycloak.quarkus.runtime.Messages;

import picocli.CommandLine;
import picocli.CommandLine.Command;

import java.util.List;
import java.util.Optional;

@Command(name = Start.NAME,
        header = "Start the server.",
        description = {
                "%nUse this command to run the server in production."
        },
        footer = "%nYou may use the \"--auto-build\" option when starting the server to avoid running the \"build\" command everytime you need to change a static property:%n%n"
                + "      $ ${PARENT-COMMAND-FULL-NAME:-$PARENTCOMMAND} ${COMMAND-NAME} --auto-build <OPTIONS>%n%n"
                + "By doing that you have an additional overhead when the server is starting. Run \"${PARENT-COMMAND-FULL-NAME:-$PARENTCOMMAND} build -h\" for more details.")
public final class Start extends AbstractStartCommand implements Runnable {

    public static final String NAME = "start";

    @CommandLine.Option(names = {AUTO_BUILD_OPTION_SHORT, AUTO_BUILD_OPTION_LONG},
            description = "Automatically detects whether the server configuration changed and a new server image must be built" +
                    " prior to starting the server. This option provides an alternative to manually running the '" + Build.NAME + "'" +
                    " prior to starting the server. Use this configuration carefully in production as it might impact the startup time.",
            paramLabel = NO_PARAM_LABEL,
            order = 1)
    Boolean autoConfig;

    @CommandLine.Mixin
    ImportRealmMixin importRealmMixin;

    @Override
    protected void doBeforeRun() {
        devProfileNotAllowedError();
    }

    private void devProfileNotAllowedError() {
        if (isDevProfileNotAllowed(spec.commandLine().getParseResult().expandedArgs())) {
            executionError(spec.commandLine(), Messages.devProfileNotAllowedError(NAME));
        }
    }

    public static boolean isDevProfileNotAllowed(List<String> currentCliArgs) {
        Optional<String> currentProfile = Optional.ofNullable(Environment.getProfile());
        Optional<String> persistedProfile = getRawPersistedProperty("kc.profile");

        setProfile(currentProfile.orElse(persistedProfile.orElse("prod")));

        if (Environment.isDevProfile() && (!currentCliArgs.contains(AUTO_BUILD_OPTION_LONG) || !currentCliArgs.contains(AUTO_BUILD_OPTION_SHORT))) {
            return true;
        }

        return false;
    }
}
