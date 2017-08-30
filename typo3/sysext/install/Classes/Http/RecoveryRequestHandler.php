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
use TYPO3\CMS\Install\Authentication\AuthenticationService;
use TYPO3\CMS\Install\Controller\Exception;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;
use TYPO3\CMS\Install\Controller\StepController;
use TYPO3\CMS\Install\Exception\AuthenticationRequiredException;
use TYPO3\CMS\Install\Service\SilentConfigurationUpgradeService;

/**
 * Request handler if
 * - an instance is not (fully) installed or basic configuration missing (= fresh installation)
 * - the install tool is locked (no file exists) etc.
 * - or the install tool session is invalid
 */
class RecoveryRequestHandler implements RequestHandlerInterface
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
     * Handles an install tool request when nothing is there
     *
     * @param ServerRequestInterface $request
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
        $controller = GeneralUtility::makeInstance(StepController::class);

        try {
            // Warning: Order of these methods is security relevant and interferes with different access
            // conditions (new/existing installation). See the single method comments for details.
            if (!$this->isInstallToolAvailable()) {
                $controller->outputInstallToolNotEnabledMessage();
            }
            if (!$this->isInitialInstallationInProgress()
                && !$this->isInstallToolPasswordSet()
            ) {
                $controller->outputInstallToolPasswordNotSetMessage();
            }
            $this->recreatePackageStatesFileIfNotExisting();

            // todo: this would be nice, if this is detected by the Request workflow and not the controller
            // controller should just execute this
            $controller->executeOrOutputFirstInstallStepIfNeeded();
            $this->adjustTrustedHostsPatternIfNeeded();
            $this->executeSilentConfigurationUpgradesIfNeeded();
            $this->initializeSession();
            $this->checkSessionToken();
            $this->checkSessionLifetime();
            $this->loginIfRequested();

            // show the login form if not authorized yet or if no initial installation is in progress
            if (!$this->session->isAuthorized() && !$this->isInitialInstallationInProgress()) {
                throw new AuthenticationRequiredException('No session initialized yet, and no first installation', 1504092092);
            }
            $this->session->refreshSession();

            $controller->setSessionService($this->session);
            $controller->execute();
        } catch (AuthenticationRequiredException $e) {
            $controller->output($controller->loginForm($e->getMessageObject()));
        } catch (RedirectException $e) {
            $controller->redirect();
        }
    }

    /**
     * This request handler can handle any request when not in CLI mode.
     *
     * @param ServerRequestInterface $request
     * @return bool Returns always TRUE
     */
    public function canHandleRequest(ServerRequestInterface $request)
    {
        return true;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority()
    {
        return 20;
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
        // no post data is there, so no token necessary
        if (empty($postValues)) {
            return true;
        }
        $tokenOk = false;
        if (isset($postValues['token'])) {
            /** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
            $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
                \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection::class
            );
            $action = (string)$postValues['action'];
            if ($action === '') {
                throw new Exception(
                    'No POST action given for token check',
                    1369326594
                );
            }
            $tokenOk = $formProtection->validateToken($postValues['token'], 'installTool', $action);
        }
        $this->handleSessionTokenCheck($tokenOk);
    }

    /**
     * If session token was not ok, the session is reset and either
     * a redirect is initialized (will load the same step step controller again) or
     * if in install tool, the login form is displayed.
     *
     * @param bool $tokenOk
     * @throws AuthenticationRequiredException
     * @throws RedirectException
     */
    protected function handleSessionTokenCheck($tokenOk)
    {
        if (!$tokenOk) {
            $this->session->resetSession();
            $this->session->startSession();

            if ($this->isInitialInstallationInProgress()) {
                throw new RedirectException('Initial installation in progress', 1504032139);
            }
            $message = new FlashMessage(
                'The form protection token was invalid. You have been logged out, please log in and try again.',
                'Invalid form token',
                FlashMessage::ERROR
            );
            throw new AuthenticationRequiredException('Invalid form token', 1504030706, null, $message);
        }
    }

    /**
     * Check if session expired.
     *
     * If session expired, the current step of step controller is reloaded
     * (if first installation is running) - or the login form is displayed.
     */
    protected function checkSessionLifetime()
    {
        if ($this->session->isExpired()) {
            // Session expired, log out user, start new session
            $this->session->resetSession();
            $this->session->startSession();

            if ($this->isInitialInstallationInProgress()) {
                throw new RedirectException('Initial installation in progress', 1504032125);
            }
            $message = new FlashMessage(
                'Your Install Tool session has expired. You have been logged out, please log in and try again.',
                'Session expired',
                FlashMessage::ERROR
            );
            throw new AuthenticationRequiredException('Session expired', 1504030725, null, $message);
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
        return !$localConfigurationFileExists
            || !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['isInitialInstallationInProgress'])
        ;
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
     * Check if the install tool password is set
     */
    protected function isInstallToolPasswordSet()
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword']);
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
    public function recreatePackageStatesFileIfNotExisting()
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
            throw new RedirectException('Changed PackageStates.php', 1504032160);
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
     * Checks the trusted hosts pattern setting
     */
    public function adjustTrustedHostsPatternIfNeeded()
    {
        if (GeneralUtility::hostHeaderValueMatchesTrustedHostsPattern($_SERVER['HTTP_HOST'])) {
            return;
        }

        /** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValueByPath('SYS/trustedHostsPattern', '.*');
        throw new RedirectException('Trusted hosts pattern adapted', 1504032088);
    }

    /**
     * Call silent upgrade class, redirect to self if configuration was changed.
     */
    public function executeSilentConfigurationUpgradesIfNeeded()
    {
        /** @var SilentConfigurationUpgradeService $upgradeService */
        $upgradeService = GeneralUtility::makeInstance(SilentConfigurationUpgradeService::class);
        try {
            $upgradeService->execute();
        } catch (RedirectException $e) {
            throw new RedirectException('Silent configuration upgrade', 1504032097);
        }
    }

    /**
     * Validate install tool password and login user if requested
     *
     * @throws Exception\RedirectException on successful login
     * @throws AuthenticationRequiredException when a login is requested but credentials are invalid
     */
    protected function loginIfRequested()
    {
        $action = $this->request->getParsedBody()['install']['action'] ?? $this->request->getQueryParams()['install']['action'] ?? '';
        $postValues = $this->request->getParsedBody()['install'];
        if ($action === 'login') {
            $service = new AuthenticationService($this->session);
            $result = $service->loginWithPassword($postValues['values']['password'] ?? null);
            if ($result === true) {
                throw new Exception\RedirectException('Login', 1504032047);
            }
            if (!isset($postValues['values']['password']) || $postValues['values']['password'] === '') {
                $messageText = 'Please enter the install tool password';
            } else {
                $saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null, 'BE');
                $hashedPassword = $saltFactory->getHashedPassword($postValues['values']['password']);
                $messageText = 'Given password does not match the install tool login password. ' .
                    'Calculated hash: ' . $hashedPassword;
            }
            $message = new FlashMessage(
                $messageText,
                'Login failed',
                FlashMessage::ERROR
            );
            throw new AuthenticationRequiredException('Login failed', 1504031979, null, $message);
        }
    }
}
