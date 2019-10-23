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

import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.AtlassianModule;
import com.atlassian.bamboo.specs.api.builders.Variable;
import com.atlassian.bamboo.specs.api.builders.notification.AnyNotificationRecipient;
import com.atlassian.bamboo.specs.api.builders.notification.Notification;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Plan;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.plan.branches.BranchCleanup;
import com.atlassian.bamboo.specs.api.builders.plan.branches.PlanBranchManagement;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.api.builders.task.Task;
import com.atlassian.bamboo.specs.builders.notification.PlanCompletedNotification;
import com.atlassian.bamboo.specs.builders.trigger.ScheduledTrigger;
import com.atlassian.bamboo.specs.util.BambooServer;

import java.util.ArrayList;

/**
 * Core master nightly test plan.
 */
@BambooSpec
public class NightlySpec extends AbstractCoreSpec {

    private static String planName = "Core master nightly";
    private static String planKey = "GTN";

    private static int numberOfAcceptanceTestJobs = 8;
    private static int numberOfFunctionalMysqlJobs = 6;
    private static int numberOfFunctionalMssqlJobs = 16;
    private static int numberOfFunctionalPgsqlJobs = 6;
    private static int numberOfFunctionalSqliteJobs = 6;
    private static int numberOfUnitRandomOrderJobs = 2;

    private String[] phpVersions = {"PHP72", "PHP73", "PHP74"};

    /**
     * Run main to publish plan on Bamboo
     */
    public static void main(final String[] args) throws Exception {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer(bambooServerName);
        bambooServer.publish(new NightlySpec().createPlan());
        bambooServer.publish(new NightlySpec().getDefaultPlanPermissions(projectKey, planKey));
    }

    /**
     * Core master pre-merge plan is in "TYPO3 core" project of bamboo
     */
    private Project project() {
        return new Project().name(projectName)
            .key(projectKey);
    }

    /**
     * Returns full Plan definition
     */
    Plan createPlan() {
        ArrayList<Stage> stages = new ArrayList<>();
        stages.add(getPreparationStage());
        stages.add(getIntegrityStage());
        stages.addAll(getUnitTestStages());
        stages.addAll(getCodeceptionMySqlStages());
        stages.addAll(getFunctionalMsSqlStagesPHP73());
        stages.addAll(getCodeceptionSqLiteStages());
        stages.addAll(getCodeceptionPgSqlStages());
        stages.addAll(getFunctionalMsSqlStagesPHP74());
        stages.addAll(getFunctionalMySqlStagesPHP72());
        stages.addAll(getFunctionalMySqlStagesPHP73());
        stages.addAll(getFunctionalMySqlStagesPHP74());
        stages.addAll(getFunctionalPGSqlStages());
        stages.addAll(getFunctionalSqliteStages());
        stages.addAll(getFunctionalMsSqlStagesPHP72());

        // Compile plan
        return new Plan(project(), planName, planKey).description("Execute TYPO3 core master nightly tests. Auto generated! See Build/bamboo of core git repository.")
            .pluginConfigurations(this.getDefaultPlanPluginConfiguration())
            .stages(stages.toArray(new Stage[0]))
            .linkedRepositories("github TYPO3 TYPO3.CMS")
            .triggers(new ScheduledTrigger().name("Scheduled")
                .description("once a day")
                .cronExpression("0 0 3 ? * *"))
            .variables(new Variable("changeUrl", ""), new Variable("patchset", ""))
            .planBranchManagement(new PlanBranchManagement().delete(new BranchCleanup())
                .notificationForCommitters())
            .notifications(new Notification().type(new PlanCompletedNotification())
                .recipients(new AnyNotificationRecipient(new AtlassianModule("com.atlassian.bamboo.plugins.bamboo-slack:recipient.slack")).recipientString("https://intercept.typo3.com/bamboo")));
    }

    /**
     * functional tests executed with DBMS Sqlite
     */
    private ArrayList<Stage> getFunctionalSqliteStages() {
        ArrayList<Stage> stages = new ArrayList<>();
        ArrayList<Job> jobs = new ArrayList<>();

        // PHP 7.4, composer default
        String phpVersion = "PHP74";
        Task composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_DEFAULT);
        jobs.addAll(this.getJobsFunctionalTestsSqlite(COMPOSER_DEFAULT, numberOfFunctionalSqliteJobs, phpVersion, composerTask, false));

