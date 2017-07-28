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

use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\EnableFileService;

/**
 * Controller abstract for shared parts of Tool, Step and Ajax controller
 */
class AbstractController
{
    /**
     * @var \TYPO3\CMS\Install\Service\SessionService
     */
    protected $session = null;

    /**
     * @var array List of valid action names that need authentication
     */
    protected $authenticationActions = [];

    /**
     * @return bool
     */
    protected function isInstallToolAvailable()
    {
        /** @var \TYPO3\CMS\Install\Service\EnableFileService $installToolEnableService */
        $installToolEnableService = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\EnableFileService::class);
        if ($installToolEnableService->isFirstInstallAllowed()) {
            return true;
        }
        return $installToolEnableService->checkInstallToolEnableFile();
    }

    /**
     * Guard method checking typo3conf/ENABLE_INSTALL_TOOL
     *
     * Checking ENABLE_INSTALL_TOOL validity is simple:
     * As soon as there is a typo3conf directory at all (not step 1 of "first install"),
     * the file must be there and valid in order to proceed.
     */
    protected function outputInstallToolNotEnabledMessageIfNeeded()
    {
        if (!$this->isInstallToolAvailable()) {
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
    }

    /**
     * Guard method checking for valid install tool password
     *
     * If installation is completed - LocalConfiguration exists and
     * installProcess is not running, and installToolPassword must be set
     */
    protected function outputInstallToolPasswordNotSetMessageIfNeeded()
    {
        if (!$this->isInitialInstallationInProgress()
            && (empty($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword']))
        ) {
            /** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $action */
            $action = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Controller\Action\Common\InstallToolPasswordNotSetAction::class);
            $action->setController('common');
            $action->setAction('installToolPasswordNotSet');
            $this->output($action->handle());
        }
    }

    /**
     * Use form protection API to find out if protected POST forms are ok.
     *
     * @throws Exception
     */
    protected function checkSessionToken()
    {
        $postValues = $this->getPostValues();
        $tokenOk = false;
        if (!empty($postValues)) {
            // A token must be given as soon as there is POST data
            if (isset($postValues['token'])) {
                /** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
                $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
                    \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class
                );
                $action = $this->getAction();
                if ($action === '') {
                    throw new Exception(
                        'No POST action given for token check',
                        1369326593
                    );
                }
                $tokenOk = $formProtection->validateToken($postValues['token'], 'installTool', $action);
            }
        } else {
            $tokenOk = true;
        }

        $this->handleSessionTokenCheck($tokenOk);
    }

    /**
     * If session token was not ok, the session is reset and either
     * a redirect is initialized (will load the same step step controller again) or
     * if in install tool, the login form is displayed.
     *
     * @param bool $tokenOk
     */
    protected function handleSessionTokenCheck($tokenOk)
    {
        if (!$tokenOk) {
            $this->session->resetSession();
            $this->session->startSession();

            if ($this->isInitialInstallationInProgress()) {
                $this->redirect();
            } else {
                /** @var $message \TYPO3\CMS\Install\Status\ErrorStatus */
                $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                $message->setTitle('Invalid form token');
                $message->setMessage(
                    'The form protection token was invalid. You have been logged out, please log in and try again.'
                );
                $this->output($this->loginForm($message));
            }
        }
    }

    /**
     * Check if session expired.
     */
    protected function checkSessionLifetime()
    {
        if ($this->session->isExpired()) {
            // Session expired, log out user, start new session
            $this->session->resetSession();
            $this->session->startSession();

            $this->handleSessionLifeTimeExpired();
        }
    }

    /**
     * If session expired, the current step of step controller is reloaded
     * (if first installation is running) - or the login form is displayed.
     */
    protected function handleSessionLifeTimeExpired()
    {
        if ($this->isInitialInstallationInProgress()) {
            $this->redirect();
        } else {
            /** @var $message \TYPO3\CMS\Install\Status\ErrorStatus */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
            $message->setTitle('Session expired');
            $message->setMessage(
                'Your Install Tool session has expired. You have been logged out, please log in and try again.'
            );
            $this->output($this->loginForm($message));
        }
    }

    /**
     * Show login form
     *
     * @param \TYPO3\CMS\Install\Status\StatusInterface $message Optional status message from controller
     * @return string Rendered HTML
     */
    protected function loginForm(\TYPO3\CMS\Install\Status\StatusInterface $message = null)
    {
        /** @var \TYPO3\CMS\Install\Controller\Action\Common\LoginForm $action */
        $action = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Controller\Action\Common\LoginForm::class);
        $action->setController('common');
        $action->setAction('login');
        $action->setToken($this->generateTokenForAction('login'));
        $action->setPostValues($this->getPostValues());
        if ($message) {
            $action->setMessages([$message]);
        }
        $content = $action->handle();
        return $content;
    }

    /**
     * Validate install tool password and login user if requested
     */
    protected function loginIfRequested()
    {
        $action = $this->getAction();
        $postValues = $this->getPostValues();
        if ($action === 'login') {
            $password = '';
            $validPassword = false;
            if (isset($postValues['values']['password']) && $postValues['values']['password'] !== '') {
                $password = $postValues['values']['password'];
                $installToolPassword = $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'];
                $saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($installToolPassword);
                if (is_object($saltFactory)) {
                    $validPassword = $saltFactory->checkPassword($password, $installToolPassword);
                } elseif (md5($password) === $installToolPassword) {
                    // Update install tool password
                    $saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null, 'BE');
                    /** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
                    $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
                    $configurationManager->setLocalConfigurationValueByPath(
                        'BE/installToolPassword',
                        $saltFactory->getHashedPassword($password)
                    );
                    $validPassword = true;
                }
            }
            if ($validPassword) {
                $this->session->setAuthorized();
                $this->sendLoginSuccessfulMail();
                $this->redirect();
            } else {
                if (!isset($postValues['values']['password']) || $postValues['values']['password'] === '') {
                    $messageText = 'Please enter the install tool password';
                } else {
                    $saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null, 'BE');
                    $hashedPassword = $saltFactory->getHashedPassword($password);
                    $messageText = 'Given password does not match the install tool login password. ' .
                        'Calculated hash: ' . $hashedPassword;
                }
                /** @var $message \TYPO3\CMS\Install\Status\ErrorStatus */
                $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
                $message->setTitle('Login failed');
                $message->setMessage($messageText);
                $this->sendLoginFailedMail();
                $this->output($this->loginForm($message));
            }
        }
    }

    /**
     * Show login for if user is not authorized yet and if
     * not in first installation process.
     */
    protected function outputLoginFormIfNotAuthorized()
    {
        if (!$this->session->isAuthorized()
            && !$this->isInitialInstallationInProgress()
        ) {
            $this->output($this->loginForm());
        } else {
            $this->session->refreshSession();
        }
    }

    /**
     * If install tool login mail is set, send a mail for a successful login.
     */
    protected function sendLoginSuccessfulMail()
    {
        $warningEmailAddress = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
        if ($warningEmailAddress) {
            /** @var \TYPO3\CMS\Core\Mail\MailMessage $mailMessage */
            $mailMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
            $mailMessage
                ->addTo($warningEmailAddress)
                ->setSubject('Install Tool Login at \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\'')
                ->addFrom($this->getSenderEmailAddress(), $this->getSenderEmailName())
                ->setBody('There has been an Install Tool login at TYPO3 site'
                . ' \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\''
                . ' (' . GeneralUtility::getIndpEnv('HTTP_HOST') . ')'
                . ' from remote address \'' . GeneralUtility::getIndpEnv('REMOTE_ADDR') . '\''
                . ' (' . GeneralUtility::getIndpEnv('REMOTE_HOST') . ')')
                ->send();
        }
    }

    /**
     * If install tool login mail is set, send a mail for a failed login.
     */
    protected function sendLoginFailedMail()
    {
        $formValues = GeneralUtility::_GP('install');
        $warningEmailAddress = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
        if ($warningEmailAddress) {
            /** @var \TYPO3\CMS\Core\Mail\MailMessage $mailMessage */
            $mailMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
            $mailMessage
                ->addTo($warningEmailAddress)
                ->setSubject('Install Tool Login ATTEMPT at \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\'')
                ->addFrom($this->getSenderEmailAddress(), $this->getSenderEmailName())
                ->setBody('There has been an Install Tool login attempt at TYPO3 site'
                . ' \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\''
                . ' (' . GeneralUtility::getIndpEnv('HTTP_HOST') . ')'
                . ' The last 5 characters of the MD5 hash of the password tried was \'' . substr(md5($formValues['password']), -5) . '\''
                . ' remote address was \'' . GeneralUtility::getIndpEnv('REMOTE_ADDR') . '\''
                . ' (' . GeneralUtility::getIndpEnv('REMOTE_HOST') . ')')
                ->send();
        }
    }

    /**
     * Generate token for specific action
     *
     * @param string $action Action name
     * @return string Form protection token
     * @throws Exception
     */
    protected function generateTokenForAction($action = null)
    {
        if (!$action) {
            $action = $this->getAction();
        }
        if ($action === '') {
            throw new Exception(
                'Token must have a valid action name',
                1369326592
            );
        }
        /** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
        $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
            \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class
        );
        return $formProtection->generateToken('installTool', $action);
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
     * Initialize session object.
     * Subclass will throw exception if session can not be created or if
     * preconditions like a valid encryption key are not set.
     */
    protected function initializeSession()
    {
        /** @var \TYPO3\CMS\Install\Service\SessionService $session */
        $this->session = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\SessionService::class);
        if (!$this->session->hasSession()) {
            $this->session->startSession();
        }
    }

    /**
     * Add status messages to session.
     * Used to output messages between requests, especially in step controller
     *
     * @param array<\TYPO3\CMS\Install\Status\StatusInterface> $messages
     */
    protected function addSessionMessages(array $messages)
    {
        foreach ($messages as $message) {
            $this->session->addMessage($message);
        }
    }

    /**
     * Required extbase ext_localconf
     * Set caching to NullBackend, install tool must not cache anything
     */
    protected function loadBaseExtensions()
    {
        // @todo: Find out if this could be left out
        require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase') . 'ext_localconf.php');

        $cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];

        $cacheConfigurationsWithCachesSetToNullBackend = [];
        foreach ($cacheConfigurations as $cacheName => $cacheConfiguration) {
            // cache_core is handled in bootstrap already
            if (is_array($cacheConfiguration) && $cacheName !== 'cache_core') {
                $cacheConfiguration['backend'] = NullBackend::class;
                $cacheConfiguration['options'] = [];
            }
            $cacheConfigurationsWithCachesSetToNullBackend[$cacheName] = $cacheConfiguration;
        }
        /** @var $cacheManager \TYPO3\CMS\Core\Cache\CacheManager */
        $cacheManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
        $cacheManager->setCacheConfigurations($cacheConfigurationsWithCachesSetToNullBackend);
    }

    /**
     * Check given action name is one of the allowed actions.
     *
     * @param string $action Given action to validate
     * @throws Exception
     */
    protected function validateAuthenticationAction($action)
    {
        if (!in_array($action, $this->authenticationActions)) {
            throw new Exception(
                $action . ' is not a valid authentication action',
                1369345838
            );
        }
    }

    /**
     * Retrieve parameter from GET or POST and sanitize
     *
     * @throws Exception
     * @return string Empty string if no action is given or sanitized action string
     */
    protected function getAction()
    {
        $formValues = GeneralUtility::_GP('install');
        $action = '';
        if (isset($formValues['action'])) {
            $action = $formValues['action'];
        }
        if ($action !== ''
            && $action !== 'login'
            && $action !== 'loginForm'
            && $action !== 'logout'
            && !in_array($action, $this->authenticationActions)
        ) {
            throw new Exception(
                'Invalid action ' . $action,
                1369325619
            );
        }
        return $action;
    }

    /**
     * Get POST form values of install tool.
     * All POST data is secured by form token protection, except in very installation step.
     *
     * @return array
     */
    protected function getPostValues()
    {
        $postValues = GeneralUtility::_POST('install');
        if (!is_array($postValues)) {
            $postValues = [];
        }
        return $postValues;
    }

    /**
     * HTTP redirect to self, preserving allowed GET variables.
     * WARNING: This exits the script execution!
     *
     * @param string $controller Can be set to 'tool' to redirect from step to tool controller
     * @param string $action Set specific action for next request, used in step controller to specify next step
     * @throws Exception\RedirectLoopException
     */
    protected function redirect($controller = '', $action = '')
    {
        $getPostValues = GeneralUtility::_GP('install');

        $parameters = [];

        // Current redirect count
        if (isset($getPostValues['redirectCount'])) {
            $redirectCount = (int)$getPostValues['redirectCount'] + 1;
        } else {
            $redirectCount = 0;
        }
        if ($redirectCount >= 10) {
            // Abort a redirect loop by throwing an exception. Calling this method
            // some times in a row is ok, but break a loop if this happens too often.
            throw new Exception\RedirectLoopException(
                'Redirect loop aborted. If this message is shown again after a reload,' .
                    ' your setup is so weird that the install tool is unable to handle it.' .
                    ' Please make sure to remove the "install[redirectCount]" parameter from your request or' .
                    ' restart the install tool from the backend navigation.',
                1380581244
            );
        }
        $parameters[] = 'install[redirectCount]=' . $redirectCount;

        // Add context parameter in case this script was called within backend scope
        $context = 'install[context]=standalone';
        if (isset($getPostValues['context']) && $getPostValues['context'] === 'backend') {
            $context = 'install[context]=backend';
        }
        $parameters[] = $context;

        // Add controller parameter
        $controllerParameter = 'install[controller]=step';
        if ((isset($getPostValues['controller']) && $getPostValues['controller'] === 'tool')
            || $controller === 'tool'
        ) {
            $controllerParameter = 'install[controller]=tool';
        }
        $parameters[] = $controllerParameter;

        // Add action if specified
        if ((string)$action !== '') {
            $parameters[] = 'install[action]=' . $action;
        }

        $redirectLocation = GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT') . '?' . implode('&', $parameters);

        \TYPO3\CMS\Core\Utility\HttpUtility::redirect(
            $redirectLocation,
            \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303
        );
    }

    /**
     * Output content.
     * WARNING: This exits the script execution!
     *
     * @param string $content Content to output
     */
    protected function output($content = '')
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo $content;
        die;
    }

    /**
     * Get sender address from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
     * If this setting is empty fall back to 'no-reply@example.com'
     *
     * @return string Returns an email address
     */
    protected function getSenderEmailAddress()
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
            ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
            : 'no-reply@example.com';
    }

    /**
     * Gets sender name from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
     * If this setting is empty, it falls back to a default string.
     *
     * @return string
     */
    protected function getSenderEmailName()
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])
            ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
            : 'TYPO3 CMS install tool';
    }
}
