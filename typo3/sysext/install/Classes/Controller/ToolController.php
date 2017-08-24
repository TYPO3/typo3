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

use TYPO3\CMS\Install\Controller\Exception\RedirectException;
use TYPO3\CMS\Install\Service\EnableFileService;

/**
 * Install tool controller, dispatcher class of the install tool.
 *
 * Handles install tool session, login and login form rendering,
 * calls actions that need authentication and handles form tokens.
 */
class ToolController extends AbstractController
{
    /**
     * @var array List of valid action names that need authentication
     */
    protected $authenticationActions = [
        'environment',
        'maintenance',
        'settings',
        'upgrade',
    ];

    /**
     * Main dispatch method
     */
    public function execute()
    {
        $this->logoutIfRequested();
        $this->loginIfRequested();
        $this->outputLoginFormIfNotAuthorized();
        $this->dispatchAuthenticationActions();
    }

    /**
     * Logout user if requested
     */
    protected function logoutIfRequested()
    {
        $action = $this->getAction();
        if ($action === 'logout') {
            if (EnableFileService::installToolEnableFileExists() && !EnableFileService::isInstallToolEnableFilePermanent()) {
                EnableFileService::removeInstallToolEnableFile();
            }

            /** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
            $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
                \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class
            );
            $formProtection->clean();
            $this->session->destroySession();
            throw new RedirectException('Forced logout', 1504032052);
        }
    }

    /**
     * Show login for if user is not authorized yet and if
     * not in first installation process.
     */
    protected function outputLoginFormIfNotAuthorized()
    {
        if (!$this->session->isAuthorized()) {
            $this->output($this->loginForm());
        } else {
            $this->session->refreshSession();
        }
    }

    /**
     * Call an action that needs authentication
     *
     * @throws Exception
     * @return string Rendered content
     */
    protected function dispatchAuthenticationActions()
    {
        $action = $this->getAction();
        if ($action === '') {
            $action = 'maintenance';
        }
        $this->validateAuthenticationAction($action);
        $actionClass = ucfirst($action);
        /** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $toolAction */
        $toolAction = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\' . $actionClass);
        if (!($toolAction instanceof Action\ActionInterface)) {
            throw new Exception(
                $action . ' does not implement ActionInterface',
                1369474309
            );
        }
        $toolAction->setController('tool');
        $toolAction->setAction($action);
        $toolAction->setToken($this->generateTokenForAction($action));
        $toolAction->setPostValues($this->getPostValues());
        $this->output($toolAction->handle());
    }
}
