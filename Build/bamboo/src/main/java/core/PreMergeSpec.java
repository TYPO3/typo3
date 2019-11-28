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
import com.atlassian.bamboo.specs.builders.trigger.RemoteTrigger;
import com.atlassian.bamboo.specs.builders.trigger.RepositoryPollingTrigger;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;
import com.atlassian.bamboo.specs.util.BambooServer;

import java.util.ArrayList;

/**
 * Core 8.7 pre-merge test plan.
 */
@BambooSpec
public class PreMergeSpec extends AbstractCoreSpec {

    private static String planName = "Core 8.7 pre-merge";
    private static String planKey = "GTC87";

    private static int numberOfAcceptanceTestJobs = 8;
    private static int numberOfFunctionalMysqlJobs = 10;
    private static int numberOfFunctionalMssqlJobs = 10;
    private static int numberOfFunctionalPgsqlJobs = 10;
    private static int numberOfUnitRandomOrderJobs = 1;

    /**
     * Run main to publish plan on Bamboo
     */
    public static void main(final String[] args) throws Exception {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer(bambooServerName);
        bambooServer.publish(new PreMergeSpec().createPlan());
        bambooServer.publish(new PreMergeSpec().getDefaultPlanPermissions(projectKey, planKey));
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
        // PREPARATION stage
        ArrayList<Job> jobsPreparationStage = new ArrayList<Job>();
        jobsPreparationStage.add(this.getJobBuildLabels());
        Stage stagePreparation = new Stage("Preparation")
                .jobs(jobsPreparationStage.toArray(new Job[jobsPreparationStage.size()]));

        // EARLY stage
        ArrayList<Job> jobsEarlyStage = new ArrayList<Job>();
        jobsEarlyStage.add(this.getJobCglCheckGitCommit("PHP72", false));
        jobsEarlyStage.add(this.getJobComposerValidate("PHP72", false));
        Stage stageEarly = new Stage("Early")
                .jobs(jobsEarlyStage.toArray(new Job[jobsEarlyStage.size()]));

        // MAIN stage
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql("PHP73", false));
        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql("PHP72", false));

        jobsMainStage.addAll(this.getJobsAcceptanceTestsBackendMysql(numberOfAcceptanceTestJobs, "PHP72", false));

        jobsMainStage.add(this.getJobIntegrationVarious("PHP72", false));

        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(numberOfFunctionalMysqlJobs, "PHP73", false));
        // mssql functionals are not executed as pre-merge
        // jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(this.numberOfFunctionalMssqlJobs, "PHP72", false));
        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(numberOfFunctionalPgsqlJobs, "PHP70", false));

        jobsMainStage.add(this.getJobUnitJavaScript("PHP72", false));

        jobsMainStage.add(this.getJobLintPhp("PHP70", false));
        jobsMainStage.add(this.getJobLintPhp("PHP71", false));
        jobsMainStage.add(this.getJobLintPhp("PHP72", false));
        jobsMainStage.add(this.getJobLintPhp("PHP73", false));

        jobsMainStage.add(this.getJobLintScssTs("PHP72", false));

        jobsMainStage.add(this.getJobUnitPhp("PHP70", false));
        jobsMainStage.add(this.getJobUnitPhp("PHP71", false));
        jobsMainStage.add(this.getJobUnitPhp("PHP72", false));
        jobsMainStage.add(this.getJobUnitPhp("PHP73", false));

        jobsMainStage.addAll(this.getJobUnitPhpRandom(numberOfUnitRandomOrderJobs, "PHP70", false));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(numberOfUnitRandomOrderJobs, "PHP71", false));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(numberOfUnitRandomOrderJobs, "PHP72", false));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(numberOfUnitRandomOrderJobs, "PHP73", false));

        Stage stageMainStage = new Stage("Main stage")
                .jobs(jobsMainStage.toArray(new Job[jobsMainStage.size()]));

        // Compile plan
        return new Plan(project(), planName, planKey)
                .description("Execute TYPO3 core 8.7 pre-merge tests. Auto generated! See Build/bamboo of core git repository.")
                .pluginConfigurations(this.getDefaultPlanPluginConfiguration())
                .stages(
                        stagePreparation,
                        stageEarly,
                        stageMainStage
                )
                .linkedRepositories("github TYPO3 TYPO3.CMS 8.7")
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
                )
                .notifications(new Notification()
                        .type(new PlanCompletedNotification())
                        .recipients(new AnyNotificationRecipient(new AtlassianModule("com.atlassian.bamboo.plugins.bamboo-slack:recipient.slack"))
                                .recipientString("https://intercept.typo3.com/bamboo")
                        )
                );
    }

    /**
     * Job checking CGL of last git commit
     */
    private Job getJobCglCheckGitCommit(String requirementIdentifier, Boolean isSecurity) {
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
}
