package core;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.permission.PermissionType;
import com.atlassian.bamboo.specs.api.builders.permission.Permissions;
import com.atlassian.bamboo.specs.api.builders.permission.PlanPermissions;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.PlanIdentifier;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.Artifact;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.AllOtherPluginsConfiguration;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.PluginConfiguration;
import com.atlassian.bamboo.specs.api.builders.requirement.Requirement;
import com.atlassian.bamboo.specs.api.builders.task.Task;
import com.atlassian.bamboo.specs.builders.task.CheckoutItem;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.task.TestParserTask;
import com.atlassian.bamboo.specs.builders.task.VcsCheckoutTask;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;
import com.atlassian.bamboo.specs.model.task.TestParserTaskProperties;
import com.atlassian.bamboo.specs.util.MapBuilder;
import org.jetbrains.annotations.NotNull;

import java.util.ArrayList;

/**
 * Abstract class with common methods of pre-merge and nightly plan
 */
abstract class AbstractCoreSpec {

    static String bambooServerName = "https://bamboo.typo3.com:443";
    static String projectName = "TYPO3 Core";
    static String projectKey = "CORE";

    private String composerRootVersionEnvironment = "COMPOSER_ROOT_VERSION=11.0.0";

    private String testingFrameworkBuildPath = "vendor/typo3/testing-framework/Resources/Core/Build/";

    // will only execute `composer install`
    public static final int COMPOSER_DEFAULT = 0;
    // will execute `composer update --with-dependencies`
    public static final int COMPOSER_MAX = 1;
    // will execute `composer update --prefer-lowest`
    public static final int COMPOSER_MIN = 2;

    /**
     * Default permissions on core plans
     */
    PlanPermissions getDefaultPlanPermissions(String projectKey, String planKey) {
        return new PlanPermissions(new PlanIdentifier(projectKey, planKey))
            .permissions(new Permissions()
                .groupPermissions("t3g-team-dev", PermissionType.ADMIN, PermissionType.VIEW, PermissionType.EDIT, PermissionType.BUILD, PermissionType.CLONE)
                .groupPermissions("team-core-dev", PermissionType.VIEW, PermissionType.BUILD)
                .loggedInUserPermissions(PermissionType.VIEW)
                .anonymousUserPermissionView()
            );
    }

    /**
     * Default permissions on core security plans
     */
    PlanPermissions getSecurityPlanPermissions(String projectKey, String planKey) {
        return new PlanPermissions(new PlanIdentifier(projectKey, planKey))
            .permissions(new Permissions()
                .groupPermissions("t3g-team-dev", PermissionType.ADMIN, PermissionType.VIEW, PermissionType.EDIT, PermissionType.BUILD, PermissionType.CLONE)
            );
    }

    /**
     * Default plan plugin configuration
     */
    PluginConfiguration getDefaultPlanPluginConfiguration() {
        return new AllOtherPluginsConfiguration()
            .configuration(new MapBuilder()
                .put("custom", new MapBuilder()
                    .put("artifactHandlers.useCustomArtifactHandlers", "false")
                    .put("buildExpiryConfig", new MapBuilder()
                        .put("duration", "30")
                        .put("period", "days")
                        .put("labelsToKeep", "")
                        .put("expiryTypeResult", "true")
                        .put("buildsToKeep", "")
                        .put("enabled", "true")
                        .build()
                    )
                    .build()
                )
                .build()
            );
    }

    /**
     * Default job plugin configuration
     */
    private PluginConfiguration getDefaultJobPluginConfiguration() {
        return new AllOtherPluginsConfiguration()
            .configuration(new MapBuilder()
                .put("repositoryDefiningWorkingDirectory", -1)
                .put("custom", new MapBuilder()
                    .put("auto", new MapBuilder()
                        .put("regex", "")
                        .put("label", "")
                        .build()
                    )
                    .put("buildHangingConfig.enabled", "false")
                    .put("ncover.path", "")
                    .put("clover", new MapBuilder()
                        .put("path", "")
                        .put("license", "")
                        .put("useLocalLicenseKey", "true")
                        .build()
                    )
                    .build()
                )
                .build()
            );
    }

