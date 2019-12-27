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

import java.util.ArrayList;

/**
 * Abstract class with common methods of pre-merge and nightly plan
 */
abstract public class AbstractCoreSpec {

    static String bambooServerName = "https://bamboo.typo3.com:443";
    static String projectName = "TYPO3 Core";
    static String projectKey = "CORE";
    /**
     * @todo This can be removed if acceptance mysql tests are rewritten and active again
     */
    protected String credentialsMysql =
            "typo3DatabaseName=\"func\"" +
                    " typo3DatabaseUsername=\"funcu\"" +
                    " typo3DatabasePassword=\"funcp\"" +
                    " typo3DatabaseHost=\"localhost\"" +
                    " typo3InstallToolPassword=\"klaus\"";
    /**
     * @todo This can be removed if acceptance pgsql tests are rewritten and active again
     */
    protected String credentialsPgsql =
            "typo3DatabaseDriver=\"pdo_pgsql\"" +
                    " typo3DatabaseName=\"func\"" +
                    " typo3DatabaseUsername=\"bamboo\"" +
                    " typo3DatabaseHost=\"localhost\"" +
                    " typo3DatabasePort=\"5433\"" +
                    " typo3InstallToolPassword=\"klaus\"";
    private String composerRootVersionEnvironment = "COMPOSER_ROOT_VERSION=8.7.30";
    private String testingFrameworkBuildPath = "vendor/typo3/testing-framework/Resources/Core/Build/";

