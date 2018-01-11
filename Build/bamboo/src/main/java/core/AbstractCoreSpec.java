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

import java.util.ArrayList;

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
import com.atlassian.bamboo.specs.builders.task.CommandTask;
import com.atlassian.bamboo.specs.builders.task.NpmTask;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.task.TestParserTask;
import com.atlassian.bamboo.specs.builders.task.VcsCheckoutTask;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;
import com.atlassian.bamboo.specs.model.task.TestParserTaskProperties;
import com.atlassian.bamboo.specs.util.MapBuilder;

/**
 * Abstract class with common methods of pre-merge and nightly plan
 */
abstract public class AbstractCoreSpec {

    protected static String bambooServerName = "https://bamboo.typo3.com:443";
    protected static String projectName = "TYPO3 Core";
    protected static String projectKey = "CORE";

    protected String composerRootVersionEnvironment = "COMPOSER_ROOT_VERSION=9.0.0";

    protected String testingFrameworkBuildPath = "vendor/typo3/testing-framework/Resources/Core/Build/";

    protected String credentialsMysql =
        "typo3DatabaseName=\"func\"" +
        " typo3DatabaseUsername=\"funcu\"" +
        " typo3DatabasePassword=\"funcp\"" +
        " typo3DatabaseHost=\"localhost\"" +
        " typo3InstallToolPassword=\"klaus\"";

    protected String credentialsMssql =
        "typo3DatabaseDriver=\"sqlsrv\"" +
        " typo3DatabaseName=\"func\"" +
        " typo3DatabasePassword='Test1234!'" +
        " typo3DatabaseUsername=\"SA\"" +
        " typo3DatabaseHost=\"localhost\"" +
        " typo3DatabasePort=\"1433\"" +
        " typo3DatabaseCharset=\"utf-8\"" +
        " typo3InstallToolPassword=\"klaus\"";

    protected String credentialsPgsql =
        "typo3DatabaseDriver=\"pdo_pgsql\"" +
        " typo3DatabaseName=\"func\"" +
        " typo3DatabaseUsername=\"bamboo\"" +
        " typo3DatabaseHost=\"localhost\"" +
        " typo3InstallToolPassword=\"klaus\"";

    /**
     * Default permissions on core plans
     *
     * @param projectName
     * @param planName
     * @return
     */
    protected PlanPermissions getDefaultPlanPermissions(String projectKey, String planKey) {
        return new PlanPermissions(new PlanIdentifier(projectKey, planKey))
            .permissions(new Permissions()
            .groupPermissions("TYPO3 GmbH", PermissionType.ADMIN, PermissionType.VIEW, PermissionType.EDIT, PermissionType.BUILD, PermissionType.CLONE)
            .groupPermissions("TYPO3 Core Team", PermissionType.VIEW, PermissionType.BUILD)
            .loggedInUserPermissions(PermissionType.VIEW)
            .anonymousUserPermissionView()
        );
    }

