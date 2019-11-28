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
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.Variable;
import com.atlassian.bamboo.specs.api.builders.notification.AnyNotificationRecipient;
import com.atlassian.bamboo.specs.api.builders.notification.Notification;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Plan;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.plan.branches.BranchCleanup;
import com.atlassian.bamboo.specs.api.builders.plan.branches.PlanBranchManagement;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.builders.notification.PlanCompletedNotification;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.trigger.ScheduledTrigger;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;
import com.atlassian.bamboo.specs.util.BambooServer;

import java.util.ArrayList;

/**
 * Core 8.7 nightly test plan.
 */
@BambooSpec
public class NightlySpec extends AbstractCoreSpec {

    private static String planName = "Core 8.7 nightly";
    private static String planKey = "GTN87";

    private static int numberOfAcceptanceTestJobs = 8;
    private static int numberOfFunctionalMysqlJobs = 6;
    private static int numberOfFunctionalMssqlJobs = 10;
    private static int numberOfFunctionalPgsqlJobs = 6;
    private static int numberOfUnitRandomOrderJobs = 2;

    private String[] phpVersions = {"PHP70", "PHP71", "PHP72", "PHP73"};

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
     * Core 8.7 pre-merge plan is in "TYPO3 core" project of bamboo
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

        Stage stageAcceptance = getAcceptanceStage();

        Stage stageFunctionalMySql = getFunctionalMySqlStage();
        Stage stageFunctionalPgSql = getFunctionalPgSqlStage();
        Stage stageFunctionalMsSql = getFunctionalMsSqlStage();

        Stage unitStage = getUnitStage();


        // Compile plan
        return new Plan(project(), planName, planKey)
                .description("Execute TYPO3 core 8.7 nightly tests. Auto generated! See Build/bamboo of core git repository.")
                .pluginConfigurations(this.getDefaultPlanPluginConfiguration())
                .stages(
                        stagePreparation,
                        stageIntegrity,
                        unitStage,
                        stageAcceptance,
                        stageFunctionalMySql,
                        stageFunctionalPgSql,
                        stageFunctionalMsSql
                )
                .linkedRepositories("github TYPO3 TYPO3.CMS 8.7")
                .triggers(
                        new ScheduledTrigger()
                                .name("Scheduled")
                                .description("once a day")
                                .cronExpression("0 22 0 ? * *")
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
     * all unit tests, for all php versions
     */
    private Stage getUnitStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (String phpVersion : phpVersions) {
            jobs.add(this.getJobUnitPhp(phpVersion, false));
            jobs.addAll(this.getJobUnitPhpRandom(numberOfUnitRandomOrderJobs, phpVersion, false));
        }


        return new Stage("Unit Tests")
                .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     * all acceptance tests, for all relevant php versions
     */
    private Stage getAcceptanceStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();

        jobs.add(this.getJobAcceptanceTestInstallMysql(phpVersions[2], false));
        jobs.add(this.getJobAcceptanceTestInstallMysql(phpVersions[3], false));

        jobs.add(this.getJobAcceptanceTestInstallPgsql(phpVersions[2], false));
        jobs.add(this.getJobAcceptanceTestInstallPgsql(phpVersions[3], false));

        jobs.addAll(this.getJobsAcceptanceTestsBackendMysql(numberOfAcceptanceTestJobs, phpVersions[2], false));
        jobs.addAll(this.getJobsAcceptanceTestsBackendMysql(numberOfAcceptanceTestJobs, phpVersions[3], false));

        return new Stage("Acceptance")
                .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     * all functional tests on MsSQL, for all  php versions
     */
    private Stage getFunctionalMsSqlStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (String phpVersion : phpVersions) {
            jobs.addAll(this.getJobsFunctionalTestsMssql(numberOfFunctionalMssqlJobs, phpVersion, false));
        }
        return new Stage("Functional MsSQL")
                .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     * all functional tests on MySQL, for all  php versions
     */
    private Stage getFunctionalMySqlStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (String phpVersion : phpVersions) {
            jobs.addAll(this.getJobsFunctionalTestsMysql(numberOfFunctionalMysqlJobs, phpVersion, false));
        }
        return new Stage("Functional MySQL")
                .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     * all functional tests on PostGreSql, for all  php versions
     */
    private Stage getFunctionalPgSqlStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (String phpVersion : phpVersions) {
            jobs.addAll(this.getJobsFunctionalTestsPgsql(numberOfFunctionalPgsqlJobs, phpVersion, false));
        }
        return new Stage("Functional PgSql")
                .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     * integrity stage - various checks for code quality
     * <p>
     * this stage is independent of actual composer or php versions
     */
    private Stage getIntegrityStage() {
        ArrayList<Job> jobs = new ArrayList<Job>();
        String phpVersionForIntegrityStage = phpVersions[2]; // the version is not very important, just use one (except for linting!)
        jobs.add(this.getJobComposerValidate(phpVersionForIntegrityStage, false));
        jobs.add(this.getJobCglCheckFullCore(phpVersionForIntegrityStage, false));
        jobs.add(this.getJobIntegrationVarious(phpVersionForIntegrityStage, false));
        jobs.add(this.getJobUnitJavaScript(phpVersionForIntegrityStage, false));
        jobs.add(this.getJobLintScssTs(phpVersionForIntegrityStage, false));

        for (String phpVersion : phpVersions) {
            jobs.add(this.getJobLintPhp(phpVersion, false));
        }
        return new Stage("Integrity")
                .jobs(jobs.toArray(new Job[jobs.size()]));
    }

    /**
     * preparation stage - this will only define labels for later communication of test results
     */
    private Stage getPreparationStage() {
        ArrayList<Job> jobsPreparationStage = new ArrayList<Job>();
        jobsPreparationStage.add(this.getJobBuildLabels());
        return new Stage("Preparation")
                .jobs(jobsPreparationStage.toArray(new Job[jobsPreparationStage.size()]));
    }

    /**
     * Job checking CGL of all core php files
     */
    private Job getJobCglCheckFullCore(String requirementIdentifier, Boolean isSecurity) {
        return new Job("Integration CGL", new BambooKey("CGLCHECK"))
                .description("Check coding guidelines of full core")
                .pluginConfigurations(this.getDefaultJobPluginConfiguration())
                .tasks(
                        this.getTaskGitCloneRepository(),
                        this.getTaskGitCherryPick(isSecurity),
                        this.getTaskStopDanglingContainers(),
                        this.getTaskComposerInstall(requirementIdentifier),
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
}
