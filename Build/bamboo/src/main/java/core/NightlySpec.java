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
import java.util.ListIterator;

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

    private int jobListSize = 50;

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

        ArrayList<Job> jobs = new ArrayList<Job>();
        jobs.addAll(getAcceptanceJobs());
        jobs.addAll(getFunctionalMySqlJobs());
        jobs.addAll(getFunctionalPgSqlJobs());
        jobs.addAll(getFunctionalMsSqlJobs());
        jobs.addAll(getUnitJobs());

        ArrayList<Stage> stages = new ArrayList<Stage>();
        stages.add(stagePreparation);
        stages.add(stageIntegrity);
        ListIterator<Job> jobIterator = jobs.listIterator();
        Stage stage = new Stage("Jobs " + (jobIterator.nextIndex()) + " - " + (jobIterator.nextIndex() - 1 + jobListSize));
        ArrayList<Job> chunkedJobs = new ArrayList<Job>();
        while (jobIterator.hasNext()) {
            chunkedJobs.add(jobIterator.next());
            if ((jobIterator.nextIndex() % jobListSize) == 0) {
                stage.jobs(chunkedJobs.toArray(new Job[chunkedJobs.size()]));
                stages.add(stage);
                stage = new Stage("Jobs " + (jobIterator.nextIndex()) + " - " + (jobIterator.nextIndex() - 1 + jobListSize));
                chunkedJobs.clear();
            }
            if (!jobIterator.hasNext()) {
                stage.jobs(chunkedJobs.toArray(new Job[chunkedJobs.size()]));
                stages.add(stage);
            }
        }

        // Compile plan
        return new Plan(project(), planName, planKey)
                .description("Execute TYPO3 core 8.7 nightly tests. Auto generated! See Build/bamboo of core git repository.")
                .pluginConfigurations(this.getDefaultPlanPluginConfiguration())
                .stages(stages.toArray(new Stage[stages.size()]))
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
    private ArrayList<Job> getUnitJobs() {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (String phpVersion : phpVersions) {
            jobs.add(this.getJobUnitPhp(phpVersion, false));
            jobs.addAll(this.getJobUnitPhpRandom(numberOfUnitRandomOrderJobs, phpVersion, false));
        }
        return jobs;
    }

    /**
     * all acceptance tests, for all relevant php versions
     */
    private ArrayList<Job> getAcceptanceJobs() {
        ArrayList<Job> jobs = new ArrayList<Job>();

        jobs.add(this.getJobAcceptanceTestInstallMysql(phpVersions[2], false));
        jobs.add(this.getJobAcceptanceTestInstallMysql(phpVersions[3], false));

        jobs.add(this.getJobAcceptanceTestInstallPgsql(phpVersions[2], false));
        jobs.add(this.getJobAcceptanceTestInstallPgsql(phpVersions[3], false));

        jobs.addAll(this.getJobsAcceptanceTestsBackendMysql(numberOfAcceptanceTestJobs, phpVersions[2], false));
        jobs.addAll(this.getJobsAcceptanceTestsBackendMysql(numberOfAcceptanceTestJobs, phpVersions[3], false));

        return jobs;
    }

    /**
     * all functional tests on MsSQL, for all  php versions
     */
    private ArrayList<Job> getFunctionalMsSqlJobs() {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (String phpVersion : phpVersions) {
            jobs.addAll(this.getJobsFunctionalTestsMssql(numberOfFunctionalMssqlJobs, phpVersion, false));
        }
        return jobs;
    }

    /**
     * all functional tests on MySQL, for all  php versions
     */
    private ArrayList<Job> getFunctionalMySqlJobs() {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (String phpVersion : phpVersions) {
            jobs.addAll(this.getJobsFunctionalTestsMysql(numberOfFunctionalMysqlJobs, phpVersion, false));
        }
        return jobs;
    }

    /**
     * all functional tests on PostGreSql, for all  php versions
     */
    private ArrayList<Job> getFunctionalPgSqlJobs() {
        ArrayList<Job> jobs = new ArrayList<Job>();

        for (String phpVersion : phpVersions) {
            jobs.addAll(this.getJobsFunctionalTestsPgsql(numberOfFunctionalPgsqlJobs, phpVersion, false));
        }
        return jobs;
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
