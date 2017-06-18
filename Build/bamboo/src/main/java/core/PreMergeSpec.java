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
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.Variable;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Plan;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.plan.branches.BranchCleanup;
import com.atlassian.bamboo.specs.api.builders.plan.branches.PlanBranchManagement;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.api.builders.requirement.Requirement;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.trigger.RemoteTrigger;
import com.atlassian.bamboo.specs.builders.trigger.RepositoryPollingTrigger;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;
import com.atlassian.bamboo.specs.util.BambooServer;

/**
 * Core master pre-merge test plan.
 */
@BambooSpec
public class PreMergeSpec extends AbstractCoreSpec {

    protected int numberOfAcceptanceTestJobs = 8;
    protected int numberOfFunctionalMysqlJobs = 10;
    protected int numberOfFunctionalMssqlJobs = 10;
    protected int numberOfFunctionalPgsqlJobs = 10;
    protected int numberOfUnitRandomOrderJobs = 2;

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

        jobsPreparationStage.add(this.getJobBuildLabels());

        jobsPreparationStage.add(this.getJobCglCheckGitCommit());

        jobsPreparationStage.add(this.getJobComposerValidate());

        Stage stagePreparation = new Stage("Preparation")
            .jobs(jobsPreparationStage.toArray(new Job[jobsPreparationStage.size()]));


        // MAIN stage
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql(this.getRequirementPhpVersion70Or71(), "PHP7071"));
        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql(this.getRequirementPhpVersion70Or71(), "PHP7071"));

        jobsMainStage.addAll(this.getJobsAcceptanceTestsMysql(this.numberOfAcceptanceTestJobs, this.getRequirementPhpVersion70Or71(), "PHP7071"));

        jobsMainStage.add(this.getJobIntegrationVarious());

        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, this.getRequirementPhpVersion70Or71(), "PHP7071"));

        jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(this.numberOfFunctionalMssqlJobs, this.getRequirementPhpVersion70Or71(), "PHP7071"));

        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(this.numberOfFunctionalPgsqlJobs, this.getRequirementPhpVersion70Or71(), "PHP7071"));

        jobsMainStage.add(this.getJobUnitJavaScript());

        jobsMainStage.add(this.getJobLintPhp(this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.add(this.getJobLintPhp(this.getRequirementPhpVersion71(), "PHP71"));

        jobsMainStage.add(this.getJobLintScssTs());

        jobsMainStage.add(this.getJobUnitPhp(this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.add(this.getJobUnitPhp(this.getRequirementPhpVersion71(), "PHP71"));

        jobsMainStage.addAll(this.getJobUnitPhpRandom(this.numberOfUnitRandomOrderJobs, this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(this.numberOfUnitRandomOrderJobs, this.getRequirementPhpVersion71(), "PHP71"));

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
     * Job creating labels needed for intercept communication
     */
    protected Job getJobBuildLabels() {
        return new Job("Create build labels", new BambooKey("CLFB"))
            .description("Create changeId and patch set labels from variable access and parsing result of a dummy task")
            .tasks(
                new ScriptTask()
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody("echo \"I'm just here for the labels!\"")
            );
    }

    /**
     * Job checking CGL of last git commit
     */
    protected Job getJobCglCheckGitCommit() {
        return new Job("Integration CGL", new BambooKey("CGLCHECK"))
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
    }
}
