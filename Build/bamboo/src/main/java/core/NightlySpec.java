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
 * Core master nightly test plan.
 */
@BambooSpec
public class NightlySpec extends AbstractCoreSpec {

    protected static String planName = "Core master nightly";
    protected static String planKey = "GTN";

    protected int numberOfAcceptanceTestJobs = 8;
    protected int numberOfFunctionalMysqlJobs = 6;
    protected int numberOfFunctionalMssqlJobs = 6;
    protected int numberOfFunctionalPgsqlJobs = 6;
    protected int numberOfFunctionalSqliteJobs = 10;
    protected int numberOfUnitRandomOrderJobs = 4;

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
     * Core master pre-merge plan is in "TYPO3 core" project of bamboo
     */
    Project project() {
        return new Project().name(projectName).key(projectKey);
    }

    /**
     * Returns full Plan definition
     */
    Plan createPlan() {
        // MAIN stage
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        jobsMainStage.add(this.getJobComposerValidate("PHP72"));

        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql("PHP72"));
        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql("PHP72"));
        jobsMainStage.add(this.getJobAcceptanceTestInstallSqlite("PHP72"));

        jobsMainStage.addAll(this.getJobsAcceptanceTestsMysql(this.numberOfAcceptanceTestJobs, "PHP72"));

        jobsMainStage.add(this.getJobCglCheckFullCore("PHP72"));

        jobsMainStage.add(this.getJobIntegrationAnnotations("PHP72"));

        jobsMainStage.add(this.getJobIntegrationVarious("PHP72"));

        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(this.numberOfFunctionalMysqlJobs, "PHP72"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(this.numberOfFunctionalMssqlJobs, "PHP72"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(this.numberOfFunctionalPgsqlJobs, "PHP72"));
        jobsMainStage.addAll(this.getJobsFunctionalTestsSqlite(this.numberOfFunctionalSqliteJobs, "PHP72"));

        jobsMainStage.add(this.getJobUnitJavaScript("PHP72"));

        jobsMainStage.add(this.getJobLintPhp("PHP72"));

        jobsMainStage.add(this.getJobLintScssTs("PHP72"));

        jobsMainStage.add(this.getJobUnitPhp("PHP72"));
        jobsMainStage.add(this.getJobUnitDeprecatedPhp("PHP72"));
        jobsMainStage.addAll(this.getJobUnitPhpRandom(this.numberOfUnitRandomOrderJobs, "PHP72"));

        Stage stageMainStage = new Stage("Main stage")
            .jobs(jobsMainStage.toArray(new Job[jobsMainStage.size()]));


        // Compile plan
        return new Plan(project(), planName, planKey)
            .description("Execute TYPO3 core master nightly tests. Auto generated! See Build/bamboo of core git repository.")
            .pluginConfigurations(this.getDefaultPlanPluginConfiguration())
            .stages(
                stageMainStage
            )
            .linkedRepositories("git.typo3.org Core")
            .triggers(
                new ScheduledTrigger()
                    .name("Scheduled")
                    .description("daily at night")
                    // daily 03:23
                    .cronExpression("0 23 3 ? * *")
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