        stages.add(new Stage("Functionals sqlite").jobs(jobs.toArray(new Job[0])));
        return stages;
    }

    /**
     * functional tests executed with DBMS PostgreSql
     */
    private ArrayList<Stage> getFunctionalPGSqlStages() {
        ArrayList<Stage> stages = new ArrayList<>();
        ArrayList<Job> jobs = new ArrayList<>();

        // PHP 7.2, composer max
        String phpVersion = "PHP72";
        Task composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MAX);
        jobs.addAll(this.getJobsFunctionalTestsPgsql(COMPOSER_MAX, numberOfFunctionalPgsqlJobs, phpVersion, composerTask, false));

        // PHP 7.4, composer min
        phpVersion = "PHP74";
        composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MIN);
        jobs.addAll(this.getJobsFunctionalTestsPgsql(COMPOSER_MIN, numberOfFunctionalPgsqlJobs, phpVersion, composerTask, false));

        stages.add(new Stage("Functionals pgsql").jobs(jobs.toArray(new Job[0])));
        return stages;
    }

    /**
     * functional tests executed with DBMS MsSQL for PHP 7.2
     */
    private ArrayList<Stage> getFunctionalMsSqlStagesPHP72() {
        ArrayList<Stage> stages = new ArrayList<>();

        // PHP 7.2, composer min, pdo
        String phpVersion = "PHP72";
        Task composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MIN);
        ArrayList<Job> jobs = new ArrayList<>(this.getJobsFunctionalTestsMssqlWithDriverPdoSqlSrv(COMPOSER_MIN, numberOfFunctionalMssqlJobs, phpVersion, composerTask, false));
        stages.add(new Stage("Functionals mssql PHP 7.2").jobs(jobs.toArray(new Job[0])));
        return stages;
    }

    /**
     * functional tests executed with DBMS MsSQL for PHP 7.3
     */
    private ArrayList<Stage> getFunctionalMsSqlStagesPHP73() {
        ArrayList<Stage> stages = new ArrayList<>();

        // PHP 7.3, composer max, sqlsrv
        String phpVersion = "PHP73";
        Task composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MAX);
        ArrayList<Job> jobs = new ArrayList<>(this.getJobsFunctionalTestsMssqlWithDriverSqlSrv(COMPOSER_MAX, numberOfFunctionalMssqlJobs, phpVersion, composerTask, false));
        stages.add(new Stage("Functionals mssql PHP 7.3").jobs(jobs.toArray(new Job[0])));
        return stages;
    }

    /**
     * functional tests executed with DBMS MsSQL for PHP 7.4
     */
    private ArrayList<Stage> getFunctionalMsSqlStagesPHP74() {
        ArrayList<Stage> stages = new ArrayList<>();

        // PHP 7.4, composer default, sqlsrv
        String phpVersion = "PHP74";
        Task composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_DEFAULT);
        ArrayList<Job> jobs = new ArrayList<>(this.getJobsFunctionalTestsMssqlWithDriverSqlSrv(COMPOSER_DEFAULT, numberOfFunctionalMssqlJobs, phpVersion, composerTask, false));
        stages.add(new Stage("Functionals mssql PHP7.4").jobs(jobs.toArray(new Job[0])));
        return stages;
    }



    /**
     * functional tests executed with DBMS MySQL with PHP 7.2
     */
    private ArrayList<Stage> getFunctionalMySqlStagesPHP72() {
        ArrayList<Stage> stages = new ArrayList<>();
        ArrayList<Job> jobs = new ArrayList<>();

        // PHP 7.2, composer min, mysqli
        String phpVersion = "PHP72";
        Task composerJob = getComposerTaskByStageNumber(phpVersion, COMPOSER_MIN);
        jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverMySqli(COMPOSER_MIN, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));

        // PHP 7.2, composer default, pdo
        composerJob = getComposerTaskByStageNumber(phpVersion, COMPOSER_DEFAULT);
        jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverPdoMysql(COMPOSER_DEFAULT, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));

        // PHP 7.2, composer max, pdo
        composerJob = getComposerTaskByStageNumber(phpVersion, COMPOSER_MAX);
        jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverPdoMysql(COMPOSER_MAX, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));

        stages.add(new Stage("Functionals mysql PHP 7.2").jobs(jobs.toArray(new Job[0])));
        return stages;
    }

    /**
     * functional tests executed with DBMS MySQL with PHP 7.3
     */
    private ArrayList<Stage> getFunctionalMySqlStagesPHP73() {
        ArrayList<Stage> stages = new ArrayList<>();
        ArrayList<Job> jobs = new ArrayList<>();

        // PHP 7.3, composer min, pdo
        String phpVersion = "PHP73";
        Task composerJob = getComposerTaskByStageNumber(phpVersion, COMPOSER_MIN);
        jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverPdoMysql(COMPOSER_MIN, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));

        // PHP 7.3, composer default, mysqli
        composerJob = getComposerTaskByStageNumber(phpVersion, COMPOSER_DEFAULT);
        jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverMySqli(COMPOSER_DEFAULT, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));

        // PHP 7.3, composer max, mysqli
        composerJob = getComposerTaskByStageNumber(phpVersion, COMPOSER_MAX);
        jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverMySqli(COMPOSER_MAX, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));

        stages.add(new Stage("Functionals mysql PHP 7.3").jobs(jobs.toArray(new Job[0])));
        return stages;
    }

    /**
     * functional tests executed with DBMS MySQL with PHP 7.4
     */
    private ArrayList<Stage> getFunctionalMySqlStagesPHP74() {
        ArrayList<Stage> stages = new ArrayList<>();
        ArrayList<Job> jobs = new ArrayList<>();

        // PHP 7.4, composer min, pdo
        String phpVersion = "PHP74";
        Task composerJob = getComposerTaskByStageNumber(phpVersion, COMPOSER_MIN);
        jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverPdoMysql(COMPOSER_MIN, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));

        // PHP 7.4, composer default, mysqli
        composerJob = getComposerTaskByStageNumber(phpVersion, COMPOSER_DEFAULT);
        jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverMySqli(COMPOSER_DEFAULT, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));

        // PHP 7.4, composer max, pdo
        composerJob = getComposerTaskByStageNumber(phpVersion, COMPOSER_MAX);
        jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverPdoMysql(COMPOSER_MAX, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));

        stages.add(new Stage("Functionals mysql PHP 7.4").jobs(jobs.toArray(new Job[0])));
        return stages;
    }

    /**
     * all tests run via codeception framework on MySql
     */
    private ArrayList<Stage> getCodeceptionMySqlStages() {
        ArrayList<Stage> stages = new ArrayList<>();

        // install tests
        String phpVersion = "PHP72";
        ArrayList<Job> jobs = new ArrayList<>();
        Task composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_DEFAULT);
        jobs.add(this.getJobAcceptanceTestInstallMysql(COMPOSER_DEFAULT, phpVersion, composerTask, false));
        composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MAX);
        jobs.add(this.getJobAcceptanceTestInstallMysql(COMPOSER_MAX, phpVersion, composerTask, false));
        composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MIN);
        jobs.add(this.getJobAcceptanceTestInstallMysql(COMPOSER_MIN, phpVersion, composerTask, false));


        // PHP 7.2, composer min
        phpVersion = "PHP72";
        composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MIN);
        jobs.addAll(this.getJobsAcceptanceTestsBackendMysql(COMPOSER_MIN, numberOfAcceptanceTestJobs, phpVersion, composerTask, false));
        jobs.addAll(this.getJobsAcceptanceTestsPageTreeMysql(COMPOSER_MIN, phpVersion, composerTask, false));

        // PHP 7.3, composer max
        phpVersion = "PHP73";
        composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MAX);
        jobs.addAll(this.getJobsAcceptanceTestsBackendMysql(COMPOSER_MAX, numberOfAcceptanceTestJobs, phpVersion, composerTask, false));
        jobs.addAll(this.getJobsAcceptanceTestsPageTreeMysql(COMPOSER_MAX, phpVersion, composerTask, false));

        // PHP 7.4, composer default
        phpVersion = "PHP74";
        composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_DEFAULT);
        jobs.addAll(this.getJobsAcceptanceTestsBackendMysql(COMPOSER_DEFAULT, numberOfAcceptanceTestJobs, phpVersion, composerTask, false));
        jobs.addAll(this.getJobsAcceptanceTestsPageTreeMysql(COMPOSER_DEFAULT, phpVersion, composerTask, false));

        stages.add(new Stage("Acceptance mysql").jobs(jobs.toArray(new Job[0])));
        return stages;
    }

    /**
     * all tests run via codeception framework on SqLite
     */
    private ArrayList<Stage> getCodeceptionSqLiteStages() {
        ArrayList<Stage> stages = new ArrayList<>();
        ArrayList<Job> jobs = new ArrayList<>();

        String phpVersion = "PHP72";
        Task composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MAX);
        jobs.add(this.getJobAcceptanceTestInstallSqlite(COMPOSER_MAX, phpVersion, composerTask, false));

        phpVersion = "PHP73";
        composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MIN);
        jobs.add(this.getJobAcceptanceTestInstallSqlite(COMPOSER_MIN, phpVersion, composerTask, false));

        phpVersion = "PHP74";
        composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_DEFAULT);
        jobs.add(this.getJobAcceptanceTestInstallSqlite(COMPOSER_DEFAULT, phpVersion, composerTask, false));

        stages.add(new Stage("Acceptance sqlite").jobs(jobs.toArray(new Job[0])));
        return stages;
    }

    /**
     * all tests run via codeception framework on PostGreSql
     */
    private ArrayList<Stage> getCodeceptionPgSqlStages() {
        ArrayList<Stage> stages = new ArrayList<>();
        ArrayList<Job> jobs = new ArrayList<>();

        String phpVersion = "PHP72";
        Task composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MIN);
        jobs.add(this.getJobAcceptanceTestInstallPgsql(COMPOSER_MIN, phpVersion, composerTask, false));
        jobs.addAll(this.getJobsAcceptanceTestsInstallToolMysql(COMPOSER_MIN, phpVersion, composerTask, false));

        phpVersion = "PHP73";
        composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_DEFAULT);
        jobs.add(this.getJobAcceptanceTestInstallPgsql(COMPOSER_DEFAULT, phpVersion, composerTask, false));
        jobs.addAll(this.getJobsAcceptanceTestsInstallToolMysql(COMPOSER_DEFAULT, phpVersion, composerTask, false));

        phpVersion = "PHP74";
        composerTask = getComposerTaskByStageNumber(phpVersion, COMPOSER_MAX);
        jobs.add(this.getJobAcceptanceTestInstallPgsql(COMPOSER_MAX, phpVersion, composerTask, false));
        jobs.addAll(this.getJobsAcceptanceTestsInstallToolMysql(COMPOSER_MAX, phpVersion, composerTask, false));

        stages.add(new Stage("Acceptance pgsql").jobs(jobs.toArray(new Job[0])));
        return stages;
    }

    /**
     * all unit tests, for all php versions and each with composer max and min install
     */
    private ArrayList<Stage> getUnitTestStages() {
        ArrayList<Stage> stages = new ArrayList<>();
        for (String phpVersion : phpVersions) {
            ArrayList<Job> jobs = new ArrayList<>();
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerTask = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.addAll(this.getJobUnitPhpRandom(stageNumber, numberOfUnitRandomOrderJobs, phpVersion, composerTask, false));
                jobs.add(this.getJobUnitDeprecatedPhp(stageNumber, phpVersion, composerTask, false));
                jobs.add(this.getJobUnitPhp(stageNumber, phpVersion, composerTask, false));
            }
            stages.add(new Stage("Unit Tests " + phpVersion).jobs(jobs.toArray(new Job[0])));
        }
        return stages;
    }

    /**
     * integrity stage - various checks for code quality
     * <p>
     * this stage is independent of actual composer or php versions
     */
    private Stage getIntegrityStage() {
        String phpVersionForIntegrityStage = phpVersions[0]; // the version is not very important, just use one (except for linting!)
        ArrayList<Job> jobs = new ArrayList<>();
        jobs.add(this.getJobIntegrationAnnotations(phpVersionForIntegrityStage, this.getTaskComposerInstall(phpVersionForIntegrityStage), false));
        jobs.add(this.getJobCglCheckFullCore(phpVersionForIntegrityStage, this.getTaskComposerInstall(phpVersionForIntegrityStage), false));
        jobs.add(this.getJobIntegrationPhpStan(phpVersionForIntegrityStage, this.getTaskComposerInstall(phpVersionForIntegrityStage), false));
        jobs.add(this.getJobIntegrationDocBlocks(phpVersionForIntegrityStage, this.getTaskComposerInstall(phpVersionForIntegrityStage), false));
        jobs.add(this.getJobIntegrationVarious(phpVersionForIntegrityStage, this.getTaskComposerInstall(phpVersionForIntegrityStage), false));
        jobs.add(this.getJobLintScssTs("JS", false));
        jobs.add(this.getJobUnitJavaScript("JS", this.getTaskComposerInstall(phpVersionForIntegrityStage), false));
        jobs.add(this.getJobComposerValidate(phpVersionForIntegrityStage, false));

        for (String phpVersion : phpVersions) {
            jobs.add(this.getJobLintPhp(phpVersion, false));
        }
        return new Stage("Integrity").jobs(jobs.toArray(new Job[0]));
    }

    /**
     * preparation stage - this will only define labels for later communication of test results
     */
    private Stage getPreparationStage() {
        ArrayList<Job> jobs = new ArrayList<>();
        jobs.add(this.getJobBuildLabels());
        return new Stage("Preparation").jobs(jobs.toArray(new Job[0]));
    }
}
