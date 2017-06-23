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
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.trigger.ScheduledTrigger;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;
import com.atlassian.bamboo.specs.util.BambooServer;

/**
 * Core 8.7 nightly test plan.
 */
@BambooSpec
public class NightlySpec extends AbstractCoreSpec {

    protected int numberOfAcceptanceTestJobs = 8;
    protected int numberOfFunctionalMysqlJobs = 6;
    protected int numberOfFunctionalMssqlJobs = 6;
    protected int numberOfFunctionalPgsqlJobs = 6;
    protected int numberOfUnitRandomOrderJobs = 4;

    /**
     * Run main to publish plan on Bamboo
     */
    public static void main(final String[] args) throws Exception {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer("https://bamboo.typo3.com:443");
        Plan plan = new NightlySpec().createPlan();
        bambooServer.publish(plan);
    }

    /**
     * Core 8.7 pre-merge plan is in "TYPO3 core" project of bamboo
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

        jobsPreparationStage.add(this.getJobComposerValidate());

        Stage stagePreparation = new Stage("Preparation")
            .jobs(jobsPreparationStage.toArray(new Job[jobsPreparationStage.size()]));


        // MAIN stage
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql(this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql(this.getRequirementPhpVersion71(), "PHP71"));

        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql(this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql(this.getRequirementPhpVersion71(), "PHP71"));

        jobsMainStage.addAll(this.getJobsAcceptanceTestsMysql(this.numberOfAcceptanceTestJobs, this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.addAll(this.getJobsAcceptanceTestsMysql(this.numberOfAcceptanceTestJobs, this.getRequirementPhpVersion71(), "PHP71"));

        jobsMainStage.add(this.getJobCglCheckFullCore());

        jobsMainStage.add(this.getJobIntegrationVarious());

        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, this.getRequirementPhpVersion71(), "PHP71"));

        jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(this.numberOfFunctionalMssqlJobs, this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(this.numberOfFunctionalMssqlJobs, this.getRequirementPhpVersion71(), "PHP71"));

        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(this.numberOfFunctionalPgsqlJobs, this.getRequirementPhpVersion70(), "PHP70"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(this.numberOfFunctionalPgsqlJobs, this.getRequirementPhpVersion71(), "PHP71"));

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
        return new Plan(project(), "Core 8.7 nightly", "GTN87")
            .description("Execute TYPO3 core 8.7 nightly tests. Auto generated! See Build/bamboo of core git repository.")
            .stages(
                stagePreparation,
                stageMainStage
            )
            .linkedRepositories("git.typo3.org Core 8.7")
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
            );
    }

    /**
     * Job checking CGL of all core php files
     */
    protected Job getJobCglCheckFullCore() {
        return new Job("Integration CGL", new BambooKey("CGLCHECK"))
            .description("Check coding guidelines of full core")
            .tasks(
                this.getTaskGitCloneRepository(),
                this.getTaskGitCherryPick(),
                this.getTaskComposerInstall(),
                new ScriptTask()
                    .description("Execute cgl check")
                    .interpreter(ScriptTaskProperties.Interpreter.BINSH_OR_CMDEXE)
                    .inlineBody(
                        this.getScriptTaskBashInlineBody() +
                        this.getScriptTaskBashPhpNoXdebug() +
                        "php_no_xdebug ./bin/php-cs-fixer fix -v --dry-run --config=Build/.php_cs typo3/\n" +
                        "exit $?\n"
                    )
            )
            .requirements(
                this.getRequirementPhpVersion70Or71()
            );
    }
}
