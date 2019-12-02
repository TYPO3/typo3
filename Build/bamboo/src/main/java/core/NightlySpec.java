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
 * Core 9.5 nightly test plan.
 */
@BambooSpec
public class NightlySpec extends AbstractCoreSpec {

    private static String planName = "Core 9.5 nightly";
    private static String planKey = "GTN95";

    private static int numberOfAcceptanceTestJobs = 8;
    private static int numberOfFunctionalMysqlJobs = 6;
    private static int numberOfFunctionalMssqlJobs = 16;
    private static int numberOfFunctionalPgsqlJobs = 6;
    private static int numberOfFunctionalSqliteJobs = 6;
    private static int numberOfUnitRandomOrderJobs = 2;

    private String[] phpVersions = {"PHP72", "PHP73"};
    private String[] mySqlPdoVersions = {"5.5", "5.6", "5.7"};
    private String[] mySqlVersions = {"5.5", "5.6", "5.7"};
    private String[] mariaDbPdoVersions = {"5.5", "10.0", "10.1", "10.3"};
    private String[] mariaDbVersions = {"5.5", "10.0", "10.1", "10.3"};
    private String[] msSqlPdoVersions = {"2012", "2014", "2016", "2017", "2019"};
    private String[] msSqlVersions = {"2012", "2014", "2016", "2017", "2019"};
    private String[] sqLiteVersions = {"3.15", "3.20", "3.25", "3.30"};
    private String[] postGreSqlVersions = {"9.3", "9.4", "9.5", "9.6", "10.11", "11.6", "12.1"};

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
     * Core 9.5 pre-merge plan is in "TYPO3 core" project of bamboo
     */
    private Project project() {
        return new Project().name(projectName).key(projectKey);
    }

    /**
     * Returns full Plan definition
     */
    Plan createPlan() {

        Stage stagePreparation = getPreparationStage();

        Stage stageIntegrity = getIntegrityStage();

        Stage stageUnitTests = getUnitTestStage();

        Stage stageCodeceptionMySqlStage = getCodeceptionMySqlStage();
        Stage stageCodeceptionSqLiteStage = getCodeceptionSqLiteStage();
        Stage stageCodeceptionPgSqlStage = getCodeceptionPgSqlStage();

        Stage stageFunctionalMySqlStage = getFunctionalMySqlStage();
        Stage stageFunctionalMySqlPdoStage = getFunctionalMySqlPdoStage();

        Stage stageFunctionalMsSqlStage = getFunctionalMsSqlStage();
        Stage stageFunctionalMsSqlPdoStage = getFunctionalMsSqlPdoStage();

        Stage stageFunctionalPgSqlStage = getFunctionalPGSqlStage();

        Stage stageFunctionalSqLiteStage = getFunctionalSqliteStage();

        // Compile plan
        return new Plan(project(), planName, planKey)
            .description("Execute TYPO3 core 9.5 nightly tests. Auto generated! See Build/bamboo of core git repository.")
            .pluginConfigurations(this.getDefaultPlanPluginConfiguration())
            .stages(
                stagePreparation,
                stageIntegrity,
                stageUnitTests,
                stageCodeceptionMySqlStage,
                stageCodeceptionSqLiteStage,
                stageCodeceptionPgSqlStage,
                stageFunctionalMySqlStage,
                stageFunctionalMySqlPdoStage,
                stageFunctionalPgSqlStage,
                stageFunctionalSqLiteStage,
                stageFunctionalMsSqlStage,
                stageFunctionalMsSqlPdoStage
            )
            .linkedRepositories("github TYPO3 TYPO3.CMS 9.5")
            .triggers(
                new ScheduledTrigger()
                    .name("Scheduled")
                    .description("once a day")
                    .cronExpression("0 0 0 ? * *")
            )
            .variables(
                new Variable("changeUrl", ""),
                new Variable("patchset", "")
            )
            .planBranchManagement(
                new PlanBranchManagement()
                    .delete(new BranchCleanup())
                    .notificationForCommitters()
            )
            .notifications(new Notification()
                .type(new PlanCompletedNotification())
                .recipients(new AnyNotificationRecipient(new AtlassianModule("com.atlassian.bamboo.plugins.bamboo-slack:recipient.slack"))
                    .recipientString("https://intercept.typo3.com/bamboo")
                )
            );
    }

