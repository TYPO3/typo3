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
        'importantActions',
        'systemEnvironment',
        'configuration',
        'folderStructure',
        'testSetup',
        'upgradeWizard',
        'upgradeAnalysis',
        'allConfiguration',
        'cleanUp',
        'loadExtensions',
        'about',
    ];

    /**
     * Main dispatch method
     */
    public function execute()
    {
        $this->loadBaseExtensions();

        // Warning: Order of these methods is security relevant and interferes with different access
        // conditions (new/existing installation). See the single method comments for details.
        $this->outputInstallToolNotEnabledMessageIfNeeded();
        $this->outputInstallToolPasswordNotSetMessageIfNeeded();
        $this->initializeSession();
        $this->checkSessionToken();
        $this->checkSessionLifetime();
        $this->logoutIfRequested();
        $this->loginIfRequested();
        $this->outputLoginFormIfNotAuthorized();
        $this->registerExtensionConfigurationErrorHandler();
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
            $this->redirect();
        }
    }

    /**
     * This function registers a shutdown function, which is called even if a fatal error occurs.
     * The request either gets redirected to an action where all extension configurations are checked for compatibility or
     * an information with a link to that action.
     */
    protected function registerExtensionConfigurationErrorHandler()
    {
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null) {
                $errorType = $error['type'];

                if ($errorType & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
                    $getPostValues = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('install');

                    $parameters = [];

                    // Add context parameter in case this script was called within backend scope
                    $context = 'install[context]=standalone';
                    if (isset($getPostValues['context']) && $getPostValues['context'] === 'backend') {
                        $context = 'install[context]=backend';
                    }
                    $parameters[] = $context;

                    // Add controller parameter
                    $parameters[] = 'install[controller]=tool';

                    // Add action if specified
                    $parameters[] = 'install[action]=loadExtensions';

                    // Add error to display a message what triggered the check
                    $errorEncoded = json_encode($error);
                    $parameters[] = 'install[lastError]=' . rawurlencode($errorEncoded);
                    // We do not use GeneralUtility here to be sure that hash generation works even if that class might not exist any more.
                    $parameters[] = 'install[lastErrorHash]=' . hash_hmac('sha1', $errorEncoded, $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . 'InstallToolError');

                    $redirectLocation = GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT') . '?' . implode('&', $parameters);

                    if (!headers_sent()) {
                        \TYPO3\CMS\Core\Utility\HttpUtility::redirect(
                            $redirectLocation,
                            \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303
                        );
                    } else {
                        echo '
<p><strong>
	The system detected a fatal error during script execution.
	Please use the <a href="' . $redirectLocation . '">extension check tool</a> to find incompatible extensions.
</strong></p>';
                    }
                }
            }
        });
    }

    /**
     * Get last error values of install tool.
     *
     * @return array
     */
    protected function getLastError()
    {
        $getVars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('install');
        $lastError = [];
        if (isset($getVars['lastError']) && isset($getVars['lastErrorHash']) && !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            $calculatedHash = hash_hmac('sha1', $getVars['lastError'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . 'InstallToolError');
            if ($calculatedHash === $getVars['lastErrorHash']) {
                $lastError = json_decode($getVars['lastError'], true);
            }
        }
        return $lastError;
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
            $action = 'importantActions';
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
        $toolAction->setLastError($this->getLastError());
        $this->output($toolAction->handle());
    }
}