    /**
     * Default plan plugin configuration
     *
     * @return
     */
    protected PluginConfiguration getDefaultPlanPluginConfiguration() {
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
     *
     * @return
     */
    protected PluginConfiguration getDefaultJobPluginConfiguration() {
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
     * Job composer validate
     */
    protected Job getJobComposerValidate() {
        return new Job("Validate composer.json", new BambooKey("VC"))
        .description("Validate composer.json before actual tests are executed")
        .pluginConfigurations(this.getDefaultJobPluginConfiguration())
        .tasks(
            this.getTaskGitCloneRepository(),
            this.getTaskGitCherryPick(),
            new CommandTask()
                .description("composer validate")
                .executable("composer").argument("validate")
                .environmentVariables(this.composerRootVersionEnvironment)
        )
        .cleanWorkingDirectory(true);
    }

    /**
     * Job acceptance test installs system on mysql
     *
     * @param Requirement requirement
     * @param String requirementIdentfier
     */
    protected Job getJobAcceptanceTestInstallMysql(Requirement requirement, String requirementIdentifier) {
        return new Job("Accept inst my " + requirementIdentifier, new BambooKey("ACINSTMY" + requirementIdentifier))
            .description("Install TYPO3 on mysql and create empty frontend page " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                this.getTaskComposerInstall(),
                this.getTaskPrepareAcceptanceTest(),
                new CommandTask()
                    .description("Execute codeception AcceptanceInstallMysql suite")
                    .executable("codecept")
                    .argument("run AcceptanceInstallMysql -d -c " + this.testingFrameworkBuildPath + "AcceptanceTestsInstallMysql.yml --xml reports.xml --html reports.html")
                    .environmentVariables(this.credentialsMysql)
            )
            .finalTasks(
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("typo3temp/var/tests/AcceptanceReportsInstallMysql/reports.xml"),
                this.getTaskDeleteMysqlDatabases(),
                this.getTaskTearDownAcceptanceTestSetup()
            )
            .requirements(
                requirement
            )
            .artifacts(new Artifact()
                .name("Test Report")
                .copyPattern("typo3temp/var/tests/AcceptanceReportsInstallMysql/")
                .shared(false)
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job acceptance test installs system and introduction package on pgsql
     *
     * @param Requirement requirement
     * @param String requirementIdentfier
     */
    protected Job getJobAcceptanceTestInstallPgsql(Requirement requirement, String requirementIdentifier) {
        return new Job("Accept inst pg " + requirementIdentifier, new BambooKey("ACINSTPG" + requirementIdentifier))
        .description("Install TYPO3 on pgsql and load introduction package " + requirementIdentifier)
        .pluginConfigurations(this.getDefaultJobPluginConfiguration())
        .tasks(
            this.getTaskGitCloneRepository(),
            this.getTaskGitCherryPick(),
            this.getTaskComposerInstall(),
            this.getTaskPrepareAcceptanceTest(),
            new CommandTask()
                .description("Execute codeception AcceptanceInstallPgsql suite")
                .executable("codecept")
                .argument("run AcceptanceInstallPgsql -d -c " + this.testingFrameworkBuildPath + "AcceptanceTestsInstallPgsql.yml --xml reports.xml --html reports.html")
                .environmentVariables(this.credentialsPgsql)
        )
        .finalTasks(
            new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                .resultDirectories("typo3temp/var/tests/AcceptanceReportsInstallPgsql/reports.xml"),
            this.getTaskDeletePgsqlDatabases(),
            this.getTaskTearDownAcceptanceTestSetup()
        )
        .requirements(
            requirement
        )
        .artifacts(new Artifact()
            .name("Test Report")
            .copyPattern("typo3temp/var/tests/AcceptanceReportsInstallPgsql/")
            .shared(false)
        )
        .cleanWorkingDirectory(true);
    }

    /**
     * Jobs for mysql based acceptance tests
     *
     * @param int numberOfChunks
     * @param Requirement requirement
     * @param String requirementIdentifier
     */
    protected ArrayList<Job> getJobsAcceptanceTestsMysql(int numberOfChunks, Requirement requirement, String requirementIdentifier) {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i=1; i<=numberOfChunks; i++) {
            jobs.add(new Job("Accept my " + requirementIdentifier + " 0" + i, new BambooKey("ACMY" + requirementIdentifier + "0" + i))
                .description("Run acceptance tests" + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(),
                    this.getTaskComposerInstall(),
                    this.getTaskPrepareAcceptanceTest(),
                    new ScriptTask()
                        .description("Split acceptance tests")
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                            "./" + this.testingFrameworkBuildPath + "Scripts/splitAcceptanceTests.sh " + numberOfChunks + "\n"
                        ),
                    new CommandTask()
                        .description("Execute codeception acceptance suite group " + i)
                        .executable("codecept")
                        .argument("run Acceptance -d -g AcceptanceTests-Job-" + i + " -c " + this.testingFrameworkBuildPath + "AcceptanceTests.yml --xml reports.xml --html reports.html")
                        .environmentVariables(this.credentialsMysql)
                )
                .finalTasks(
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("typo3temp/var/tests/AcceptanceReports/reports.xml"),
                    this.getTaskDeleteMysqlDatabases(),
                    this.getTaskTearDownAcceptanceTestSetup()
                )
                .requirements(
                    requirement
                )
                .artifacts(new Artifact()
                    .name("Test Report")
                    .copyPattern("typo3temp/var/tests/AcceptanceReports/")
                    .shared(false)
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Jobs for mysql based functional tests
     *
     * @param int numberOfChunks
     * @param Requirement requirement
     * @param String requirementIdentifier
     */
    protected ArrayList<Job> getJobsFunctionalTestsMysql(int numberOfChunks, Requirement requirement, String requirementIdentifier) {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i=0; i<numberOfChunks; i++) {
            jobs.add(new Job("Func mysql " + requirementIdentifier + " 0" + i, new BambooKey("FMY" + requirementIdentifier + "0" + i))
                .description("Run functional tests on mysql DB " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(),
                    this.getTaskComposerInstall(),
                    this.getTaskSplitFunctionalJobs(numberOfChunks),
                    new ScriptTask()
                        .description("Run phpunit with functional chunk 0" + i)
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                            "./bin/phpunit --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "FunctionalTests-Job-" + i + ".xml"
                        )
                        .environmentVariables(this.credentialsMysql)
                )
                .finalTasks(
                    this.getTaskDeleteMysqlDatabases(),
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    requirement
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Jobs for mssql based functional tests
     *
     * @param int numberOfChunks
     * @param Requirement requirement
     * @param String requirementIdentifier
     */
    protected ArrayList<Job> getJobsFunctionalTestsMssql(int numberOfChunks, Requirement requirement, String requirementIdentifier) {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i=0; i<numberOfChunks; i++) {
            jobs.add(new Job("Func mssql " + requirementIdentifier + " 0" + i, new BambooKey("FMS" + requirementIdentifier + "0" + i))
                .description("Run functional tests on mysql DB " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(),
                    this.getTaskComposerInstall(),
                    this.getTaskSplitFunctionalJobs(numberOfChunks),
                    new ScriptTask()
                        .description("Run phpunit with functional chunk 0" + i)
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                            "./bin/phpunit --exclude-group not-mssql --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "FunctionalTests-Job-" + i + ".xml"
                        )
                        .environmentVariables(this.credentialsMssql)
                )
                .finalTasks(
                    this.getTaskDeleteMssqlDatabases(),
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    requirement
                )
                .cleanWorkingDirectory(true)
                .enabled(false)
            );
        }

        return jobs;
    }

    /**
     * Jobs for pgsql based functional tests
     *
     * @param int numberOfChunks
     * @param Requirement requirement
     * @param String requirementIdentifier
     */
    protected ArrayList<Job> getJobsFunctionalTestsPgsql(int numberOfChunks, Requirement requirement, String requirementIdentifier) {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i=0; i<numberOfChunks; i++) {
            jobs.add(new Job("Func pgsql " + requirementIdentifier + " 0" + i, new BambooKey("FPG" + requirementIdentifier + "0" + i))
                .description("Run functional tests on pgsql DB " + requirementIdentifier)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(),
                    this.getTaskComposerInstall(),
                    this.getTaskSplitFunctionalJobs(numberOfChunks),
                    new ScriptTask()
                        .description("Run phpunit with functional chunk 0" + i)
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                            "./bin/phpunit --exclude-group not-postgres --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "FunctionalTests-Job-" + i + ".xml"
                        )
                        .environmentVariables(this.credentialsPgsql)
                )
                .finalTasks(
                    this.getTaskDeletePgsqlDatabases(),
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    requirement
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Job with integration test checking for valid @xy annotations
     */
    protected Job getJobIntegrationAnnotations() {
        return new Job("Integration annotations", new BambooKey("IANNO"))
            .description("Check docblock-annotations by executing Build/Scripts/annotationChecker.php script")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                this.getTaskComposerInstall(),
                new ScriptTask()
                    .description("Execute annotations check script")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                        "./Build/Scripts/annotationChecker.php\n"
                    )
            )
            .requirements(
                this.getRequirementPhpVersion72()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job with various smaller script tests
     */
    protected Job getJobIntegrationVarious() {
        // Exception code checker, xlf, permissions, rst file check
        return new Job("Integration various", new BambooKey("CDECC"))
            .description("Checks duplicate exceptions, git submodules, xlf files, permissions, rst")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                this.getTaskComposerInstall(),
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
                        "./Build/Scripts/validateRstFiles.sh"
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
                        "./Build/Scripts/extensionScannerRstFileReferences.php"
                    )
            )
            .requirements(
                this.getRequirementPhpVersion72()
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job for javascript unit tests
     */
    protected Job getJobUnitJavaScript() {
        return new Job("Unit JavaScript", new BambooKey("JSUT"))
            .description("Run JavaScript unit tests")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                this.getTaskComposerInstall(),
                new ScriptTask()
                    .description("yarn install in Build/ dir")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                        "yarn install"
                    )
                    .workingSubdirectory("Build/"),
                new ScriptTask()
                    .description("Run tests")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                        "./Build/node_modules/karma/bin/karma start " + this.testingFrameworkBuildPath + "Configuration/JSUnit/karma.conf.js --single-run"
                    )
            )
            .finalTasks(
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("typo3temp/var/tests/*")
            )
            .requirements(
                this.getRequirementPhpVersion72()
            )
            .artifacts(
                new Artifact()
                    .name("Clover Report (System)")
                    .copyPattern("**/*.*")
                    .location("Build/target/site/clover")
                    .shared(false)
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job for PHP lint
     *
     * @param Requirement requirement
     * @param String requirementIdentfier
     */
    protected Job getJobLintPhp(Requirement requirement, String requirementIdentifier) {
        return new Job("Lint " + requirementIdentifier, new BambooKey("L" + requirementIdentifier))
            .description("Run php -l on source files for linting " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                new ScriptTask()
                    .description("Run php lint")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                        "find . -name \\*.php -print0 | xargs -0 -n1 -P2 php -l >/dev/null\n"
                    )
            )
            .requirements(
                requirement
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job for lint npm scss and typescript
     */
    protected Job getJobLintScssTs() {
        return new Job("Lint scss ts", new BambooKey("LSTS"))
            .description("Run npm lint in Build/ dir")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                new ScriptTask()
                    .description("yarn install in Build/ dir")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                        "yarn install"
                    )
                    .workingSubdirectory("Build/"),
                new NpmTask()
                    .description("Run npm lint")
                    .nodeExecutable("Node.js")
                    .workingSubdirectory("Build/")
                    .command("run lint")
            )
            .requirements(
                new Requirement("system.imageVersion")
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job for unit testing PHP
     *
     * @param Requirement requirement
     * @param String requirementIdentfier
     */
    protected Job getJobUnitPhp(Requirement requirement, String requirementIdentifier) {
        return new Job("Unit " + requirementIdentifier, new BambooKey("UT" + requirementIdentifier))
            .description("Run unit tests " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                this.getTaskComposerInstall(),
                new ScriptTask()
                    .description("Run phpunit")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                        this.getScriptTaskBashPhpNoXdebug() +
                        "php_no_xdebug bin/phpunit --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "UnitTests.xml"
                    )
            )
            .finalTasks(
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("test-reports/phpunit.xml")
            )
            .requirements(
                requirement
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Job for unit testing deprecated PHP
     *
     * @param Requirement requirement
     * @param String requirementIdentfier
     */
    protected Job getJobUnitDeprecatedPhp(Requirement requirement, String requirementIdentifier) {
        return new Job("Unit deprecated " + requirementIdentifier, new BambooKey("UTD" + requirementIdentifier))
            .description("Run deprecated unit tests " + requirementIdentifier)
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                this.getTaskComposerInstall(),
                new ScriptTask()
                    .description("Run phpunit")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                        this.getScriptTaskBashPhpNoXdebug() +
                        "php_no_xdebug bin/phpunit --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "UnitTestsDeprecated.xml"
                    )
            )
            .finalTasks(
                new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                    .resultDirectories("test-reports/phpunit.xml")
            )
            .requirements(
                requirement
            )
            .cleanWorkingDirectory(true);
    }

    /**
     * Jobs for unit testing PHP in random test order
     *
     * @param int numberOfRuns
     * @param Requirement requirement
     * @param String requirementIdentfier
     */
    protected ArrayList<Job> getJobUnitPhpRandom(int numberOfRuns, Requirement requirement, String requirementIdentifier) {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (int i=0; i<numberOfRuns; i++) {
            jobs.add(new Job("Unit " + requirementIdentifier + " random 0" + i, new BambooKey("UTR" + requirementIdentifier + "0" + i))
                .description("Run unit tests on " + requirementIdentifier + " in random order 0" + i)
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(),
                    this.getTaskComposerInstall(),
                    new ScriptTask()
                        .description("Run phpunit-randomizer")
                        .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                        .inlineBody(
                            this.getScriptTaskBashInlineBody() +
                            this.getScriptTaskBashPhpNoXdebug() +
                            "php_no_xdebug bin/phpunit-randomizer --log-junit test-reports/phpunit.xml -c " + this.testingFrameworkBuildPath + "UnitTests.xml --order rand"
                        )
                )
                .finalTasks(
                    new TestParserTask(TestParserTaskProperties.TestType.JUNIT)
                        .resultDirectories("test-reports/phpunit.xml")
                )
                .requirements(
                    requirement
                )
                .cleanWorkingDirectory(true)
            );
        }

        return jobs;
    }

    /**
     * Task definition for basic core clone of linked default repository
     */
    protected Task getTaskGitCloneRepository() {
        return new VcsCheckoutTask()
            .description("Checkout git core")
            .checkoutItems(new CheckoutItem().defaultRepository())
            .cleanCheckout(true);
    }

    /**
     * Task definition to cherry pick a patch set from gerrit on top of cloned core
     */
    protected Task getTaskGitCherryPick() {
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

    /**
     * Task definition to execute composer install
     */
    protected Task getTaskComposerInstall() {
        return new CommandTask()
            .description("composer install")
            .executable("composer")
            .argument("install -n")
            .environmentVariables(this.composerRootVersionEnvironment);
    }

    /**
     * Task to prepare an acceptance test starting selenium and others
     */
    protected Task getTaskPrepareAcceptanceTest() {
        return new ScriptTask()
            .description("Start php web server, chromedriver, prepare environment")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                "php -S localhost:8000 >/dev/null 2>&1 &\n" +
                "echo $! > phpserver.pid\n" +
                "\n" +
                "./bin/chromedriver --url-base=/wd/hub >/dev/null 2>&1 &\n" +
                "echo $! > chromedriver.pid\n" +
                "\n" +
                "mkdir -p typo3temp/var/tests/\n"
            );
    }

    /**
     * Task to delete any created mysql test databases, used as final task
     */
    protected Task getTaskDeleteMysqlDatabases() {
        return new ScriptTask()
            .description("Delete mysql test dbs")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                "DB_STARTS_WITH=\"func_\"\n" +
                "MUSER=\"funcu\"\n" +
                "MPWD=\"funcp\"\n" +
                "MYSQL=\"mysql\"\n" +
                "DBS=\"$($MYSQL -u $MUSER -p\"$MPWD\" -Bse 'show databases')\"\n" +
                "\n" +
                "for db in $DBS; do\n" +
                "    if [[ \"$db\" == $DB_STARTS_WITH* ]]; then\n" +
                "        echo \"Deleting $db\"\n" +
                "        $MYSQL -u $MUSER -p\"$MPWD\" -Bse \"drop database $db\"\n" +
                "    fi\n" +
                "done\n"
            );
    }

    /**
     * Task to delete any created mssql test databases, used as final task
     */
    protected Task getTaskDeleteMssqlDatabases() {
        return new ScriptTask()
            .description("Delete mssql test dbs")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                "DBS=`/opt/mssql-tools/bin/sqlcmd -S localhost -U SA -P 'Test1234!' -Q 'select name from sys.databases' | grep '^func_'`\n" +
                "\n" +
                "for db in $DBS; do\n" +
                "    echo \"Deleteing $db\"\n" +
                "    /opt/mssql-tools/bin/sqlcmd -S localhost -U SA -P 'Test1234!' -Q \"drop database $db\"\n" +
                "done\n"
            );
    }

    /**
     * Task to delete any created pgsql test databases, used as final task
     */
    protected Task getTaskDeletePgsqlDatabases() {
        return new ScriptTask()
            .description("Delete pgsql test dbs")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                "DB_STARTS_WITH=\"func_\"\n" +
                "PGUSER=\"bamboo\"\n" +
                "DBS=\"$(/usr/bin/psql -qtA -c 'SELECT datname FROM pg_database WHERE datistemplate = false;' postgres)\"\n" +
                "\n" +
                "for db in $DBS; do\n" +
                "    if [[ \"$db\" == $DB_STARTS_WITH* ]]; then\n" +
                "        echo \"Deleting $db\"\n" +
                "        /usr/bin/psql -qtA -c \"DROP DATABASE $db\" postgres\n" +
                "    fi\n" +
                "done\n"
            );
    }

    /**
     * Task to stop selenium and friends, opposite of getTaskPrepareAcceptanceTest, used as final task
     */
    protected Task getTaskTearDownAcceptanceTestSetup() {
        return new ScriptTask()
            .description("Stop acceptance test services like chromedriver and friends")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                "kill `cat phpserver.pid`\n" +
                "kill `cat chromedriver.pid`\n"
            );
    }

    /**
     * Task to split functional jobs into chunks
     */
    protected Task getTaskSplitFunctionalJobs(int numberOfJobs) {
        return new ScriptTask()
            .description("Create list of test files to execute per job")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                "./" + this.testingFrameworkBuildPath + "Scripts/splitFunctionalTests.sh " + numberOfJobs
            );
    }