    /**
     *
     * functional tests in all composer install stages, executed with DBMS Sqlite
     */
    private Stage getFunctionalSqliteStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        for (String phpVersion : phpVersions) {
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerTask = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.addAll(this.getJobsFunctionalTestsSqlite(stageNumber, numberOfFunctionalSqliteJobs, phpVersion, composerTask, false));
            }
        }
        return new Stage("Functionals SqLite")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     *
     * functional tests in all composer install stages, executed with DBMS PostgreSql
     */
    private Stage getFunctionalPGSqlStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        for (String phpVersion : phpVersions) {
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerTask = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.addAll(this.getJobsFunctionalTestsPgsql(stageNumber, numberOfFunctionalPgsqlJobs, phpVersion, composerTask, false));
            }
        }
        return new Stage("Functionals PostGreSQL")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     *
     * functional tests in all composer install stages, executed with DBMS MsSQL, driver is pdo_sqlsrv
     */
    private Stage getFunctionalMsSqlPdoStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        for (String phpVersion : phpVersions) {
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerTask = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.addAll(this.getJobsFunctionalTestsMssqlWithDriverPdoSqlSrv(stageNumber, numberOfFunctionalMssqlJobs, phpVersion, composerTask, false));
            }
        }
        return new Stage("Functionals MsSql PDO")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     *
     * functional tests in all composer install stages, executed with DBMS MsSQL, driver is sqlsrv
     */
    private Stage getFunctionalMsSqlStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        for (String phpVersion : phpVersions) {
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerTask = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.addAll(this.getJobsFunctionalTestsMssqlWithDriverSqlSrv(stageNumber, numberOfFunctionalMssqlJobs, phpVersion, composerTask, false));
            }
        }
        return new Stage("Functionals MsSql")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     *
     * functional tests in all composer install stages, executed with DBMS MySQL, driver is mysqli
     */
    private Stage getFunctionalMySqlStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        for (String phpVersion : phpVersions) {
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerJob = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverMySqli(stageNumber, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));
            }
        }
        return new Stage("Functionals MySql")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     *
     * functional tests in all composer install stages, executed with DBMS MySQL, driver is pdo_mysql
     */
    private Stage getFunctionalMySqlPdoStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        for (String phpVersion : phpVersions) {
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerJob = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.addAll(this.getJobsFunctionalTestsMysqlWithDriverPdoMysql(stageNumber, numberOfFunctionalMysqlJobs, phpVersion, composerJob, false));
            }
        }
        return new Stage("Functionals MySql PDO")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     *
     * all tests run via codeception framework on MySql, for all php versions and each with composer max and min install
     */
    private Stage getCodeceptionMySqlStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        for (String phpVersion : phpVersions) {
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerTask = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.add(this.getJobAcceptanceTestInstallMysql(stageNumber, phpVersion, composerTask, false));
                jobs.addAll(this.getJobsAcceptanceTestsBackendMysql(stageNumber, numberOfAcceptanceTestJobs, phpVersion, composerTask, false));
            }
        }
        return new Stage("Acceptance MySql")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     *
     * all tests run via codeception framework on SqLite, for all php versions and each with composer max and min install
     */
    private Stage getCodeceptionSqLiteStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        for (String phpVersion : phpVersions) {
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerTask = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.add(this.getJobAcceptanceTestInstallSqlite(stageNumber, phpVersion, composerTask, false));
            }
        }
        return new Stage("Acceptance Sqlite")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     *
     * all tests run via codeception framework on PostGreSql, for all php versions and each with composer max and min install
     */
    private Stage getCodeceptionPgSqlStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        for (String phpVersion : phpVersions) {
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerTask = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.add(this.getJobAcceptanceTestInstallPgsql(stageNumber, phpVersion, composerTask, false));
            }
        }
        return new Stage("Acceptance PostGreSql")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     *
     * all unit tests, for all php versions and each with composer max and min install
     */
    private Stage getUnitTestStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        for (String phpVersion : phpVersions) {
            for (int stageNumber = 0; stageNumber <= 2; stageNumber++) {
                Task composerTask = getComposerTaskByStageNumber(phpVersion, stageNumber);
                jobs.addAll(this.getJobUnitPhpRandom(stageNumber, numberOfUnitRandomOrderJobs, phpVersion, composerTask, false));
                jobs.add(this.getJobUnitDeprecatedPhp(stageNumber, phpVersion, composerTask, false));
                jobs.add(this.getJobUnitPhp(stageNumber, phpVersion, composerTask, false));
            }
        }
        return new Stage("Unit Tests")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     * integrity stage - various checks for code quality
     *
     * this stage is independent of actual composer or php versions
     */
    private Stage getIntegrityStage() {
        String phpVersionForIntegrityStage = phpVersions[0]; // the version is not very important, just use one (except for linting!)
        ArrayList<Job> jobs = new ArrayList<Job>();
        jobs.add(this.getJobIntegrationAnnotations(phpVersionForIntegrityStage, this.getTaskComposerInstall(phpVersionForIntegrityStage), false));
        jobs.add(this.getJobCglCheckFullCore(phpVersionForIntegrityStage, this.getTaskComposerInstall(phpVersionForIntegrityStage), false));
        jobs.add(this.getJobIntegrationVarious(phpVersionForIntegrityStage, this.getTaskComposerInstall(phpVersionForIntegrityStage), false));
        jobs.add(this.getJobLintScssTs(phpVersionForIntegrityStage, false));
        jobs.add(this.getJobUnitJavaScript(phpVersionForIntegrityStage, this.getTaskComposerInstall(phpVersionForIntegrityStage),false));
        jobs.add(this.getJobComposerValidate(phpVersionForIntegrityStage, false));

        for (String phpVersion : phpVersions) {
            jobs.add(this.getJobLintPhp(phpVersion, false));
        }
        return new Stage("Integrity")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     *
     * preparation stage - this will only define labels for later communication of test results
     */
    private Stage getPreparationStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        jobs.add(this.getJobBuildLabels());
        return new Stage("Preparation")
            .jobs(jobs.toArray(new Job[jobs.size()]));
    }
}
