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

/**
 * Install tool ajax controller, handles ajax requests
 */
class AjaxController extends AbstractController
{
    /**
     * @var string
     */
    protected $unauthorized = 'unauthorized';

    /**
     * @var array List of valid action names that need authentication
     */
    protected $authenticationActions = [
        'extensionCompatibilityTester',
        'uninstallExtension',
        'clearCache',
        'clearAllCache',
        'dumpAutoload',
        'coreUpdateUpdateVersionMatrix',
        'coreUpdateIsUpdateAvailable',
        'coreUpdateCheckPreConditions',
        'coreUpdateDownload',
        'coreUpdateVerifyChecksum',
        'coreUpdateUnpack',
        'coreUpdateMove',
        'coreUpdateActivate',
        'folderStatus',
        'environmentStatus',
        'tcaExtTablesCheck',
        'tcaMigrationsCheck',
        'filterManager',
        'saveIgnoredItems',
        'removeIgnoredItems',
    ];

    /**
     * Main entry point
     */
    public function execute()
    {
        $this->loadBaseExtensions();
        // Warning: Order of these methods is security relevant and interferes with different access
        // conditions (new/existing installation). See the single method comments for details.
        $this->outputInstallToolNotEnabledMessageIfNeeded();
        $this->checkInstallToolPasswordNotSet();
        $this->initializeSession();
        $this->checkSessionToken();
        $this->checkSessionLifetime();
        $this->checkLogin();
        $this->dispatchAuthenticationActions();
    }

    /**
     * Check whether the install tool is enabled
     */
    protected function outputInstallToolNotEnabledMessageIfNeeded()
    {
        if (!$this->isInstallToolAvailable()) {
            $this->output($this->unauthorized);
        }
    }

    /**
     * Check if the install tool password is set
     */
    protected function checkInstallToolPasswordNotSet()
    {
        if (empty($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'])) {
            $this->output($this->unauthorized);
        }
    }

    /**
     * Check login status
     */
    protected function checkLogin()
    {
        if (!$this->session->isAuthorized()) {
            $this->output($this->unauthorized);
        } else {
            $this->session->refreshSession();
        }
    }

    /**
     * Overwrites abstract method
     * In contrast to abstract method, a response "you are not authorized is outputted"
     *
     * @param bool $tokenOk
     */
    protected function handleSessionTokenCheck($tokenOk)
    {
        if (!$tokenOk) {
            $this->output($this->unauthorized);
        }
    }

    /**
     * Overwrites abstract method
     * In contrast to abstract method, a response "you are not authorized is outputted"
     */
    protected function handleSessionLifeTimeExpired()
    {
        $this->output($this->unauthorized);
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
            $this->output('noAction');
        }
        $this->validateAuthenticationAction($action);
        $actionClass = ucfirst($action);
        /** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $toolAction */
        $toolAction = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Controller\\Action\\Ajax\\' . $actionClass);
        if (!($toolAction instanceof Action\ActionInterface)) {
            throw new Exception(
                $action . ' does not implement ActionInterface',
                1369474308
            );
        }
        $toolAction->setController('ajax');
        $toolAction->setAction($action);
        $toolAction->setToken($this->generateTokenForAction($action));
        $toolAction->setPostValues($this->getPostValues());
        $this->output($toolAction->handle());
    }

    /**
     * Output content.
     * WARNING: This exits the script execution!
     *
     * @param string $content JSON encoded content to output
     */
    protected function output($content = '')
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo $content;
        die;
    }
}
