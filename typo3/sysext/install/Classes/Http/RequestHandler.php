<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Http;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\AbstractController;
use TYPO3\CMS\Install\Controller\Exception;
use TYPO3\CMS\Install\Exception\AuthenticationRequiredException;

/**
 * Default request handler for all requests inside the TYPO3 Install Tool, which does a simple hardcoded
 * dispatching to a controller based on the get/post variable.
 */
class RequestHandler implements RequestHandlerInterface
{
    /**
     * Instance of the current TYPO3 bootstrap
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var \TYPO3\CMS\Install\Service\SessionService
     */
    protected $session = null;

    /**
     * Constructor handing over the bootstrap
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Handles an install tool request for normal operations
     *
     * @param ServerRequestInterface $request
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
        $getPost = !empty($request->getQueryParams()['install']) ? $request->getQueryParams()['install'] : $request->getParsedBody()['install'];
        if ($getPost['controller'] === 'ajax') {
            $controllerClassName = \TYPO3\CMS\Install\Controller\AjaxController::class;
        } else {
            $controllerClassName = \TYPO3\CMS\Install\Controller\ToolController::class;
        }
        /** @var AbstractController $controller */
        $controller = GeneralUtility::makeInstance($controllerClassName);
        try {
            $this->initializeSession();
            $this->checkSessionToken();
            $this->checkSessionLifetime();
            $controller->setSessionService($this->session);
            $controller->execute();
        } catch (AuthenticationRequiredException $e) {
            // show the login form
            $controller->output($controller->loginForm($e->getMessageObject()));
        } catch (Exception\RedirectException $e) {
            $controller->redirect();
        }
    }

    /**
     * This request handler can handle any request when not in CLI mode.
     * Warning: Order of these methods is security relevant and interferes with different access
     * conditions (new/existing installation). See the single method comments for details.
     *
     * @param ServerRequestInterface $request
     * @return bool Returns always TRUE
     */
    public function canHandleRequest(ServerRequestInterface $request)
    {
        return
            $this->isInstallToolAvailable()
            && $this->bootstrap->checkIfEssentialConfigurationExists()
            && !$this->isInitialInstallationInProgress()
            && $this->isInstallToolPasswordSet()
        ;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 50;
    }

    /**
     * Checks if ENABLE_INSTALL_TOOL exists.
     * Does not check for LocalConfiguration.php file as this is done within
     * Bootstrap->checkIfEssentialConfigurationExists() before.
     *
     * @return bool
     */
    protected function isInstallToolAvailable()
    {
        /** @var \TYPO3\CMS\Install\Service\EnableFileService $installToolEnableService */
        $installToolEnableService = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\EnableFileService::class);
        return $installToolEnableService->checkInstallToolEnableFile();
    }

    /**
     * Checks if first installation is in progress
     * Does not check for LocalConfiguration.php file as this is done within
     * Bootstrap->checkIfEssentialConfigurationExists() before.
     *
     * @return bool TRUE if installation is in progress
     */
    protected function isInitialInstallationInProgress()
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['isInitialInstallationInProgress']);
    }

    /**
     * Check if the install tool password is set
     */
    protected function isInstallToolPasswordSet()
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword']);
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
     * Use form protection API to find out if protected POST forms are ok.
     *
     * @throws Exception
     */
    protected function checkSessionToken()
    {
        $postValues = $this->request->getParsedBody()['install'];
        $tokenOk = false;
        if (!empty($postValues)) {
            // A token must be given as soon as there is POST data
            if (isset($postValues['token'])) {
                /** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
                $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
                    \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class
                );
                $action = (string)$postValues['action'];
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
     * @throws AuthenticationRequiredException if a form token was submitted but was not valid
     */
    protected function handleSessionTokenCheck($tokenOk)
    {
        if (!$tokenOk) {
            $this->session->resetSession();
            $this->session->startSession();

            $message = new FlashMessage(
                'The form protection token was invalid. You have been logged out, please log in and try again.',
                'Invalid form token',
                FlashMessage::ERROR
            );
            throw new AuthenticationRequiredException('Invalid form token', 1504030810, null, $message);
        }
    }

    /**
     * Check if session expired.
     * If the session has expired, the login form is displayed.
     *
     * @throws AuthenticationRequiredException if the session has expired
     */
    protected function checkSessionLifetime()
    {
        if ($this->session->isExpired()) {
            // Session expired, log out user, start new session
            $this->session->resetSession();
            $this->session->startSession();

            $message = new FlashMessage(
                'Your Install Tool session has expired. You have been logged out, please log in and try again.',
                'Session expired',
                FlashMessage::ERROR
            );
            throw new AuthenticationRequiredException('Session expired', 1504030839, null, $message);
        }
    }
}
