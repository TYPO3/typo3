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

import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.builders.trigger.RemoteTrigger;

import java.util.ArrayList;

/**
 * Pre-Merge and Security Pre-Merge tests use the same setup, but different repositories and triggers
 * Bundling the common bits in a parent class eases changes in the future
 */
abstract class AbstractPreMergeSpec extends AbstractCoreSpec {

    private static int numberOfAcceptanceTestJobs = 10;
    private static int numberOfFunctionalMysqlJobs = 10;
    private static int numberOfFunctionalMssqlJobs = 10;
    private static int numberOfFunctionalPgsqlJobs = 10;
    private static int numberOfFunctionalSqliteJobs = 10;
    private static int numberOfUnitRandomOrderJobs = 1;

    private String[] phpVersions = {"PHP72", "PHP73", "PHP74"};

    /**
     * override in concrete class in function createPlan. If not security repo related, set to false
     */
    Boolean isSecurity = true;

    RemoteTrigger getGerritTrigger() {
        return new RemoteTrigger()
            .name("Remote trigger for pre-merge builds")
            .description("Gerrit")
            .triggerIPAddresses("5.10.165.218,91.184.35.13");
    }

    Stage getMainStage() {
        ArrayList<Job> jobsMainStage = new ArrayList<Job>();

        jobsMainStage.add(this.getJobAcceptanceTestInstallMysql(0, phpVersions[2], this.getTaskComposerInstall(phpVersions[2]), isSecurity));
        jobsMainStage.add(this.getJobAcceptanceTestInstallPgsql(0, phpVersions[1], this.getTaskComposerInstall(phpVersions[1]), isSecurity));
        jobsMainStage.add(this.getJobAcceptanceTestInstallSqlite(0, phpVersions[0], this.getTaskComposerInstall(phpVersions[0]), isSecurity));

        jobsMainStage.addAll(this.getJobsAcceptanceTestsBackendMysql(0, numberOfAcceptanceTestJobs, phpVersions[1], this.getTaskComposerInstall(phpVersions[1]), isSecurity));
        jobsMainStage.addAll(this.getJobsAcceptanceTestsPageTreeMysql(0, phpVersions[1], this.getTaskComposerInstall(phpVersions[1]), isSecurity));
        jobsMainStage.addAll(this.getJobsAcceptanceTestsInstallToolMysql(0, phpVersions[1], this.getTaskComposerInstall(phpVersions[1]), isSecurity));

        jobsMainStage.add(this.getJobIntegrationPhpStan(phpVersions[0], this.getTaskComposerInstall(phpVersions[0]), isSecurity));
        jobsMainStage.add(this.getJobIntegrationDocBlocks(phpVersions[0], this.getTaskComposerInstall(phpVersions[0]), isSecurity));
        jobsMainStage.add(this.getJobIntegrationAnnotations(phpVersions[0], this.getTaskComposerInstall(phpVersions[0]), isSecurity));

        jobsMainStage.add(this.getJobIntegrationVarious(phpVersions[0], this.getTaskComposerInstall(phpVersions[0]), isSecurity));

        jobsMainStage.addAll(this.getJobsFunctionalTestsMysqlWithDriverMySqli(0, numberOfFunctionalMysqlJobs, phpVersions[2], this.getTaskComposerInstall(phpVersions[2]), isSecurity));
        // mssql functionals are not executed as pre-merge
        // jobsMainStage.addAll(this.getJobsFunctionalTestsMssql(0, this.numberOfFunctionalMssqlJobs, "PHP72", this.getTaskComposerInstall("PHP72"), false));
        jobsMainStage.addAll(this.getJobsFunctionalTestsPgsql(0, numberOfFunctionalPgsqlJobs, phpVersions[1], this.getTaskComposerInstall(phpVersions[1]), isSecurity));
        jobsMainStage.addAll(this.getJobsFunctionalTestsSqlite(0, numberOfFunctionalSqliteJobs, phpVersions[0], this.getTaskComposerInstall(phpVersions[0]), isSecurity));

        jobsMainStage.add(this.getJobUnitJavaScript("JS", this.getTaskComposerInstall(phpVersions[0]), isSecurity));

        for (String phpVersion : phpVersions) {
            jobsMainStage.add(this.getJobLintPhp(phpVersion, isSecurity));
        }

        jobsMainStage.add(this.getJobLintScssTs("JS", isSecurity));

        for (String phpVersion : phpVersions) {
            jobsMainStage.add(this.getJobUnitPhp(0, phpVersion, this.getTaskComposerInstall(phpVersion), isSecurity));
        }
        for (String phpVersion : phpVersions) {
            jobsMainStage.add(this.getJobUnitDeprecatedPhp(0, phpVersion, this.getTaskComposerInstall(phpVersion), isSecurity));
        }
        for (String phpVersion : phpVersions) {
            jobsMainStage.addAll(this.getJobUnitPhpRandom(0, numberOfUnitRandomOrderJobs, phpVersion, this.getTaskComposerInstall(phpVersion), isSecurity));
        }

        return new Stage("Main stage")
            .jobs(jobsMainStage.toArray(new Job[jobsMainStage.size()]));
    }

    Stage getEarlyStage() {
        ArrayList<Job> jobsEarlyStage = new ArrayList<Job>();
        jobsEarlyStage.add(this.getJobCglCheckGitCommit(phpVersions[0], isSecurity));
        jobsEarlyStage.add(this.getJobComposerValidate(phpVersions[0], isSecurity));
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
     * Core master pre-merge plan is in "TYPO3 core" project of bamboo
     */
    Project project() {
        return new Project().name(projectName).key(projectKey);
    }
}
