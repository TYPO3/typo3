package core;

import java.util.ArrayList;

import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.Variable;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Plan;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.Artifact;
import com.atlassian.bamboo.specs.api.builders.plan.branches.BranchCleanup;
import com.atlassian.bamboo.specs.api.builders.plan.branches.PlanBranchManagement;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.api.builders.requirement.Requirement;
import com.atlassian.bamboo.specs.api.builders.task.Task;
import com.atlassian.bamboo.specs.builders.task.CheckoutItem;
import com.atlassian.bamboo.specs.builders.task.CommandTask;
import com.atlassian.bamboo.specs.builders.task.NpmTask;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.task.TestParserTask;
import com.atlassian.bamboo.specs.builders.task.VcsCheckoutTask;
import com.atlassian.bamboo.specs.builders.trigger.RemoteTrigger;
import com.atlassian.bamboo.specs.builders.trigger.RepositoryPollingTrigger;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;
import com.atlassian.bamboo.specs.model.task.TestParserTaskProperties;
import com.atlassian.bamboo.specs.util.BambooServer;

/**
 * Core master pre-merge test plan.
 */
@BambooSpec
public class PreMergeSpec {

    protected int numberOfAcceptanceTestJobs = 8;
    protected int numberOfFunctionalMysqlJobs = 10;
    protected int numberOfFunctionalMssqlJobs = 10;
    protected int numberOfFunctionalPgsqlJobs = 10;

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
     * Run main to publish plan on Bamboo
     */
    public static void main(final String[] args) throws Exception {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer("https://bamboo.typo3.com:443");
        Plan plan = new PreMergeSpec().createPlan();
        bambooServer.publish(plan);
    }

    /**
     * Core master pre-merge plan is in "TYPO3 core" project of bamboo
     */
    Project project() {
        return new Project().name("TYPO3 Core").key("CORE");
    }

    /**
     * Returns full Plan definition
     */
    Plan createPlan() {
        // PREPARATION stage
        ArrayList<Job> jobsPreparationStage = new ArrayList<Job>();

        // Label task
        Job jobLabel = new Job("Create build labels", new BambooKey("CLFB"))
            .description("Create changeId and patch set labels from variable access and parsing result of a dummy task")
            .tasks(
                new ScriptTask()
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody("echo \"I'm just here for the labels!\"")
            );
        jobsPreparationStage.add(jobLabel);

        // Composer validate test
        Job jobValidateComposer = new Job("Validate composer.json", new BambooKey("VC"))
            .description("Validate composer.json before actual tests are executed")
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                new CommandTask()
                    .description("composer validate")
                    .executable("composer").argument("validate")
                    .environmentVariables(this.composerRootVersionEnvironment)
            );
        jobsPreparationStage.add(jobValidateComposer);

        // Compile preparation stage
        Stage stagePreparation = new Stage("Preparation")
            .jobs(jobsPreparationStage.toArray(new Job[jobsPreparationStage.size()]));