    /**
     * Default permissions on core plans
     */
    PlanPermissions getDefaultPlanPermissions(String projectKey, String planKey) {
        return new PlanPermissions(new PlanIdentifier(projectKey, planKey))
                .permissions(new Permissions()
                        .groupPermissions("TYPO3 GmbH", PermissionType.ADMIN, PermissionType.VIEW, PermissionType.EDIT, PermissionType.BUILD, PermissionType.CLONE)
                        .groupPermissions("TYPO3 Core Team", PermissionType.VIEW, PermissionType.BUILD)
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
                        .groupPermissions("TYPO3 GmbH", PermissionType.ADMIN, PermissionType.VIEW, PermissionType.EDIT, PermissionType.BUILD, PermissionType.CLONE)
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
    PluginConfiguration getDefaultJobPluginConfiguration() {
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
     * Job acceptance test installs system on mysql
     */
    Job getJobAcceptanceTestInstallMysql(String requirementIdentifier, Boolean isSecurity) {
        return new Job("Accept inst my " + requirementIdentifier, new BambooKey("ACINSTMY" + requirementIdentifier))
                .description("Install TYPO3 on mariadb and load introduction package " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                        this.getTaskGitCloneRepository(),
                        this.getTaskGitCherryPick(isSecurity),
                        this.getTaskStopDanglingContainers(),
                        this.getTaskComposerInstall(requirementIdentifier),
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
    Job getJobAcceptanceTestInstallPgsql(String requirementIdentifier, Boolean isSecurity) {
        return new Job("Accept inst pg " + requirementIdentifier, new BambooKey("ACINSTPG" + requirementIdentifier))
                .description("Install TYPO3 on pgsql and load introduction package " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                        this.getTaskGitCloneRepository(),
                        this.getTaskGitCherryPick(isSecurity),
                        this.getTaskStopDanglingContainers(),
                        this.getTaskComposerInstall(requirementIdentifier),
                        this.getTaskPrepareAcceptanceTest(),
                        this.getTaskDockerDependenciesAcceptanceInstallPostgres95(),
                        new ScriptTask()
                                .description("Install TYPO3 on postgresql 9.5")
                                .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                                .inlineBody(
                                        this.getScriptTaskBashInlineBody() +
                                                "function codecept() {\n" +
                                                "    docker run \\\n" +
                                                "        -u ${HOST_UID} \\\n" +
                                                "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                                "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                                "        -e typo3InstallPostgresqlDatabaseHost=postgres9-5 \\\n" +
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
     * Jobs for mysql based acceptance tests
     */
    ArrayList<Job> getJobsAcceptanceTestsBackendMysql(int numberOfChunks, String requirementIdentifier, Boolean isSecurity) {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 1; i <= numberOfChunks; i++) {
            jobs.add(new Job("Accept my " + requirementIdentifier + " 0" + i, new BambooKey("ACMY" + requirementIdentifier + "0" + i))
                    .description("Run acceptance tests" + requirementIdentifier)
                    .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                    .tasks(
                            this.getTaskGitCloneRepository(),
                            this.getTaskGitCherryPick(isSecurity),
                            this.getTaskStopDanglingContainers(),
                            this.getTaskComposerInstall(requirementIdentifier),
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
                                                    "        bin/bash -c \"cd ${PWD}; ./" + this.testingFrameworkBuildPath + "Scripts/splitAcceptanceTests.sh $*\"\n" +
                                                    "}\n" +
                                                    "\n" +
                                                    "splitAcceptanceTests " + numberOfChunks
                                    ),
                            new ScriptTask()
                                    .description("Execute codeception acceptance suite group " + i)
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
     * Jobs for mysql based functional tests
     */
    ArrayList<Job> getJobsFunctionalTestsMysql(int numberOfChunks, String requirementIdentifier, Boolean isSecurity) {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 0; i < numberOfChunks; i++) {
            jobs.add(new Job("Func mysql " + requirementIdentifier + " 0" + i, new BambooKey("FMY" + requirementIdentifier + "0" + i))
                    .description("Run functional tests on mysql DB " + requirementIdentifier)
                    .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                    .tasks(
                            this.getTaskGitCloneRepository(),
                            this.getTaskGitCherryPick(isSecurity),
                            this.getTaskStopDanglingContainers(),
                            this.getTaskComposerInstall(requirementIdentifier),
                            this.getTaskDockerDependenciesFunctionalMariadb10(),
                            this.getTaskSplitFunctionalJobs(numberOfChunks),
                            new ScriptTask()
                                    .description("Run phpunit with functional chunk 0" + i)
                                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                                    .inlineBody(
                                            this.getScriptTaskBashInlineBody() +
                                                    "function phpunit() {\n" +
                                                    "    docker run \\\n" +
                                                    "        -u ${HOST_UID} \\\n" +
                                                    "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                                    "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
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
     * Jobs for mssql based functional tests
     */
    ArrayList<Job> getJobsFunctionalTestsMssql(int numberOfChunks, String requirementIdentifier, Boolean isSecurity) {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 0; i < numberOfChunks; i++) {
            jobs.add(new Job("Func mssql " + requirementIdentifier + " 0" + i, new BambooKey("FMS" + requirementIdentifier + "0" + i))
                    .description("Run functional tests on mssql DB " + requirementIdentifier)
                    .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                    .tasks(
                            this.getTaskGitCloneRepository(),
                            this.getTaskGitCherryPick(isSecurity),
                            this.getTaskStopDanglingContainers(),
                            this.getTaskComposerInstall(requirementIdentifier),
                            this.getTaskDockerDependenciesFunctionalMssql(),
                            this.getTaskSplitFunctionalJobs(numberOfChunks),
                            new ScriptTask()
                                    .description("Run phpunit with functional chunk 0" + i)
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
                                                    "        -e typo3DatabaseHost=mssql2017cu17 \\\n" +
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
    ArrayList<Job> getJobsFunctionalTestsPgsql(int numberOfChunks, String requirementIdentifier, Boolean isSecurity) {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 0; i < numberOfChunks; i++) {
            jobs.add(new Job("Func pgsql " + requirementIdentifier + " 0" + i, new BambooKey("FPG" + requirementIdentifier + "0" + i))
                    .description("Run functional tests on pgsql DB " + requirementIdentifier)
                    .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                    .tasks(
                            this.getTaskGitCloneRepository(),
                            this.getTaskGitCherryPick(isSecurity),
                            this.getTaskStopDanglingContainers(),
                            this.getTaskComposerInstall(requirementIdentifier),
                            this.getTaskDockerDependenciesFunctionalPostgres95(),
                            this.getTaskSplitFunctionalJobs(numberOfChunks),
                            new ScriptTask()
                                    .description("Run phpunit with functional chunk 0" + i)
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
                                                    "        -e typo3DatabaseHost=postgres9-5 \\\n" +
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
     * Job with various smaller script tests
     */
    Job getJobIntegrationVarious(String requirementIdentifier, Boolean isSecurity) {
        // Exception code checker, xlf, permissions, rst file check
        return new Job("Integration various", new BambooKey("CDECC"))
                .description("Checks duplicate exceptions, git submodules, xlf files, permissions, rst")
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                        this.getTaskGitCloneRepository(),
                        this.getTaskGitCherryPick(isSecurity),
                        this.getTaskStopDanglingContainers(),
                        this.getTaskComposerInstall(requirementIdentifier),
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
    Job getJobUnitJavaScript(String requirementIdentifier, Boolean isSecurity) {
        return new Job("Unit JavaScript", new BambooKey("JSUT"))
                .description("Run JavaScript unit tests")
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                        this.getTaskGitCloneRepository(),
                        this.getTaskGitCherryPick(isSecurity),
                        this.getTaskStopDanglingContainers(),
                        this.getTaskComposerInstall(requirementIdentifier),
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
                                                "karma start " + this.testingFrameworkBuildPath + "Configuration/JSUnit/karma.conf.js --single-run"
                                )
                )
                .finalTasks(
                        new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                                .resultDirectories("typo3temp/var/tests/*")
                )
                .artifacts(
                        new Artifact()
                                .name("Clover Report (System)")
                                .copyPattern("**/*.*")
                                .location("Build/target/site/clover")
                                .shared(false)
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
    Job getJobUnitPhp(String requirementIdentifier, Boolean isSecurity) {
        return new Job("Unit " + requirementIdentifier, new BambooKey("UT" + requirementIdentifier))
                .description("Run unit tests " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                        this.getTaskGitCloneRepository(),
                        this.getTaskGitCherryPick(isSecurity),
                        this.getTaskStopDanglingContainers(),
                        this.getTaskComposerInstall(requirementIdentifier),
                        this.getTaskDockerDependenciesUnit(),
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
                                                "        -e typo3TestingRedisHost=redis4 \\\n" +
                                                "        -e typo3TestingMemcachedHost=memcached1-5 \\\n" +
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
                        this.getTaskStopDockerDependencies(),
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
    ArrayList<Job> getJobUnitPhpRandom(int numberOfRuns, String requirementIdentifier, Boolean isSecurity) {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i = 0; i < numberOfRuns; i++) {
            jobs.add(new Job("Unit " + requirementIdentifier + " random 0" + i, new BambooKey("UTR" + requirementIdentifier + "0" + i))
                    .description("Run unit tests on " + requirementIdentifier + " in random order 0" + i)
                    .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                    .tasks(
                            this.getTaskGitCloneRepository(),
                            this.getTaskGitCherryPick(isSecurity),
                            this.getTaskStopDanglingContainers(),
                            this.getTaskComposerInstall(requirementIdentifier),
                            this.getTaskDockerDependenciesUnit(),
                            new ScriptTask()
                                    .description("Run phpunit-randomizer")
                                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                                    .inlineBody(
                                            this.getScriptTaskBashInlineBody() +
                                                    "function phpunitRandomizer() {\n" +
                                                    "    docker run \\\n" +
                                                    "        -u ${HOST_UID} \\\n" +
                                                    "        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/passwd:/etc/passwd \\\n" +
                                                    "        -v ${BAMBOO_COMPOSE_PROJECT_NAME}_bamboo-data:/srv/bamboo/xml-data/build-dir/ \\\n" +
                                                    "        -e typo3TestingRedisHost=redis4 \\\n" +
                                                    "        -e typo3TestingMemcachedHost=memcached1-5 \\\n" +
                                                    "        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n" +
                                                    "        --network ${BAMBOO_COMPOSE_PROJECT_NAME}_test \\\n" +
                                                    "        --rm \\\n" +
                                                    "        typo3gmbh/" + requirementIdentifier.toLowerCase() + ":latest \\\n" +
                                                    "        bin/bash -c \"cd ${PWD}; php -n -c /etc/php/cli-no-xdebug/php.ini bin/phpunit-randomizer $*\"\n" +
                                                    "}\n" +
                                                    "\n" +
                                                    "phpunitRandomizer --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "UnitTests.xml --order rand"
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
    Task getTaskGitCloneRepository() {
        return new VcsCheckoutTask()
                .description("Checkout git core")
                .checkoutItems(new CheckoutItem().defaultRepository());
    }

    /**
     * Task definition to cherry pick a patch set from gerrit on top of cloned core
     */
    Task getTaskGitCherryPick(Boolean isSecurity) {
        if (isSecurity) {
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
                                    "    gerrit-cherry-pick https://review.typo3.org/Teams/Security/TYPO3v4-Core $CHANGEURLID/$PATCHSET || exit 1\n" +
                                    "fi\n"
                    );
        } else {
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
                                    "    gerrit-cherry-pick https://review.typo3.org/Packages/TYPO3.CMS $CHANGEURLID/$PATCHSET || exit 1\n" +
                                    "fi\n"
                    );
        }
    }

    /**
     * Safety net task executed before other task that call containers to
     * stop any dangling containers from a previous run on this agent.
     */
    Task getTaskStopDanglingContainers() {
        return new ScriptTask()
                .description("Stop dangling containers")
                .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                                "cd Build/testing-docker/bamboo\n" +
                                "docker-compose down -v\n" +
                                "docker rm -f ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc\n" +
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
     * Task to prepare an acceptance test starting selenium and others
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
    private Task getTaskDockerDependenciesAcceptanceInstallPostgres95() {
        return new ScriptTask()
                .description("Start docker siblings for acceptance test install postgres")
                .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                                "cd Build/testing-docker/bamboo\n" +
                                "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                                "docker-compose run start_dependencies_acceptance_install_postgres9-5"
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
    private Task getTaskDockerDependenciesFunctionalPostgres95() {
        return new ScriptTask()
                .description("Start docker siblings for functional tests on postgres9-5")
                .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                                "cd Build/testing-docker/bamboo\n" +
                                "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                                "docker-compose run start_dependencies_functional_postgres9-5"
                );
    }

    /**
     * Start docker sibling containers to execute unit tests
     */
    private Task getTaskDockerDependenciesUnit() {
        return new ScriptTask()
                .description("Start docker siblings for unit tests")
                .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                                "cd Build/testing-docker/bamboo\n" +
                                "echo COMPOSE_PROJECT_NAME=${BAMBOO_COMPOSE_PROJECT_NAME}sib > .env\n" +
                                "docker-compose run start_dependencies_unit"
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
    private Task getTaskSplitFunctionalJobs(int numberOfJobs) {
        return new ScriptTask()
                .description("Create list of test files to execute per job")
                .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                                "./" + this.testingFrameworkBuildPath + "Scripts/splitFunctionalTests.sh " + numberOfJobs
                );
    }

    /**
     * Requirement for docker 1.0 set by bamboo-agents
     */
    Requirement getRequirementDocker10() {
        return new Requirement("system.hasDocker")
                .matchValue("1.0")
                .matchType(Requirement.MatchType.EQUALS);
    }

    /**
     * A bash header for script tasks forking a bash if needed
     */
    String getScriptTaskBashInlineBody() {
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
