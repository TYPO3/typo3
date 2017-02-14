<?php
namespace TYPO3\CMS\Install\Controller;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;

/**
 * Install step controller, dispatcher class of step actions.
 */
class StepController extends AbstractController
{
    /**
     * @var array List of valid action names that need authentication. Order is important!
     */
    protected $authenticationActions = [
        'environmentAndFolders',
        'databaseConnect',
        'databaseSelect',
        'databaseData',
        'defaultConfiguration',
    ];

    /**
     * Index action acts as a dispatcher to different steps
     *
     * Warning: Order of these methods is security relevant and interferes with different access
     * conditions (new/existing installation). See the single method comments for details.
     *
     * @throws Exception
     */
    public function execute()
    {
        $this->loadBaseExtensions();
        $this->outputInstallToolNotEnabledMessageIfNeeded();
        $this->outputInstallToolPasswordNotSetMessageIfNeeded();
        $this->recreatePackageStatesFileIfNotExisting();
        $this->executeOrOutputFirstInstallStepIfNeeded();
        $this->adjustTrustedHostsPatternIfNeeded();
        $this->executeSilentConfigurationUpgradesIfNeeded();
        $this->initializeSession();
        $this->checkSessionToken();
        $this->checkSessionLifetime();
        $this->loginIfRequested();
        $this->outputLoginFormIfNotAuthorized();
        $this->executeSpecificStep();
        $this->outputSpecificStep();
        $this->redirectToTool();
    }

    /**
     * Execute a step action if requested. If executed, a redirect is done, so
     * the next request will render step one again if needed or initiate a
     * request to test the next step.
     *
     * @throws Exception
     */
    protected function executeSpecificStep()
    {
        $action = $this->getAction();
        $postValues = $this->getPostValues();
        if ($action && isset($postValues['set']) && $postValues['set'] === 'execute') {
            $stepAction = $this->getActionInstance($action);
            $stepAction->setAction($action);
            $stepAction->setToken($this->generateTokenForAction($action));
            $stepAction->setPostValues($this->getPostValues());
            $messages = $stepAction->execute();
            $this->addSessionMessages($messages);
            $this->redirect();
        }
    }

    /**
     * Render a specific step. Fallback to first step if none is given.
     * The according step is instantiated and 'needsExecution' is called. If
     * it needs execution, the step will be rendered, otherwise a redirect
     * to test the next step is initiated.
     */
    protected function outputSpecificStep()
    {
        $action = $this->getAction();
        if ($action === '') {
            // First step action
            list($action) = $this->authenticationActions;
        }
        $stepAction = $this->getActionInstance($action);
        $stepAction->setAction($action);
        $stepAction->setController('step');
        $stepAction->setToken($this->generateTokenForAction($action));
        $stepAction->setPostValues($this->getPostValues());

        $needsExecution = true;
        try {
            // needsExecution() may throw a RedirectException to communicate that it changed
            // configuration parameters and need an application reload.
            $needsExecution = $stepAction->needsExecution();
        } catch (Exception\RedirectException $e) {
            $this->redirect();
        }

        if ($needsExecution) {
            if ($this->isInitialInstallationInProgress()) {
                $currentStep = (array_search($action, $this->authenticationActions) + 1);
                $totalSteps = count($this->authenticationActions);
                $stepAction->setStepsCounter($currentStep, $totalSteps);
            }
            $stepAction->setMessages($this->session->getMessagesAndFlush());
            $this->output($stepAction->handle());
        } else {
            // Redirect to next step if there are any
            $currentPosition = array_keys($this->authenticationActions, $action, true);
            $nextAction = array_slice($this->authenticationActions, $currentPosition[0] + 1, 1);
            if (!empty($nextAction)) {
                $this->redirect('', $nextAction[0]);
            }
        }
    }

    /**
     * Instantiate a specific action class
     *
     * @param string $action Action to instantiate
     * @throws Exception
     * @return \TYPO3\CMS\Install\Controller\Action\Step\StepInterface
     */
    protected function getActionInstance($action)
    {
        $this->validateAuthenticationAction($action);
        $actionClass = ucfirst($action);
        /** @var \TYPO3\CMS\Install\Controller\Action\Step\StepInterface $stepAction */
        $stepAction = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Controller\\Action\\Step\\' . $actionClass);
        if (!($stepAction instanceof Action\Step\StepInterface)) {
            throw new Exception(
                $action . ' does non implement StepInterface',
                1371303903
            );
        }
        return $stepAction;
    }

    /**
     * If the last step was reached and none needs execution, a redirect
     * to call the tool controller is initiated.
     */
    protected function redirectToTool()
    {
        $this->redirect('tool');
    }