        // MAIN stage
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        // Installer acceptance test job with mysql
        Job jobAcceptanceInstallWithMysql = new Job("Accept install mysql", new BambooKey("ACINSTMY"))
            .description("Install TYPO3 on mysql and create empty frontend page")
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
                new Requirement("system.phpVersion")
                    .matchValue("7\\.0|7\\.1")
                    .matchType(Requirement.MatchType.MATCHES)
            )
            .artifacts(new Artifact()
                .name("Test Report")
                .copyPattern("typo3temp/var/tests/AcceptanceReportsInstallMysql/")
                .shared(false));
        jobsMainStage.add(jobAcceptanceInstallWithMysql);

        // Acceptance test jobs
        for (int i=1; i<=this.numberOfAcceptanceTestJobs; i++) {
            Job jobAcceptanceMysql = new Job("Accept mysql 0" + i, new BambooKey("ACMYSQL0" + i))
                .description("Run acceptance tests")
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
                            "./" + this.testingFrameworkBuildPath + "Scripts/splitAcceptanceTests.sh " + this.numberOfAcceptanceTestJobs + "\n"
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
                    new Requirement("system.phpVersion")
                        .matchValue("7\\.0|7\\.1")
                        .matchType(Requirement.MatchType.MATCHES)
                )
                .artifacts(new Artifact()
                    .name("Test Report")
                    .copyPattern("typo3temp/var/tests/AcceptanceReports/")
                    .shared(false)
                );
            jobsMainStage.add(jobAcceptanceMysql);
        }

        // CGL checker
        Job jobCglCheck = new Job("Integration CGL", new BambooKey("CGLCHECK"))
            .description("Check coding guidelines by executing Build/Scripts/cglFixMyCommit.sh script")
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                this.getTaskComposerInstall(),
                new ScriptTask()
                    .description("Execute cgl check script")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                        "./Build/Scripts/cglFixMyCommit.sh dryrun\n"
                    )
            )
            .requirements(
                new Requirement("system.phpVersion")
                    .matchValue("7\\.0|7\\.1")
                    .matchType(Requirement.MatchType.MATCHES)
            );
        jobsMainStage.add(jobCglCheck);

        // Exception code checker, xlf, permissions, rst file check
        Job jobIntegration = new Job("Integration various", new BambooKey("CDECC"))
            .description("Checks duplicate exceptions, git submodules, xlf files, permissions, rst")
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
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
                    )
            );
        jobsMainStage.add(jobIntegration);

        // Functional tests mysql php70 or php71
        for (int i=0; i<this.numberOfFunctionalMysqlJobs; i++) {
            Job jobFunctionalMysql = new Job("Func mysql 0" + i, new BambooKey("FMY0" + i))
                .description("Run functional tests on mysql DB")
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(),
                    this.getTaskComposerInstall(),
                    this.getTaskSplitFunctionalJobs(numberOfFunctionalMysqlJobs),
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
                    new Requirement("system.phpVersion")
                        .matchValue("7\\.0|7\\.1")
                        .matchType(Requirement.MatchType.MATCHES)
                );
            jobsMainStage.add(jobFunctionalMysql);
        }

        // Functional tests mssql php70
        for (int i=0; i<this.numberOfFunctionalMssqlJobs; i++) {
            Job jobFunctionalMssql = new Job("Func mssql php70 0" + i, new BambooKey("FMS0" + i))
                .description("Run functional tests on mssql DB")
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(),
                    this.getTaskComposerInstall(),
                    this.getTaskSplitFunctionalJobs(numberOfFunctionalMssqlJobs),
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
                    new Requirement("system.phpVersion")
                        .matchValue("7.0")
                        .matchType(Requirement.MatchType.EQUALS)
                );
            jobsMainStage.add(jobFunctionalMssql);
        }

        // Functional tests postgres php71
        for (int i=0; i<this.numberOfFunctionalPgsqlJobs; i++) {
            Job jobFunctionalPgsql = new Job("Func pgsql php71 0" + i, new BambooKey("FPG0" + i))
                .description("Run functional tests on pgsql DB")
                .tasks(
                    this.getTaskGitCloneRepository(),
                    this.getTaskGitCherryPick(),
                    this.getTaskComposerInstall(),
                    this.getTaskSplitFunctionalJobs(numberOfFunctionalPgsqlJobs),
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
                    new Requirement("system.phpVersion")
                        .matchValue("7.1")
                        .matchType(Requirement.MatchType.EQUALS)
                );
            jobsMainStage.add(jobFunctionalPgsql);
        }

        // JavaScript unit tests
        Job jobUnitJavaScript = new Job("Unit JavaScript", new BambooKey("JSUT"))
            .description("Run JavaScript unit tests")
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                this.getTaskComposerInstall(),
                new NpmTask()
                    .description("npm install in Build/ dir")
                    .nodeExecutable("Node.js")
                    .workingSubdirectory("Build/")
                    .command("install"),
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
                new Requirement("system.phpVersion")
                    .matchValue("7\\.0|7\\.1")
                    .matchType(Requirement.MatchType.MATCHES)
            )
            .artifacts(
                new Artifact()
                    .name("Clover Report (System)")
                    .copyPattern("**/*.*")
                    .location("Build/target/site/clover")
                    .shared(false)
            );
        jobsMainStage.add(jobUnitJavaScript);

        // Lint php files with php70
        Job jobLintPhp70 = new Job("Lint php70", new BambooKey("LP70"))
            .description("Run php -l on source files for linting")
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
            .requirements(new Requirement("system.phpVersion")
                .matchValue("7.0")
                .matchType(Requirement.MatchType.EQUALS)
            );
        jobsMainStage.add(jobLintPhp70);

        // Lint php files with php71
        Job jobLintPhp71 = new Job("Lint php71", new BambooKey("LP71"))
            .description("Run php -l on source files for linting")
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
            .requirements(new Requirement("system.phpVersion")
                .matchValue("7.1")
                .matchType(Requirement.MatchType.EQUALS)
            );
        jobsMainStage.add(jobLintPhp71);

        // Lint scss and typescript files
        Job jobLintScssTypescript = new Job("Lint scss ts", new BambooKey("LSTS"))
            .description("Run npm lint in Build/ dir")
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                new NpmTask()
                    .description("npm install in Build/ dir")
                    .nodeExecutable("Node.js")
                    .workingSubdirectory("Build/")
                    .command("install"),
                new NpmTask()
                    .description("Run npm lint")
                    .nodeExecutable("Node.js")
                    .workingSubdirectory("Build/")
                    .command("run lint")
            )
            .requirements(
                new Requirement("system.imageVersion")
            );
        jobsMainStage.add(jobLintScssTypescript);

        // Unit tests php70
        Job jobUnitPhp70 = new Job("Unit php70", new BambooKey("UT70"))
            .description("Run unit tests on PHP 7.0")
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
            .requirements(new Requirement("system.phpVersion")
                .matchValue("7.0")
                .matchType(Requirement.MatchType.EQUALS)
            );
        jobsMainStage.add(jobUnitPhp70);

        // Unit tests php70 random 01
        Job jobUnitPhp70Random01 = new Job("Unit php70 random 01", new BambooKey("UT70R01"))
            .description("Run unit tests on PHP 7.0 in random order 01")
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
            .requirements(new Requirement("system.phpVersion")
                .matchValue("7.0")
                .matchType(Requirement.MatchType.EQUALS)
            );
        jobsMainStage.add(jobUnitPhp70Random01);

        // Unit tests php70 random 02
        Job jobUnitPhp70Random02 = new Job("Unit php70 random 02", new BambooKey("UT70R02"))
            .description("Run unit tests on PHP 7.0 in random order 02")
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
            .requirements(new Requirement("system.phpVersion")
                .matchValue("7.0")
                .matchType(Requirement.MatchType.EQUALS)
            );
        jobsMainStage.add(jobUnitPhp70Random02);

        // Unit tests php71
        Job jobUnitPhp71 = new Job("Unit php71", new BambooKey("UT71"))
            .description("Run unit tests on PHP 7.1")
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
            .requirements(new Requirement("system.phpVersion")
                .matchValue("7.1")
                .matchType(Requirement.MatchType.EQUALS)
            );
        jobsMainStage.add(jobUnitPhp71);

        // Unit tests php71 random 01
        Job jobUnitPhp71Random01 = new Job("Unit php71 random 01", new BambooKey("UT71R01"))
            .description("Run unit tests on PHP 7.1 in random order 01")
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
            .requirements(new Requirement("system.phpVersion")
                .matchValue("7.1")
                .matchType(Requirement.MatchType.EQUALS)
            );
        jobsMainStage.add(jobUnitPhp71Random01);

        // Unit tests php71 random 02
        Job jobUnitPhp71Random02 = new Job("Unit php71 random 02", new BambooKey("UT71R02"))
            .description("Run unit tests on PHP 7.1 in random order 02")
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
            .requirements(new Requirement("system.phpVersion")
                .matchValue("7.1")
                .matchType(Requirement.MatchType.EQUALS)
            );
        jobsMainStage.add(jobUnitPhp71Random02);

        // Compile main stage
        Stage stageMainStage = new Stage("Main stage")
            .jobs(jobsMainStage.toArray(new Job[jobsMainStage.size()]));

        // Compile plan
        return new Plan(project(), "Core master pre-merge", "GTC")
            .description("Execute TYPO3 core master pre-merge tests. Auto generated! See Build/bamboo of core git repository.")
            .stages(
                stagePreparation,
                stageMainStage
            )
            .linkedRepositories("git.typo3.org Core")
            .triggers(
                new RepositoryPollingTrigger()
                    .name("Repository polling for post-merge builds"),
                new RemoteTrigger()
                    .name("Remote trigger for pre-merge builds")
                    .description("Gerrit")
                    .triggerIPAddresses("5.10.165.218,91.184.35.13"))
            .variables(
                new Variable("changeUrl", ""),
                new Variable("patchset", "")
            )
            .planBranchManagement(
                new PlanBranchManagement()
                    .delete(new BranchCleanup())
                    .notificationForCommitters()
            );
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
                "    gerrit-cherry-pick https://review.typo3.org/Packages/TYPO3.CMS $CHANGEURLID/$PATCHSET\n" +
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
            .description("Start xvfb, selenium, php web server, prepare chrome environment")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                "# start xvfb until chrome headless can be used\n" +
                "/sbin/start-stop-daemon --start --quiet --pidfile xvfb.pid --make-pidfile --background --exec /usr/bin/Xvfb :99\n" +
                "\n" +
                "# the display chrome should render to (xvfb)\n" +
                "export DISPLAY=\":99\"\n" +
                "\n" +
                "PATH=$PATH:./bin DBUS_SESSION_BUS_ADDRESS=/dev/null ./bin/selenium-server-standalone >/dev/null 2>&1 & \n" +
                "echo $! > selenium.pid\n" +
                "\n" +
                "# Wait for selenium server to load\n" +
                "until $(curl --output /dev/null --silent --head --fail http://localhost:4444/wd/hub); do\n" +
                "    printf '.'\n    sleep 1\n" +
                "done\n" +
                "\n" +
                "php -S localhost:8000 >/dev/null 2>&1 &\n" +
                "echo $! > phpserver.pid\n" +
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
            .description("Stop acceptance test services like selenium and friends")
            .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
            .inlineBody(
                this.getScriptTaskBashInlineBody() +
                "kill `cat phpserver.pid`\n" +
                "kill `cat selenium.pid`\n" +
                "kill `cat xvfb.pid`\n"
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
