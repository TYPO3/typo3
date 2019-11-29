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

import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.trigger.RemoteTrigger;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;

import java.util.ArrayList;

/**
 * Pre-Merge and Security Pre-Merge tests use the same setup, but different repositories and triggers
 * Bundling the common bits in a parent class eases changes in the future
 */
abstract class AbstractPreMergeSpec extends AbstractCoreSpec {

    private static int numberOfAcceptanceTestJobs = 8;
    private static int numberOfFunctionalMysqlJobs = 10;
    private static int numberOfFunctionalPgsqlJobs = 10;
    private static int numberOfUnitRandomOrderJobs = 1;
    private static int numberOfFunctionalMssqlJobs = 10;
    Boolean isSecurity = true;
    private String[] phpVersions = {"PHP70", "PHP71", "PHP72", "PHP73"};

    /**
     * Core 8.7 plans are in "TYPO3 core" project of bamboo
     */
    protected Project project() {
        return new Project().name(projectName).key(projectKey);
    }

    Stage getMainStage() {
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql(phpVersions[3], isSecurity));
        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql(phpVersions[2], isSecurity));

        jobsMainStage.addAll(this.getJobsAcceptanceTestsBackendMysql(numberOfAcceptanceTestJobs, phpVersions[2], isSecurity));

        jobsMainStage.add(this.getJobIntegrationVarious(phpVersions[2], isSecurity));

        jobsMainStage.addAll(this.getJobsFunctionalTestsMysql(numberOfFunctionalMysqlJobs, phpVersions[3], isSecurity));
        // mssql functionals are not executed as pre-merge
        // jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(this.numberOfFunctionalMssqlJobs, "PHP72", isSecurity));
        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(numberOfFunctionalPgsqlJobs, "PHP70", isSecurity));

        jobsMainStage.add(this.getJobUnitJavaScript(phpVersions[2], isSecurity));

        for (String phpVersion : phpVersions) {
            jobsMainStage.add(this.getJobLintPhp(phpVersion, isSecurity));
        }

        jobsMainStage.add(this.getJobLintScssTs(phpVersions[2], isSecurity));

        for (String phpVersion : phpVersions) {
            jobsMainStage.add(this.getJobUnitPhp(phpVersion, isSecurity));
        }
        for (String phpVersion : phpVersions) {
            jobsMainStage.addAll(this.getJobUnitPhpRandom(numberOfUnitRandomOrderJobs, phpVersion, isSecurity));
        }

        return new Stage("Main stage")
                .jobs(jobsMainStage.toArray(new Job[jobsMainStage.size()]));
    }

    RemoteTrigger getGerritTrigger() {
        return new RemoteTrigger()
                .name("Remote trigger for pre-merge builds")
                .description("Gerrit")
                .triggerIPAddresses("5.10.165.218,91.184.35.13");
    }

    Stage getEarlyStage() {
        ArrayList<Job> jobsEarlyStage = new ArrayList<Job>();
        jobsEarlyStage.add(this.getJobCglCheckGitCommit(phpVersions[2], isSecurity));
        jobsEarlyStage.add(this.getJobComposerValidate(phpVersions[2], isSecurity));
        return new Stage("Early")
                .jobs(jobsEarlyStage.toArray(new Job[jobsEarlyStage.size()]));
    }

    Stage getPreparationStage() {
        ArrayList<Job> jobsPreparationStage = new ArrayList<Job>();
        jobsPreparationStage.add(this.getJobBuildLabels());
        return new Stage("Preparation")
                .jobs(jobsPreparationStage.toArray(new Job[jobsPreparationStage.size()]));
    }

    /**
     * Job checking CGL of last git commit
     */
    Job getJobCglCheckGitCommit(String requirementIdentifier, Boolean isSecurity) {
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
