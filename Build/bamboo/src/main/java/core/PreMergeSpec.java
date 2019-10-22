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
import com.atlassian.bamboo.specs.api.builders.requirement.Requirement;
import com.atlassian.bamboo.specs.builders.notification.PlanCompletedNotification;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.trigger.RemoteTrigger;
import com.atlassian.bamboo.specs.builders.trigger.RepositoryPollingTrigger;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;
import com.atlassian.bamboo.specs.util.BambooServer;

/**
 * Core 9.5 pre-merge test plan.
 */
@BambooSpec
public class PreMergeSpec extends AbstractCoreSpec {

    protected static String planName = "Core 9.5 pre-merge";
    protected static String planKey = "GTC95";

    protected int numberOfAcceptanceTestJobs = 10;
    protected int numberOfFunctionalMysqlJobs = 10;
    protected int numberOfFunctionalMssqlJobs = 10;
    protected int numberOfFunctionalPgsqlJobs = 10;
    protected int numberOfFunctionalSqliteJobs = 10;
    protected int numberOfUnitRandomOrderJobs = 1;

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
     * Core 9.5 pre-merge plan is in "TYPO3 core" project of bamboo
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

        // EARLY stage
        ArrayList<Job> jobsEarlyStage = new ArrayList<Job>();
        jobsEarlyStage.add(this.getJobCglCheckGitCommit("PHP72", false));
        jobsEarlyStage.add(this.getJobComposerValidate("PHP72", false));
        Stage stageEarly = new Stage("Early")
            .jobs(jobsEarlyStage.toArray(new Job[jobsEarlyStage.size()]));

        // MAIN stage
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql(0, "PHP73", this.getTaskComposerInstall("PHP73"), false));
        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql(0, "PHP72", this.getTaskComposerInstall("PHP72"), false));
        jobsMainStage.add(this.getJobAcceptanceTestInstallSqlite(0, "PHP72", this.getTaskComposerInstall("PHP72"), false));

        jobsMainStage.addAll(this.getJobsAcceptanceTestsBackendMysql(0, this.numberOfAcceptanceTestJobs, "PHP73", this.getTaskComposerInstall("PHP73"), false));

        jobsMainStage.add(this.getJobIntegrationAnnotations(0, "PHP72", this.getTaskComposerInstall("PHP72"), false));

        jobsMainStage.add(this.getJobIntegrationVarious(0, "PHP72", this.getTaskComposerInstall("PHP72"), false));

        jobsMainStage.addAll(this.getJobsFunctionalTestsMysqlWithDriverMySqli(0, this.numberOfFunctionalMysqlJobs, "PHP73", this.getTaskComposerInstall("PHP73"), false));
        // mssql functionals are not executed as pre-merge
        // jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(0, this.numberOfFunctionalMssqlJobs, "PHP72", this.getTaskComposerInstall("PHP72"), false));
        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(0, this.numberOfFunctionalPgsqlJobs, "PHP72", this.getTaskComposerInstall("PHP72"), false));
        jobsMainStage.addAll(this.getJobsFunctionalTestsSqlite(0, this.numberOfFunctionalSqliteJobs, "PHP72", this.getTaskComposerInstall("PHP72"), false));

        jobsMainStage.add(this.getJobUnitJavaScript(0, "PHP72", this.getTaskComposerInstall("PHP72"), false));

        jobsMainStage.add(this.getJobLintPhp("PHP72", false));
        jobsMainStage.add(this.getJobLintPhp("PHP73", false));

        jobsMainStage.add(this.getJobLintScssTs("PHP72", false));

        jobsMainStage.add(this.getJobUnitPhp(0, "PHP72", this.getTaskComposerInstall("PHP72"), false));
        jobsMainStage.add(this.getJobUnitPhp(0, "PHP73", this.getTaskComposerInstall("PHP73"), false));
        jobsMainStage.add(this.getJobUnitDeprecatedPhp(0, "PHP72", this.getTaskComposerInstall("PHP72"), false));
        jobsMainStage.add(this.getJobUnitDeprecatedPhp(0, "PHP73", this.getTaskComposerInstall("PHP73"), false));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(0, this.numberOfUnitRandomOrderJobs, "PHP72", this.getTaskComposerInstall("PHP72"), false));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(0, this.numberOfUnitRandomOrderJobs, "PHP73", this.getTaskComposerInstall("PHP73"), false));

        Stage stageMainStage = new Stage("Main stage")
            .jobs(jobsMainStage.toArray(new Job[jobsMainStage.size()]));

        // Compile plan
        return new Plan(project(), planName, planKey)
            .description("Execute TYPO3 core 9.5 pre-merge tests. Auto generated! See Build/bamboo of core git repository.")
            .pluginConfigurations(this.getDefaultPlanPluginConfiguration())
            .stages(
                stagePreparation,
                stageEarly,
                stageMainStage
            )
            .linkedRepositories("github TYPO3 TYPO3.CMS 9.5")
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
}
