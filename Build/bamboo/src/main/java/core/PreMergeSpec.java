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
import com.atlassian.bamboo.specs.api.builders.plan.configuration.AllOtherPluginsConfiguration;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.builders.notification.PlanCompletedNotification;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.trigger.RemoteTrigger;
import com.atlassian.bamboo.specs.builders.trigger.RepositoryPollingTrigger;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;

/**
 * Core 7.6 pre-merge test plan.
 */
@BambooSpec
public class PreMergeSpec extends AbstractCoreSpec {

    protected static String planName = "Core 7.6 pre-merge";
    protected static String planKey = "GTC76";

    protected int numberOfFunctionalMysqlJobs = 5;

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
     * Core 7.6 pre-merge plan is in "TYPO3 core" project of bamboo
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

        jobsPreparationStage.add(this.getJobComposerValidate());

        Stage stagePreparation = new Stage("Preparation")
            .jobs(jobsPreparationStage.toArray(new Job[jobsPreparationStage.size()]));

        // MAIN stage
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        jobsMainStage.add(this.getJobIntegrationVarious());

        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, this.getRequirementPhpVersion55(), "PHP55"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, this.getRequirementPhpVersion56(), "PHP56"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, this.getRequirementPhpVersion71(), "PHP71"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, this.getRequirementPhpVersion72(), "PHP72"));

        jobsMainStage.add(this.getJobLintPhp(this.getRequirementPhpVersion55(), "PHP55"));
        jobsMainStage.add(this.getJobLintPhp(this.getRequirementPhpVersion56(), "PHP56"));
        jobsMainStage.add(this.getJobLintPhp(this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.add(this.getJobLintPhp(this.getRequirementPhpVersion71(), "PHP71"));
        jobsMainStage.add(this.getJobLintPhp(this.getRequirementPhpVersion72(), "PHP72"));

        jobsMainStage.add(this.getJobUnitPhp(this.getRequirementPhpVersion55(), "PHP55"));
        jobsMainStage.add(this.getJobUnitPhp(this.getRequirementPhpVersion56(), "PHP56"));
        jobsMainStage.add(this.getJobUnitPhp(this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.add(this.getJobUnitPhp(this.getRequirementPhpVersion71(), "PHP71"));
        jobsMainStage.add(this.getJobUnitPhp(this.getRequirementPhpVersion72(), "PHP72"));

        Stage stageMainStage = new Stage("Main stage")
            .jobs(jobsMainStage.toArray(new Job[jobsMainStage.size()]));

        // Compile plan
        return new Plan(project(), planName, planKey)
            .description("Execute TYPO3 core 7.6 pre-merge tests. Auto generated! See Build/bamboo of core git repository.")
            .pluginConfigurations(this.getDefaultPlanPluginConfiguration())
            .stages(
                stagePreparation,
                stageMainStage
            )
            .linkedRepositories("git.typo3.org Core 7.6")
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
                    .recipientString("https://intercept.typo3.com/index.php")
                )
        );
    }

    /**
     * Job creating labels needed for intercept communication
     */
    protected Job getJobBuildLabels() {
        return new Job("Create build labels", new BambooKey("CLFB"))
            .description("Create changeId and patch set labels from variable access and parsing result of a dummy task")
            .pluginConfigurations(new AllOtherPluginsConfiguration()
                .configuration(new MapBuilder()
                    .put("repositoryDefiningWorkingDirectory", -1)
                    .put("custom", new MapBuilder()
                        .put("auto", new MapBuilder()
                            .put("regex", "https:\\/\\/review\\.typo3\\.org\\/(#\\/c\\/)?(\\d+)")
                            .put("label", "change-\\2\\, patchset-${bamboo.patchset}")
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
            .cleanWorkingDirectory(true);
    }
}