    /**
     * Requirement for php 7.0
     */
    protected Requirement getRequirementPhpVersion70() {
        return new Requirement("system.phpVersion")
            .matchValue("7.0")
            .matchType(Requirement.MatchType.EQUALS);
    }

    /**
     * Requirement for php 7.1
     */
    protected Requirement getRequirementPhpVersion71() {
        return new Requirement("system.phpVersion")
            .matchValue("7.1")
            .matchType(Requirement.MatchType.EQUALS);
    }

    /**
     * Requirement for php 7.2
     */
    protected Requirement getRequirementPhpVersion72() {
        return new Requirement("system.phpVersion")
            .matchValue("7.2")
            .matchType(Requirement.MatchType.EQUALS);
    }

    /**
     * Requirement for php 7.0 or 7.1
     */
    protected Requirement getRequirementPhpVersion70Or71() {
        return new Requirement("system.phpVersion")
            .matchValue("7\\.0|7\\.1")
            .matchType(Requirement.MatchType.MATCHES);
    }

    /**
     * Requirement for php 7.0 or 7.1 or 7.2
     */
    protected Requirement getRequirementPhpVersion70Or71Or72() {
        return new Requirement("system.phpVersion")
            .matchValue("7\\.0|7\\.1|7\\.2")
            .matchType(Requirement.MatchType.MATCHES);
    }

    /**
     * A bash header for script tasks forking a bash if needed
     */
    protected String getScriptTaskBashInlineBody() {
        return
            "#!/bin/bash\n" +
            "\n" +
            "if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n" +
            "    bash \"$0\" \"$@\"\n" +
            "    exit \"$?\"\n" +
            "fi\n" +
            "\n";
    }

    /**
     * A bash function providing a php bin without xdebug
     */
    protected String getScriptTaskBashPhpNoXdebug() {
        return
            "php_no_xdebug () {\n" +
            "    temporaryPath=\"$(mktemp -t php.XXXX).ini\"\n" +
            "    php -i | grep \"\\.ini\" | grep -o -e '\\(/[A-Za-z0-9._-]\\+\\)\\+\\.ini' | grep -v xdebug | xargs awk 'FNR==1{print \"\"}1' > \"${temporaryPath}\"\n" +
            "    php -n -c \"${temporaryPath}\" \"$@\"\n" +
            "    RETURN=$?\n" +
            "    rm -f \"${temporaryPath}\"\n" +
            "    exit $RETURN\n" +
            "}\n" +
            "\n";
    }
}
