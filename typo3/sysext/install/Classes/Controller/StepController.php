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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Action\Step\AbstractStepAction;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\CMS\Install\Service\SessionService;

/**
 * Install step controller, dispatcher class of step actions.
 */
class StepController extends AbstractController
{
    /**
     * @var SessionService
     */
    protected $session = null;

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
     * @param SessionService $session
     */
    public function setSessionService(SessionService $session)
    {
        $this->session = $session;
    }

    /**
     * Index action acts as a dispatcher to different steps
     *
     * @throws Exception
     */
    public function execute()
    {
        $this->executeSpecificStep();
        $this->outputSpecificStep();
        $this->redirectToTool();
    }

    /**
     * Guard method checking typo3conf/ENABLE_INSTALL_TOOL
     *
     * Checking ENABLE_INSTALL_TOOL validity is simple:
     * As soon as there is a typo3conf directory at all (not step 1 of "first install"),
     * the file must be there and valid in order to proceed.
     */
    public function outputInstallToolNotEnabledMessage()
    {
        if (!EnableFileService::isFirstInstallAllowed() && !\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->checkIfEssentialConfigurationExists()) {
            /** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $action */
            $action = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Controller\Action\Common\FirstInstallAction::class);
            $action->setAction('firstInstall');
        } else {
            /** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $action */
            $action = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Controller\Action\Common\InstallToolDisabledAction::class);
            $action->setAction('installToolDisabled');
        }
        $action->setController('common');
        $this->output($action->handle());
    }

    /**
     * Guard method checking for valid install tool password
     *
     * If installation is completed - LocalConfiguration exists and
     * installProcess is not running, and installToolPassword must be set
     */
    public function outputInstallToolPasswordNotSetMessage()
    {
        /** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $action */
        $action = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Controller\Action\Common\InstallToolPasswordNotSetAction::class);
        $action->setController('common');
        $action->setAction('installToolPasswordNotSet');
        $this->output($action->handle());
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
            /** @var AbstractStepAction $stepAction */
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
        /** @var AbstractStepAction $stepAction */
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
    public function executeOrOutputFirstInstallStepIfNeeded()
    {
        $action = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Controller\Action\Step\EnvironmentAndFolders::class);
        $postValues = $this->getPostValues();

        $wasExecuted = false;
        $errorMessagesFromExecute = [];
        if (isset($postValues['action']) && $postValues['action'] === 'environmentAndFolders') {
            $errorMessagesFromExecute = $action->execute();
            $wasExecuted = true;
        }

        // needsExecution() may throw a RedirectException to communicate that it changed
        // configuration parameters and need an application reload.
        $needsExecution = $action->needsExecution();

        if (!@is_dir(PATH_typo3conf) || $needsExecution) {
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
     * First installation is in progress, if LocalConfiguration does not exist,
     * or if isInitialInstallationInProgress is not set or FALSE.
     *
     * @return bool TRUE if installation is in progress
     */
    protected function isInitialInstallationInProgress()
    {
        /** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);

        $localConfigurationFileLocation = $configurationManager->getLocalConfigurationFileLocation();
        $localConfigurationFileExists = @is_file($localConfigurationFileLocation);
        $result = false;
        if (!$localConfigurationFileExists
            || !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['isInitialInstallationInProgress'])
        ) {
            $result = true;
        }
        return $result;
    }

    /**
     * Add status messages to session.
     * Used to output messages between requests, especially in step controller
     *
     * @param FlashMessage[] $messages
     */
    protected function addSessionMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->session->addMessage($message);
        }
    }
}
