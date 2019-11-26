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
import com.atlassian.bamboo.specs.builders.notification.PlanCompletedNotification;
import com.atlassian.bamboo.specs.builders.trigger.RemoteTrigger;
import com.atlassian.bamboo.specs.util.BambooServer;

import java.util.ArrayList;

/**
 * Core master security test plan.
 */
@BambooSpec
public class SecuritySpec extends AbstractCoreSpec {

    private static String planName = "Core master security";
    private static String planKey = "GTS";

    private static int numberOfAcceptanceTestJobs = 10;
    private static int numberOfFunctionalMysqlJobs = 10;
    private static int numberOfFunctionalMssqlJobs = 10;
    private static int numberOfFunctionalPgsqlJobs = 10;
    private static int numberOfFunctionalSqliteJobs = 10;
    private static int numberOfUnitRandomOrderJobs = 1;

    /**
     * Run main to publish plan on Bamboo
     */
    public static void main(final String[] args) throws Exception {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer(bambooServerName);
        bambooServer.publish(new SecuritySpec().createPlan());
        bambooServer.publish(new SecuritySpec().getSecurityPlanPermissions(projectKey, planKey));
    }

    /**
     * Core master pre-merge plan is in "TYPO3 core" project of bamboo
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
        jobsEarlyStage.add(this.getJobCglCheckGitCommit("PHP72", true));
        jobsEarlyStage.add(this.getJobComposerValidate("PHP72", true));
        Stage stageEarly = new Stage("Early")
            .jobs(jobsEarlyStage.toArray(new Job[jobsEarlyStage.size()]));

        // MAIN stage
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql(0, "PHP73", this.getTaskComposerInstall("PHP73"), true));
        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql(0, "PHP72", this.getTaskComposerInstall("PHP72"), true));
        jobsMainStage.add(this.getJobAcceptanceTestInstallSqlite(0, "PHP72", this.getTaskComposerInstall("PHP72"), true));

        jobsMainStage.addAll(this.getJobsAcceptanceTestsBackendMysql(0, numberOfAcceptanceTestJobs, "PHP73", this.getTaskComposerInstall("PHP73"), true));

        jobsMainStage.add(this.getJobIntegrationDocBlocks("PHP72", this.getTaskComposerInstall("PHP72"), true));
        jobsMainStage.add(this.getJobIntegrationAnnotations("PHP72", this.getTaskComposerInstall("PHP72"), true));

        jobsMainStage.add(this.getJobIntegrationVarious("PHP72", this.getTaskComposerInstall("PHP72"), true));

        jobsMainStage.addAll(this.getJobsFunctionalTestsMysqlWithDriverMySqli(0, numberOfFunctionalMysqlJobs, "PHP73", this.getTaskComposerInstall("PHP73"), true));
        // mssql functionals are not executed as pre-merge
        // jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(0, this.numberOfFunctionalMssqlJobs, "PHP72", this.getTaskComposerInstall("PHP72"), true));
        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(0, numberOfFunctionalPgsqlJobs, "PHP72", this.getTaskComposerInstall("PHP72"), true));
        jobsMainStage.addAll(this.getJobsFunctionalTestsSqlite(0, numberOfFunctionalSqliteJobs, "PHP72", this.getTaskComposerInstall("PHP72"), true));

        jobsMainStage.add(this.getJobUnitJavaScript("JS", this.getTaskComposerInstall("PHP72"), true));

        jobsMainStage.add(this.getJobLintPhp("PHP72", true));
        jobsMainStage.add(this.getJobLintPhp("PHP73", true));

        jobsMainStage.add(this.getJobLintScssTs("JS", true));

        jobsMainStage.add(this.getJobUnitPhp(0, "PHP72", this.getTaskComposerInstall("PHP72"), true));
        jobsMainStage.add(this.getJobUnitPhp(0, "PHP73", this.getTaskComposerInstall("PHP73"), true));
        jobsMainStage.add(this.getJobUnitDeprecatedPhp(0, "PHP72", this.getTaskComposerInstall("PHP72"), true));
        jobsMainStage.add(this.getJobUnitDeprecatedPhp(0, "PHP73", this.getTaskComposerInstall("PHP73"), true));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(0, numberOfUnitRandomOrderJobs, "PHP72", this.getTaskComposerInstall("PHP72"), true));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(0, numberOfUnitRandomOrderJobs, "PHP73", this.getTaskComposerInstall("PHP73"), true));

        Stage stageMainStage = new Stage("Main stage")
            .jobs(jobsMainStage.toArray(new Job[jobsMainStage.size()]));

        // Compile plan
        return new Plan(project(), planName, planKey)
            .description("Execute TYPO3 core master security tests. Auto generated! See Build/bamboo of core git repository.")
            .pluginConfigurations(this.getDefaultPlanPluginConfiguration())
            .stages(
                stagePreparation,
                stageEarly,
                stageMainStage
            )
            .linkedRepositories("github TYPO3 TYPO3.CMS")
            .triggers(
                new RemoteTrigger()
                    .name("Remote trigger for security builds")
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