    /**
     * Job creating labels needed for intercept communication
     */
    Job getJobBuildLabels() {
        return new Job("Create build labels", new BambooKey("CLFB"))
            .description("Create changeId and patch set labels from variable access and parsing result of a dummy task")
            .pluginConfigurations(new AllOtherPluginsConfiguration()
                .configuration(new MapBuilder()
                    .put("repositoryDefiningWorkingDirectory", -1)
                    .put("custom", new MapBuilder()
                        .put("auto", new MapBuilder()
                            .put("label", "change-${bamboo.changeUrl}, patchset-${bamboo.patchset}")
                            .build()
                        )
                        .put("buildHangingConfig.enabled", "false")
                        .put("ncover.path", "")
                        .put("clover", new MapBuilder()
                            .put("path", "")
                            .put("license", "")
                            .put("useLocalLicenseKey", "true")
                            .build()
                        )
                        .build()
                    )
                    .build()
                )
            )
            .tasks(
                new ScriptTask()
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody("echo \"I'm just here for the labels!\"")
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job composer validate
     */
    Job getJobComposerValidate(String requirementIdentifier, Boolean isSecurity) {
        return new Job("Validate composer.json", new BambooKey("VC"))
            .description("Validate composer.json before actual tests are executed")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                new ScriptTask()
                    .description("composer validate")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            this.getScriptTaskComposer(requirementIdentifier) +
                            "composer validate"
                    )
                    .environmentVariables(this.composerRootVersionEnvironment)
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }


    /**
     * Job checking CGL of last git commit
     */
    Job getJobCglCheckGitCommit(String requirementIdentifier, Boolean isSecurity) {
        return new Job("Integration CGL", new BambooKey("CGLCHECK"))
            .description("Check coding guidelines by executing Build/Scripts/cglFixMyCommit.sh script")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                this.getTaskComposerInstall(requirementIdentifier),
                new ScriptTask()
                    .description("Execute cgl check script")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function cglFixMyCommit() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./Build/Scripts/cglFixMyCommit.sh $*\"\n" +
                            "}\n" +
                            "\n" +
                            "cglFixMyCommit dryrun\n"
                    )
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job checking CGL of all core php files
     */
    Job getJobCglCheckFullCore( String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        return new Job("Integration CGL " , new BambooKey("CGLCHECK"))
            .description("Check coding guidelines of full core")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                new ScriptTask()
                    .description("Execute cgl check")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function phpCsFixer() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/php-cs-fixer $*\"\n" +
                            "}\n" +
                            "\n" +
                            "phpCsFixer fix -v --dry-run --path-mode intersection --config=Build/.php_cs typo3/\n" +
                            "exit $?"
                    )
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job acceptance test installs system on mariadb
     */
    Job getJobAcceptanceTestInstallMysql(int stageNumber, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        return new Job("Accept inst my " + name + " " + requirementIdentifier, new BambooKey("ACINSTMY" + stageNumber + requirementIdentifier))
            .description("Install TYPO3 on mariadb and load introduction package " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                this.getTaskPrepareAcceptanceTest(),
                this.getTaskDockerDependenciesAcceptanceInstallMariadb10(),
                new ScriptTask()
                    .description("Install TYPO3 on mariadb 10")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function codecept() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        -e typo3InstallMysqlDatabaseHost=${typo3InstallMysqlDatabaseHost} \\\n" +
                            "        -e typo3InstallMysqlDatabaseName=${typo3InstallMysqlDatabaseName} \\\n" +
                            "        -e typo3InstallMysqlDatabaseUsername=${typo3InstallMysqlDatabaseUsername} \\\n" +
                            "        -e typo3InstallMysqlDatabasePassword=${typo3InstallMysqlDatabasePassword} \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./bin/codecept $*\"\n" +
                            "}\n" +
                            "\n" +
                            "codecept run Install -d -c typo3/sysext/core/Tests/codeception.yml --env=mysql --xml reports.xml --html reports.html\n"
                    )
            )
            .finalTasks(
                this.getTaskStopDockerDependencies(),
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("typo3temp/var/tests/AcceptanceReports/reports.xml")
            )
            .artifacts(new Artifact()
                .name("Test Report")
                .copyPattern("typo3temp/var/tests/AcceptanceReports/")
                .shared(false)
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job acceptance test installs system and introduction package on pgsql
     */
    Job getJobAcceptanceTestInstallPgsql(int stageNumber, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        return new Job("Accept inst pg " + name + " " + requirementIdentifier, new BambooKey("ACINSTPG" + stageNumber + requirementIdentifier))
            .description("Install TYPO3 on pgsql and load introduction package " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                this.getTaskPrepareAcceptanceTest(),
                this.getTaskDockerDependenciesAcceptancePostgres10(),
                new ScriptTask()
                    .description("Install TYPO3 on postgresql 10")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function codecept() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        -e typo3InstallPostgresqlDatabaseHost=${typo3InstallPostgresqlDatabaseHost} \\\n" +
                            "        -e typo3InstallPostgresqlDatabaseName=${typo3InstallPostgresqlDatabaseName} \\\n" +
                            "        -e typo3InstallPostgresqlDatabaseUsername=${typo3InstallPostgresqlDatabaseUsername} \\\n" +
                            "        -e typo3InstallPostgresqlDatabasePassword=${typo3InstallPostgresqlDatabasePassword} \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./bin/codecept $*\"\n" +
                            "}\n" +
                            "\n" +
                            "codecept run Install -d -c typo3/sysext/core/Tests/codeception.yml --env=postgresql --xml reports.xml --html reports.html\n"
                    )
            )
            .finalTasks(
                this.getTaskStopDockerDependencies(),
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("typo3temp/var/tests/AcceptanceReports/reports.xml")
            )
            .artifacts(new Artifact()
                .name("Test Report")
                .copyPattern("typo3temp/var/tests/AcceptanceReports/")
                .shared(false)
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job acceptance test installs system and introduction package on sqlite
     */
    Job getJobAcceptanceTestInstallSqlite(int stageNumber, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        return new Job("Accept inst sq " + name + " " + requirementIdentifier, new BambooKey("ACINSTSQ" + stageNumber + requirementIdentifier))
            .description("Install TYPO3 on sqlite and load introduction package " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                this.getTaskPrepareAcceptanceTest(),
                this.getTaskDockerDependenciesAcceptanceInstallSqlite(),
                new ScriptTask()
                    .description("Install TYPO3 on sqlite")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function codecept() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./bin/codecept $*\"\n" +
                            "}\n" +
                            "\n" +
                            "codecept run Install -d -c typo3/sysext/core/Tests/codeception.yml --env=sqlite --xml reports.xml --html reports.html\n"
                    )
            )
            .finalTasks(
                this.getTaskStopDockerDependencies(),
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("typo3temp/var/tests/AcceptanceReports/reports.xml")
            )
            .artifacts(new Artifact()
                .name("Test Report")
                .copyPattern("typo3temp/var/tests/AcceptanceReports/")
                .shared(false)
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Jobs for mysql based acceptance tests
     */
    ArrayList<Job> getJobsAcceptanceTestsBackendMysql(int stageNumber, int numberOfChunks, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 1; i <= numberOfChunks; i++) {
            String formattedI = "" + i;
            if (i < 10) {
                formattedI = "0" + i;
            }
            jobs.add(new Job("Accept my " + name + " " + requirementIdentifier + " " + formattedI, new BambooKey("ACMY" + stageNumber + requirementIdentifier + formattedI))
                .description("Run acceptance tests" + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(isSecurity),
                    this.getTaskStopDanglingContainers(),
                    composerTask,
                    this.getTaskPrepareAcceptanceTest(),
                    this.getTaskDockerDependenciesAcceptanceBackendMariadb10(),
                    new ScriptTask()
                        .description("Split acceptance tests")
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                                "function splitAcceptanceTests() {\n" +
                                "    docker run \\\n" +
                                "        -u ${HOST_UID} \\\n" +
                                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                                "        --rm \\\n" +
                                "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                                "        bin/bash -c \"cd ${PWD}; ./" + this.testingFrameworkBuildPath + "Scripts/splitAcceptanceTests.php $*\"\n" +
                                "}\n" +
                                "\n" +
                                "splitAcceptanceTests " + numberOfChunks + " -v"
                        ),
                    new ScriptTask()
                        .description("Execute codeception acceptance suite group " + formattedI)
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                                "function codecept() {\n" +
                                "    docker run \\\n" +
                                "        -u ${HOST_UID} \\\n" +
                                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                "        -e typo3DatabaseName=func_test \\\n" +
                                "        -e typo3DatabaseUsername=root \\\n" +
                                "        -e typo3DatabasePassword=funcp  \\\n" +
                                "        -e typo3DatabaseHost=mariadb10  \\\n" +
                                "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                                "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                                "        --rm \\\n" +
                                "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                                "        bin/bash -c \"cd ${PWD}; ./bin/codecept $*\"\n" +
                                "}\n" +
                                "\n" +
                                "codecept run Backend -d -g AcceptanceTests-Job-" + i + " -c typo3/sysext/core/Tests/codeception.yml --xml reports.xml --html reports.html\n"
                        )
                )
                .finalTasks(
                    this.getTaskStopDockerDependencies(),
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("typo3temp/var/tests/AcceptanceReports/reports.xml")
                )
                .artifacts(new Artifact()
                    .name("Test Report")
                    .copyPattern("typo3temp/var/tests/AcceptanceReports/")
                    .shared(false)
                )
                .requirements(
                    this.getRequirementDocker10()
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Jobs for mysql based acceptance tests
     */
    ArrayList<Job> getJobsAcceptanceTestsPageTreeMysql(int stageNumber, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        ArrayList<Job> jobs = new ArrayList<Job>();

        jobs.add(new Job("Accept PageTree my " + name + " " + requirementIdentifier, new BambooKey("ACPTMY" + stageNumber + requirementIdentifier))
            .description("Run acceptance tests for page tree" + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                this.getTaskPrepareAcceptanceTest(),
                this.getTaskDockerDependenciesAcceptanceBackendMariadb10(),
                new ScriptTask()
                    .description("Execute codeception acceptance test for pageTree.")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function codecept() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        -e typo3DatabaseName=func_test \\\n" +
                            "        -e typo3DatabaseUsername=root \\\n" +
                            "        -e typo3DatabasePassword=funcp  \\\n" +
                            "        -e typo3DatabaseHost=mariadb10  \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./bin/codecept $*\"\n" +
                            "}\n" +
                            "\n" +
                            "codecept run PageTree -d -c typo3/sysext/core/Tests/codeception.yml --xml reports.xml --html reports.html\n"
                    )
            )
            .finalTasks(
                this.getTaskStopDockerDependencies(),
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("typo3temp/var/tests/AcceptanceReports/reports.xml")
            )
            .artifacts(new Artifact()
                .name("Test Report")
                .copyPattern("typo3temp/var/tests/AcceptanceReports/")
                .shared(false)
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true)
        );

        return jobs;
    }

    ArrayList<Job> getJobsAcceptanceTestsInstallToolMysql(int stageNumber, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        ArrayList<Job> jobs = new ArrayList<Job>();

        jobs.add(new Job("Accept InstallTool my " + name + " " + requirementIdentifier, new BambooKey("ACITMY" + stageNumber + requirementIdentifier))
            .description("Run acceptance tests for install tool " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                this.getTaskPrepareAcceptanceTest(),
                this.getTaskDockerDependenciesAcceptanceBackendMariadb10(),
                new ScriptTask()
                    .description("Execute codeception acceptance test for standalone install tool.")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function codecept() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        -e typo3DatabaseName=func_test \\\n" +
                            "        -e typo3DatabaseUsername=root \\\n" +
                            "        -e typo3DatabasePassword=funcp  \\\n" +
                            "        -e typo3DatabaseHost=mariadb10  \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./bin/codecept $*\"\n" +
                            "}\n" +
                            "\n" +
                            "codecept run InstallTool -d -c typo3/sysext/core/Tests/codeception.yml --env=mysql --xml reports.xml --html reports.html\n"
                    )
            )
            .finalTasks(
                this.getTaskStopDockerDependencies(),
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("typo3temp/var/tests/AcceptanceReports/reports.xml")
            )
            .artifacts(new Artifact()
                .name("Test Report")
                .copyPattern("typo3temp/var/tests/AcceptanceReports/")
                .shared(false)
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true)
        );

        return jobs;
    }

    /**
     * Jobs for mysql based functional tests with driver mysqli
     */
    ArrayList<Job> getJobsFunctionalTestsMysqlWithDriverMySqli(int stageNumber, int numberOfChunks, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 1; i <= numberOfChunks; i++) {
            String formattedI = "" + i;
            if (i < 10) {
                formattedI = "0" + i;
            }
            jobs.add(new Job("Func mysql " + name + " " + requirementIdentifier + " " + formattedI, new BambooKey("FMYI" + stageNumber + requirementIdentifier + formattedI))
                .description("Run functional tests on mysql DB " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(isSecurity),
                    this.getTaskStopDanglingContainers(),
                    composerTask,
                    this.getTaskDockerDependenciesFunctionalMariadb10(),
                    this.getTaskSplitFunctionalJobs(numberOfChunks, requirementIdentifier),
                    new ScriptTask()
                        .description("Run phpunit with functional chunk " + formattedI)
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                                "function phpunit() {\n" +
                                "    docker run \\\n" +
                                "        -u ${HOST_UID} \\\n" +
                                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                "        -e typo3DatabaseDriver=mysqli \\\n" +
                                "        -e typo3DatabaseName=func_test \\\n" +
                                "        -e typo3DatabaseUsername=root \\\n" +
                                "        -e typo3DatabasePassword=funcp \\\n" +
                                "        -e typo3DatabaseHost=mariadb10 \\\n" +
                                "        -e typo3TestingRedisHost=${BAMBOO_COMPOSE_PROJECT_NAME}sib_redis4_1 \\\n" +
                                "        -e typo3TestingMemcachedHost=${BAMBOO_COMPOSE_PROJECT_NAME}sib_memcached1-5_1 \\\n" +
                                "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                                "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                                "        --rm \\\n" +
                                "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                                "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/phpunit $*\"\n" +
                                "}\n" +
                                "\n" +
                                "phpunit --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "FunctionalTests-Job-" + i + ".xml"
                        )
                )
                .finalTasks(
                    this.getTaskStopDockerDependencies(),
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    this.getRequirementDocker10()
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Jobs for mysql based functional tests with driver pdo_mysql
     */
    ArrayList<Job> getJobsFunctionalTestsMysqlWithDriverPdoMysql(int stageNumber, int numberOfChunks, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 1; i <= numberOfChunks; i++) {
            String formattedI = "" + i;
            if (i < 10) {
                formattedI = "0" + i;
            }
            jobs.add(new Job("Func mysql with pdo " + name + " " + requirementIdentifier + " " + formattedI, new BambooKey("FMYP" + stageNumber + requirementIdentifier + formattedI))
                .description("Run functional tests on mysql DB with PDO driver " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(isSecurity),
                    this.getTaskStopDanglingContainers(),
                    composerTask,
                    this.getTaskDockerDependenciesFunctionalMariadb10(),
                    this.getTaskSplitFunctionalJobs(numberOfChunks, requirementIdentifier),
                    new ScriptTask()
                        .description("Run phpunit with functional chunk " + formattedI)
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                                "function phpunit() {\n" +
                                "    docker run \\\n" +
                                "        -u ${HOST_UID} \\\n" +
                                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                "        -e typo3DatabaseDriver=pdo_mysql \\\n" +
                                "        -e typo3DatabaseName=func_test \\\n" +
                                "        -e typo3DatabaseUsername=root \\\n" +
                                "        -e typo3DatabasePassword=funcp \\\n" +
                                "        -e typo3DatabaseHost=mariadb10 \\\n" +
                                "        -e typo3TestingRedisHost=${BAMBOO_COMPOSE_PROJECT_NAME}sib_redis4_1 \\\n" +
                                "        -e typo3TestingMemcachedHost=${BAMBOO_COMPOSE_PROJECT_NAME}sib_memcached1-5_1 \\\n" +
                                "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                                "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                                "        --rm \\\n" +
                                "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                                "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/phpunit $*\"\n" +
                                "}\n" +
                                "\n" +
                                "phpunit --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "FunctionalTests-Job-" + i + ".xml"
                        )
                )
                .finalTasks(
                    this.getTaskStopDockerDependencies(),
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    this.getRequirementDocker10()
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Jobs for mssql based functional tests with driver sqlsrv
     */
    ArrayList<Job> getJobsFunctionalTestsMssqlWithDriverSqlSrv(int stageNumber, int numberOfChunks, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 1; i <= numberOfChunks; i++) {
            String formattedI = "" + i;
            if (i < 10) {
                formattedI = "0" + i;
            }
            jobs.add(new Job("Func mssql sqlsrv " + name + " " + requirementIdentifier + " " + formattedI, new BambooKey("FMSS" + stageNumber + requirementIdentifier + formattedI))
                .description("Run functional tests on mysql DB with sqlsrv driver " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(isSecurity),
                    this.getTaskStopDanglingContainers(),
                    composerTask,
                    this.getTaskDockerDependenciesFunctionalMssql(),
                    this.getTaskSplitFunctionalJobs(numberOfChunks, requirementIdentifier),
                    new ScriptTask()
                        .description("Run phpunit with functional chunk " + formattedI)
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                                "function phpunit() {\n" +
                                "    docker run \\\n" +
                                "        -u ${HOST_UID} \\\n" +
                                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                "        -e typo3DatabaseDriver=sqlsrv \\\n" +
                                "        -e typo3DatabaseName=func \\\n" +
                                "        -e typo3DatabasePassword=Test1234! \\\n" +
                                "        -e typo3DatabaseUsername=SA \\\n" +
                                "        -e typo3DatabaseHost=localhost \\\n" +
                                "        -e typo3DatabasePort=1433 \\\n" +
                                "        -e typo3DatabaseCharset=utf-8 \\\n" +
                                "        -e typo3DatabaseHost=mssql2019latest \\\n" +
                                "        -e typo3TestingRedisHost=${BAMBOO_COMPOSE_PROJECT_NAME}sib_redis4_1 \\\n" +
                                "        -e typo3TestingMemcachedHost=${BAMBOO_COMPOSE_PROJECT_NAME}sib_memcached1-5_1 \\\n" +
                                "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                                "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                                "        --rm \\\n" +
                                "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                                "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/phpunit $*\"\n" +
                                "}\n" +
                                "\n" +
                                "phpunit --exclude-group not-mssql --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "FunctionalTests-Job-" + i + ".xml"
                        )
                )
                .finalTasks(
                    this.getTaskStopDockerDependencies(),
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    this.getRequirementDocker10()
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Jobs for mssql based functional tests with driver pdo_sqlsrv
     */
    ArrayList<Job> getJobsFunctionalTestsMssqlWithDriverPdoSqlSrv(int stageNumber, int numberOfChunks, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 1; i <= numberOfChunks; i++) {
            String formattedI = "" + i;
            if (i < 10) {
                formattedI = "0" + i;
            }
            jobs.add(new Job("Func mssql pdo " + name + " " + requirementIdentifier + " " + formattedI, new BambooKey("FMSP" + stageNumber + requirementIdentifier + formattedI))
                .description("Run functional tests on mssql DB with PDO driver " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(isSecurity),
                    this.getTaskStopDanglingContainers(),
                    composerTask,
                    this.getTaskDockerDependenciesFunctionalMssql(),
                    this.getTaskSplitFunctionalJobs(numberOfChunks, requirementIdentifier),
                    new ScriptTask()
                        .description("Run phpunit with functional chunk " + formattedI)
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                                "function phpunit() {\n" +
                                "    docker run \\\n" +
                                "        -u ${HOST_UID} \\\n" +
                                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                "        -e typo3DatabaseDriver=pdo_sqlsrv \\\n" +
                                "        -e typo3DatabaseName=func \\\n" +
                                "        -e typo3DatabasePassword=Test1234! \\\n" +
                                "        -e typo3DatabaseUsername=SA \\\n" +
                                "        -e typo3DatabaseHost=localhost \\\n" +
                                "        -e typo3DatabasePort=1433 \\\n" +
                                "        -e typo3DatabaseCharset=utf-8 \\\n" +
                                "        -e typo3DatabaseHost=mssql2019latest \\\n" +
                                "        -e typo3TestingRedisHost=${BAMBOO_COMPOSE_PROJECT_NAME}sib_redis4_1 \\\n" +
                                "        -e typo3TestingMemcachedHost=${BAMBOO_COMPOSE_PROJECT_NAME}sib_memcached1-5_1 \\\n" +
                                "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                                "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                                "        --rm \\\n" +
                                "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                                "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/phpunit $*\"\n" +
                                "}\n" +
                                "\n" +
                                "phpunit --exclude-group not-mssql --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "FunctionalTests-Job-" + i + ".xml"
                        )
                )
                .finalTasks(
                    this.getTaskStopDockerDependencies(),
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    this.getRequirementDocker10()
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Jobs for pgsql based functional tests
     */
    ArrayList<Job> getJobsFunctionalTestsPgsql(int stageNumber, int numberOfChunks, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 1; i <= numberOfChunks; i++) {
            String formattedI = "" + i;
            if (i < 10) {
                formattedI = "0" + i;
            }
            jobs.add(new Job("Func pgsql " + name + " " + requirementIdentifier + " " + formattedI, new BambooKey("FPG" + stageNumber + requirementIdentifier + formattedI))
                .description("Run functional tests on pgsql DB " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(isSecurity),
                    this.getTaskStopDanglingContainers(),
                    composerTask,
                    this.getTaskDockerDependenciesFunctionalPostgres10(),
                    this.getTaskSplitFunctionalJobs(numberOfChunks, requirementIdentifier),
                    new ScriptTask()
                        .description("Run phpunit with functional chunk " + formattedI)
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                                "function phpunit() {\n" +
                                "    docker run \\\n" +
                                "        -u ${HOST_UID} \\\n" +
                                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                "        -e typo3DatabaseDriver=pdo_pgsql \\\n" +
                                "        -e typo3DatabaseName=bamboo \\\n" +
                                "        -e typo3DatabaseUsername=bamboo \\\n" +
                                "        -e typo3DatabaseHost=postgres10 \\\n" +
                                "        -e typo3DatabasePassword=funcp \\\n" +
                                "        -e typo3TestingRedisHost=redis4 \\\n" +
                                "        -e typo3TestingMemcachedHost=memcached1-5 \\\n" +
                                "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                                "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                                "        --rm \\\n" +
                                "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                                "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/phpunit $*\"\n" +
                                "}\n" +
                                "\n" +
                                "phpunit --exclude-group not-postgres --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "FunctionalTests-Job-" + i + ".xml"
                        )
                )
                .finalTasks(
                    this.getTaskStopDockerDependencies(),
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    this.getRequirementDocker10()
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Jobs for sqlite based functional tests
     */
    ArrayList<Job> getJobsFunctionalTestsSqlite(int stageNumber, int numberOfChunks, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 1; i <= numberOfChunks; i++) {
            String formattedI = "" + i;
            if (i < 10) {
                formattedI = "0" + i;
            }
            jobs.add(new Job("Func sqlite " + name + " " + requirementIdentifier + " " + formattedI, new BambooKey("FSL" + stageNumber + requirementIdentifier + formattedI))
                .description("Run functional tests on sqlite DB " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(isSecurity),
                    this.getTaskStopDanglingContainers(),
                    composerTask,
                    this.getTaskSplitFunctionalJobs(numberOfChunks, requirementIdentifier),
                    this.getTaskDockerDependenciesFunctionalSqlite(),
                    new ScriptTask()
                        .description("Run phpunit with functional chunk " + formattedI)
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                                "function phpunit() {\n" +
                                "    docker run \\\n" +
                                "        -u ${HOST_UID} \\\n" +
                                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                "        -e typo3DatabaseDriver=pdo_sqlite \\\n" +
                                "        -e typo3TestingRedisHost=redis4 \\\n" +
                                "        -e typo3TestingMemcachedHost=memcached1-5 \\\n" +
                                "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                                "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                                "        --rm \\\n" +
                                "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                                "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/phpunit $*\"\n" +
                                "}\n" +
                                "\n" +
                                "phpunit --exclude-group not-sqlite --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "FunctionalTests-Job-" + i + ".xml"
                        )
                )
                .finalTasks(
                    this.getTaskStopDockerDependencies(),
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    this.getRequirementDocker10()
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Job with integration test checking for valid @xy annotations
     */
    Job getJobIntegrationAnnotations(String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        return new Job("Integration annotations ", new BambooKey("IANNO"))
            .description("Check docblock-annotations by executing Build/Scripts/annotationChecker.php script")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                new ScriptTask()
                    .description("Execute annotations check script")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function annotationChecker() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./Build/Scripts/annotationChecker.php $*\"\n" +
                            "}\n" +
                            "\n" +
                            "annotationChecker"
                    )
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job with integration test checking for valid php doc blocks
     */
    Job getJobIntegrationDocBlocks(String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        return new Job("Integration doc blocks", new BambooKey("IDB"))
            .description("Check doc blocks by executing Build/Scripts/docBlockChecker.php script")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                new ScriptTask()
                    .description("Execute doc block check script")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function dockBlockChecker() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./Build/Scripts/docBlockChecker.php $*\"\n" +
                            "}\n" +
                            "\n" +
                            "dockBlockChecker"
                    )
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job with integration test checking the source code with phpstan/phpstan
     *
     * @param String requirementIdentifier
     * @param Task composerTask
     * @param Boolean isSecurity
     */
    protected Job getJobIntegrationPhpStan(String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        return new Job("Integration phpstan", new BambooKey("PHPSTAN"))
            .description("Check source code via phpstan")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                new ScriptTask()
                    .description("Run phpstan")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function phpstan() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./bin/phpstan analyse --no-progress --no-interaction $*\"\n" +
                            "}\n" +
                            "\n" +
                            "phpstan"
                    )
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job with various smaller script tests
     */
    Job getJobIntegrationVarious(String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        // Exception code checker, xlf, permissions, rst file check
        return new Job("Integration various", new BambooKey("CDECC"))
            .description("Checks duplicate exceptions, git submodules, xlf files, permissions, rst")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                new ScriptTask()
                    .description("Run duplicate exception code check script")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "./Build/Scripts/duplicateExceptionCodeCheck.sh\n"
                    ),
                new ScriptTask()
                    .description("Run git submodule status and verify there are none")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "if [[ `git submodule status 2>&1 | wc -l` -ne 0 ]]; then\n" +
                            "    echo \\\"Found a submodule definition in repository\\\";\n" +
                            "    exit 99;\n" +
                            "fi\n"
                    ),
                new ScriptTask()
                    .description("Run permission check script")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "./Build/Scripts/checkFilePermissions.sh\n"
                    ),
                new ScriptTask()
                    .description("Run xlf check")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "./Build/Scripts/xlfcheck.sh"
                    ),
                new ScriptTask()
                    .description("Run rst check")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function validateRstFiles() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./Build/Scripts/validateRstFiles.php $*\"\n" +
                            "}\n" +
                            "\n" +
                            "validateRstFiles"
                    ),
                new ScriptTask()
                    .description("Run path length check")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "./Build/Scripts/maxFilePathLength.sh"
                    ),
                new ScriptTask()
                    .description("Run extension scanner ReST file reference tester")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function extensionScannerRstFileReferences() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./Build/Scripts/extensionScannerRstFileReferences.php $*\"\n" +
                            "}\n" +
                            "\n" +
                            "extensionScannerRstFileReferences"
                    ),
                new ScriptTask()
                    .description("Run functional fixture csv format checker")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function checkIntegrityCsvFixtures() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./Build/Scripts/checkIntegrityCsvFixtures.php $*\"\n" +
                            "}\n" +
                            "\n" +
                            "checkIntegrityCsvFixtures"
                    ),
                new ScriptTask()
                    .description("Run UTF-8 BOM check")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function checkIntegrityBom() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./Build/Scripts/checkUtf8Bom.sh $*\"\n" +
                            "}\n" +
                            "\n" +
                            "checkIntegrityBom"
                    ),
                new ScriptTask()
                    .description("Run composer.json integrity check")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function checkIntegrityComposer() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./Build/Scripts/checkIntegrityComposer.php $*\"\n" +
                            "}\n" +
                            "\n" +
                            "checkIntegrityComposer"
                    )
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job for javascript unit tests
     */
    Job getJobUnitJavaScript(String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        return new Job("Unit JavaScript", new BambooKey("JSUT"))
            .description("Run JavaScript unit tests")
            .pluginConfigurations(
                new AllOtherPluginsConfiguration()
                    .configuration(new MapBuilder()
                        .put("repositoryDefiningWorkingDirectory", -1)
                        .put("custom", new MapBuilder()
                            .put("auto", new MapBuilder()
                                .put("regex", "")
                                .put("label", "")
                                .build()
                            )
                            .put("buildHangingConfig.enabled", "false")
                            .put("ncover.path", "")
                            .put("clover", new MapBuilder()
                                .put("path", "typo3temp/var/tests/karma.clover.xml")
                                .put("integration", "custom")
                                .put("exists", "true")
                                .build()
                            )
                            .build()
                        )
                        .build()
                    )
            )
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                new ScriptTask()
                    .description("yarn install in Build/ dir")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function yarn() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        -e HOME=${HOME} \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}/Build; yarn $*\"\n" +
                            "}\n" +
                            "\n" +
                            "yarn install"
                    ),
                new ScriptTask()
                    .description("Run tests")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function karma() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        -e HOME=${HOME} \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; ./Build/node_modules/karma/bin/karma $*\"\n" +
                            "}\n" +
                            "\n" +
                            "karma start " + this.testingFrameworkBuildPath + "Configuration/JSUnit/karma.conf.ci.js --single-run"
                    )
            )
            .finalTasks(
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("typo3temp/var/tests/*")
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job for PHP lint
     */
    Job getJobLintPhp(String requirementIdentifier, Boolean isSecurity) {
        return new Job("Lint " + requirementIdentifier, new BambooKey("L" + requirementIdentifier))
            .description("Run php -l on source files for linting " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                new ScriptTask()
                    .description("Run php lint")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function runLint() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        -e HOME=${HOME} \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; find . -name \\*.php -print0 | xargs -0 -n1 -P2 php -n -c /etc/php/cli-no-xdebug/php.ini -l >/dev/null\"\n" +
                            "}\n" +
                            "\n" +
                            "runLint"
                    )
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job for lint npm scss and typescript
     */
    Job getJobLintScssTs(String requirementIdentifier, Boolean isSecurity) {
        return new Job("Lint scss ts", new BambooKey("LSTS"))
            .description("Lint scss and ts, build css and js, test git is clean")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                new ScriptTask()
                    .description("yarn install in Build/ dir")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function yarn() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        -e HOME=${HOME} \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}/Build; yarn $*\"\n" +
                            "}\n" +
                            "\n" +
                            "yarn install"
                    ),
                new ScriptTask()
                    .description("Run grunt lint")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function grunt() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        -e HOME=${HOME} \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}/Build; ./node_modules/grunt/bin/grunt $*\"\n" +
                            "}\n" +
                            "\n" +
                            "grunt lint"
                    ),
                new ScriptTask()
                    .description("Run grunt build")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function grunt() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        -e HOME=${HOME} \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}/Build; ./node_modules/grunt/bin/grunt $*\"\n" +
                            "}\n" +
                            "\n" +
                            "grunt build"
                    ),
                new ScriptTask()
                    .description("add changed files and show final status")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "git add *\n" +
                            "git status"
                    ),
                new ScriptTask()
                    .description("git status to check for changed files after build-js")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "git status | grep -q \"nothing to commit, working tree clean\""
                    )
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job for unit testing PHP
     */
    Job getJobUnitPhp(int stageNumber, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        return new Job("Unit" + name + " " + requirementIdentifier, new BambooKey("UT" + stageNumber + requirementIdentifier))
            .description("Run unit tests " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                new ScriptTask()
                    .description("Run phpunit")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function phpunit() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/phpunit $*\"\n" +
                            "}\n" +
                            "\n" +
                            "phpunit --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "UnitTests.xml"
                    )
            )
            .finalTasks(
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("test-reports/phpunit.xml")
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    @NotNull
    private String getTaskNamePartForComposer(int stageNumber) {
        String name = "";
        if (stageNumber == 1) {
            name = " composer max";
        } else {
            if (stageNumber == 2) {
                name = " composer min";
            }
        }
        return name;
    }

    /**
     * the composer task needed for the current php version and composer install stage
     */
    Task getComposerTaskByStageNumber(String phpVersion, int stageNumber) {
        Task composerTask = this.getTaskComposerInstall(phpVersion);
        if (stageNumber == COMPOSER_MAX) {
            composerTask = this.getTaskComposerUpdateMax(phpVersion);
        } else {
            if (stageNumber == COMPOSER_MIN) {
                composerTask = this.getTaskComposerUpdateMin(phpVersion);
            }
        }
        return composerTask;
    }

    /**
     * Job for unit testing deprecated PHP
     */
    Job getJobUnitDeprecatedPhp(int stageNumber, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        return new Job("Unit deprecated " + name + " " + requirementIdentifier, new BambooKey("UTD" + stageNumber + requirementIdentifier))
            .description("Run deprecated unit tests " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(isSecurity),
                this.getTaskStopDanglingContainers(),
                composerTask,
                new ScriptTask()
                    .description("Run phpunit")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                            "function phpunit() {\n" +
                            "    docker run \\\n" +
                            "        -u ${HOST_UID} \\\n" +
                            "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                            "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                            "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                            "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                            "        --rm \\\n" +
                            "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                            "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/phpunit $*\"\n" +
                            "}\n" +
                            "\n" +
                            "phpunit --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "UnitTestsDeprecated.xml"
                    )
            )
            .finalTasks(
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("test-reports/phpunit.xml")
            )
            .requirements(
                this.getRequirementDocker10()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Jobs for unit testing PHP in random test order
     */
    ArrayList<Job> getJobUnitPhpRandom(int stageNumber, int numberOfRuns, String requirementIdentifier, Task composerTask, Boolean isSecurity) {
        String name = getTaskNamePartForComposer(stageNumber);
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 1; i <= numberOfRuns; i++) {
            jobs.add(new Job("Unit " + name + " " + requirementIdentifier + " random " + i, new BambooKey("UTR" + stageNumber + requirementIdentifier + i))
                .description("Run unit tests on " + requirementIdentifier + " in random order 0" + i)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(isSecurity),
                    this.getTaskStopDanglingContainers(),
                    composerTask,
                    new ScriptTask()
                        .description("Run phpunit random order")
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                                "function phpunit() {\n" +
                                "    docker run \\\n" +
                                "        -u ${HOST_UID} \\\n" +
                                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                                "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                                "        --rm \\\n" +
                                "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                                "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/phpunit $*\"\n" +
                                "}\n" +
                                "\n" +
                                "phpunit --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "UnitTests.xml --order-by=random"
                        )
                )
                .finalTasks(
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    this.getRequirementDocker10()
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Task definition for basic core clone of linked default repository
     */
    private Task getTaskGitCloneRepository() {
        return new VcsCheckoutTask()
            .description("Checkout git core")
            .checkoutItems(new CheckoutItem().defaultRepository());
    }

    /**
     * Task definition to cherry pick a patch set from gerrit on top of cloned core
     */
    private Task getTaskGitCherryPick(Boolean isSecurity) {
        String cherryPickRepository = isSecurity ? "Teams/Security/TYPO3v4-Core" : "Packages/TYPO3.CMS";

        return new ScriptTask()
            .description("Gerrit cherry pick")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "CHANGEURL=${bamboo.changeUrl}\n" +
                    "CHANGEURLID=${CHANGEURL#https://review.typo3.org/}\n" +
                    "PATCHSET=${bamboo.patchset}\n" +
                    "\n" +
                    "if [[ $CHANGEURL ]]; then\n" +
                    "    NEXT_WAIT_TIME=0\n" +
                    "    until gerrit-cherry-pick https://review.typo3.org/" + cherryPickRepository + " $CHANGEURLID/$PATCHSET; do\n" +
                    "        [[ $NEXT_WAIT_TIME -eq 5 ]] && exit 1\n" +
                    "        sleep $(( NEXT_WAIT_TIME++ ))\n" +
                    "    done\n" +
                    "fi\n"
            );
    }

    /**
     * Safety net task executed before other task that call containers to
     * stop any dangling containers from a previous run on this agent.
     */
    private Task getTaskStopDanglingContainers() {
        return new ScriptTask()
            .description("Stop dangling containers")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "cd Build/testing-docker/bamboo\n" +
                    "docker inspect -f '{{.State.Running}}' ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc > /dev/null 2>&1\n" +
                    "if [[ $? -eq 0 ]]; then\n" +
                    "    docker-compose down -v\n" +
                    "    docker rm -f ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc\n" +
                    "fi\n" +
                    "exit 0\n"
            );
    }

    /**
     * Task definition to execute composer install
     */
    Task getTaskComposerInstall(String requirementIdentifier) {
        return new ScriptTask()
            .description("composer install")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    this.getScriptTaskComposer(requirementIdentifier) +
                    "composer install --no-progress --no-suggest --no-interaction"
            )
            .environmentVariables(this.composerRootVersionEnvironment);
    }

    /**
     * Task definition to execute 'composer update --with-dependencies'.
     * This will update all dependencies to current possible maximum version.
     * Used in nightly to see if we are compatible with updates from dependencies.
     * <p>
     * We update in 2 steps: First composer install as usual, then update. This
     * way it is easy to see which packages are updated in comparison to what is
     * currently defined in composer.lock.
     */
    Task getTaskComposerUpdateMax(String requirementIdentifier) {
        return new ScriptTask()
            .description("composer update --with-dependencies")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    this.getScriptTaskComposer(requirementIdentifier) +
                    "composer update --no-progress --no-suggest --no-interaction"
            )
            .environmentVariables(this.composerRootVersionEnvironment);
    }

    /**
     * Task definition to execute 'composer update --prefer-lowest'.
     * This will update all dependencies to current possible minimum version.
     * Used in nightly to see if we are compatible with lowest possible dependency versions.
     * <p>
     * We update in 2 steps: First composer install as usual, then update. This
     * way it is easy to see which packages are updated in comparison to what is
     * currently defined in composer.lock.
     */
    Task getTaskComposerUpdateMin(String requirementIdentifier) {
        return new ScriptTask()
            .description("composer update --prefer-lowest")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    this.getScriptTaskComposer(requirementIdentifier) +
                    "composer update --prefer-lowest --no-progress --no-suggest --no-interaction"
            )
            .environmentVariables(this.composerRootVersionEnvironment);
    }

    /**
     * Task to prepare an acceptance test
     */
    private Task getTaskPrepareAcceptanceTest() {
        return new ScriptTask()
            .description("Prepare acceptance test environment")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "mkdir -p typo3temp/var/tests/\n"
            );
    }

    /**
     * Start docker sibling containers to execute acceptance install tests on mariadb
     */
    private Task getTaskDockerDependenciesAcceptanceInstallMariadb10() {
        return new ScriptTask()
            .description("Start docker siblings for acceptance test install mariadb")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "cd Build/testing-docker/bamboo\n" +
                    "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                    "docker-compose run start_dependencies_acceptance_install_mariadb10"
            );
    }

    /**
     * Start docker sibling containers to execute acceptance install tests on postgres
     */
    private Task getTaskDockerDependenciesAcceptancePostgres10() {
        return new ScriptTask()
            .description("Start docker siblings for acceptance test install postgres")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "cd Build/testing-docker/bamboo\n" +
                    "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                    "docker-compose run start_dependencies_acceptance_install_postgres10"
            );
    }

    /**
     * Start docker sibling containers to execute acceptance install tests on sqlite
     */
    private Task getTaskDockerDependenciesAcceptanceInstallSqlite() {
        return new ScriptTask()
            .description("Start docker siblings for acceptance test install sqlite")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "cd Build/testing-docker/bamboo\n" +
                    "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                    "docker-compose run start_dependencies_acceptance_install_sqlite"
            );
    }

    /**
     * Start docker sibling containers to execute acceptance backend tests on mariadb
     */
    private Task getTaskDockerDependenciesAcceptanceBackendMariadb10() {
        return new ScriptTask()
            .description("Start docker siblings for acceptance test backend mariadb")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "cd Build/testing-docker/bamboo\n" +
                    "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                    "docker-compose run start_dependencies_acceptance_backend_mariadb10"
            );
    }

    /**
     * Start docker sibling containers to execute functional tests on mariadb
     */
    private Task getTaskDockerDependenciesFunctionalMariadb10() {
        return new ScriptTask()
            .description("Start docker siblings for functional tests on mariadb")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "cd Build/testing-docker/bamboo\n" +
                    "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                    "docker-compose run start_dependencies_functional_mariadb10"
            );
    }

    /**
     * Start docker sibling containers to execute functional tests on mssql
     */
    private Task getTaskDockerDependenciesFunctionalMssql() {
        return new ScriptTask()
            .description("Start docker siblings for functional tests on mssql")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "cd Build/testing-docker/bamboo\n" +
                    "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                    "docker-compose run start_dependencies_functional_mssql"
            );
    }

    /**
     * Start docker sibling containers to execute functional tests on postgres
     */
    private Task getTaskDockerDependenciesFunctionalPostgres10() {
        return new ScriptTask()
            .description("Start docker siblings for functional tests on postgres10")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "cd Build/testing-docker/bamboo\n" +
                    "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                    "docker-compose run start_dependencies_functional_postgres10"
            );
    }

    /**
     * Start docker sibling containers to execute functional tests on sqlite
     */
    private Task getTaskDockerDependenciesFunctionalSqlite() {
        return new ScriptTask()
            .description("Start docker siblings for functional tests on sqlite")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "cd Build/testing-docker/bamboo\n" +
                    "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                    "docker-compose run start_dependencies_functional_sqlite"
            );
    }

    /**
     * Stop started docker containers
     */
    private Task getTaskStopDockerDependencies() {
        return new ScriptTask()
            .description("Stop docker siblings")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "cd Build/testing-docker/bamboo\n" +
                    "docker-compose down -v"
            );
    }

    /**
     * Task to split functional jobs into chunks
     */
    private Task getTaskSplitFunctionalJobs(int numberOfJobs, String requirementIdentifier) {
        return new ScriptTask()
            .description("Create list of test files to execute per job")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                    "function splitFunctionalTests() {\n" +
                    "    docker run \\\n" +
                    "        -u ${HOST_UID} \\\n" +
                    "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                    "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                    "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                    "        --rm \\\n" +
                    "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                    "        bin/bash -c \"cd ${PWD}; ./" + this.testingFrameworkBuildPath + "Scripts/splitFunctionalTests.php $*\"\n" +
                    "}\n" +
                    "\n" +
                    "splitFunctionalTests " + numberOfJobs + " -v"
            );
    }

    /**
     * Requirement for docker 1.0 set by bamboo-agents
     */
    private Requirement getRequirementDocker10() {
        return new Requirement("system.hasDocker")
            .matchValue("1.0")
            .matchType(Requirement.MatchType.EQUALS);
    }

    /**
     * A bash header for script tasks forking a bash if needed
     */
    private String getScriptTaskBashInlineBody() {
        return
            "#!/bin/bash\n" +
                "\n" +
                "if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n" +
                "    bash \"$0\" \"$@\"\n" +
                "    exit \"$?\"\n" +
                "fi\n" +
                "\n" +
                "set -x\n" +
                "\n";
    }

    /**
     * A bash function aliasing 'composer' as docker command
     */
    private String getScriptTaskComposer(String requirementIdentifier) {
        return
            "function composer() {\n" +
                "    docker run \\\n" +
                "        -u ${HOST_UID} \\\n" +
                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                "        -e COMPOSER_ROOT_VERSION=${COMPOSER_ROOT_VERSION} \\\n" +
                "        -e HOME=${HOME} \\\n" +
                "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                "        --rm \\\n" +
                "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                "        bin/bash -c \"cd ${PWD}; composer $*\"\n" +
                "}\n" +
                "\n";
    }
}