    /**
     * Create PackageStates.php if missing and LocalConfiguration exists.
     *
     * It is fired if PackageStates.php is deleted on a running instance,
     * all packages marked as "part of minimal system" are activated in this case.
     *
     * The step installer creates typo3conf/, LocalConfiguration and PackageStates in
     * one call, so an "installation in progress" does not trigger creation of
     * PackageStates here.
     *
     * @throws \Exception
     */
    protected function recreatePackageStatesFileIfNotExisting()
    {
        /** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        $localConfigurationFileLocation = $configurationManager->getLocalConfigurationFileLocation();
        $localConfigurationFileExists = is_file($localConfigurationFileLocation);
        $packageStatesFilePath = PATH_typo3conf . 'PackageStates.php';
        $localConfigurationBackupFilePath = preg_replace(
            '/\\.php$/',
            '.beforePackageStatesMigration.php',
            $configurationManager->getLocalConfigurationFileLocation()
        );

        if (file_exists($packageStatesFilePath)
            || (is_dir(PATH_typo3conf) && !$localConfigurationFileExists)
            || !is_dir(PATH_typo3conf)
        ) {
            return;
        }

        try {
            /** @var \TYPO3\CMS\Core\Package\FailsafePackageManager $packageManager */
            $packageManager = \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->getEarlyInstance(\TYPO3\CMS\Core\Package\PackageManager::class);

            // Activate all packages required for a minimal usable system
            $packages = $packageManager->getAvailablePackages();
            foreach ($packages as $package) {
                /** @var $package \TYPO3\CMS\Core\Package\PackageInterface */
                if ($package instanceof \TYPO3\CMS\Core\Package\PackageInterface
                    && $package->isPartOfMinimalUsableSystem()
                ) {
                    $packageManager->activatePackage($package->getPackageKey());
                }
            }

            // Backup LocalConfiguration.php
            copy(
                $configurationManager->getLocalConfigurationFileLocation(),
                $localConfigurationBackupFilePath
            );

            $packageManager->forceSortAndSavePackageStates();

            // Perform a reload to self, so bootstrap now uses new PackageStates.php
            $this->redirect();
        } catch (\Exception $exception) {
            if (file_exists($packageStatesFilePath)) {
                unlink($packageStatesFilePath);
            }
            if (file_exists($localConfigurationBackupFilePath)) {
                unlink($localConfigurationBackupFilePath);
            }
            throw $exception;
        }
    }

    /**
     * The first install step has a special standing and needs separate handling:
     * At this point no directory exists (no typo3conf, no typo3temp), so we can
     * not start the session handling (that stores the install tool session within typo3temp).
     * This also means, we can not start the token handling for CSRF protection. This
     * is no real problem, since no local configuration or other security relevant
     * information was created yet.
     *
     * So, if no typo3conf directory exists yet, the first step is just rendered, or
     * executed if called so. After that, a redirect is initiated to proceed with
     * other tasks.
     */
    protected function executeOrOutputFirstInstallStepIfNeeded()
    {
        $postValues = $this->getPostValues();

        $wasExecuted = false;
        $errorMessagesFromExecute = [];
        if (isset($postValues['action'])
            && $postValues['action'] === 'environmentAndFolders'
        ) {
            /** @var \TYPO3\CMS\Install\Controller\Action\Step\StepInterface $action */
            $action = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Controller\Action\Step\EnvironmentAndFolders::class);
            $errorMessagesFromExecute = $action->execute();
            $wasExecuted = true;
        }

        /** @var \TYPO3\CMS\Install\Controller\Action\Step\StepInterface $action */
        $action = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Controller\Action\Step\EnvironmentAndFolders::class);

        $needsExecution = true;
        try {
            // needsExecution() may throw a RedirectException to communicate that it changed
            // configuration parameters and need an application reload.
            $needsExecution = $action->needsExecution();
        } catch (Exception\RedirectException $e) {
            $this->redirect();
        }

        if (!@is_dir(PATH_typo3conf) || $needsExecution) {
            /** @var \TYPO3\CMS\Install\Controller\Action\Step\StepInterface $action */
            $action = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Controller\Action\Step\EnvironmentAndFolders::class);
            if ($this->isInitialInstallationInProgress()) {
                $currentStep = (array_search('environmentAndFolders', $this->authenticationActions) + 1);
                $totalSteps = count($this->authenticationActions);
                $action->setStepsCounter($currentStep, $totalSteps);
            }
            $action->setController('step');
            $action->setAction('environmentAndFolders');
            if (!empty($errorMessagesFromExecute)) {
                $action->setMessages($errorMessagesFromExecute);
            }
            $this->output($action->handle());
        }

        if ($wasExecuted) {
            $this->redirect();
        }
    }

    /**
     * Checks the trusted hosts pattern setting
     */
    protected function adjustTrustedHostsPatternIfNeeded()
    {
        if (GeneralUtility::hostHeaderValueMatchesTrustedHostsPattern($_SERVER['HTTP_HOST'])) {
            return;
        }

        /** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValueByPath('SYS/trustedHostsPattern', '.*');
        $this->redirect();
    }

    /**
     * Call silent upgrade class, redirect to self if configuration was changed.
     */
    protected function executeSilentConfigurationUpgradesIfNeeded()
    {
        /** @var SilentConfigurationUpgradeService $upgradeService */
        $upgradeService = GeneralUtility::makeInstance(SilentConfigurationUpgradeService::class);
        try {
            $upgradeService->execute();
        } catch (Exception\RedirectException $e) {
            $this->redirect();
        }
    }
}
