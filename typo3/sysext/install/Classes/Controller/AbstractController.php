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
use TYPO3\CMS\Install\Controller\Exception\RedirectException;
use TYPO3\CMS\Install\Exception\AuthenticationRequiredException;
use TYPO3\CMS\Install\Service\SessionService;

/**
 * Controller abstract for shared parts of Tool, Step and Ajax controller
 */
class AbstractController
{
    /**
     * @var SessionService
     */
    protected $session = null;

    /**
     * @var array List of valid action names that need authentication
     */
    protected $authenticationActions = [];

    /**
     * @param SessionService $session
     */
    public function setSessionService(SessionService $session)
    {
        $this->session = $session;
    }

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
     * Show login form
     *
     * @param \TYPO3\CMS\Install\Status\StatusInterface $message Optional status message from controller
     * @return string Rendered HTML
     */
    public function loginForm(\TYPO3\CMS\Install\Status\StatusInterface $message = null)
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
     *
     * @throws RedirectException on successful login
     * @throws AuthenticationRequiredException when a login is requested but credentials are invalid
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
                throw new RedirectException('Login', 1504032046);
            }
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
            throw new AuthenticationRequiredException('Login failed', 1504031979, null, $message);
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
    public function getAction()
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
    public function redirect($controller = '', $action = '')
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
    public function output($content = '')
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
