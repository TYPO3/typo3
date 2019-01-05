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

/**
 * Core 8.7 nightly test plan.
 */
@BambooSpec
public class NightlySpec extends AbstractCoreSpec {

    protected static String planName = "Core 8.7 nightly";
    protected static String planKey = "GTN87";

    protected int numberOfAcceptanceTestJobs = 8;
    protected int numberOfFunctionalMysqlJobs = 6;
    protected int numberOfFunctionalMssqlJobs = 10;
    protected int numberOfFunctionalPgsqlJobs = 6;
    protected int numberOfUnitRandomOrderJobs = 2;

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
    Project project() {
        return new Project().name(projectName).key(projectKey);
    }

    /**
     * Returns full Plan definition
     */
    Plan createPlan() {
        // PREPARATION stage
        ArrayList<Job> jobsPreparationStage = new ArrayList<Job>();
        jobsPreparationStage.add(this.getJobBuildLabels());
        Stage stagePreparation = new Stage("Preparation")
            .jobs(jobsPreparationStage.toArray(new Job[jobsPreparationStage.size()]));


        // MAIN stage
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        jobsMainStage.add(this.getJobComposerValidate("PHP72"));

        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql("PHP72"));
        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql("PHP73"));

        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql("PHP72"));
        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql("PHP73"));

        jobsMainStage.addAll(this.getJobsAcceptanceTestsBackendMysql(this.numberOfAcceptanceTestJobs, "PHP72"));
        jobsMainStage.addAll(this.getJobsAcceptanceTestsBackendMysql(this.numberOfAcceptanceTestJobs, "PHP73"));

        jobsMainStage.add(this.getJobCglCheckFullCore("PHP72"));

        jobsMainStage.add(this.getJobIntegrationVarious("PHP72"));

        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, "PHP70"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, "PHP71"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, "PHP72"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, "PHP73"));

        jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(this.numberOfFunctionalMssqlJobs, "PHP70"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(this.numberOfFunctionalMssqlJobs, "PHP71"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(this.numberOfFunctionalMssqlJobs, "PHP72"));
        // no mssql with php 7.3 yet
        // jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(this.numberOfFunctionalMssqlJobs, "PHP73"));

        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(this.numberOfFunctionalPgsqlJobs, "PHP70"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(this.numberOfFunctionalPgsqlJobs, "PHP71"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(this.numberOfFunctionalPgsqlJobs, "PHP72"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(this.numberOfFunctionalPgsqlJobs, "PHP73"));

        jobsMainStage.add(this.getJobUnitJavaScript("PHP72"));

        jobsMainStage.add(this.getJobLintPhp("PHP70"));
        jobsMainStage.add(this.getJobLintPhp("PHP71"));
        jobsMainStage.add(this.getJobLintPhp("PHP72"));
        jobsMainStage.add(this.getJobLintPhp("PHP73"));

        jobsMainStage.add(this.getJobLintScssTs("PHP72"));

        jobsMainStage.add(this.getJobUnitPhp("PHP70"));
        jobsMainStage.add(this.getJobUnitPhp("PHP71"));
        jobsMainStage.add(this.getJobUnitPhp("PHP72"));
        jobsMainStage.add(this.getJobUnitPhp("PHP73"));

        jobsMainStage.addAll(this.getJobUnitPhpRandom(this.numberOfUnitRandomOrderJobs, "PHP70"));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(this.numberOfUnitRandomOrderJobs, "PHP71"));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(this.numberOfUnitRandomOrderJobs, "PHP72"));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(this.numberOfUnitRandomOrderJobs, "PHP73"));

        Stage stageMainStage = new Stage("Main stage")
            .jobs(jobsMainStage.toArray(new Job[jobsMainStage.size()]));


        // Compile plan
        return new Plan(project(), planName, planKey)
            .description("Execute TYPO3 core 8.7 nightly tests. Auto generated! See Build/bamboo of core git repository.")
            .pluginConfigurations(this.getDefaultPlanPluginConfiguration())
            .stages(
                stagePreparation,
                stageMainStage
            )
            .linkedRepositories("github TYPO3 TYPO3.CMS 8.7")
            .triggers(
                new ScheduledTrigger()
                    .name("Scheduled")
                    .description("daily at night")
                    // daily 04:42
                    .cronExpression("0 42 4 ? * *")
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
     * Job checking CGL of all core php files
     *
     * @param String requirementIdentifier
     */
    protected Job getJobCglCheckFullCore(String requirementIdentifier) {
        return new Job("Integration CGL", new BambooKey("CGLCHECK"))
            .description("Check coding guidelines of full core")
            .pluginConfigurations(this.getDefaultJobPluginConfiguration())
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
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
